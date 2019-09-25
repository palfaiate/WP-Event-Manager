<?php
/**
 * Email content when notifying the administrator of an expiring event listing.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/emails/employer-expiring-event.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.33.4
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
$edit_post_link = admin_url( sprintf( 'post.php?post=%d&amp;action=edit', $event->ID ) );

echo '<p>';
if ( $expiring_today ) {
	// translators: %1$s placeholder is URL to the blog. %2$s placeholder is the name of the site.
	echo wp_kses_post( sprintf( __( 'The following event listing is expiring today from <a href="%1$s">%2$s</a>.', 'wp-event-manager' ), esc_url( home_url() ), esc_html( get_bloginfo( 'name' ) ) ) );
} else {
	// translators: %1$s placeholder is URL to the blog. %2$s placeholder is the name of the site.
	echo wp_kses_post( sprintf( __( 'The following event listing is expiring soon from <a href="%1$s">%2$s</a>.', 'wp-event-manager' ), esc_url( home_url() ), esc_html( get_bloginfo( 'name' ) ) ) );
}

echo ' ';

// translators: Placeholder is URL to site's WP admin.
echo wp_kses_post( sprintf( __( 'Visit <a href="%s">WordPress admin</a> to manage the listing.', 'wp-event-manager' ), esc_url( $edit_post_link ) ) );
echo '</p>';

/**
 * Show details about the event listing.
 *
 * @param WP_Post              $event            The event listing to show details for.
 * @param WP_event_Manager_Email $email          Email object for the notification.
 * @param bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @param bool                 $plain_text     True if the email is being sent as plain text.
 */
do_action( 'event_manager_email_event_details', $event, $email, true, $plain_text );
