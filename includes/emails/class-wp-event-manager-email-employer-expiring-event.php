<?php
/**
 * File containing the class WP_event_Manager_Email_Employer_Expiring_event.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email notification to employers when a event is expiring.
 *
 * @since 1.31.0
 * @extends WP_event_Manager_Email
 */
class WP_event_Manager_Email_Employer_Expiring_event extends WP_event_Manager_Email_Template {
	const SETTING_NOTICE_PERIOD_NAME    = 'notice_period_days';
	const SETTING_NOTICE_PERIOD_DEFAULT = '1';

	/**
	 * Get the unique email notification key.
	 *
	 * @return string
	 */
	public static function get_key() {
		return 'employer_expiring_event';
	}

	/**
	 * Get the friendly name for this email notification.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Employer Notice of Expiring event Listings', 'wp-event-manager' );
	}

	/**
	 * Get the description for this email notification.
	 *
	 * @type abstract
	 * @return string
	 */
	public static function get_description() {
		return __( 'Send notices to employers before a event listing expires.', 'wp-event-manager' );
	}

	/**
	 * Get the notice period in days from the notification settings.
	 *
	 * @param array $settings
	 * @return int
	 */
	public static function get_notice_period( $settings ) {
		if ( isset( $settings[ self::SETTING_NOTICE_PERIOD_NAME ] ) ) {
			return absint( $settings[ self::SETTING_NOTICE_PERIOD_NAME ] );
		}
		return absint( self::SETTING_NOTICE_PERIOD_DEFAULT );
	}

	/**
	 * Get the email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		$args = $this->get_args();

		/**
		 * event listing post object.
		 *
		 * @var WP_Post $event
		 */
		$event = $args['event'];

		// translators: Placeholder %s is the event listing post title.
		return sprintf( __( 'event Listing Expiring: %s', 'wp-event-manager' ), $event->post_title );
	}

	/**
	 * Get `From:` address header value. Can be simple email or formatted `Firstname Lastname <email@example.com>`.
	 *
	 * @return string|bool Email from value or false to use WordPress' default.
	 */
	public function get_from() {
		return false;
	}

	/**
	 * Get array or comma-separated list of email addresses to send message.
	 *
	 * @return string|array
	 */
	public function get_to() {
		$args = $this->get_args();
		return $args['author']->user_email;
	}

	/**
	 * Expand arguments as necessary for the generation of the email.
	 *
	 * @param array $args
	 * @return mixed
	 */
	protected function prepare_args( $args ) {
		$args = parent::prepare_args( $args );

		if ( isset( $args['event'] ) ) {
			$args['expiring_today'] = false;
			$today                  = date( 'Y-m-d', current_time( 'timestamp' ) );
			$expiring_date          = date( 'Y-m-d', strtotime( $args['event']->_event_expires ) );
			if ( ! empty( $args['event']->_event_expires ) && $today === $expiring_date ) {
				$args['expiring_today'] = true;
			}
		}

		return $args;
	}

	/**
	 * Get the settings for this email notifications.
	 *
	 * @return array
	 */
	public static function get_setting_fields() {
		$fields   = parent::get_setting_fields();
		$fields[] = [
			'name'       => self::SETTING_NOTICE_PERIOD_NAME,
			'std'        => self::SETTING_NOTICE_PERIOD_DEFAULT,
			'label'      => __( 'Notice Period', 'wp-event-manager' ),
			'type'       => 'number',
			'after'      => ' ' . __( 'days', 'wp-event-manager' ),
			'attributes' => [ 'min' => 0 ],
		];
		return $fields;
	}

	/**
	 * Is this email notification enabled by default?
	 *
	 * @return bool
	 */
	public static function is_default_enabled() {
		return false;
	}

	/**
	 * Checks the arguments and returns whether the email notification is properly set up.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$args = $this->get_args();
		return isset( $args['event'] )
					&& $args['event'] instanceof WP_Post
					&& isset( $args['author'] )
					&& $args['author'] instanceof WP_User
					&& ! empty( $args['author']->user_email );
	}

}
