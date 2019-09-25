<?php
/**
 * Email content when notifying employers of an expiring event listing.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/emails/employer-expiring-event.php.
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

echo '<p>';
if ( $expiring_today ) {
	echo wp_kses_post(
		sprintf(
			__( 'The following event listing is expiring today from <a href="%s">%s</a>.', 'wp-event-manager' ),
			home_url(),
			get_bloginfo( 'name' )
		)
	);
} else {
	echo wp_kses_post(
		sprintf(
			__( 'The following event listing is expiring soon from <a href="%s">%s</a>.', 'wp-event-manager' ),
			home_url(),
			get_bloginfo( 'name' )
		)
	);
}
echo wp_kses_post(
	sprintf(
		' ' . __( 'Visit the <a href="%s">event listing dashboard</a> to manage the listing.', 'wp-event-manager' ),
		esc_url( event_manager_get_permalink( 'event_dashboard' ) )
	)
);
echo '</p>';

/**
 * Show details about the event listing.
 *
 * @param WP_Post              $event            The event listing to show details for.
 * @param WP_event_Manager_Email $email          Email object for the notification.
 * @param bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @param bool                 $plain_text     True if the email is being sent as plain text.
 */
do_action( 'event_manager_email_event_details', $event, $email, false, $plain_text );
