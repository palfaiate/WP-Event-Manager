<?php
/**
 * File containing the class WP_event_Manager_CPT.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles actions and filters specific to the custom post type for event Listings.
 *
 * @since 1.0.0
 */
class WP_event_Manager_CPT {

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
		add_filter( 'enter_title_here', [ $this, 'enter_title_here' ], 1, 2 );
		add_filter( 'manage_edit-event_listing_columns', [ $this, 'columns' ] );
		add_filter( 'list_table_primary_column', [ $this, 'primary_column' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'row_actions' ] );
		add_action( 'manage_event_listing_posts_custom_column', [ $this, 'custom_columns' ], 2 );
		add_filter( 'manage_edit-event_listing_sortable_columns', [ $this, 'sortable_columns' ] );
		add_filter( 'request', [ $this, 'sort_columns' ] );
		add_action( 'parse_query', [ $this, 'search_meta' ] );
		add_action( 'parse_query', [ $this, 'filter_meta' ] );
		add_filter( 'get_search_query', [ $this, 'search_meta_label' ] );
		add_filter( 'post_updated_messages', [ $this, 'post_updated_messages' ] );
		add_action( 'bulk_actions-edit-event_listing', [ $this, 'add_bulk_actions' ] );
		add_action( 'handle_bulk_actions-edit-event_listing', [ $this, 'do_bulk_actions' ], 10, 3 );
		add_action( 'admin_init', [ $this, 'approve_event' ] );
		add_action( 'admin_notices', [ $this, 'action_notices' ] );
		add_action( 'view_mode_post_types', [ $this, 'disable_view_mode' ] );

		if ( get_option( 'event_manager_enable_categories' ) ) {
			add_action( 'restrict_manage_posts', [ $this, 'events_by_category' ] );
		}
		add_action( 'restrict_manage_posts', [ $this, 'events_meta_filters' ] );

