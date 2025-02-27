<?php
/**
 * File containing the class WP_event_Manager_Email_Admin_Expiring_event.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email notification to the site administrator when a event is expiring.
 *
 * @since 1.31.0
 * @extends WP_event_Manager_Email
 */
class WP_event_Manager_Email_Admin_Expiring_event extends WP_event_Manager_Email_Employer_Expiring_event {
	/**
	 * Get the unique email notification key.
	 *
	 * @return string
	 */
	public static function get_key() {
		return 'admin_expiring_event';
	}

	/**
	 * Get the friendly name for this email notification.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Admin Notice of Expiring event Listings', 'wp-event-manager' );
	}

	/**
	 * Get the description for this email notification.
	 *
	 * @type abstract
	 * @return string
	 */
	public static function get_description() {
		return __( 'Send notices to the site administrator before a event listing expires.', 'wp-event-manager' );
	}

	/**
	 * Get array or comma-separated list of email addresses to send message.
	 *
	 * @return string|array
	 */
	public function get_to() {
		return get_option( 'admin_email', false );
	}

}
