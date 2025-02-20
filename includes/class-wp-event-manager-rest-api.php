<?php
/**
 * File containing the class WP_event_Manager_REST_API.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles functionality related to the REST API.
 *
 * @since 1.33.0
 */
class WP_event_Manager_REST_API {
	/**
	 * Sets up initial hooks.
	 *
	 * @static
	 */
	public static function init() {
		add_filter( 'rest_prepare_event_listing', [ __CLASS__, 'prepare_event_listing' ], 10, 2 );
	}

	/**
	 * Filters the event listing data for a REST API response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 * @return WP_REST_Response
	 */
	public static function prepare_event_listing( $response, $post ) {
		$current_user = wp_get_current_user();
		$fields       = WP_event_Manager_Post_Types::get_event_listing_fields();
		$data         = $response->get_data();

		foreach ( $data['meta'] as $meta_key => $meta_value ) {
			if ( isset( $fields[ $meta_key ] ) && is_callable( $fields[ $meta_key ]['auth_view_callback'] ) ) {
				$is_viewable = call_user_func( $fields[ $meta_key ]['auth_view_callback'], false, $meta_key, $post->ID, $current_user->ID );
				if ( ! $is_viewable ) {
					unset( $data['meta'][ $meta_key ] );
				}
			}
		}

		$response->set_data( $data );

		return $response;
	}
}
