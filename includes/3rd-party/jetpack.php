<?php
/**
 * Adds additional compatibility with Jetpack.
 *
 * @package wp-event-manager
 */

/**
 * Skip filled event listings.
 *
 * @param bool    $skip_post
 * @param WP_Post $post
 * @return bool
 */
function wpjm_jetpack_skip_filled_event_listings( $skip_post, $post ) {
	if ( 'event_listing' !== $post->post_type ) {
		return $skip_post;
	}

	if ( is_position_filled( $post ) ) {
		return true;
	}

	return $skip_post;
}
add_action( 'jetpack_sitemap_skip_post', 'wpjm_jetpack_skip_filled_event_listings', 10, 2 );

/**
 * Add `event_listing` post type to sitemap.
 *
 * @param array $post_types
 * @return array
 */
function wpjm_jetpack_add_post_type( $post_types ) {
	$post_types[] = 'event_listing';
	return $post_types;
}
add_filter( 'jetpack_sitemap_post_types', 'wpjm_jetpack_add_post_type' );
