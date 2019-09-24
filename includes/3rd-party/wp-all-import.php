<?php
/**
 * Adds additional compatibility with WP All Import.
 *
 * @package wp-event-manager
 */

add_action( 'pmxi_saved_post', 'wpjm_pmxi_saved_post', 10, 1 );

/**
 * After importing via WP All Import, adds default meta data.
 *
 * @param  int $post_id
 */
function wpjm_pmxi_saved_post( $post_id ) {
	if ( 'event_listing' === get_post_type( $post_id ) ) {
		WP_event_Manager_Post_Types::instance()->maybe_add_default_meta_data( $post_id, get_post( $post_id ) );
		if ( ! WP_event_Manager_Geocode::has_location_data( $post_id ) ) {
			$location = get_post_meta( $post_id, '_event_location', true );
			if ( $location ) {
				WP_event_Manager_Geocode::generate_location_data( $post_id, $location );
			}
		}
	}
}
