<?php
/**
 * File containing the class WP_event_Manager_API.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles API requests for WP Event Manager.
 *
 * @package wp-event-manager
 * @since 1.0.0
 */
class WP_event_Manager_API {

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
		add_filter( 'query_vars', [ $this, 'add_query_vars' ], 0 );
		add_action( 'parse_request', [ $this, 'api_requests' ], 0 );
	}

	/**
	 * Adds query vars used in API calls.
	 *
	 * @param array $vars the query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'event-manager-api';
		return $vars;
	}

	/**
	 * Adds endpoint for API requests.
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'event-manager-api', EP_ALL );
	}

	/**
	 * API request - Trigger any API requests (handy for third party plugins/gateways).
	 */
	public function api_requests() {
		global $wp;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- If necessary/possible, nonce should be checked by API handler.
		if ( ! empty( $_GET['event-manager-api'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- If necessary/possible, nonce should be checked by API handler.
			$wp->query_vars['event-manager-api'] = sanitize_text_field( wp_unslash( $_GET['event-manager-api'] ) );
		}

		if ( ! empty( $wp->query_vars['event-manager-api'] ) ) {
			// Buffer, we won't want any output here.
			ob_start();

			// Get API trigger.
			$api = strtolower( esc_attr( $wp->query_vars['event-manager-api'] ) );

			// Load class if exists.
			if ( has_action( 'event_manager_api_' . $api ) && class_exists( $api ) ) {
				$api_class = new $api();
			}

			/**
			 * Performs an API action.
			 * The dynamic part of the action, $api, is the API action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'event_manager_api_' . $api );

			// Done, clear buffer and exit.
			ob_end_clean();
			wp_die();
		}
	}
}

WP_event_Manager_API::instance();
