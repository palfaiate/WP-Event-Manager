<?php
/**
 * File containing the class WP_event_Manager_Post_Types.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles displays and hooks for the event Listing custom post type.
 *
 * @since 1.0.0
 */
class WP_event_Manager_Post_Types {

	const PERMALINK_OPTION_NAME = 'event_manager_permalinks';

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.26.0
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.26.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ], 0 );
		add_action( 'init', [ $this, 'prepare_block_editor' ] );
		add_action( 'init', [ $this, 'register_meta_fields' ] );
		add_filter( 'admin_head', [ $this, 'admin_head' ] );
		add_action( 'event_manager_check_for_expired_events', [ $this, 'check_for_expired_events' ] );
		add_action( 'event_manager_delete_old_previews', [ $this, 'delete_old_previews' ] );

		add_action( 'pending_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'preview_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'draft_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'auto-draft_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'expired_to_publish', [ $this, 'set_expiry' ] );

		add_action( 'wp_head', [ $this, 'noindex_expired_filled_event_listings' ] );
		add_action( 'wp_footer', [ $this, 'output_structured_data' ] );

		add_filter( 'the_event_description', 'wptexturize' );
		add_filter( 'the_event_description', 'convert_smilies' );
		add_filter( 'the_event_description', 'convert_chars' );
		add_filter( 'the_event_description', 'wpautop' );
		add_filter( 'the_event_description', 'shortcode_unautop' );
		add_filter( 'the_event_description', 'prepend_attachment' );
		if ( ! empty( $GLOBALS['wp_embed'] ) ) {
			add_filter( 'the_event_description', [ $GLOBALS['wp_embed'], 'run_shortcode' ], 8 );
			add_filter( 'the_event_description', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
		}

		add_action( 'event_manager_application_details_email', [ $this, 'application_details_email' ] );
		add_action( 'event_manager_application_details_url', [ $this, 'application_details_url' ] );

		add_filter( 'wp_insert_post_data', [ $this, 'fix_post_name' ], 10, 2 );
		add_action( 'add_post_meta', [ $this, 'maybe_add_geolocation_data' ], 10, 3 );
		add_action( 'update_post_meta', [ $this, 'update_post_meta' ], 10, 4 );
		add_action( 'wp_insert_post', [ $this, 'maybe_add_default_meta_data' ], 10, 2 );
		add_filter( 'post_types_to_delete_with_user', [ $this, 'delete_user_add_event_listings_post_type' ] );

		add_action( 'transition_post_status', [ $this, 'track_event_submission' ], 10, 3 );

		add_action( 'parse_query', [ $this, 'add_feed_query_args' ] );

		// Single event content.
		$this->event_content_filter( true );
	}

	/**
	 * Prepare CPTs for special block editor situations.
	 */
	public function prepare_block_editor() {
		add_filter( 'allowed_block_types', [ $this, 'force_classic_block' ], 10, 2 );

		if ( false === event_manager_multi_event_type() ) {
			add_filter( 'rest_prepare_taxonomy', [ $this, 'hide_event_type_block_editor_selector' ], 10, 3 );
		}
	}

	/**
	 * Forces event listings to just have the classic block. This is necessary with the use of the classic editor on
	 * the frontend.
	 *
	 * @param array   $allowed_block_types
	 * @param WP_Post $post
	 * @return array
	 */
	public function force_classic_block( $allowed_block_types, $post ) {
		if ( 'event_listing' === $post->post_type ) {
			return [ 'core/freeform' ];
		}
		return $allowed_block_types;
	}

	/**
	 * Filters a taxonomy returned from the REST API.
	 *
	 * Allows modification of the taxonomy data right before it is returned.
	 *
	 * @param WP_REST_Response $response  The response object.
	 * @param object           $taxonomy  The original taxonomy object.
	 * @param WP_REST_Request  $request   Request used to generate the response.
	 *
	 * @return WP_REST_Response
	 */
	public function hide_event_type_block_editor_selector( $response, $taxonomy, $request ) {
		if (
			'event_listing_type' === $taxonomy->name
			&& 'edit' === $request->get_param( 'context' )
		) {
			$response->data['visibility']['show_ui'] = false;
		}
		return $response;
	}

	/**
	 * Registers the custom post type and taxonomies.
	 */
	public function register_post_types() {
		if ( post_type_exists( 'event_listing' ) ) {
			return;
		}

		$admin_capability = 'manage_event_listings';

		$permalink_structure = self::get_permalink_structure();

		/**
		 * Taxonomies
		 */
		if ( get_option( 'event_manager_enable_categories' ) ) {
			$singular = __( 'event category', 'wp-event-manager' );
			$plural   = __( 'event categories', 'wp-event-manager' );

			if ( current_theme_supports( 'event-manager-templates' ) ) {
				$rewrite = [
					'slug'         => $permalink_structure['category_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => false,
				];
				$public  = true;
			} else {
				$rewrite = false;
				$public  = false;
			}

			register_taxonomy(
				'event_listing_category',
				apply_filters( 'register_taxonomy_event_listing_category_object_type', [ 'event_listing' ] ),
				apply_filters(
					'register_taxonomy_event_listing_category_args',
					[
						'hierarchical'          => true,
						'update_count_callback' => '_update_post_term_count',
						'label'                 => $plural,
						'labels'                => [
							'name'              => $plural,
							'singular_name'     => $singular,
							'menu_name'         => ucwords( $plural ),
							// translators: Placeholder %s is the plural label of the event listing category taxonomy type.
							'search_items'      => sprintf( __( 'Search %s', 'wp-event-manager' ), $plural ),
							// translators: Placeholder %s is the plural label of the event listing category taxonomy type.
							'all_items'         => sprintf( __( 'All %s', 'wp-event-manager' ), $plural ),
							// translators: Placeholder %s is the singular label of the event listing category taxonomy type.
							'parent_item'       => sprintf( __( 'Parent %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing category taxonomy type.
							'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing category taxonomy type.
							'edit_item'         => sprintf( __( 'Edit %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing category taxonomy type.
							'update_item'       => sprintf( __( 'Update %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing category taxonomy type.
							'add_new_item'      => sprintf( __( 'Add New %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing category taxonomy type.
							'new_item_name'     => sprintf( __( 'New %s Name', 'wp-event-manager' ), $singular ),
						],
						'show_ui'               => true,
						'show_tagcloud'         => false,
						'public'                => $public,
						'capabilities'          => [
							'manage_terms' => $admin_capability,
							'edit_terms'   => $admin_capability,
							'delete_terms' => $admin_capability,
							'assign_terms' => $admin_capability,
						],
						'rewrite'               => $rewrite,
						'show_in_rest'          => true,
						'rest_base'             => 'event-categories',

					]
				)
			);
		}

		if ( get_option( 'event_manager_enable_types' ) ) {
			$singular = __( 'event type', 'wp-event-manager' );
			$plural   = __( 'event types', 'wp-event-manager' );

			if ( current_theme_supports( 'event-manager-templates' ) ) {
				$rewrite = [
					'slug'         => $permalink_structure['type_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => false,
				];
				$public  = true;
			} else {
				$rewrite = false;
				$public  = false;
			}

			register_taxonomy(
				'event_listing_type',
				apply_filters( 'register_taxonomy_event_listing_type_object_type', [ 'event_listing' ] ),
				apply_filters(
					'register_taxonomy_event_listing_type_args',
					[
						'hierarchical'         => true,
						'label'                => $plural,
						'labels'               => [
							'name'              => $plural,
							'singular_name'     => $singular,
							'menu_name'         => ucwords( $plural ),
							// translators: Placeholder %s is the plural label of the event listing event type taxonomy type.
							'search_items'      => sprintf( __( 'Search %s', 'wp-event-manager' ), $plural ),
							// translators: Placeholder %s is the plural label of the event listing event type taxonomy type.
							'all_items'         => sprintf( __( 'All %s', 'wp-event-manager' ), $plural ),
							// translators: Placeholder %s is the singular label of the event listing event type taxonomy type.
							'parent_item'       => sprintf( __( 'Parent %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing event type taxonomy type.
							'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing event type taxonomy type.
							'edit_item'         => sprintf( __( 'Edit %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing event type taxonomy type.
							'update_item'       => sprintf( __( 'Update %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing event type taxonomy type.
							'add_new_item'      => sprintf( __( 'Add New %s', 'wp-event-manager' ), $singular ),
							// translators: Placeholder %s is the singular label of the event listing event type taxonomy type.
							'new_item_name'     => sprintf( __( 'New %s Name', 'wp-event-manager' ), $singular ),
						],
						'show_ui'              => true,
						'show_tagcloud'        => false,
						'public'               => $public,
						'capabilities'         => [
							'manage_terms' => $admin_capability,
							'edit_terms'   => $admin_capability,
							'delete_terms' => $admin_capability,
							'assign_terms' => $admin_capability,
						],
						'rewrite'              => $rewrite,
						'show_in_rest'         => true,
						'rest_base'            => 'event-types',
						'meta_box_sanitize_cb' => [ $this, 'sanitize_event_type_meta_box_input' ],
					]
				)
			);
			if ( function_exists( 'wpjm_event_listing_employment_type_enabled' ) && wpjm_event_listing_employment_type_enabled() ) {
				register_meta(
					'term',
					'employment_type',
					[
						'object_subtype'    => 'event_listing_type',
						'show_in_rest'      => true,
						'type'              => 'string',
						'single'            => true,
						'description'       => esc_html__( 'Employment Type', 'wp-event-manager' ),
						'sanitize_callback' => [ $this, 'sanitize_employment_type' ],
					]
				);
			}
		}

		/**
		 * Post types
		 */
		$singular = __( 'event', 'wp-event-manager' );
		$plural   = __( 'events', 'wp-event-manager' );

		/**
		 * Set whether to add archive page support when registering the event listing post type.
		 *
		 * @since 1.30.0
		 *
		 * @param bool $enable_event_archive_page
		 */
		if ( apply_filters( 'event_manager_enable_event_archive_page', current_theme_supports( 'event-manager-templates' ) ) ) {
			$has_archive = $permalink_structure['events_archive_rewrite_slug'];
		} else {
			$has_archive = false;
		}

		$rewrite = [
			'slug'       => $permalink_structure['event_rewrite_slug'],
			'with_front' => false,
			'feeds'      => true,
			'pages'      => false,
		];

		register_post_type(
			'event_listing',
			apply_filters(
				'register_post_type_event_listing',
				[
					'labels'                => [
						'name'                  => $plural,
						'singular_name'         => $singular,
						'menu_name'             => __( 'event Listings', 'wp-event-manager' ),
						// translators: Placeholder %s is the plural label of the event listing post type.
						'all_items'             => sprintf( __( 'All %s', 'wp-event-manager' ), $plural ),
						'add_new'               => __( 'Add New', 'wp-event-manager' ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'add_new_item'          => sprintf( __( 'Add %s', 'wp-event-manager' ), $singular ),
						'edit'                  => __( 'Edit', 'wp-event-manager' ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'edit_item'             => sprintf( __( 'Edit %s', 'wp-event-manager' ), $singular ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'new_item'              => sprintf( __( 'New %s', 'wp-event-manager' ), $singular ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'view'                  => sprintf( __( 'View %s', 'wp-event-manager' ), $singular ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'view_item'             => sprintf( __( 'View %s', 'wp-event-manager' ), $singular ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'search_items'          => sprintf( __( 'Search %s', 'wp-event-manager' ), $plural ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'not_found'             => sprintf( __( 'No %s found', 'wp-event-manager' ), $plural ),
						// translators: Placeholder %s is the plural label of the event listing post type.
						'not_found_in_trash'    => sprintf( __( 'No %s found in trash', 'wp-event-manager' ), $plural ),
						// translators: Placeholder %s is the singular label of the event listing post type.
						'parent'                => sprintf( __( 'Parent %s', 'wp-event-manager' ), $singular ),
						'featured_image'        => __( 'Company Logo', 'wp-event-manager' ),
						'set_featured_image'    => __( 'Set company logo', 'wp-event-manager' ),
						'remove_featured_image' => __( 'Remove company logo', 'wp-event-manager' ),
						'use_featured_image'    => __( 'Use as company logo', 'wp-event-manager' ),
					],
					// translators: Placeholder %s is the plural label of the event listing post type.
					'description'           => sprintf( __( 'This is where you can create and manage %s.', 'wp-event-manager' ), $plural ),
					'public'                => true,
					'show_ui'               => true,
					'capability_type'       => 'event_listing',
					'map_meta_cap'          => true,
					'publicly_queryable'    => true,
					'exclude_from_search'   => false,
					'hierarchical'          => false,
					'rewrite'               => $rewrite,
					'query_var'             => true,
					'supports'              => [ 'title', 'editor', 'custom-fields', 'publicize', 'thumbnail', 'author' ],
					'has_archive'           => $has_archive,
					'show_in_nav_menus'     => false,
					'delete_with_user'      => true,
					'show_in_rest'          => true,
					'rest_base'             => 'event-listings',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'template'              => [ [ 'core/freeform' ] ],
					'template_lock'         => 'all',
					'menu_position'         => 30,
				]
			)
		);

		/**
		 * Feeds
		 */
		add_feed( self::get_event_feed_name(), [ $this, 'event_feed' ] );

		/**
		 * Post status
		 */
		register_post_status(
			'expired',
			[
				'label'                     => _x( 'Expired', 'post status', 'wp-event-manager' ),
				'public'                    => true,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				// translators: Placeholder %s is the number of expired posts of this type.
				'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'wp-event-manager' ),
			]
		);
		register_post_status(
			'preview',
			[
				'label'                     => _x( 'Preview', 'post status', 'wp-event-manager' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				// translators: Placeholder %s is the number of posts in a preview state.
				'label_count'               => _n_noop( 'Preview <span class="count">(%s)</span>', 'Preview <span class="count">(%s)</span>', 'wp-event-manager' ),
			]
		);
	}

	/**
	 * Change label for admin menu item to show number of event Listing items pending approval.
	 */
	public function admin_head() {
		global $menu;

		$pending_events = WP_event_Manager_Cache_Helper::get_listings_count();

		// No need to go further if no pending events, menu is not set, or is not an array.
		if ( empty( $pending_events ) || empty( $menu ) || ! is_array( $menu ) ) {
			return;
		}

		// Try to pull menu_name from post type object to support themes/plugins that change the menu string.
		$post_type = get_post_type_object( 'event_listing' );
		$plural    = isset( $post_type->labels, $post_type->labels->menu_name ) ? $post_type->labels->menu_name : __( 'event Listings', 'wp-event-manager' );

		foreach ( $menu as $key => $menu_item ) {
			if ( strpos( $menu_item[0], $plural ) === 0 ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Only way to add pending listing count.
				$menu[ $key ][0] .= " <span class='awaiting-mod update-plugins count-" . esc_attr( $pending_events ) . "'><span class='pending-count'>" . absint( number_format_i18n( $pending_events ) ) . '</span></span>';
				break;
			}
		}
	}

	/**
	 * Filter the post content of event listings.
	 *
	 * @since 1.33.0
	 * @param string $post_content Post content to filter.
	 */
	public static function output_kses_post( $post_content ) {
		echo wp_kses( $post_content, self::kses_allowed_html() );
	}

	/**
	 * Returns the expanded set of tags allowed in event listing content.
	 *
	 * @since 1.33.0
	 * @return string
	 */
	private static function kses_allowed_html() {
		/**
		 * Change the allowed tags in event listing content.
		 *
		 * @since 1.33.0
		 *
		 * @param array $allowed_html Tags allowed in event listing posts.
		 */
		return apply_filters(
			'event_manager_kses_allowed_html',
			array_replace_recursive( // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replace_recursiveFound
				wp_kses_allowed_html( 'post' ),
				[
					'iframe' => [
						'src'             => true,
						'width'           => true,
						'height'          => true,
						'frameborder'     => true,
						'marginwidth'     => true,
						'marginheight'    => true,
						'scrolling'       => true,
						'title'           => true,
						'allow'           => true,
						'allowfullscreen' => true,
					],
				]
			)
		);
	}

	/**
	 * Sanitize event type meta box input data from WP admin.
	 *
	 * @param WP_Taxonomy $taxonomy  Taxonomy being sterilized.
	 * @param mixed       $input     Raw term data from the 'tax_input' field.
	 * @return int[]|int
	 */
	public function sanitize_event_type_meta_box_input( $taxonomy, $input ) {
		if ( is_array( $input ) ) {
			return array_map( 'intval', $input );
		}
		return intval( $input );
	}

	/**
	 * Toggles content filter on and off.
	 *
	 * @param bool $enable
	 */
	private function event_content_filter( $enable ) {
		if ( ! $enable ) {
			remove_filter( 'the_content', [ $this, 'event_content' ] );
		} else {
			add_filter( 'the_content', [ $this, 'event_content' ] );
		}
	}

	/**
	 * Adds extra content before/after the post for single event listings.
	 *
	 * @param string $content
	 * @return string
	 */
	public function event_content( $content ) {
		global $post;

		if ( ! is_singular( 'event_listing' ) || ! in_the_loop() || 'event_listing' !== $post->post_type ) {
			return $content;
		}

		ob_start();

		$this->event_content_filter( false );

		do_action( 'event_content_start' );

		get_event_manager_template_part( 'content-single', 'event_listing' );

		do_action( 'event_content_end' );

		$this->event_content_filter( true );

		return apply_filters( 'event_manager_single_event_content', ob_get_clean(), $post );
	}

	/**
	 * Generates the RSS feed for event Listings.
	 */
	public function event_feed() {
		global $event_manager_keyword;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Input used to filter public data in feed.
		$input_posts_per_page  = isset( $_GET['posts_per_page'] ) ? absint( $_GET['posts_per_page'] ) : 10;
		$input_search_location = isset( $_GET['search_location'] ) ? sanitize_text_field( wp_unslash( $_GET['search_location'] ) ) : false;
		$input_event_types       = isset( $_GET['event_types'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['event_types'] ) ) ) : false;
		$input_event_categories  = isset( $_GET['event_categories'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['event_categories'] ) ) ) : false;
		$event_manager_keyword   = isset( $_GET['search_keywords'] ) ? sanitize_text_field( wp_unslash( $_GET['search_keywords'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$query_args = [
			'post_type'           => 'event_listing',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $input_posts_per_page,
			'paged'               => absint( get_query_var( 'paged', 1 ) ),
			'tax_query'           => [],
			'meta_query'          => [],
		];

		if ( ! empty( $input_search_location ) ) {
			$location_meta_keys = [ 'geolocation_formatted_address', '_event_location', 'geolocation_state_long' ];
			$location_search    = [ 'relation' => 'OR' ];
			foreach ( $location_meta_keys as $meta_key ) {
				$location_search[] = [
					'key'     => $meta_key,
					'value'   => $input_search_location,
					'compare' => 'like',
				];
			}
			$query_args['meta_query'][] = $location_search;
		}

		if ( ! empty( $input_event_types ) ) {
			$query_args['tax_query'][] = [
				'taxonomy' => 'event_listing_type',
				'field'    => 'slug',
				'terms'    => $input_event_types + [ 0 ],
			];
		}

		if ( ! empty( $input_event_categories ) ) {
			$cats                      = $input_event_categories + [ 0 ];
			$field                     = is_numeric( $cats ) ? 'term_id' : 'slug';
			$operator                  = 'all' === get_option( 'event_manager_category_filter_type', 'all' ) && count( $cats ) > 1 ? 'AND' : 'IN';
			$query_args['tax_query'][] = [
				'taxonomy'         => 'event_listing_category',
				'field'            => $field,
				'terms'            => $cats,
				'include_children' => 'AND' !== $operator,
				'operator'         => $operator,
			];
		}

		if ( ! empty( $event_manager_keyword ) ) {
			$query_args['s'] = $event_manager_keyword;
			add_filter( 'posts_search', 'get_event_listings_keyword_search' );
		}

		if ( empty( $query_args['meta_query'] ) ) {
			unset( $query_args['meta_query'] );
		}

		if ( empty( $query_args['tax_query'] ) ) {
			unset( $query_args['tax_query'] );
		}

		// phpcs:ignore WordPress.WP.DiscouragedFunctions
		query_posts( apply_filters( 'event_feed_args', $query_args ) );
		add_action( 'rss2_ns', [ $this, 'event_feed_namespace' ] );
		add_action( 'rss2_item', [ $this, 'event_feed_item' ] );
		do_feed_rss2( false );
		remove_filter( 'posts_search', 'get_event_listings_keyword_search' );
	}

	/**
	 * Adds query arguments in order to make sure that the feed properly queries the 'event_listing' type.
	 *
	 * @param WP_Query $wp
	 */
	public function add_feed_query_args( $wp ) {

		// Let's leave if not the event feed.
		if ( ! isset( $wp->query_vars['feed'] ) || self::get_event_feed_name() !== $wp->query_vars['feed'] ) {
			return;
		}

		// Leave if not a feed.
		if ( false === $wp->is_feed ) {
			return;
		}

		// If the post_type was already set, let's get out of here.
		if ( isset( $wp->query_vars['post_type'] ) && ! empty( $wp->query_vars['post_type'] ) ) {
			return;
		}

		$wp->query_vars['post_type'] = 'event_listing';
	}

	/**
	 * Adds a custom namespace to the event feed.
	 */
	public function event_feed_namespace() {
		echo 'xmlns:event_listing="' . esc_url( site_url() ) . '"' . "\n";
	}

	/**
	 * Adds custom data to the event feed.
	 */
	public function event_feed_item() {
		$post_id   = get_the_ID();
		$location  = get_the_event_location( $post_id );
		$company   = get_the_company_name( $post_id );
		$event_types = wpjm_get_the_event_types( $post_id );

		if ( $location ) {
			echo '<event_listing:location><![CDATA[' . esc_html( $location ) . "]]></event_listing:location>\n";
		}
		if ( ! empty( $event_types ) ) {
			$event_types_names = implode( ', ', wp_list_pluck( $event_types, 'name' ) );
			echo '<event_listing:event_type><![CDATA[' . esc_html( $event_types_names ) . "]]></event_listing:event_type>\n";
		}
		if ( $company ) {
			echo '<event_listing:company><![CDATA[' . esc_html( $company ) . "]]></event_listing:company>\n";
		}

		/**
		 * Fires at the end of each event RSS feed item.
		 *
		 * @param int $post_id The post ID of the event.
		 */
		do_action( 'event_feed_item', $post_id );
	}

	/**
	 * Maintenance task to expire events.
	 */
	public function check_for_expired_events() {
		// Change status to expired.
		$event_ids = get_posts(
			[
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => '_event_expires',
						'value'   => 0,
						'compare' => '>',
					],
					[
						'key'     => '_event_expires',
						'value'   => date( 'Y-m-d', current_time( 'timestamp' ) ),
						'compare' => '<',
					],
				],
			]
		);

		if ( $event_ids ) {
			foreach ( $event_ids as $event_id ) {
				$event_data                = [];
				$event_data['ID']          = $event_id;
				$event_data['post_status'] = 'expired';
				wp_update_post( $event_data );
			}
		}

		// Delete old expired events.

		/**
		 * Set whether or not we should delete expired events after a certain amount of time.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $delete_expired_events Whether we should delete expired events after a certain amount of time. Defaults to false.
		 */
		if ( apply_filters( 'event_manager_delete_expired_events', false ) ) {
			/**
			 * Days to preserve expired event listings before deleting them.
			 *
			 * @since 1.0.0
			 *
			 * @param int $delete_expired_events_days Number of days to preserve expired event listings before deleting them.
			 */
			$delete_expired_events_days = apply_filters( 'event_manager_delete_expired_events_days', 30 );

			$event_ids = get_posts(
				[
					'post_type'      => 'event_listing',
					'post_status'    => 'expired',
					'fields'         => 'ids',
					'date_query'     => [
						[
							'column' => 'post_modified',
							'before' => date( 'Y-m-d', strtotime( '-' . $delete_expired_events_days . ' days', current_time( 'timestamp' ) ) ),
						],
					],
					'posts_per_page' => -1,
				]
			);

			if ( $event_ids ) {
				foreach ( $event_ids as $event_id ) {
					wp_trash_post( $event_id );
				}
			}
		}
	}

	/**
	 * Deletes old previewed events after 30 days to keep the DB clean.
	 */
	public function delete_old_previews() {
		// Delete old events stuck in preview.
		$event_ids = get_posts(
			[
				'post_type'      => 'event_listing',
				'post_status'    => 'preview',
				'fields'         => 'ids',
				'date_query'     => [
					[
						'column' => 'post_modified',
						'before' => date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) ),
					],
				],
				'posts_per_page' => -1,
			]
		);

		if ( $event_ids ) {
			foreach ( $event_ids as $event_id ) {
				wp_delete_post( $event_id, true );
			}
		}
	}

	/**
	 * Typo wrapper for `set_expiry` method.
	 *
	 * @param WP_Post $post
	 * @since 1.0.0
	 * @deprecated 1.0.1
	 */
	public function set_expirey( $post ) {
		_deprecated_function( __METHOD__, '1.0.1', 'WP_event_Manager_Post_Types::set_expiry' );
		$this->set_expiry( $post );
	}

	/**
	 * Sets expiry date when event status changes.
	 *
	 * @param WP_Post $post
	 */
	public function set_expiry( $post ) {
		if ( 'event_listing' !== $post->post_type ) {
			return;
		}

		// See if it is already set.
		if ( metadata_exists( 'post', $post->ID, '_event_expires' ) ) {
			$expires = get_post_meta( $post->ID, '_event_expires', true );
			if ( $expires && strtotime( $expires ) < current_time( 'timestamp' ) ) {
				update_post_meta( $post->ID, '_event_expires', '' );
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce check handled by WP core.
		$input_event_expires = isset( $_POST['_event_expires'] ) ? sanitize_text_field( wp_unslash( $_POST['_event_expires'] ) ) : null;

		// See if the user has set the expiry manually.
		if ( ! empty( $input_event_expires ) ) {
			update_post_meta( $post->ID, '_event_expires', date( 'Y-m-d', strtotime( $input_event_expires ) ) );
		} elseif ( ! isset( $expires ) ) {
			// No manual setting? Lets generate a date if there isn't already one.
			$expires = calculate_event_expiry( $post->ID );
			update_post_meta( $post->ID, '_event_expires', $expires );

			// In case we are saving a post, ensure post data is updated so the field is not overridden.
			if ( null !== $input_event_expires ) {
				$_POST['_event_expires'] = $expires;
			}
		}
	}

	/**
	 * Displays the application content when the application method is an email.
	 *
	 * @param stdClass $apply
	 */
	public function application_details_email( $apply ) {
		get_event_manager_template( 'event-application-email.php', [ 'apply' => $apply ] );
	}

	/**
	 * Displays the application content when the application method is a url.
	 *
	 * @param stdClass $apply
	 */
	public function application_details_url( $apply ) {
		get_event_manager_template( 'event-application-url.php', [ 'apply' => $apply ] );
	}

	/**
	 * Fixes post name when wp_update_post changes it.
	 *
	 * @param array $data
	 * @param array $postarr
	 * @return array
	 */
	public function fix_post_name( $data, $postarr ) {
		if ( 'event_listing' === $data['post_type']
			&& 'pending' === $data['post_status']
			&& ! current_user_can( 'publish_posts' )
			&& isset( $postarr['post_name'] )
		) {
			$data['post_name'] = $postarr['post_name'];
		}
		return $data;
	}

	/**
	 * Returns the name of the event RSS feed.
	 *
	 * @return string
	 */
	public static function get_event_feed_name() {
		/**
		 * Change the name of the event feed.
		 *
		 * NOTE: When you override this, you must re-save permalink settings to clear the rewrite cache.
		 *
		 * @since 1.32.0
		 *
		 * @param string $event_feed_name Slug used for the event feed.
		 */
		return apply_filters( 'event_manager_event_feed_name', 'event_feed' );
	}

	/**
	 * Get the permalink settings directly from the option.
	 *
	 * @return array Permalink settings option.
	 */
	public static function get_raw_permalink_settings() {
		/**
		 * Option `wpjm_permalinks` was renamed to match other options in 1.32.0.
		 *
		 * Reference to the old option and support for non-standard plugin updates will be removed in 1.34.0.
		 */
		$legacy_permalink_settings = '[]';
		if ( false !== get_option( 'wpjm_permalinks', false ) ) {
			$legacy_permalink_settings = wp_json_encode( get_option( 'wpjm_permalinks', [] ) );
			delete_option( 'wpjm_permalinks' );
		}

		return (array) json_decode( get_option( self::PERMALINK_OPTION_NAME, $legacy_permalink_settings ), true );
	}

	/**
	 * Retrieves permalink settings.
	 *
	 * @see https://github.com/woocommerce/woocommerce/blob/3.0.8/includes/wc-core-functions.php#L1573
	 * @since 1.28.0
	 * @return array
	 */
	public static function get_permalink_structure() {
		// Switch to the site's default locale, bypassing the active user's locale.
		if ( function_exists( 'switch_to_locale' ) && did_action( 'admin_init' ) ) {
			switch_to_locale( get_locale() );
		}

		$permalink_settings = self::get_raw_permalink_settings();

		// First-time activations will get this cleared on activation.
		if ( ! array_key_exists( 'events_archive', $permalink_settings ) ) {
			// Create entry to prevent future checks.
			$permalink_settings['events_archive'] = '';
			if ( current_theme_supports( 'event-manager-templates' ) ) {
				// This isn't the first activation and the theme supports it. Set the default to legacy value.
				$permalink_settings['events_archive'] = _x( 'events', 'Post type archive slug - resave permalinks after changing this', 'wp-event-manager' );
			}
			update_option( self::PERMALINK_OPTION_NAME, wp_json_encode( $permalink_settings ) );
		}

		$permalinks = wp_parse_args(
			$permalink_settings,
			[
				'event_base'      => '',
				'category_base' => '',
				'type_base'     => '',
				'events_archive'  => '',
			]
		);

		// Ensure rewrite slugs are set. Use legacy translation options if not.
		$permalinks['event_rewrite_slug']          = untrailingslashit( empty( $permalinks['event_base'] ) ? _x( 'event', 'event permalink - resave permalinks after changing this', 'wp-event-manager' ) : $permalinks['event_base'] );
		$permalinks['category_rewrite_slug']     = untrailingslashit( empty( $permalinks['category_base'] ) ? _x( 'event-category', 'event category slug - resave permalinks after changing this', 'wp-event-manager' ) : $permalinks['category_base'] );
		$permalinks['type_rewrite_slug']         = untrailingslashit( empty( $permalinks['type_base'] ) ? _x( 'event-type', 'event type slug - resave permalinks after changing this', 'wp-event-manager' ) : $permalinks['type_base'] );
		$permalinks['events_archive_rewrite_slug'] = untrailingslashit( empty( $permalinks['events_archive'] ) ? 'event-listings' : $permalinks['events_archive'] );

		// Restore the original locale.
		if ( function_exists( 'restore_current_locale' ) && did_action( 'admin_init' ) ) {
			restore_current_locale();
		}
		return $permalinks;
	}

	/**
	 * Generates location data if a post is added.
	 *
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public function maybe_add_geolocation_data( $object_id, $meta_key, $meta_value ) {
		if ( '_event_location' !== $meta_key || 'event_listing' !== get_post_type( $object_id ) ) {
			return;
		}
		do_action( 'event_manager_event_location_edited', $object_id, $meta_value );
	}

	/**
	 * Triggered when updating meta on a event listing.
	 *
	 * @param int    $meta_id
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public function update_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'event_listing' !== get_post_type( $object_id ) ) {
			return;
		}

		switch ( $meta_key ) {
			case '_event_location':
				$this->maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value );
				break;
			case '_featured':
				$this->maybe_update_menu_order( $meta_id, $object_id, $meta_key, $meta_value );
				break;
		}
	}

	/**
	 * Generates location data if a post is updated.
	 *
	 * @param int    $meta_id (Unused).
	 * @param int    $object_id
	 * @param string $meta_key (Unused).
	 * @param mixed  $meta_value
	 */
	public function maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value ) {
		do_action( 'event_manager_event_location_edited', $object_id, $meta_value );
	}

	/**
	 * Maybe sets menu_order if the featured status of a event is changed.
	 *
	 * @param int    $meta_id (Unused).
	 * @param int    $object_id
	 * @param string $meta_key (Unused).
	 * @param mixed  $meta_value
	 */
	public function maybe_update_menu_order( $meta_id, $object_id, $meta_key, $meta_value ) {
		global $wpdb;

		if ( 1 === intval( $meta_value ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update post menu order without firing actions.
			$wpdb->update(
				$wpdb->posts,
				[ 'menu_order' => -1 ],
				[ 'ID' => $object_id ]
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update post menu order without firing actions.
			$wpdb->update(
				$wpdb->posts,
				[ 'menu_order' => 0 ],
				[
					'ID'         => $object_id,
					'menu_order' => -1,
				]
			);
		}

		clean_post_cache( $object_id );
	}

	/**
	 * Legacy.
	 *
	 * @param int    $meta_id
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 * @deprecated 1.19.1
	 */
	public function maybe_generate_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value ) {
		_deprecated_function( __METHOD__, '1.19.1', 'WP_event_Manager_Post_Types::maybe_update_geolocation_data' );
		$this->maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Maybe sets default meta data for event listings.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function maybe_add_default_meta_data( $post_id, $post ) {
		if ( empty( $post ) || 'event_listing' === $post->post_type ) {
			add_post_meta( $post_id, '_filled', 0, true );
			add_post_meta( $post_id, '_featured', 0, true );
		}
	}

	/**
	 * Track event submission from the backend.
	 *
	 * @param string  $new_status  New post status.
	 * @param string  $old_status  Old status.
	 * @param WP_Post $post        Post object.
	 */
	public function track_event_submission( $new_status, $old_status, $post ) {
		if ( empty( $post ) || 'event_listing' !== get_post_type( $post ) ) {
			return;
		}

		if ( $new_status === $old_status || 'publish' !== $new_status ) {
			return;
		}

		// For the purpose of this event, we only care about admin requests and REST API requests.
		if ( ! is_admin() && ! WP_event_Manager_Usage_Tracking::is_rest_request() ) {
			return;
		}

		$source = WP_event_Manager_Usage_Tracking::is_rest_request() ? 'rest_api' : 'admin';

		if ( 'pending' === $old_status ) {
			// Track approving a new event listing.
			WP_event_Manager_Usage_Tracking::track_event_approval(
				$post->ID,
				[
					'source' => $source,
				]
			);

			return;
		}

		WP_event_Manager_Usage_Tracking::track_event_submission(
			$post->ID,
			[
				'source'     => $source,
				'old_status' => $old_status,
			]
		);
	}

	/**
	 * Add noindex for expired and filled event listings.
	 */
	public function noindex_expired_filled_event_listings() {
		if ( ! is_single() ) {
			return;
		}

		$post = get_post();
		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return;
		}

		if ( wpjm_allow_indexing_event_listing() ) {
			return;
		}

		wp_no_robots();
	}

	/**
	 * Add structured data to the footer of event listing pages.
	 */
	public function output_structured_data() {
		if ( ! is_single() ) {
			return;
		}

		if ( ! wpjm_output_event_listing_structured_data() ) {
			return;
		}

		$structured_data = wpjm_get_event_listing_structured_data();
		if ( ! empty( $structured_data ) ) {
			echo '<!-- WP Event Manager Structured Data -->' . "\r\n";
			echo '<script type="application/ld+json">' . wpjm_esc_json( wp_json_encode( $structured_data ), true ) . '</script>';
		}
	}

	/**
	 * Sanitize and verify employment type.
	 *
	 * @param string $employment_type
	 * @return string
	 */
	public function sanitize_employment_type( $employment_type ) {
		$employment_types = wpjm_event_listing_employment_type_options();
		if ( ! isset( $employment_types[ $employment_type ] ) ) {
			return null;
		}
		return $employment_type;
	}

	/**
	 * Registers event listing meta fields.
	 */
	public function register_meta_fields() {
		$fields = self::get_event_listing_fields();

		foreach ( $fields as $meta_key => $field ) {
			register_meta(
				'post',
				$meta_key,
				[
					'type'              => $field['data_type'],
					'show_in_rest'      => $field['show_in_rest'],
					'description'       => $field['label'],
					'sanitize_callback' => $field['sanitize_callback'],
					'auth_callback'     => $field['auth_edit_callback'],
					'single'            => true,
					'object_subtype'    => 'event_listing',
				]
			);
		}
	}

	/**
	 * Returns configuration for custom fields on event Listing posts.
	 *
	 * @return array See `event_manager_event_listing_data_fields` filter for more documentation.
	 */
	public static function get_event_listing_fields() {
		$default_field = [
			'label'              => null,
			'placeholder'        => null,
			'description'        => null,
			'priority'           => 10,
			'value'              => null,
			'default'            => null,
			'classes'            => [],
			'type'               => 'text',
			'data_type'          => 'string',
			'show_in_admin'      => true,
			'show_in_rest'       => false,
			'auth_edit_callback' => [ __CLASS__, 'auth_check_can_edit_event_listings' ],
			'auth_view_callback' => null,
			'sanitize_callback'  => [ __CLASS__, 'sanitize_meta_field_based_on_input_type' ],
		];

		$allowed_application_method     = get_option( 'event_manager_allowed_application_method', '' );
		$application_method_label       = __( 'Application email/URL', 'wp-event-manager' );
		$application_method_placeholder = __( 'Enter an email address or website URL', 'wp-event-manager' );

		if ( 'email' === $allowed_application_method ) {
			$application_method_label       = __( 'Application email', 'wp-event-manager' );
			$application_method_placeholder = __( 'you@example.com', 'wp-event-manager' );
		} elseif ( 'url' === $allowed_application_method ) {
			$application_method_label       = __( 'Application URL', 'wp-event-manager' );
			$application_method_placeholder = __( 'https://', 'wp-event-manager' );
		}

		$fields = [
			'_event_location'    => [
				'label'         => __( 'Location', 'wp-event-manager' ),
				'placeholder'   => __( 'e.g. "London"', 'wp-event-manager' ),
				'description'   => __( 'Leave this blank if the location is not important.', 'wp-event-manager' ),
				'priority'      => 1,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_application'     => [
				'label'             => $application_method_label,
				'placeholder'       => $application_method_placeholder,
				'description'       => __( 'This field is required for the "application" area to appear beneath the listing.', 'wp-event-manager' ),
				'priority'          => 2,
				'data_type'         => 'string',
				'show_in_admin'     => true,
				'show_in_rest'      => true,
				'sanitize_callback' => [ __CLASS__, 'sanitize_meta_field_application' ],
			],
			'_company_name'    => [
				'label'         => __( 'Company Name', 'wp-event-manager' ),
				'placeholder'   => '',
				'priority'      => 3,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_company_website' => [
				'label'             => __( 'Company Website', 'wp-event-manager' ),
				'placeholder'       => '',
				'priority'          => 4,
				'data_type'         => 'string',
				'show_in_admin'     => true,
				'show_in_rest'      => true,
				'sanitize_callback' => [ __CLASS__, 'sanitize_meta_field_url' ],
			],
			'_company_tagline' => [
				'label'         => __( 'Company Tagline', 'wp-event-manager' ),
				'placeholder'   => __( 'Brief description about the company', 'wp-event-manager' ),
				'priority'      => 5,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_company_twitter' => [
				'label'         => __( 'Company Twitter', 'wp-event-manager' ),
				'placeholder'   => '@yourcompany',
				'priority'      => 6,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_company_video'   => [
				'label'             => __( 'Company Video', 'wp-event-manager' ),
				'placeholder'       => __( 'URL to the company video', 'wp-event-manager' ),
				'type'              => 'file',
				'priority'          => 8,
				'data_type'         => 'string',
				'show_in_admin'     => true,
				'show_in_rest'      => true,
				'sanitize_callback' => [ __CLASS__, 'sanitize_meta_field_url' ],
			],
			'_filled'          => [
				'label'         => __( 'Position Filled', 'wp-event-manager' ),
				'type'          => 'checkbox',
				'priority'      => 9,
				'data_type'     => 'integer',
				'show_in_admin' => true,
				'show_in_rest'  => true,
				'description'   => __( 'Filled listings will no longer accept applications.', 'wp-event-manager' ),
			],
			'_featured'        => [
				'label'              => __( 'Featured Listing', 'wp-event-manager' ),
				'type'               => 'checkbox',
				'description'        => __( 'Featured listings will be sticky during searches, and can be styled differently.', 'wp-event-manager' ),
				'priority'           => 10,
				'data_type'          => 'integer',
				'show_in_admin'      => true,
				'show_in_rest'       => true,
				'auth_edit_callback' => [ __CLASS__, 'auth_check_can_manage_event_listings' ],
			],
			'_event_expires'     => [
				'label'              => __( 'Listing Expiry Date', 'wp-event-manager' ),
				'priority'           => 11,
				'show_in_admin'      => true,
				'show_in_rest'       => true,
				'data_type'          => 'string',
				'classes'            => [ 'event-manager-datepicker' ],
				'auth_edit_callback' => [ __CLASS__, 'auth_check_can_manage_event_listings' ],
				'auth_view_callback' => [ __CLASS__, 'auth_check_can_edit_event_listings' ],
				'sanitize_callback'  => [ __CLASS__, 'sanitize_meta_field_date' ],
			],
		];

		/**
		 * Filters event listing data fields.
		 *
		 * For the REST API, do not pass fields you don't want to be visible to the current visitor when `show_in_rest`
		 * is `true`. To add values and other data when generating the WP admin form, use filter
		 * `event_manager_event_listing_wp_admin_fields` which should have `$post_id` in context.
		 *
		 * @since 1.0.0
		 * @since 1.27.0 $post_id was added.
		 * @since 1.33.0 Used both in WP admin and REST API. Removed `$post_id` attribute. Added fields for REST API.
		 *
		 * @param array    $fields  {
		 *     event listing meta fields for REST API and WP admin. Associative array with meta key as the index.
		 *     All fields except for `$label` are optional and have working defaults.
		 *
		 *     @type array $meta_key {
		 *         @type string        $label              Label to show for field. Used in: WP Admin; REST API.
		 *         @type string        $placeholder        Placeholder to show in empty form fields. Used in: WP Admin.
		 *         @type string        $description        Longer description to shown below form field.
		 *                                                 Used in: WP Admin.
		 *         @type array         $classes            Classes to apply to form input field. Used in: WP Admin.
		 *         @type int           $priority           Field placement priority for WP admin. Lower is first.
		 *                                                 Used in: WP Admin (Default: 10).
		 *         @type string        $value              Override standard retrieval of meta value in form field.
		 *                                                 Used in: WP Admin.
		 *         @type string        $default            Default value on form field if no other value is set for
		 *                                                 field. Used in: WP Admin (Since 1.33.0).
		 *         @type string        $type               Type of form field to render. Used in: WP Admin
		 *                                                 (Default: 'text').
		 *         @type string        $data_type          Data type to cast to. Options: 'string', 'boolean',
		 *                                                 'integer', 'number'.  Used in: REST API. (Since 1.33.0;
		 *                                                 Default: 'string').
		 *         @type bool|callable $show_in_admin      Whether field should be displayed in WP admin. Can be
		 *                                                 callable that returns boolean. Used in: WP Admin
		 *                                                 (Since 1.33.0; Default: true).
		 *         @type bool|array    $show_in_rest       Whether data associated with this meta key can put in REST
		 *                                                 API response for event listings. Can be used to pass REST API
		 *                                                 arguments in `show_in_rest` parameter. Used in: REST API
		 *                                                 (Since 1.33.0; Default: false).
		 *         @type callable      $auth_edit_callback {
		 *             Decides if specific user can edit the meta key. Used in: WP Admin; REST API.
		 *             Defaults to callable that limits to those who can edit specific the event listing (also limited
		 *             by relevant endpoints).
		 *
		 *             @see WP core filter `auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}`.
		 *             @since 1.33.0
		 *
		 *             @param bool   $allowed   Whether the user can add the object meta. Default false.
		 *             @param string $meta_key  The meta key.
		 *             @param int    $object_id Post ID for event Listing.
		 *             @param int    $user_id   User ID.
		 *
		 *             @return bool
		 *         }
		 *         @type callable      $auth_view_callback {
		 *             Decides if specific user can view value of the meta key. Used in: REST API.
		 *             Defaults to visible to all (if shown in REST API, which by default is false).
		 *
		 *             @see WPJM method `WP_event_Manager_REST_API::prepare_event_listing()`.
		 *             @since 1.33.0
		 *
		 *             @param bool   $allowed   Whether the user can add the object meta. Default false.
		 *             @param string $meta_key  The meta key.
		 *             @param int    $object_id Post ID for event Listing.
		 *             @param int    $user_id   User ID.
		 *
		 *             @return bool
		 *         }
		 *         @type callable      $sanitize_callback  {
		 *             Sanitizes the meta value before saving to database. Used in: WP Admin; REST API; Frontend.
		 *             Defaults to callable that sanitizes based on the field type.
		 *
		 *             @see WP core filter `auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}`
		 *             @since 1.33.0
		 *
		 *             @param mixed  $meta_value Value of meta field that needs sanitization.
		 *             @param string $meta_key   Meta key that is being sanitized.
		 *
		 *             @return mixed
		 *         }
		 *     }
		 * }
		 */
		$fields = apply_filters( 'event_manager_event_listing_data_fields', $fields );

		// Ensure default fields are set.
		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = array_merge( $default_field, $field );
		}

		return $fields;
	}

	/**
	 * Sanitize meta fields based on input type.
	 *
	 * @param mixed  $meta_value Value of meta field that needs sanitization.
	 * @param string $meta_key   Meta key that is being sanitized.
	 * @return mixed
	 */
	public static function sanitize_meta_field_based_on_input_type( $meta_value, $meta_key ) {
		$fields = self::get_event_listing_fields();

		if ( is_string( $meta_value ) ) {
			$meta_value = trim( $meta_value );
		}

		$type = 'text';
		if ( isset( $fields[ $meta_key ] ) ) {
			$type = $fields[ $meta_key ]['type'];
		}

		if ( 'textarea' === $type || 'wp_editor' === $type ) {
			return wp_kses_post( wp_unslash( $meta_value ) );
		}

		if ( 'checkbox' === $type ) {
			if ( $meta_value && '0' !== $meta_value ) {
				return 1;
			}

			return 0;
		}

		if ( is_array( $meta_value ) ) {
			return array_filter( array_map( 'sanitize_text_field', $meta_value ) );
		}

		return sanitize_text_field( $meta_value );
	}

	/**
	 * Sanitize `_application` meta field.
	 *
	 * @param string $meta_value Value of meta field that needs sanitization.
	 * @return string
	 */
	public static function sanitize_meta_field_application( $meta_value ) {
		if ( is_email( $meta_value ) ) {
			return sanitize_email( $meta_value );
		}

		return self::sanitize_meta_field_url( $meta_value );
	}

	/**
	 * Sanitize URL meta fields.
	 *
	 * @param string $meta_value Value of meta field that needs sanitization.
	 * @return string
	 */
	public static function sanitize_meta_field_url( $meta_value ) {
		$meta_value = trim( $meta_value );
		if ( '' === $meta_value ) {
			return $meta_value;
		}

		return esc_url_raw( $meta_value );
	}

	/**
	 * Sanitize date meta fields.
	 *
	 * @param string $meta_value Value of meta field that needs sanitization.
	 * @return string
	 */
	public static function sanitize_meta_field_date( $meta_value ) {
		$meta_value = trim( $meta_value );

		// Matches yyyy-mm-dd.
		if ( ! preg_match( '/[\d]{4}\-[\d]{2}\-[\d]{2}/', $meta_value ) ) {
			return '';
		}

		// Checks for valid date.
		if ( date( 'Y-m-d', strtotime( $meta_value ) ) !== $meta_value ) {
			return '';
		}

		return $meta_value;
	}

	/**
	 * Checks if user can manage event listings.
	 *
	 * @param bool   $allowed   Whether the user can edit the event listing meta.
	 * @param string $meta_key  The meta key.
	 * @param int    $post_id   event listing's post ID.
	 * @param int    $user_id   User ID.
	 *
	 * @return bool Whether the user can edit the event listing meta.
	 */
	public static function auth_check_can_manage_event_listings( $allowed, $meta_key, $post_id, $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return false;
		}

		return $user->has_cap( 'manage_event_listings' );
	}

	/**
	 * Checks if user can edit event listings.
	 *
	 * @param bool   $allowed   Whether the user can edit the event listing meta.
	 * @param string $meta_key  The meta key.
	 * @param int    $post_id   event listing's post ID.
	 * @param int    $user_id   User ID.
	 *
	 * @return bool Whether the user can edit the event listing meta.
	 */
	public static function auth_check_can_edit_event_listings( $allowed, $meta_key, $post_id, $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return false;
		}

		if ( empty( $post_id ) ) {
			return current_user_can( 'edit_event_listings' );
		}

		return event_manager_user_can_edit_event( $post_id );
	}

	/**
	 * Checks if user can edit other's event listings.
	 *
	 * @param bool   $allowed   Whether the user can edit the event listing meta.
	 * @param string $meta_key  The meta key.
	 * @param int    $post_id   event listing's post ID.
	 * @param int    $user_id   User ID.
	 *
	 * @return bool Whether the user can edit the event listing meta.
	 */
	public static function auth_check_can_edit_others_event_listings( $allowed, $meta_key, $post_id, $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return false;
		}

		return $user->has_cap( 'edit_others_event_listings' );
	}

	/**
	 * Add post type for event Manager to list of post types deleted with user.
	 *
	 * @since 1.33.0
	 *
	 * @param array $types
	 * @return array
	 */
	public function delete_user_add_event_listings_post_type( $types ) {
		$types[] = 'event_listing';

		return $types;
	}
}
