<?php
/**
 * Adds additional compatibility with Related Post for WordPress.
 *
 * @package wp-event-manager
 */

add_filter( 'rp4wp_get_template', 'wpjm_rp4wp_template', 10, 3 );
add_filter( 'rp4wp_related_meta_fields', 'wpjm_rp4wp_related_meta_fields', 10, 3 );
add_filter( 'rp4wp_related_meta_fields_weight', 'wpjm_rp4wp_related_meta_fields_weight', 10, 3 );

/**
 * Replaces RP4WP template with the template from event Manager.
 *
 * @param  string $located
 * @param  string $template_name
 * @param  array  $args
 * @return string
 */
function wpjm_rp4wp_template( $located, $template_name, $args ) {
	if ( 'related-post-default.php' === $template_name && 'event_listing' === $args['related_post']->post_type ) {
		return event_MANAGER_PLUGIN_DIR . '/templates/content-event_listing.php';
	}
	return $located;
}

/**
 * Adds meta fields for RP4WP to relate events by.
 *
 * @param  array   $meta_fields
 * @param  int     $post_id
 * @param  WP_Post $post
 * @return array
 */
function wpjm_rp4wp_related_meta_fields( $meta_fields, $post_id, $post ) {
	if ( 'event_listing' === $post->post_type ) {
		$meta_fields[] = '_company_name';
		$meta_fields[] = '_event_location';
	}
	return $meta_fields;
}

/**
 * Adds meta fields for RP4WP to relate events by.
 *
 * @param  int     $weight
 * @param  WP_Post $post
 * @param  string  $meta_field
 * @return int
 */
function wpjm_rp4wp_related_meta_fields_weight( $weight, $post, $meta_field ) {
	if ( 'event_listing' === $post->post_type ) {
		$weight = 100;
	}
	return $weight;
}
