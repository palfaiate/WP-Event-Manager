<?php
/**
 * File containing the class WP_event_Manager_Ajax.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles event Manager's Ajax endpoints.
 *
 * @since 1.0.0
 */
class WP_event_Manager_Ajax {

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
		add_action( 'init', [ __CLASS__, 'add_endpoint' ] );
		add_action( 'template_redirect', [ __CLASS__, 'do_jm_ajax' ], 0 );

		// JM Ajax endpoints.
		add_action( 'event_manager_ajax_get_listings', [ $this, 'get_listings' ] );
		add_action( 'event_manager_ajax_upload_file', [ $this, 'upload_file' ] );

		// BW compatible handlers.
		add_action( 'wp_ajax_nopriv_event_manager_get_listings', [ $this, 'get_listings' ] );
		add_action( 'wp_ajax_event_manager_get_listings', [ $this, 'get_listings' ] );
		add_action( 'wp_ajax_nopriv_event_manager_upload_file', [ $this, 'upload_file' ] );
		add_action( 'wp_ajax_event_manager_upload_file', [ $this, 'upload_file' ] );
		add_action( 'wp_ajax_event_manager_search_users', [ $this, 'ajax_search_users' ] );
	}

	/**
	 * Adds endpoint for frontend Ajax requests.
	 */
	public static function add_endpoint() {
		add_rewrite_tag( '%jm-ajax%', '([^/]*)' );
		add_rewrite_rule( 'jm-ajax/([^/]*)/?', 'index.php?jm-ajax=$matches[1]', 'top' );
		add_rewrite_rule( 'index.php/jm-ajax/([^/]*)/?', 'index.php?jm-ajax=$matches[1]', 'top' );
	}

	/**
	 * Gets event Manager's Ajax Endpoint.
	 *
	 * @param  string $request      Optional.
	 * @return string
	 */
	public static function get_endpoint( $request = '%%endpoint%%' ) {
		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$endpoint = trailingslashit( home_url( '/index.php/jm-ajax/' . $request . '/', 'relative' ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$endpoint = trailingslashit( home_url( '/jm-ajax/' . $request . '/', 'relative' ) );
		} else {
			$endpoint = add_query_arg( 'jm-ajax', $request, trailingslashit( home_url( '', 'relative' ) ) );
		}
		return esc_url_raw( $endpoint );
	}

	/**
	 * Performs event Manager's Ajax actions.
	 */
	public static function do_jm_ajax() {
		global $wp_query;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		if ( ! empty( $_GET['jm-ajax'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
			$wp_query->set( 'jm-ajax', sanitize_text_field( wp_unslash( $_GET['jm-ajax'] ) ) );
		}

		$action = $wp_query->get( 'jm-ajax' );
		if ( $action ) {
			if ( ! defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}

			// Not home - this is an ajax endpoint.
			$wp_query->is_home = false;

			/**
			 * Performs an Ajax action.
			 * The dynamic part of the action, $action, is the predefined Ajax action to be performed.
			 *
			 * @since 1.23.0
			 */
			do_action( 'event_manager_ajax_' . sanitize_text_field( $action ) );
			wp_die();
		}
	}

	/**
	 * Returns event Listings for Ajax endpoint.
	 */
	public function get_listings() {
		// Get input variables.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Fetching data only; often for logged out visitors.
		$search_location    = isset( $_REQUEST['search_location'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search_location'] ) ) : '';
		$search_keywords    = isset( $_REQUEST['search_keywords'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search_keywords'] ) ) : '';
		$search_categories  = isset( $_REQUEST['search_categories'] ) ? wp_unslash( $_REQUEST['search_categories'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is sanitized below.
		$filter_event_types   = isset( $_REQUEST['filter_event_type'] ) ? array_filter( array_map( 'sanitize_title', wp_unslash( (array) $_REQUEST['filter_event_type'] ) ) ) : null;
		$filter_post_status = isset( $_REQUEST['filter_post_status'] ) ? array_filter( array_map( 'sanitize_title', wp_unslash( (array) $_REQUEST['filter_post_status'] ) ) ) : null;
		$order              = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';
		$orderby            = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'featured';
		$page               = isset( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1;
		$per_page           = isset( $_REQUEST['per_page'] ) ? absint( $_REQUEST['per_page'] ) : absint( get_option( 'event_manager_per_page' ) );
		$filled             = isset( $_REQUEST['filled'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filled'] ) ) : null;
		$featured           = isset( $_REQUEST['featured'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['featured'] ) ) : null;
		$show_pagination    = isset( $_REQUEST['show_pagination'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['show_pagination'] ) ) : null;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( is_array( $search_categories ) ) {
			$search_categories = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_categories ) ) );
		} else {
			$search_categories = array_filter( [ sanitize_text_field( wp_unslash( $search_categories ) ) ] );
		}

		$types              = get_event_listing_types();
		$event_types_filtered = ! is_null( $filter_event_types ) && count( $types ) !== count( $filter_event_types );

		$args = [
			'search_location'   => $search_location,
			'search_keywords'   => $search_keywords,
			'search_categories' => $search_categories,
			'event_types'         => is_null( $filter_event_types ) || count( $types ) === count( $filter_event_types ) ? '' : $filter_event_types + [ 0 ],
			'post_status'       => $filter_post_status,
			'orderby'           => $orderby,
			'order'             => $order,
			'offset'            => ( $page - 1 ) * $per_page,
			'posts_per_page'    => max( 1, $per_page ),
		];

		if ( 'true' === $filled || 'false' === $filled ) {
			$args['filled'] = 'true' === $filled;
		}

		if ( 'true' === $featured || 'false' === $featured ) {
			$args['featured'] = 'true' === $featured;
			$args['orderby']  = 'featured' === $orderby ? 'date' : $orderby;
		}

		/**
		 * Get the arguments to use when building the event Listing WP Query.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Arguments used for generating event Listing query (see `get_event_listings()`).
		 */
		$events = get_event_listings( apply_filters( 'event_manager_get_listings_args', $args ) );

		$result = [
			'found_events'    => $events->have_posts(),
			'showing'       => '',
			'max_num_pages' => $events->max_num_pages,
		];

		if ( $events->post_count && ( $search_location || $search_keywords || $search_categories || $event_types_filtered ) ) {
			// translators: Placeholder %d is the number of found search results.
			$message               = sprintf( _n( 'Search completed. Found %d matching record.', 'Search completed. Found %d matching records.', $events->found_posts, 'wp-event-manager' ), $events->found_posts );
			$result['showing_all'] = true;
		} else {
			$message = '';
		}

		$search_values = [
			'location'   => $search_location,
			'keywords'   => $search_keywords,
			'categories' => $search_categories,
		];

		/**
		 * Filter the message that describes the results of the search query.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message Default message that is generated when posts are found.
		 * @param array $search_values {
		 *  Helpful values often used in the generation of this message.
		 *
		 *  @type string $location   Query used to filter by event listing location.
		 *  @type string $keywords   Query used to filter by general keywords.
		 *  @type array  $categories List of the categories to filter by.
		 * }
		 */
		$result['showing'] = apply_filters( 'event_manager_get_listings_custom_filter_text', $message, $search_values );

		// Generate RSS link.
		$result['showing_links'] = event_manager_get_filtered_links(
			[
				'filter_event_types'  => $filter_event_types,
				'search_location'   => $search_location,
				'search_categories' => $search_categories,
				'search_keywords'   => $search_keywords,
			]
		);

		/**
		 * Send back a response to the AJAX request without creating HTML.
		 *
		 * @since 1.26.0
		 *
		 * @param array $result
		 * @param WP_Query $events
		 * @return bool True by default. Change to false to halt further response.
		 */
		if ( true !== apply_filters( 'event_manager_ajax_get_events_html_results', true, $result, $events ) ) {
			/**
			 * Filters the results of the event listing Ajax query to be sent back to the client.
			 *
			 * @since 1.0.0
			 *
			 * @param array $result {
			 *  Package of the query results along with meta information.
			 *
			 *  @type bool   $found_events    Whether or not events were found in the query.
			 *  @type string $showing       Description of the search query and results.
			 *  @type int    $max_num_pages Number of pages in the search result.
			 *  @type string $html          HTML representation of the search results (only if filter
			 *                              `event_manager_ajax_get_events_html_results` returns true).
			 *  @type array $pagination     Pagination links to use for stepping through filter results.
			 * }
			 */
			wp_send_json( apply_filters( 'event_manager_get_listings_result', $result, $events ) );

			return;
		}

		ob_start();

		if ( $result['found_events'] ) {
			while ( $events->have_posts() ) {
				$events->the_post();
				get_event_manager_template_part( 'content', 'event_listing' );
			}
		} else {
			get_event_manager_template_part( 'content', 'no-events-found' );
		}

		$result['html'] = ob_get_clean();

		// Generate pagination.
		if ( 'true' === $show_pagination ) {
			$result['pagination'] = get_event_listing_pagination( $events->max_num_pages, $page );
		}

		/** This filter is documented in includes/class-wp-event-manager-ajax.php (above) */
		wp_send_json( apply_filters( 'event_manager_get_listings_result', $result, $events ) );
	}

	/**
	 * Uploads file from an Ajax request.
	 *
	 * No nonce field since the form may be statically cached.
	 */
	public function upload_file() {
		if ( ! event_manager_user_can_upload_file_via_ajax() ) {
			wp_send_json_error( __( 'You must be logged in to upload files using this method.', 'wp-event-manager' ) );
			return;
		}
		$data = [
			'files' => [],
		];

		if ( ! empty( $_FILES ) ) {
			foreach ( $_FILES as $file_key => $file ) {
				$files_to_upload = event_manager_prepare_uploaded_files( $file );
				foreach ( $files_to_upload as $file_to_upload ) {
					$uploaded_file = event_manager_upload_file(
						$file_to_upload,
						[
							'file_key' => $file_key,
						]
					);

					if ( is_wp_error( $uploaded_file ) ) {
						$data['files'][] = [
							'error' => $uploaded_file->get_error_message(),
						];
					} else {
						$data['files'][] = $uploaded_file;
					}
				}
			}
		}

		wp_send_json( $data );
	}

	/**
	 * Checks if user can search for other users in ajax call.
	 *
	 * @return bool
	 */
	private static function user_can_search_users() {
		$user_can_search_users = false;

		/**
		 * Filter the capabilities that are allowed to search for users in ajax call.
		 *
		 * @since 1.32.0
		 *
		 * @param array $user_caps Array of capabilities/roles that are allowed to search for users.
		 */
		$allowed_capabilities = apply_filters( 'event_manager_caps_can_search_users', [ 'edit_event_listings' ] );
		foreach ( $allowed_capabilities as $cap ) {
			if ( current_user_can( $cap ) ) {
				$user_can_search_users = true;
				break;
			}
		}

		/**
		 * Filters whether the current user can search for users in ajax call.
		 *
		 * @since 1.32.0
		 *
		 * @param bool $user_can_search_users True if they are allowed, false if not.
		 */
		return apply_filters( 'event_manager_user_can_search_users', $user_can_search_users );
	}

	/**
	 * Search for users and return json.
	 */
	public static function ajax_search_users() {
		check_ajax_referer( 'search-users', 'security' );

		if ( ! self::user_can_search_users() ) {
			wp_die( -1 );
		}

		$term     = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		$page     = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
		$per_page = 20;

		$exclude = [];
		if ( ! empty( $_GET['exclude'] ) ) {
			$exclude = array_map( 'intval', $_GET['exclude'] );
		}

		if ( empty( $term ) ) {
			wp_die();
		}

		$more_exist = false;
		$users      = [];

		// Search by ID.
		if ( is_numeric( $term ) && ! in_array( intval( $term ), $exclude, true ) ) {
			$user = get_user_by( 'ID', intval( $term ) );
			if ( $user instanceof WP_User ) {
				$users[ $user->ID ] = $user;
			}
		}

		if ( empty( $users ) ) {
			$search_args = [
				'exclude'        => $exclude,
				'search'         => '*' . esc_attr( $term ) . '*',
				'search_columns' => [ 'user_login', 'user_email', 'user_nicename', 'display_name' ],
				'number'         => $per_page,
				'paged'          => $page,
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			];

			/**
			 * Modify the arguments used for `WP_User_Query` constructor.
			 *
			 * @since 1.32.0
			 *
			 * @see https://codex.wordpress.org/Class_Reference/WP_User_Query
			 *
			 * @param array  $search_args Argument array used in `WP_User_Query` constructor.
			 * @param string $term        Search term.
			 * @param int[]  $exclude     Array of IDs to exclude.
			 * @param int    $page        Current page.
			 */
			$search_args = apply_filters( 'event_manager_search_users_args', $search_args, $term, $exclude, $page );

			$user_query  = new WP_User_Query( $search_args );
			$users       = $user_query->get_results();
			$total_pages = ceil( $user_query->get_total() / $per_page );
			$more_exist  = $total_pages > $page;
		}

		$found_users = [];

		foreach ( $users as $user ) {
			$found_users[ $user->ID ] = sprintf(
				// translators: Used in user select. %1$s is the user's display name; #%2$s is the user ID; %3$s is the user email.
				esc_html__( '%1$s (#%2$s – %3$s)', 'wp-event-manager' ),
				htmlentities( $user->display_name ),
				absint( $user->ID ),
				$user->user_email
			);
		}

		$response = [
			'results' => $found_users,
			'more'    => $more_exist,
		];

		/**
		 * Modify the search results response for users in ajax call.
		 *
		 * @since 1.32.0
		 *
		 * @param array  $response    {
		 *      @type array   $results Array of all found users; id => string descriptor
		 *      @type boolean $more    True if there is an additional page.
		 * }
		 * @param string $term        Search term.
		 * @param int[]  $exclude     Array of IDs to exclude.
		 * @param int    $page        Current page.
		 */
		$response = apply_filters( 'event_manager_search_users_response', $response, $term, $exclude, $page );

		wp_send_json( $response );
	}
}

WP_event_Manager_Ajax::instance();
