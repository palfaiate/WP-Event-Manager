<?php
/**
 * Email content when notifying admin of an updated event listing.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/emails/admin-updated-event.php.
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
?>
	<p><?php
		echo wp_kses_post(
			sprintf( __( 'A event listing has been updated on <a href="%s">%s</a>.', 'wp-event-manager' ), home_url(), esc_html( get_bloginfo( 'name' ) ) ) );
		switch ( $event->post_status ) {
			case 'publish':
				printf( ' ' . esc_html__( 'The changes have been published and are now available to the public.', 'wp-event-manager' ) );
				break;
			case 'pending':
				echo wp_kses_post( sprintf(
					' ' . __( 'The event listing is not publicly available until the changes are approved by an administrator in the site\'s <a href="%s">WordPress admin</a>.', 'wp-event-manager' ),
					esc_url( admin_url( 'edit.php?post_type=event_listing' ) )
				) );
				break;
		}
		?></p>
<?php

/**
 * Show details about the event listing.
 *
 * @param WP_Post              $event            The event listing to show details for.
 * @param WP_event_Manager_Email $email          Email object for the notification.
 * @param bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @param bool                 $plain_text     True if the email is being sent as plain text.
 */
do_action( 'event_manager_email_event_details', $event, $email, true, $plain_text );
