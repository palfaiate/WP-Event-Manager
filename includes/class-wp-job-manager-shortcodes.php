<?php
/**
 * File containing the class WP_event_Manager_Shortcodes.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the shortcodes for WP Event Manager.
 *
 * @since 1.0.0
 */
class WP_event_Manager_Shortcodes {

	/**
	 * Dashboard message.
	 *
	 * @access private
	 * @var string
	 */
	private $event_dashboard_message = '';

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
		add_action( 'wp', [ $this, 'shortcode_action_handler' ] );
		add_action( 'event_manager_event_dashboard_content_edit', [ $this, 'edit_event' ] );
		add_action( 'event_manager_event_filters_end', [ $this, 'event_filter_event_types' ], 20 );
		add_action( 'event_manager_event_filters_end', [ $this, 'event_filter_results' ], 30 );
		add_action( 'event_manager_output_events_no_results', [ $this, 'output_no_results' ] );
		add_shortcode( 'submit_event_form', [ $this, 'submit_event_form' ] );
		add_shortcode( 'event_dashboard', [ $this, 'event_dashboard' ] );
		add_shortcode( 'events', [ $this, 'output_events' ] );
		add_shortcode( 'event', [ $this, 'output_event' ] );
		add_shortcode( 'event_summary', [ $this, 'output_event_summary' ] );
		add_shortcode( 'event_apply', [ $this, 'output_event_apply' ] );
	}

	/**
	 * Handles actions which need to be run before the shortcode e.g. post actions.
	 */
	public function shortcode_action_handler() {
		global $post;

		if ( is_page() && has_shortcode( $post->post_content, 'event_dashboard' ) ) {
			$this->event_dashboard_handler();
		}
	}

	/**
	 * Shows the event submission form.
	 *
	 * @param array $atts
	 * @return string|null
	 */
	public function submit_event_form( $atts = [] ) {
		return $GLOBALS['event_manager']->forms->get_form( 'submit-event', $atts );
	}

	/**
	 * Handles actions on event dashboard.
	 *
	 * @throws Exception On action handling error.
	 */
	public function event_dashboard_handler() {
		if (
			! empty( $_REQUEST['action'] )
			&& ! empty( $_REQUEST['_wpnonce'] )
			&& wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'event_manager_my_event_actions' ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce should not be modified.
		) {

			$action = sanitize_title( wp_unslash( $_REQUEST['action'] ) );
			$event_id = isset( $_REQUEST['event_id'] ) ? absint( $_REQUEST['event_id'] ) : 0;

			try {
				// Get event.
				$event = get_post( $event_id );

				// Check ownership.
				if ( ! event_manager_user_can_edit_event( $event_id ) ) {
					throw new Exception( __( 'Invalid ID', 'wp-event-manager' ) );
				}

				switch ( $action ) {
					case 'mark_filled':
						// Check status.
						if ( 1 === intval( $event->_filled ) ) {
							throw new Exception( __( 'This position has already been filled', 'wp-event-manager' ) );
						}

						// Update.
						update_post_meta( $event_id, '_filled', 1 );

						// Message.
						// translators: Placeholder %s is the event listing title.
						$this->event_dashboard_message = '<div class="event-manager-message">' . esc_html( sprintf( __( '%s has been filled', 'wp-event-manager' ), wpjm_get_the_event_title( $event ) ) ) . '</div>';
						break;
					case 'mark_not_filled':
						// Check status.
						if ( 1 !== intval( $event->_filled ) ) {
							throw new Exception( __( 'This position is not filled', 'wp-event-manager' ) );
						}

						// Update.
						update_post_meta( $event_id, '_filled', 0 );

						// Message.
						// translators: Placeholder %s is the event listing title.
						$this->event_dashboard_message = '<div class="event-manager-message">' . esc_html( sprintf( __( '%s has been marked as not filled', 'wp-event-manager' ), wpjm_get_the_event_title( $event ) ) ) . '</div>';
						break;
					case 'delete':
						// Trash it.
						wp_trash_post( $event_id );

						// Message.
						// translators: Placeholder %s is the event listing title.
						$this->event_dashboard_message = '<div class="event-manager-message">' . esc_html( sprintf( __( '%s has been deleted', 'wp-event-manager' ), wpjm_get_the_event_title( $event ) ) ) . '</div>';

						break;
					case 'duplicate':
						if ( ! event_manager_get_permalink( 'submit_event_form' ) ) {
							throw new Exception( __( 'Missing submission page.', 'wp-event-manager' ) );
						}

						$new_event_id = event_manager_duplicate_listing( $event_id );

						if ( $new_event_id ) {
							wp_safe_redirect( add_query_arg( [ 'event_id' => absint( $new_event_id ) ], event_manager_get_permalink( 'submit_event_form' ) ) );
							exit;
						}

						break;
					case 'relist':
					case 'continue':
						if ( ! event_manager_get_permalink( 'submit_event_form' ) ) {
							throw new Exception( __( 'Missing submission page.', 'wp-event-manager' ) );
						}

						// redirect to post page.
						wp_safe_redirect( add_query_arg( [ 'event_id' => absint( $event_id ) ], event_manager_get_permalink( 'submit_event_form' ) ) );
						exit;
					default:
						do_action( 'event_manager_event_dashboard_do_action_' . $action, $event_id );
						break;
				}

				do_action( 'event_manager_my_event_do_action', $action, $event_id );

				/**
				 * Set a success message for a custom dashboard action handler.
				 *
				 * When left empty, no success message will be shown.
				 *
				 * @since 1.31.1
				 *
				 * @param string  $message  Text for the success message. Default: empty string.
				 * @param string  $action   The name of the custom action.
				 * @param int     $event_id   The ID for the event that's been altered.
				 */
				$success_message = apply_filters( 'event_manager_event_dashboard_success_message', '', $action, $event_id );
				if ( $success_message ) {
					$this->event_dashboard_message = '<div class="event-manager-message">' . $success_message . '</div>';
				}
			} catch ( Exception $e ) {
				$this->event_dashboard_message = '<div class="event-manager-error">' . wp_kses_post( $e->getMessage() ) . '</div>';
			}
		}
	}

	/**
	 * Handles shortcode which lists the logged in user's events.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function event_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			ob_start();
			get_event_manager_template( 'event-dashboard-login.php' );
			return ob_get_clean();
		}

		$new_atts       = shortcode_atts(
			[
				'posts_per_page' => '25',
			],
			$atts
		);
		$posts_per_page = $new_atts['posts_per_page'];

		wp_enqueue_script( 'wp-event-manager-event-dashboard' );

		ob_start();

		// If doing an action, show conditional content if needed....
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$action = isset( $_REQUEST['action'] ) ? sanitize_title( wp_unslash( $_REQUEST['action'] ) ) : false;
		if ( ! empty( $action ) ) {
			// Show alternative content if a plugin wants to.
			if ( has_action( 'event_manager_event_dashboard_content_' . $action ) ) {
				do_action( 'event_manager_event_dashboard_content_' . $action, $atts );

				return ob_get_clean();
			}
		}

		// ....If not show the event dashboard.
		$args = apply_filters(
			'event_manager_get_dashboard_events_args',
			[
				'post_type'           => 'event_listing',
				'post_status'         => [ 'publish', 'expired', 'pending', 'draft', 'preview' ],
				'ignore_sticky_posts' => 1,
				'posts_per_page'      => $posts_per_page,
				'offset'              => ( max( 1, get_query_var( 'paged' ) ) - 1 ) * $posts_per_page,
				'orderby'             => 'date',
				'order'               => 'desc',
				'author'              => get_current_user_id(),
			]
		);

		$events = new WP_Query();

		echo wp_kses_post( $this->event_dashboard_message );

		$event_dashboard_columns = apply_filters(
			'event_manager_event_dashboard_columns',
			[
				'event_title' => __( 'Title', 'wp-event-manager' ),
				'filled'    => __( 'Filled?', 'wp-event-manager' ),
				'date'      => __( 'Date Posted', 'wp-event-manager' ),
				'expires'   => __( 'Listing Expires', 'wp-event-manager' ),
			]
		);

		get_event_manager_template(
			'event-dashboard.php',
			[
				'events'                  => $events->query( $args ),
				'max_num_pages'         => $events->max_num_pages,
				'event_dashboard_columns' => $event_dashboard_columns,
			]
		);

		return ob_get_clean();
	}

	/**
	 * Displays edit event form.
	 */
	public function edit_event() {
		global $event_manager;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output should be appropriately escaped in the form generator.
		echo $event_manager->forms->get_form( 'edit-event' );
	}

	/**
	 * Lists all event listings.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function output_events( $atts ) {
		ob_start();

		$atts = shortcode_atts(
			apply_filters(
				'event_manager_output_events_defaults',
				[
					'per_page'                  => get_option( 'event_manager_per_page' ),
					'orderby'                   => 'featured',
					'order'                     => 'DESC',

					// Filters + cats.
					'show_filters'              => true,
					'show_categories'           => true,
					'show_category_multiselect' => get_option( 'event_manager_enable_default_category_multiselect', false ),
					'show_pagination'           => false,
					'show_more'                 => true,

					// Limit what events are shown based on category, post status, and type.
					'categories'                => '',
					'event_types'                 => '',
					'post_status'               => '',
					'featured'                  => null, // True to show only featured, false to hide featured, leave null to show both.
					'filled'                    => null, // True to show only filled, false to hide filled, leave null to show both/use the settings.

					// Default values for filters.
					'location'                  => '',
					'keywords'                  => '',
					'selected_category'         => '',
					'selected_event_types'        => implode( ',', array_values( get_event_listing_types( 'id=>slug' ) ) ),
				]
			),
			$atts
		);

		if ( ! get_option( 'event_manager_enable_categories' ) ) {
			$atts['show_categories'] = false;
		}

		// String and bool handling.
		$atts['show_filters']              = $this->string_to_bool( $atts['show_filters'] );
		$atts['show_categories']           = $this->string_to_bool( $atts['show_categories'] );
		$atts['show_category_multiselect'] = $this->string_to_bool( $atts['show_category_multiselect'] );
		$atts['show_more']                 = $this->string_to_bool( $atts['show_more'] );
		$atts['show_pagination']           = $this->string_to_bool( $atts['show_pagination'] );

		if ( ! is_null( $atts['featured'] ) ) {
			$atts['featured'] = ( is_bool( $atts['featured'] ) && $atts['featured'] ) || in_array( $atts['featured'], [ 1, '1', 'true', 'yes' ], true );
		}

		if ( ! is_null( $atts['filled'] ) ) {
			$atts['filled'] = ( is_bool( $atts['filled'] ) && $atts['filled'] ) || in_array( $atts['filled'], [ 1, '1', 'true', 'yes' ], true );
		}

		// Get keywords, location, category and type from querystring if set.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		if ( ! empty( $_GET['search_keywords'] ) ) {
			$atts['keywords'] = sanitize_text_field( wp_unslash( $_GET['search_keywords'] ) );
		}
		if ( ! empty( $_GET['search_location'] ) ) {
			$atts['location'] = sanitize_text_field( wp_unslash( $_GET['search_location'] ) );
		}
		if ( ! empty( $_GET['search_category'] ) ) {
			$atts['selected_category'] = sanitize_text_field( wp_unslash( $_GET['search_category'] ) );
		}
		if ( ! empty( $_GET['search_event_type'] ) ) {
			$atts['selected_event_types'] = sanitize_text_field( wp_unslash( $_GET['search_event_type'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Array handling.
		$atts['categories']         = is_array( $atts['categories'] ) ? $atts['categories'] : array_filter( array_map( 'trim', explode( ',', $atts['categories'] ) ) );
		$atts['selected_category']  = is_array( $atts['selected_category'] ) ? $atts['selected_category'] : array_filter( array_map( 'trim', explode( ',', $atts['selected_category'] ) ) );
		$atts['event_types']          = is_array( $atts['event_types'] ) ? $atts['event_types'] : array_filter( array_map( 'trim', explode( ',', $atts['event_types'] ) ) );
		$atts['post_status']        = is_array( $atts['post_status'] ) ? $atts['post_status'] : array_filter( array_map( 'trim', explode( ',', $atts['post_status'] ) ) );
		$atts['selected_event_types'] = is_array( $atts['selected_event_types'] ) ? $atts['selected_event_types'] : array_filter( array_map( 'trim', explode( ',', $atts['selected_event_types'] ) ) );

		// Normalize field for categories.
		if ( ! empty( $atts['selected_category'] ) ) {
			foreach ( $atts['selected_category'] as $cat_index => $category ) {
				if ( ! is_numeric( $category ) ) {
					$term = get_term_by( 'slug', $category, 'event_listing_category' );

					if ( $term ) {
						$atts['selected_category'][ $cat_index ] = $term->term_id;
					}
				}
			}
		}

		$data_attributes = [
			'location'        => $atts['location'],
			'keywords'        => $atts['keywords'],
			'show_filters'    => $atts['show_filters'] ? 'true' : 'false',
			'show_pagination' => $atts['show_pagination'] ? 'true' : 'false',
			'per_page'        => $atts['per_page'],
			'orderby'         => $atts['orderby'],
			'order'           => $atts['order'],
			'categories'      => implode( ',', $atts['categories'] ),
		];

		if ( $atts['show_filters'] ) {
			get_event_manager_template(
				'event-filters.php',
				[
					'per_page'                  => $atts['per_page'],
					'orderby'                   => $atts['orderby'],
					'order'                     => $atts['order'],
					'show_categories'           => $atts['show_categories'],
					'categories'                => $atts['categories'],
					'selected_category'         => $atts['selected_category'],
					'event_types'                 => $atts['event_types'],
					'atts'                      => $atts,
					'location'                  => $atts['location'],
					'keywords'                  => $atts['keywords'],
					'selected_event_types'        => $atts['selected_event_types'],
					'show_category_multiselect' => $atts['show_category_multiselect'],
				]
			);

			get_event_manager_template( 'event-listings-start.php' );
			get_event_manager_template( 'event-listings-end.php' );

			if ( ! $atts['show_pagination'] && $atts['show_more'] ) {
				echo '<a class="load_more_events" href="#" style="display:none;"><strong>' . esc_html__( 'Load more listings', 'wp-event-manager' ) . '</strong></a>';
			}
		} else {
			$events = get_event_listings(
				apply_filters(
					'event_manager_output_events_args',
					[
						'search_location'   => $atts['location'],
						'search_keywords'   => $atts['keywords'],
						'post_status'       => $atts['post_status'],
						'search_categories' => $atts['categories'],
						'event_types'         => $atts['event_types'],
						'orderby'           => $atts['orderby'],
						'order'             => $atts['order'],
						'posts_per_page'    => $atts['per_page'],
						'featured'          => $atts['featured'],
						'filled'            => $atts['filled'],
					]
				)
			);

			if ( ! empty( $atts['event_types'] ) ) {
				$data_attributes['event_types'] = implode( ',', $atts['event_types'] );
			}

			if ( $events->have_posts() ) {
				get_event_manager_template( 'event-listings-start.php' );
				while ( $events->have_posts() ) {
					$events->the_post();
					get_event_manager_template_part( 'content', 'event_listing' );
				}
				get_event_manager_template( 'event-listings-end.php' );
				if ( $events->found_posts > $atts['per_page'] && $atts['show_more'] ) {
					wp_enqueue_script( 'wp-event-manager-ajax-filters' );
					if ( $atts['show_pagination'] ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output.
						echo get_event_listing_pagination( $events->max_num_pages );
					} else {
						echo '<a class="load_more_events" href="#"><strong>' . esc_html__( 'Load more listings', 'wp-event-manager' ) . '</strong></a>';
					}
				}
			} else {
				do_action( 'event_manager_output_events_no_results' );
			}
			wp_reset_postdata();
		}

		$data_attributes_string = '';
		if ( ! is_null( $atts['featured'] ) ) {
			$data_attributes['featured'] = $atts['featured'] ? 'true' : 'false';
		}
		if ( ! is_null( $atts['filled'] ) ) {
			$data_attributes['filled'] = $atts['filled'] ? 'true' : 'false';
		}
		if ( ! empty( $atts['post_status'] ) ) {
			$data_attributes['post_status'] = implode( ',', $atts['post_status'] );
		}

		$data_attributes['post_id'] = isset( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : 0;

		/**
		 * Pass additional data to the event listings <div> wrapper.
		 *
		 * @since 1.34.0
		 *
		 * @param array $data_attributes {
		 *     Key => Value array of data attributes to pass.
		 *
		 *     @type string $$key Value to pass as a data attribute.
		 * }
		 * @param array $atts            Attributes for the shortcode.
		 */
		$data_attributes = apply_filters( 'event_manager_events_shortcode_data_attributes', $data_attributes, $atts );

		foreach ( $data_attributes as $key => $value ) {
			$data_attributes_string .= 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}

		$event_listings_output = apply_filters( 'event_manager_event_listings_output', ob_get_clean() );

		return '<div class="event_listings" ' . $data_attributes_string . '>' . $event_listings_output . '</div>';
	}

	/**
	 * Displays some content when no results were found.
	 */
	public function output_no_results() {
		get_event_manager_template( 'content-no-events-found.php' );
	}

	/**
	 * Gets string as a bool.
	 *
	 * @param  string $value
	 * @return bool
	 */
	public function string_to_bool( $value ) {
		return ( is_bool( $value ) && $value ) || in_array( $value, [ 1, '1', 'true', 'yes' ], true );
	}

	/**
	 * Shows event types.
	 *
	 * @param  array $atts
	 */
	public function event_filter_event_types( $atts ) {
		$event_types          = is_array( $atts['event_types'] ) ? $atts['event_types'] : array_filter( array_map( 'trim', explode( ',', $atts['event_types'] ) ) );
		$selected_event_types = is_array( $atts['selected_event_types'] ) ? $atts['selected_event_types'] : array_filter( array_map( 'trim', explode( ',', $atts['selected_event_types'] ) ) );

		get_event_manager_template(
			'event-filter-event-types.php',
			[
				'event_types'          => $event_types,
				'atts'               => $atts,
				'selected_event_types' => $selected_event_types,
			]
		);
	}

	/**
	 * Shows results div.
	 */
	public function event_filter_results() {
		echo '<div class="showing_events"></div>';
	}

	/**
	 * Shows a single event.
	 *
	 * @param array $atts
	 * @return string|null
	 */
	public function output_event( $atts ) {
		$atts = shortcode_atts(
			[
				'id' => '',
			],
			$atts
		);

		if ( ! $atts['id'] ) {
			return null;
		}

		ob_start();

		$args = [
			'post_type'   => 'event_listing',
			'post_status' => 'publish',
			'p'           => $atts['id'],
		];

		$events = new WP_Query( $args );

		if ( $events->have_posts() ) {
			while ( $events->have_posts() ) {
				$events->the_post();
				echo '<h1>' . esc_html( wpjm_get_the_event_title() ) . '</h1>';
				get_event_manager_template_part( 'content-single', 'event_listing' );
			}
		}

		wp_reset_postdata();

		return '<div class="event_shortcode single_event_listing">' . ob_get_clean() . '</div>';
	}

	/**
	 * Handles the event Summary shortcode.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function output_event_summary( $atts ) {
		$atts = shortcode_atts(
			[
				'id'       => '',
				'width'    => '250px',
				'align'    => 'left',
				'featured' => null, // True to show only featured, false to hide featured, leave null to show both (when leaving out id).
				'limit'    => 1,
			],
			$atts
		);

		ob_start();

		$args = [
			'post_type'   => 'event_listing',
			'post_status' => 'publish',
		];

		if ( ! $atts['id'] ) {
			$args['posts_per_page'] = $atts['limit'];
			$args['orderby']        = 'rand';
			if ( ! is_null( $atts['featured'] ) ) {
				$args['meta_query'] = [
					[
						'key'     => '_featured',
						'value'   => '1',
						'compare' => $atts['featured'] ? '=' : '!=',
					],
				];
			}
		} else {
			$args['p'] = absint( $atts['id'] );
		}

		$events = new WP_Query( $args );

		if ( $events->have_posts() ) {
			while ( $events->have_posts() ) {
				$events->the_post();
				$width = $atts['width'] ? $atts['width'] : 'auto';
				echo '<div class="event_summary_shortcode align' . esc_attr( $atts['align'] ) . '" style="width: ' . esc_attr( $width ) . '">';
				get_event_manager_template_part( 'content-summary', 'event_listing' );
				echo '</div>';
			}
		}

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Shows the application area.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function output_event_apply( $atts ) {
		$new_atts = shortcode_atts(
			[
				'id' => '',
			],
			$atts
		);
		$id       = $new_atts['id'];

		ob_start();

		$args = [
			'post_type'   => 'event_listing',
			'post_status' => 'publish',
		];

		if ( ! $id ) {
			return '';
		} else {
			$args['p'] = absint( $id );
		}

		$events = new WP_Query( $args );

		if ( $events->have_posts() ) {
			while ( $events->have_posts() ) {
				$events->the_post();
				$apply = get_the_event_application_method();
				do_action( 'event_manager_before_event_apply_' . absint( $id ) );
				if ( apply_filters( 'event_manager_show_event_apply_' . absint( $id ), true ) ) {
					echo '<div class="event-manager-application-wrapper">';
					do_action( 'event_manager_application_details_' . $apply->type, $apply );
					echo '</div>';
				}
				do_action( 'event_manager_after_event_apply_' . absint( $id ) );
			}
			wp_reset_postdata();
		}

		return ob_get_clean();
	}
}

WP_event_Manager_Shortcodes::instance();
