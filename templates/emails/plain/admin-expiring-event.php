<?php
/**
 * Email content when notifying the administrator of an expiring event listing.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/emails/plain/employer-expiring-event.php.
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

/**
 * @var WP_Post $event
 */
$event = $args['event'];

/**
 * @var bool
 */
$expiring_today = $args['expiring_today'];

if ( $expiring_today ) {
	printf(
		esc_html__( 'The following event listing is expiring today from %s (%s).', 'wp-event-manager' ),
		esc_html( get_bloginfo( 'name' ) ),
		esc_url( home_url() )
	);
} else {
	printf(
		esc_html__( 'The following event listing is expiring soon from %s (%s).', 'wp-event-manager' ),
		esc_html( get_bloginfo( 'name' ) ),
		esc_url( home_url() )
	);
}
$edit_post_link = admin_url( sprintf( 'post.php?post=%d&amp;action=edit', $event->ID ) );
printf(
	' ' . esc_html__( 'Visit WordPress admin (%s) to manage the listing.', 'wp-event-manager' ),
	esc_url( $edit_post_link )
);

/**
 * Show details about the event listing.
 *
 * @param WP_Post              $event            The event listing to show details for.
 * @param WP_event_Manager_Email $email          Email object for the notification.
 * @param bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @param bool                 $plain_text     True if the email is being sent as plain text.
 */
do_action( 'event_manager_email_event_details', $event, $email, true, $plain_text );
