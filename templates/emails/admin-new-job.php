<?php
/**
 * Email content when notifying admin of a new event listing.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/emails/admin-new-event.php.
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
			sprintf(
				__( 'A new event listing has been submitted to <a href="%s">%s</a>.', 'wp-event-manager' ),
				home_url(),
				get_bloginfo( 'name' )
			)
		);
		switch ( $event->post_status ) {
			case 'publish':
				printf( ' ' . esc_html__( 'It has been published and is now available to the public.', 'wp-event-manager' ) );
				break;
			case 'pending':
				echo wp_kses_post(
					sprintf(
						' ' . __( 'It is awaiting approval by an administrator in <a href="%s">WordPress admin</a>.','wp-event-manager' ),
						esc_url( admin_url( 'edit.php?post_type=event_listing' ) )
					)
				);
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
