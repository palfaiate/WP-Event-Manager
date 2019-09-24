<?php
/**
 * Notice when event has been submitted.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-submitted.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.31.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $wp_post_types;

switch ( $event->post_status ) :
	case 'publish' :
		echo wp_kses_post(
			sprintf(
				__( '%s listed successfully. To view your listing <a href="%s">click here</a>.', 'wp-event-manager' ),
				esc_html( $wp_post_types['event_listing']->labels->singular_name ),
				get_permalink( $event->ID )
			)
		);
	break;
	case 'pending' :
		echo wp_kses_post(
			sprintf(
				esc_html__( '%s submitted successfully. Your listing will be visible once approved.', 'wp-event-manager' ),
				esc_html( $wp_post_types['event_listing']->labels->singular_name )
			)
		);
	break;
	default :
		do_action( 'event_manager_event_submitted_content_' . str_replace( '-', '_', sanitize_title( $event->post_status ) ), $event );
	break;
endswitch;

do_action( 'event_manager_event_submitted_content_after', sanitize_title( $event->post_status ), $event );