		foreach ( [ 'post', 'post-new' ] as $hook ) {
			add_action( "admin_footer-{$hook}.php", [ $this, 'extend_submitdiv_post_status' ] );
		}
	}

	/**
	 * Returns the list of bulk actions that can be performed on event listings.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions_handled                         = [];
		$actions_handled['approve_events']         = [
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'label'   => __( 'Approve %s', 'wp-event-manager' ),
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'notice'  => __( '%s approved', 'wp-event-manager' ),
			'handler' => [ $this, 'bulk_action_handle_approve_event' ],
		];
		$actions_handled['expire_events']          = [
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'label'   => __( 'Expire %s', 'wp-event-manager' ),
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'notice'  => __( '%s expired', 'wp-event-manager' ),
			'handler' => [ $this, 'bulk_action_handle_expire_event' ],
		];
		$actions_handled['mark_events_filled']     = [
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'label'   => __( 'Mark %s Filled', 'wp-event-manager' ),
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'notice'  => __( '%s marked as filled', 'wp-event-manager' ),
			'handler' => [ $this, 'bulk_action_handle_mark_event_filled' ],
		];
		$actions_handled['mark_events_not_filled'] = [
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'label'   => __( 'Mark %s Not Filled', 'wp-event-manager' ),
			// translators: Placeholder (%s) is the plural name of the event listings post type.
			'notice'  => __( '%s marked as not filled', 'wp-event-manager' ),
			'handler' => [ $this, 'bulk_action_handle_mark_event_not_filled' ],
		];

		/**
		 * Filters the bulk actions that can be applied to event listings.
		 *
		 * @since 1.27.0
		 *
		 * @param array $actions_handled {
		 *     Bulk actions that can be handled, indexed by a unique key name (approve_events, expire_events, etc). Handlers
		 *     are responsible for checking abilities (`current_user_can( 'manage_event_listings', $post_id )`) before
		 *     performing action.
		 *
		 *     @type string   $label   Label for the bulk actions dropdown. Passed through sprintf with label name of event listing post type.
		 *     @type string   $notice  Success notice shown after performing the action. Passed through sprintf with title(s) of affected event listings.
		 *     @type callback $handler Callable handler for performing action. Passed one argument (int $post_id) and should return true on success and false on failure.
		 * }
		 */
		return apply_filters( 'wpjm_event_listing_bulk_actions', $actions_handled );
	}

	/**
	 * Adds bulk actions to drop downs on event Listing admin page.
	 *
	 * @param array $bulk_actions
	 * @return array
	 */
	public function add_bulk_actions( $bulk_actions ) {
		global $wp_post_types;

		foreach ( $this->get_bulk_actions() as $key => $bulk_action ) {
			if ( isset( $bulk_action['label'] ) ) {
				$bulk_actions[ $key ] = sprintf( $bulk_action['label'], $wp_post_types['event_listing']->labels->name );
			}
		}
		return $bulk_actions;
	}

	/**
	 * Performs bulk actions on event Listing admin page.
	 *
	 * @since 1.27.0
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $action       The action being taken.
	 * @param array  $post_ids     The posts to take the action on.
	 */
	public function do_bulk_actions( $redirect_url, $action, $post_ids ) {
		$actions_handled = $this->get_bulk_actions();
		if ( isset( $actions_handled[ $action ] ) && isset( $actions_handled[ $action ]['handler'] ) ) {
			$handled_events = [];
			if ( ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					if (
						'event_listing' === get_post_type( $post_id )
						&& call_user_func( $actions_handled[ $action ]['handler'], $post_id )
					) {
						$handled_events[] = $post_id;
					}
				}
				wp_safe_redirect( add_query_arg( 'handled_events', $handled_events, add_query_arg( 'action_performed', $action, $redirect_url ) ) );
				exit;
			}
		}
	}

	/**
	 * Performs bulk action to approve a single event listing.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public function bulk_action_handle_approve_event( $post_id ) {
		$event_data = [
			'ID'          => $post_id,
			'post_status' => 'publish',
		];
		if (
			in_array( get_post_status( $post_id ), [ 'pending', 'pending_payment' ], true )
			&& current_user_can( 'publish_post', $post_id )
			&& wp_update_post( $event_data )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Performs bulk action to expire a single event listing.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function bulk_action_handle_expire_event( $post_id ) {
		$event_data = [
			'ID'          => $post_id,
			'post_status' => 'expired',
		];
		if (
			current_user_can( 'manage_event_listings', $post_id )
			&& wp_update_post( $event_data )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Performs bulk action to mark a single event listing as filled.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public function bulk_action_handle_mark_event_filled( $post_id ) {
		if (
			current_user_can( 'manage_event_listings', $post_id )
			&& update_post_meta( $post_id, '_filled', 1 )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Performs bulk action to mark a single event listing as not filled.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function bulk_action_handle_mark_event_not_filled( $post_id ) {
		if (
			current_user_can( 'manage_event_listings', $post_id )
			&& update_post_meta( $post_id, '_filled', 0 )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Approves a single event.
	 */
	public function approve_event() {
		if (
			! empty( $_GET['approve_event'] )
			&& ! empty( $_REQUEST['_wpnonce'] )
			&& wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'approve_event' ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce should not be modified.
			&& current_user_can( 'publish_post', absint( $_GET['approve_event'] ) )
		) {
			$post_id  = absint( $_GET['approve_event'] );
			$event_data = [
				'ID'          => $post_id,
				'post_status' => 'publish',
			];
			wp_update_post( $event_data );
			wp_safe_redirect( remove_query_arg( 'approve_event', add_query_arg( 'handled_events', $post_id, add_query_arg( 'action_performed', 'approve_events', admin_url( 'edit.php?post_type=event_listing' ) ) ) ) );
			exit;
		}
	}

	/**
	 * Shows a notice if we did a bulk action.
	 */
	public function action_notices() {
		global $post_type, $pagenow;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$handled_events    = isset( $_REQUEST['handled_events'] ) && is_array( $_REQUEST['handled_events'] ) ? array_map( 'absint', $_REQUEST['handled_events'] ) : false;
		$action          = isset( $_REQUEST['action_performed'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action_performed'] ) ) : false;
		$actions_handled = $this->get_bulk_actions();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if (
			'edit.php' === $pagenow
			&& 'event_listing' === $post_type
			&& $action
			&& ! empty( $handled_events )
			&& isset( $actions_handled[ $action ] )
			&& isset( $actions_handled[ $action ]['notice'] )
		) {
			if ( is_array( $handled_events ) ) {
				$titles = [];
				foreach ( $handled_events as $event_id ) {
					$titles[] = wpjm_get_the_event_title( $event_id );
				}
				echo '<div class="updated"><p>' . wp_kses_post( sprintf( $actions_handled[ $action ]['notice'], '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) ) . '</p></div>';
			} else {
				echo '<div class="updated"><p>' . wp_kses_post( sprintf( $actions_handled[ $action ]['notice'], '&quot;' . wpjm_get_the_event_title( absint( $handled_events ) ) . '&quot;' ) ) . '</p></div>';
			}
		}
	}

	/**
	 * Shows category dropdown.
	 */
	public function events_by_category() {
		global $typenow, $wp_query;

		if ( 'event_listing' !== $typenow || ! taxonomy_exists( 'event_listing_category' ) ) {
			return;
		}

		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-category-walker.php';

		$r                 = [];
		$r['taxonomy']     = 'event_listing_category';
		$r['pad_counts']   = 1;
		$r['hierarchical'] = 1;
		$r['hide_empty']   = 0;
		$r['show_count']   = 1;
		$r['selected']     = ( isset( $wp_query->query['event_listing_category'] ) ) ? $wp_query->query['event_listing_category'] : '';
		$r['menu_order']   = false;
		$terms             = get_terms( $r );
		$walker            = new WP_event_Manager_Category_Walker();

		if ( ! $terms ) {
			return;
		}

		$allowed_html = [
			'option' => [
				'value'    => [],
				'selected' => [],
				'class'    => [],
			],
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No changes or data exposed based on input.
		$selected_category = isset( $_GET['event_listing_category'] ) ? sanitize_text_field( wp_unslash( $_GET['event_listing_category'] ) ) : '';
		echo "<select name='event_listing_category' id='dropdown_event_listing_category'>";
		echo '<option value="" ' . selected( $selected_category, '', false ) . '>' . esc_html__( 'Select category', 'wp-event-manager' ) . '</option>';
		echo wp_kses( $walker->walk( $terms, 0, $r ), $allowed_html );
		echo '</select>';

	}

	/**
	 * Output dropdowns for filters based on post meta.
	 *
	 * @since 1.31.0
	 */
	public function events_meta_filters() {
		global $typenow;

		// Only add the filters for event_listings.
		if ( 'event_listing' !== $typenow ) {
			return;
		}

		// Filter by Filled.
		$this->events_filter_dropdown(
			'event_listing_filled',
			[
				[
					'value' => '',
					'text'  => __( 'Select Filled', 'wp-event-manager' ),
				],
				[
					'value' => '1',
					'text'  => __( 'Filled', 'wp-event-manager' ),
				],
				[
					'value' => '0',
					'text'  => __( 'Not Filled', 'wp-event-manager' ),
				],
			]
		);

		// Filter by Featured.
		$this->events_filter_dropdown(
			'event_listing_featured',
			[
				[
					'value' => '',
					'text'  => __( 'Select Featured', 'wp-event-manager' ),
				],
				[
					'value' => '1',
					'text'  => __( 'Featured', 'wp-event-manager' ),
				],
				[
					'value' => '0',
					'text'  => __( 'Not Featured', 'wp-event-manager' ),
				],
			]
		);
	}

	/**
	 * Shows dropdown to filter by the given URL parameter. The dropdown will
	 * have three options: "Select $name", "$name", and "Not $name".
	 *
	 * The $options element should be an array of arrays, each with the
	 * attributes needed to create an <option> HTML element. The attributes are
	 * as follows:
	 *
	 * $options[i]['value']  The value for the <option> HTML element.
	 * $options[i]['text']   The text for the <option> HTML element.
	 *
	 * @since 1.31.0
	 *
	 * @param string $param        The URL parameter.
	 * @param array  $options      The options for the dropdown. See the description above.
	 */
	private function events_filter_dropdown( $param, $options ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No changes or data exposed based on input.
		$selected = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';

		echo '<select name="' . esc_attr( $param ) . '" id="dropdown_' . esc_attr( $param ) . '">';

		foreach ( $options as $option ) {
			echo '<option value="' . esc_attr( $option['value'] ) . '"'
				. ( $selected === $option['value'] ? ' selected' : '' )
				. '>' . esc_html( $option['text'] ) . '</option>';
		}
		echo '</select>';

	}

	/**
	 * Filters page title placeholder text to show custom label.
	 *
	 * @param string      $text
	 * @param WP_Post|int $post
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		if ( 'event_listing' === $post->post_type ) {
			return esc_html__( 'Position', 'wp-event-manager' );
		}
		return $text;
	}

	/**
	 * Filters the post updated message array to add custom post type's messages.
	 *
	 * @param array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID, $wp_post_types;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No changes based on input.
		$revision_title = isset( $_GET['revision'] ) ? wp_post_revision_title( (int) $_GET['revision'], false ) : false;

		$messages['event_listing'] = [
			0  => '',
			// translators: %1$s is the singular name of the event listing post type; %2$s is the URL to view the listing.
			1  => sprintf( __( '%1$s updated. <a href="%2$s">View</a>', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
			2  => __( 'Custom field updated.', 'wp-event-manager' ),
			3  => __( 'Custom field deleted.', 'wp-event-manager' ),
			// translators: %s is the singular name of the event listing post type.
			4  => sprintf( esc_html__( '%s updated.', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->singular_name ),
			// translators: %1$s is the singular name of the event listing post type; %2$s is the revision number.
			5  => $revision_title ? sprintf( __( '%1$s restored to revision from %2$s', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->singular_name, $revision_title ) : false,
			// translators: %1$s is the singular name of the event listing post type; %2$s is the URL to view the listing.
			6  => sprintf( __( '%1$s published. <a href="%2$s">View</a>', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
			// translators: %1$s is the singular name of the event listing post type; %2$s is the URL to view the listing.
			7  => sprintf( esc_html__( '%s saved.', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->singular_name ),
			// translators: %1$s is the singular name of the event listing post type; %2$s is the URL to preview the listing.
			8  => sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview</a>', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9  => sprintf(
				// translators: %1$s is the singular name of the post type; %2$s is the date the post will be published; %3$s is the URL to preview the listing.
				__( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview</a>', 'wp-event-manager' ),
				$wp_post_types['event_listing']->labels->singular_name,
				date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post_ID ) )
			),
			// translators: %1$s is the singular name of the event listing post type; %2$s is the URL to view the listing.
			10 => sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview</a>', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		];

		return $messages;
	}

	/**
	 * Adds columns to admin listing of event Listings.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = [];
		}

		unset( $columns['title'], $columns['date'], $columns['author'] );

		$columns['event_position']         = __( 'Position', 'wp-event-manager' );
		$columns['event_listing_type']     = __( 'Type', 'wp-event-manager' );
		$columns['event_location']         = __( 'Location', 'wp-event-manager' );
		$columns['event_status']           = '<span class="tips" data-tip="' . __( 'Status', 'wp-event-manager' ) . '">' . __( 'Status', 'wp-event-manager' ) . '</span>';
		$columns['event_posted']           = __( 'Posted', 'wp-event-manager' );
		$columns['event_expires']          = __( 'Expires', 'wp-event-manager' );
		$columns['event_listing_category'] = __( 'Categories', 'wp-event-manager' );
		$columns['featured_event']         = '<span class="tips" data-tip="' . __( 'Featured?', 'wp-event-manager' ) . '">' . __( 'Featured?', 'wp-event-manager' ) . '</span>';
		$columns['filled']               = '<span class="tips" data-tip="' . __( 'Filled?', 'wp-event-manager' ) . '">' . __( 'Filled?', 'wp-event-manager' ) . '</span>';
		$columns['event_actions']          = __( 'Actions', 'wp-event-manager' );

		if ( ! get_option( 'event_manager_enable_categories' ) ) {
			unset( $columns['event_listing_category'] );
		}

		if ( ! get_option( 'event_manager_enable_types' ) ) {
			unset( $columns['event_listing_type'] );
		}

		return $columns;
	}

	/**
	 * This is required to make column responsive since WP 4.3
	 *
	 * @access public
	 * @param string $column
	 * @param string $screen
	 * @return string
	 */
	public function primary_column( $column, $screen ) {
		if ( 'edit-event_listing' === $screen ) {
			$column = 'event_position';
		}
		return $column;
	}

	/**
	 * Removes all action links because WordPress add it to primary column.
	 * Note: Removing all actions also remove mobile "Show more details" toggle button.
	 * So the button need to be added manually in custom_columns callback for primary column.
	 *
	 * @access public
	 * @param array $actions
	 * @return array
	 */
	public function row_actions( $actions ) {
		if ( 'event_listing' === get_post_type() ) {
			return [];
		}
		return $actions;
	}

	/**
	 * Displays the content for each custom column on the admin list for event Listings.
	 *
	 * @param mixed $column
	 */
	public function custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case 'event_listing_type':
				$types = wpjm_get_the_event_types( $post );

				if ( $types && ! empty( $types ) ) {
					foreach ( $types as $type ) {
						echo '<span class="event-type ' . esc_attr( $type->slug ) . '">' . esc_html( $type->name ) . '</span>';
					}
				}
				break;
			case 'event_position':
				echo '<div class="event_position">';
				// translators: %d is the post ID for the event listing.
				echo '<a href="' . esc_url( admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) ) . '" class="tips event_title" data-tip="' . sprintf( esc_html__( 'ID: %d', 'wp-event-manager' ), intval( $post->ID ) ) . '">' . esc_html( wpjm_get_the_event_title() ) . '</a>';

				echo '<div class="company">';

				if ( get_the_company_website() ) {
					the_company_name( '<span class="tips" data-tip="' . esc_attr( get_the_company_tagline() ) . '"><a href="' . esc_url( get_the_company_website() ) . '">', '</a></span>' );
				} else {
					the_company_name( '<span class="tips" data-tip="' . esc_attr( get_the_company_tagline() ) . '">', '</span>' );
				}

				echo '</div>';

				the_company_logo();
				echo '</div>';
				echo '<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';
				break;
			case 'event_location':
				the_event_location( true, $post );
				break;
			case 'event_listing_category':
				$terms = get_the_term_list( $post->ID, $column, '', ', ', '' );
				if ( ! $terms ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					echo wp_kses_post( $terms );
				}
				break;
			case 'filled':
				if ( is_position_filled( $post ) ) {
					echo '&#10004;';
				} else {
					echo '&ndash;';
				}
				break;
			case 'featured_event':
				if ( is_position_featured( $post ) ) {
					echo '&#10004;';
				} else {
					echo '&ndash;';
				}
				break;
			case 'event_posted':
				echo '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $post->post_date ) ) ) . '</strong><span>';
				// translators: %s placeholder is the username of the user.
				echo ( empty( $post->post_author ) ? esc_html__( 'by a guest', 'wp-event-manager' ) : sprintf( esc_html__( 'by %s', 'wp-event-manager' ), '<a href="' . esc_url( add_query_arg( 'author', $post->post_author ) ) . '">' . esc_html( get_the_author() ) . '</a>' ) ) . '</span>';
				break;
			case 'event_expires':
				if ( $post->_event_expires ) {
					echo '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $post->_event_expires ) ) ) . '</strong>';
				} else {
					echo '&ndash;';
				}
				break;
			case 'event_status':
				echo '<span data-tip="' . esc_attr( get_the_event_status( $post ) ) . '" class="tips status-' . esc_attr( $post->post_status ) . '">' . esc_html( get_the_event_status( $post ) ) . '</span>';
				break;
			case 'event_actions':
				echo '<div class="actions">';
				$admin_actions = apply_filters( 'post_row_actions', [], $post );

				if ( in_array( $post->post_status, [ 'pending', 'pending_payment' ], true ) && current_user_can( 'publish_post', $post->ID ) ) {
					$admin_actions['approve'] = [
						'action' => 'approve',
						'name'   => __( 'Approve', 'wp-event-manager' ),
						'url'    => wp_nonce_url( add_query_arg( 'approve_event', $post->ID ), 'approve_event' ),
					];
				}
				if ( 'trash' !== $post->post_status ) {
					if ( current_user_can( 'read_post', $post->ID ) ) {
						$admin_actions['view'] = [
							'action' => 'view',
							'name'   => __( 'View', 'wp-event-manager' ),
							'url'    => get_permalink( $post->ID ),
						];
					}
					if ( current_user_can( 'edit_post', $post->ID ) ) {
						$admin_actions['edit'] = [
							'action' => 'edit',
							'name'   => __( 'Edit', 'wp-event-manager' ),
							'url'    => get_edit_post_link( $post->ID ),
						];
					}
					if ( current_user_can( 'delete_post', $post->ID ) ) {
						$admin_actions['delete'] = [
							'action' => 'delete',
							'name'   => __( 'Delete', 'wp-event-manager' ),
							'url'    => get_delete_post_link( $post->ID ),
						];
					}
				}

				$admin_actions = apply_filters( 'event_manager_admin_actions', $admin_actions, $post );

				foreach ( $admin_actions as $action ) {
					if ( is_array( $action ) ) {
						printf( '<a class="button button-icon tips icon-%1$s" href="%2$s" data-tip="%3$s">%4$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_html( $action['name'] ) );
					} else {
						echo wp_kses_post( str_replace( 'class="', 'class="button ', $action ) );
					}
				}

				echo '</div>';

				break;
		}
	}

	/**
	 * Filters the list table sortable columns for the admin list of event Listings.
	 *
	 * @param mixed $columns
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$custom = [
			'event_posted'   => 'date',
			'event_position' => 'title',
			'event_location' => 'event_location',
			'event_expires'  => 'event_expires',
		];
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Sorts the admin listing of event Listings by updating the main query in the request.
	 *
	 * @param mixed $vars Variables with sort arguments.
	 * @return array
	 */
	public function sort_columns( $vars ) {
		if ( isset( $vars['orderby'] ) ) {
			if ( 'event_expires' === $vars['orderby'] ) {
				$vars = array_merge(
					$vars,
					[
						'meta_key' => '_event_expires',
						'orderby'  => 'meta_value',
					]
				);
			} elseif ( 'event_location' === $vars['orderby'] ) {
				$vars = array_merge(
					$vars,
					[
						'meta_key' => '_event_location',
						'orderby'  => 'meta_value',
					]
				);
			}
		}
		return $vars;
	}

	/**
	 * Searches custom fields as well as content.
	 *
	 * @param WP_Query $wp
	 */
	public function search_meta( $wp ) {
		global $pagenow, $wpdb;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'event_listing' !== $wp->query_vars['post_type'] ) {
			return;
		}

		$post_ids = array_unique(
			array_merge(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WP_Query doesn't allow for meta query to be an optional match.
				$wpdb->get_col(
					$wpdb->prepare(
						"SELECT posts.ID
						FROM {$wpdb->posts} posts
						WHERE (
							posts.ID IN (
								SELECT post_id
								FROM {$wpdb->postmeta}
								WHERE meta_value LIKE %s
							)
							OR posts.post_title LIKE %s
							OR posts.post_content LIKE %s
						)
						AND posts.post_type = 'event_listing'",
						'%' . $wpdb->esc_like( $wp->query_vars['s'] ) . '%',
						'%' . $wpdb->esc_like( $wp->query_vars['s'] ) . '%',
						'%' . $wpdb->esc_like( $wp->query_vars['s'] ) . '%'
					)
				),
				[ 0 ]
			)
		);

		// Adjust the query vars.
		unset( $wp->query_vars['s'] );
		$wp->query_vars['event_listing_search'] = true;
		$wp->query_vars['post__in']           = $post_ids;
	}

	/**
	 * Filters by meta fields.
	 *
	 * @param WP_Query $wp
	 */
	public function filter_meta( $wp ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['post_type'] ) || 'event_listing' !== $wp->query_vars['post_type'] ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$input_event_listing_filled   = isset( $_GET['event_listing_filled'] ) && '' !== $_GET['event_listing_filled'] ? absint( $_GET['event_listing_filled'] ) : false;
		$input_event_listing_featured = isset( $_GET['event_listing_featured'] ) && '' !== $_GET['event_listing_featured'] ? absint( $_GET['event_listing_featured'] ) : false;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$meta_query = $wp->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = [];
		}

		// Filter on _filled meta.
		if ( false !== $input_event_listing_filled ) {
			$meta_query[] = [
				'key'   => '_filled',
				'value' => $input_event_listing_filled,
			];
		}

		// Filter on _featured meta.
		if ( false !== $input_event_listing_featured ) {
			$meta_query[] = [
				'key'   => '_featured',
				'value' => $input_event_listing_featured,
			];
		}

		// Set new meta query.
		if ( ! empty( $meta_query ) ) {
			$wp->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Changes the label when searching meta.
	 *
	 * @param string $query
	 * @return string
	 */
	public function search_meta_label( $query ) {
		global $pagenow, $typenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		if ( 'edit.php' !== $pagenow || 'event_listing' !== $typenow || ! get_query_var( 'event_listing_search' ) || ! isset( $_GET['s'] ) ) {
			return $query;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		return sanitize_text_field( wp_unslash( $_GET['s'] ) );
	}

	/**
	 * Adds post status to the "submitdiv" Meta Box and post type WP List Table screens. Based on https://gist.github.com/franz-josef-kaiser/2930190
	 */
	public function extend_submitdiv_post_status() {
		global $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction.
		if ( 'event_listing' !== $post_type ) {
			return;
		}

		// Get all non-builtin post status and add them as <option>.
		$options = '';
		$display = '';
		foreach ( get_event_listing_post_statuses() as $status => $name ) {
			$selected = selected( $post->post_status, $status, false );

			// If we one of our custom post status is selected, remember it.
			if ( $selected ) {
				$display = $name;
			}

			// Build the options.
			$options .= "<option{$selected} value='{$status}'>" . esc_html( $name ) . '</option>';
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( decodeURIComponent( '<?php echo rawurlencode( (string) wp_specialchars_decode( $display ) ); ?>' ) );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( decodeURIComponent( '<?php echo rawurlencode( (string) wp_specialchars_decode( $options ) ); ?>' ) );
			} );
		</script>
		<?php
	}

	/**
	 * Removes event_listing from the list of post types that support "View Mode" option
	 *
	 * @param array $post_types Array of post types that support view mode.
	 * @return array            Array of post types that support view mode, without event_listing post type.
	 */
	public function disable_view_mode( $post_types ) {
		unset( $post_types['event_listing'] );
		return $post_types;
	}
}
