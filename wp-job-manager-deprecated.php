<?php
/**
 * Deprecated functions. Do not use these.
 *
 * @package wp-event-manager
 */

if ( ! function_exists( 'order_featured_event_listing' ) ) :
	/**
	 * Was used for sorting.
	 *
	 * @deprecated 1.22.4
	 *
	 * @param array $args
	 * @return array
	 */
	function order_featured_event_listing( $args ) {
		global $wpdb;
		$args['orderby'] = "$wpdb->posts.menu_order ASC, $wpdb->posts.post_date DESC";
		return $args;
	}
endif;



if ( ! function_exists( 'the_event_type' ) ) :
	/**
	 * Displays the event type for the listing.
	 *
	 * @since 1.0.0
	 * @deprecated 1.27.0 Use `wpjm_the_event_types()` instead.
	 *
	 * @param int|WP_Post $post
	 * @return string
	 */
	function the_event_type( $post = null ) {
		_deprecated_function( __FUNCTION__, '1.27.0', 'wpjm_the_event_types' );

		if ( ! get_option( 'event_manager_enable_types' ) ) {
			return '';
		}
		$event_type = get_the_event_type( $post );
		if ( $event_type ) {
			echo esc_html( $event_type->name );
		}
	}
endif;

if ( ! function_exists( 'get_the_event_type' ) ) :
	/**
	 * Gets the event type for the listing.
	 *
	 * @since 1.0.0
	 * @deprecated 1.27.0 Use `wpjm_get_the_event_types()` instead.
	 *
	 * @param int|WP_Post $post (default: null).
	 * @return string|bool|null
	 */
	function get_the_event_type( $post = null ) {
		_deprecated_function( __FUNCTION__, '1.27.0', 'wpjm_get_the_event_types' );

		$post = get_post( $post );
		if ( 'event_listing' !== $post->post_type ) {
			return;
		}

		$types = wp_get_post_terms( $post->ID, 'event_listing_type' );

		if ( $types ) {
			$type = current( $types );
		} else {
			$type = false;
		}

		return apply_filters( 'the_event_type', $type, $post );
	}
endif;

if ( ! function_exists( 'wpjm_get_permalink_structure' ) ) :
	/**
	 * Retrieves permalink settings. Moved to `WP_event_Manager_Post_Types` class in 1.28.0.
	 *
	 * @since 1.27.0
	 * @deprecated 1.28.0
	 * @return array
	 */
	function wpjm_get_permalink_structure() {
		return WP_event_Manager_Post_Types::get_permalink_structure();
	}
endif;


if ( ! function_exists( 'event_manager_add_post_types' ) ) :
	/**
	 *  Adds event listing post types to list of types to be removed with user. Moved to `WP_event_Manager_Post_Types` class in 1.33.0.
	 *
	 * @deprecated 1.33.0
	 *
	 * @param array $types
	 * @return array
	 */
	function event_manager_add_post_types( $types ) {
		_deprecated_function( __FUNCTION__, '1.33.0', 'WP_event_Manager_Post_Types::delete_user_add_event_listings_post_type' );

		return WP_event_Manager_Post_Types::instance()->delete_user_add_event_listings_post_type( $types );
	}
endif;
