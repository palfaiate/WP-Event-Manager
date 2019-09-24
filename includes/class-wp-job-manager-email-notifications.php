<?php
/**
 * File containing the class WP_event_Manager_Email_Notifications.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for WP Event Manager's email notification system.
 *
 * @since 1.31.0
 */
final class WP_event_Manager_Email_Notifications {
	const EMAIL_SETTING_PREFIX     = 'event_manager_email_';
	const EMAIL_SETTING_ENABLED    = 'enabled';
	const EMAIL_SETTING_PLAIN_TEXT = 'plain_text';

	/**
	 * Notifications to be scheduled.
	 *
	 * @var array
	 */
	private static $deferred_notifications = [];

	/**
	 * Sets up initial hooks.
	 *
	 * @static
	 */
	public static function init() {
		add_action( 'event_manager_send_notification', [ __CLASS__, 'schedule_notification' ], 10, 2 );
		add_action( 'event_manager_email_init', [ __CLASS__, 'lazy_init' ] );
		add_action( 'event_manager_email_event_details', [ __CLASS__, 'output_event_details' ], 10, 4 );
		add_action( 'event_manager_email_header', [ __CLASS__, 'output_header' ], 10, 3 );
		add_action( 'event_manager_email_footer', [ __CLASS__, 'output_footer' ], 10, 3 );
		add_action( 'event_manager_email_daily_notices', [ __CLASS__, 'send_employer_expiring_notice' ] );
		add_action( 'event_manager_email_daily_notices', [ __CLASS__, 'send_admin_expiring_notice' ] );
		add_filter( 'event_manager_settings', [ __CLASS__, 'add_event_manager_email_settings' ], 1 );
		add_action( 'event_manager_event_submitted', [ __CLASS__, 'send_new_event_notification' ] );
		add_action( 'event_manager_user_edit_event_listing', [ __CLASS__, 'send_updated_event_notification' ] );
	}

	/**
	 * Gets list of email notifications handled by WP Event Manager core.
	 *
	 * @return array
	 */
	public static function core_email_notifications() {
		return [
			'WP_event_Manager_Email_Admin_New_event',
			'WP_event_Manager_Email_Admin_Updated_event',
			'WP_event_Manager_Email_Admin_Expiring_event',
			'WP_event_Manager_Email_Employer_Expiring_event',
		];
	}

	/**
	 * Sets up an email notification to be sent at the end of the script's execution.
	 *
	 * Do not call manually.
	 *
	 * @access private
	 *
	 * @param string $notification
	 * @param array  $args
	 */
	public static function schedule_notification( $notification, $args = [] ) {
		self::maybe_init();

		self::$deferred_notifications[] = [ $notification, $args ];
	}

	/**
	 * Sends all notifications collected during execution.
	 *
	 * Do not call manually.
	 *
	 * @access private
	 */
	public static function send_deferred_notifications() {
		$email_notifications = self::get_email_notifications( true );
		foreach ( self::$deferred_notifications as $email ) {
			if (
				! is_string( $email[0] )
				|| ! isset( $email_notifications[ $email[0] ] )
			) {
				continue;
			}

			$email_class            = $email_notifications[ $email[0] ];
			$email_notification_key = $email[0];
			$email_args             = is_array( $email[1] ) ? $email[1] : [];

			self::send_email( $email[0], new $email_class( $email_args, self::get_email_settings( $email_notification_key ) ) );
		}
	}

	/**
	 * Initialize if necessary.
	 */
	public static function maybe_init() {
		if ( 0 === did_action( 'event_manager_email_init' ) ) {
			/**
			 * Lazily load remaining files needed for email notifications. Do this here instead of in
			 * `shutdown` for proper logging in case of syntax errors.
			 *
			 * @since 1.31.0
			 */
			do_action( 'event_manager_email_init' );
		}
	}

	/**
	 * Include email files.
	 *
	 * Do not call manually.
	 *
	 * @access private
	 */
	public static function lazy_init() {
		add_action( 'shutdown', [ __CLASS__, 'send_deferred_notifications' ] );

		include_once event_MANAGER_PLUGIN_DIR . '/includes/emails/class-wp-event-manager-email-admin-new-event.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/emails/class-wp-event-manager-email-admin-updated-event.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/emails/class-wp-event-manager-email-employer-expiring-event.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/emails/class-wp-event-manager-email-admin-expiring-event.php';

		if ( ! class_exists( 'Emogrifier' ) && class_exists( 'DOMDocument' ) && version_compare( PHP_VERSION, '5.5', '>=' ) ) {
			include_once event_MANAGER_PLUGIN_DIR . '/lib/emogrifier/class-emogrifier.php';
		}
	}

	/**
	 * Clear the deferred notifications email array.
	 *
	 * Do not call manually. Only for help with tests.
	 *
	 * @access private
	 */
	public static function clear_deferred_notifications() {
		if ( ! defined( 'PHPUNIT_WPJM_TESTSUITE' ) || ! PHPUNIT_WPJM_TESTSUITE ) {
			die( 'This is just for use while testing' );
		}
		self::$deferred_notifications = [];
	}

	/**
	 * Gets a list of all email notifications that WP Event Manager handles.
	 *
	 * @param bool $enabled_notifications_only
	 * @return array
	 */
	public static function get_email_notifications( $enabled_notifications_only = false ) {
		self::maybe_init();

		/**
		 * Retrieves all email notifications to be sent.
		 *
		 * @since 1.31.0
		 *
		 * @param array $email_notifications All the email notifications to be registered.
		 */
		$email_notification_classes = array_unique( apply_filters( 'event_manager_email_notifications', self::core_email_notifications() ) );
		$email_notifications        = [];

		/**
		 * Email class in loop.
		 *
		 * @var WP_event_Manager_Email $email_class
		 */
		foreach ( $email_notification_classes as $email_class ) {
			// Check to make sure email notification is valid.
			if ( ! self::is_email_notification_valid( $email_class ) ) {
				continue;
			}

			// PHP 5.2: Using `call_user_func()` but `$email_class::get_key()` preferred.
			$email_notification_key = call_user_func( [ $email_class, 'get_key' ] );
			if (
				isset( $email_notifications[ $email_notification_key ] )
				|| ( $enabled_notifications_only && ! self::is_email_notification_enabled( $email_notification_key ) )
			) {
				continue;
			}

			$email_notifications[ $email_notification_key ] = $email_class;
		}

		return $email_notifications;
	}

	/**
	 * Show details about the event listing.
	 *
	 * @param WP_Post              $event            The event listing to show details for.
	 * @param WP_event_Manager_Email $email          Email object for the notification.
	 * @param bool                 $sent_to_admin  True if this is being sent to an administrator.
	 * @param bool                 $plain_text     True if the email is being sent as plain text.
	 */
	public static function output_event_details( $event, $email, $sent_to_admin, $plain_text = false ) {
		$template_segment = self::locate_template_file( 'email-event-details', $plain_text );
		if ( ! file_exists( $template_segment ) ) {
			return;
		}

		$fields = self::get_event_detail_fields( $event, $sent_to_admin, $plain_text );

		include $template_segment;
	}

	/**
	 * Get the event fields to show in email templates.
	 *
	 * @param WP_Post $event
	 * @param bool    $sent_to_admin
	 * @param bool    $plain_text
	 * @return array
	 */
	private static function get_event_detail_fields( WP_Post $event, $sent_to_admin, $plain_text = false ) {
		$fields = [];

		$fields['event_title'] = [
			'label' => __( 'event title', 'wp-event-manager' ),
			'value' => $event->post_title,
		];

		if ( $sent_to_admin || 'publish' === $event->post_status ) {
			$fields['event_title']['url'] = get_permalink( $event );
		}

		$event_location = get_the_event_location( $event );
		if ( ! empty( $event_location ) ) {
			$fields['event_location'] = [
				'label' => __( 'Location', 'wp-event-manager' ),
				'value' => $event_location,
			];
		}

		if ( get_option( 'event_manager_enable_types' ) && wp_count_terms( 'event_listing_type' ) > 0 ) {
			$event_types = wpjm_get_the_event_types( $event );
			if ( ! empty( $event_types ) ) {
				$fields['event_type'] = [
					'label' => __( 'event type', 'wp-event-manager' ),
					'value' => implode( ', ', wp_list_pluck( $event_types, 'name' ) ),
				];
			}
		}

		if ( get_option( 'event_manager_enable_categories' ) && wp_count_terms( 'event_listing_category' ) > 0 ) {
			$event_categories = wpjm_get_the_event_categories( $event );
			if ( ! empty( $event_categories ) ) {
				$fields['event_category'] = [
					'label' => __( 'event category', 'wp-event-manager' ),
					'value' => implode( ', ', wp_list_pluck( $event_categories, 'name' ) ),
				];
			}
		}

		$company_name = get_the_company_name( $event );
		if ( ! empty( $company_name ) ) {
			$fields['company_name'] = [
				'label' => __( 'Company name', 'wp-event-manager' ),
				'value' => $company_name,
			];
		}

		$company_website = get_the_company_website( $event );
		if ( ! empty( $company_website ) ) {
			$fields['company_website'] = [
				'label' => __( 'Company website', 'wp-event-manager' ),
				'value' => $plain_text ? $company_website : sprintf( '<a href="%1$s">%1$s</a>', esc_url( $company_website, [ 'http', 'https' ] ) ),
			];
		}

		$event_expires = get_post_meta( $event->ID, '_event_expires', true );
		if ( ! empty( $event_expires ) ) {
			$event_expires_str       = date_i18n( get_option( 'date_format' ), strtotime( $event_expires ) );
			$fields['event_expires'] = [
				'label' => __( 'Listing expires', 'wp-event-manager' ),
				'value' => $event_expires_str,
			];
		}

		if ( $sent_to_admin ) {
			$author = get_user_by( 'ID', $event->post_author );
			if ( $author instanceof WP_User ) {
				$fields['author'] = [
					'label' => __( 'Posted by', 'wp-event-manager' ),
					'value' => $author->user_nicename,
					'url'   => 'mailto:' . $author->user_email,
				];
			}
		}

		/**
		 * Modify the fields shown in email notifications in the details summary a event listing.
		 *
		 * @since 1.31.0
		 *
		 * @param array   $fields         {
		 *     Array of fields. Each field is keyed with a unique identifier.
		 *     {
		 *          @type string $label Label to show next to field.
		 *          @type string $value Value for field.
		 *          @type string $url   URL to provide with the value (optional).
		 *     }
		 * }
		 * @param WP_Post $event            event listing.
		 * @param bool    $sent_to_admin  True if being sent in an admin notification.
		 * @param bool    $plain_text     True if being sent as plain text.
		 */
		return apply_filters( 'event_manager_emails_event_detail_fields', $fields, $event, $sent_to_admin, $plain_text );
	}

	/**
	 * Output email header.
	 *
	 * @param string $email_notification_key  Email notification key for email being sent.
	 * @param bool   $sent_to_admin           True if this is being sent to an administrator.
	 * @param bool   $plain_text              True if the email is being sent as plain text.
	 */
	public static function output_header( $email_notification_key, $sent_to_admin, $plain_text = false ) {
		$template_segment = self::email_template_path_alternative( $email_notification_key, 'email-header', $plain_text );
		if ( false === $template_segment ) {
			$template_segment = self::locate_template_file( 'email-header', $plain_text );
		}
		if ( ! $template_segment || ! file_exists( $template_segment ) ) {
			return;
		}
		include $template_segment;
	}

	/**
	 * Output email footer.
	 *
	 * @param string $email_notification_key  Email notification key for email being sent.
	 * @param bool   $sent_to_admin           True if this is being sent to an administrator.
	 * @param bool   $plain_text              True if the email is being sent as plain text.
	 */
	public static function output_footer( $email_notification_key, $sent_to_admin, $plain_text = false ) {
		$template_segment = self::email_template_path_alternative( $email_notification_key, 'email-footer', $plain_text );
		if ( false === $template_segment ) {
			$template_segment = self::locate_template_file( 'email-footer', $plain_text );
		}
		if ( ! $template_segment || ! file_exists( $template_segment ) ) {
			return;
		}
		include $template_segment;
	}

	/**
	 * Checks for an alternative email template segment in the template path specified by the current email.
	 * Useful to provide alternative email headers and footers for a specific WPJM extension plugin.
	 *
	 * @param string $email_notification_key  Email notification key for email being sent.
	 * @param string $template_name           Name of the template to check.
	 * @param bool   $plain_text              True if the email is being sent as plain text.
	 * @return bool|string Returns path to template path alternative or false if none exists.
	 */
	private static function email_template_path_alternative( $email_notification_key, $template_name, $plain_text ) {
		$email_class = self::get_email_class( $email_notification_key );
		if ( ! $email_class || ! is_subclass_of( $email_class, 'WP_event_Manager_Email_Template' ) ) {
			return false;
		}

		$template_default_path = call_user_func( [ $email_class, 'get_template_default_path' ] );
		if ( '' === $template_default_path ) {
			return false;
		}

		$template_path = call_user_func( [ $email_class, 'get_template_path' ] );
		$template      = self::locate_template_file( $template_name, $plain_text, $template_path, $template_default_path );
		if ( '' === $template ) {
			return false;
		}

		return $template;
	}

	/**
	 * Locate template file.
	 *
	 * @param string $template_name
	 * @param bool   $plain_text
	 * @param string $template_path
	 * @param string $default_path
	 * @return string
	 */
	public static function locate_template_file( $template_name, $plain_text = false, $template_path = 'event_manager', $default_path = '' ) {
		return locate_event_manager_template( WP_event_Manager_Email_Template::generate_template_file_name( $template_name, $plain_text ), $template_path, $default_path );
	}

	/**
	 * Add email notification settings for the event manager context.
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function add_event_manager_email_settings( $settings ) {
		return self::add_email_settings( $settings, WP_event_Manager_Email::get_context() );
	}

	/**
	 * Add email notification settings for a context.
	 *
	 * @param array  $settings
	 * @param string $context
	 * @return array
	 */
	public static function add_email_settings( $settings, $context ) {
		$email_notifications = self::get_email_notifications( false );
		$email_settings      = [];

		foreach ( $email_notifications as $email_notification_key => $email_class ) {
			$email_notification_context = call_user_func( [ $email_class, 'get_context' ] );
			if ( $context !== $email_notification_context ) {
				continue;
			}

			$email_settings[] = [
				'type'         => 'multi_enable_expand',
				'class'        => 'email-setting-row no-separator',
				'name'         => self::EMAIL_SETTING_PREFIX . call_user_func( [ $email_class, 'get_key' ] ),
				'enable_field' => [
					'name'     => self::EMAIL_SETTING_ENABLED,
					'cb_label' => call_user_func( [ $email_class, 'get_name' ] ),
					'desc'     => call_user_func( [ $email_class, 'get_description' ] ),
				],
				'label'        => false,
				'std'          => self::get_email_setting_defaults( $email_notification_key ),
				'settings'     => self::get_email_setting_fields( $email_notification_key ),
			];
		}

		if ( ! empty( $email_settings ) ) {
			$settings['email_notifications'] = [
				__( 'Email Notifications', 'wp-event-manager' ),
				$email_settings,
				[
					'before' => __( 'Select the email notifications to enable.', 'wp-event-manager' ),
				],
			];
		}

		return $settings;
	}

	/**
	 * Checks if a particular notification is enabled or not.
	 *
	 * @param string $email_notification_key
	 * @return bool
	 */
	public static function is_email_notification_enabled( $email_notification_key ) {
		$settings = self::get_email_settings( $email_notification_key );

		$is_email_notification_enabled = ! empty( $settings[ self::EMAIL_SETTING_ENABLED ] );

		/**
		 * Filter whether an notification email is enabled.
		 *
		 * @since 1.31.0
		 *
		 * @param bool   $is_email_notification_enabled
		 * @param string $email_notification_key
		 */
		return apply_filters( 'event_manager_email_is_email_notification_enabled', $is_email_notification_enabled, $email_notification_key );
	}

	/**
	 * Checks if we should send emails using plain text.
	 *
	 * @param string $email_notification_key
	 * @return bool
	 */
	public static function send_as_plain_text( $email_notification_key ) {
		$settings = self::get_email_settings( $email_notification_key );

		$send_as_plain_text = ! empty( $settings[ self::EMAIL_SETTING_PLAIN_TEXT ] );

		/**
		 * Filter whether to send emails as plain text.
		 *
		 * @since 1.31.0
		 *
		 * @param bool   $send_as_plain_text
		 * @param string $email_notification_key
		 */
		return apply_filters( 'event_manager_email_send_as_plain_text', $send_as_plain_text, $email_notification_key );
	}

	/**
	 * Sending notices to employers for expiring event listings.
	 */
	public static function send_employer_expiring_notice() {
		self::maybe_init();

		$email_key = WP_event_Manager_Email_Employer_Expiring_event::get_key();
		if ( ! self::is_email_notification_enabled( $email_key ) ) {
			return;
		}
		$settings    = self::get_email_settings( $email_key );
		$days_notice = WP_event_Manager_Email_Employer_Expiring_event::get_notice_period( $settings );
		self::send_expiring_notice( $email_key, $days_notice );
	}

	/**
	 * Sending notices to the site administrator for expiring event listings.
	 */
	public static function send_admin_expiring_notice() {
		self::maybe_init();

		$email_key = WP_event_Manager_Email_Admin_Expiring_event::get_key();
		if ( ! self::is_email_notification_enabled( $email_key ) ) {
			return;
		}
		$settings    = self::get_email_settings( $email_key );
		$days_notice = WP_event_Manager_Email_Admin_Expiring_event::get_notice_period( $settings );
		self::send_expiring_notice( $email_key, $days_notice );
	}

	/**
	 * Fire the action to send a new event notification to the admin.
	 *
	 * @param int $event_id
	 */
	public static function send_new_event_notification( $event_id ) {
		do_action( 'event_manager_send_notification', 'admin_new_event', [ 'event_id' => $event_id ] );
	}

	/**
	 * Fire the action to send a updated event notification to the admin.
	 *
	 * @param int $event_id
	 */
	public static function send_updated_event_notification( $event_id ) {
		do_action( 'event_manager_send_notification', 'admin_updated_event', [ 'event_id' => $event_id ] );
	}

	/**
	 * Send notice based on event expiration date.
	 *
	 * @param string $email_notification_key
	 * @param int    $days_notice
	 */
	private static function send_expiring_notice( $email_notification_key, $days_notice ) {
		$notice_before_ts = current_time( 'timestamp' ) + ( DAY_IN_SECONDS * $days_notice );
		$event_ids          = get_posts(
			[
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'   => '_event_expires',
						'value' => date( 'Y-m-d', $notice_before_ts ),
					],
				],
			]
		);

		if ( $event_ids ) {
			foreach ( $event_ids as $event_id ) {
				do_action( 'event_manager_send_notification', $email_notification_key, [ 'event_id' => $event_id ] );
			}
		}
	}

	/**
	 * Get the setting fields for an email.
	 *
	 * @param string $email_notification_key
	 * @return array
	 */
	private static function get_email_setting_fields( $email_notification_key ) {
		$email_class    = self::get_email_class( $email_notification_key );
		$core_settings  = [
			[
				'name'    => 'plain_text',
				'std'     => '0',
				'label'   => __( 'Format', 'wp-event-manager' ),
				'type'    => 'radio',
				'options' => [
					'1' => __( 'Send plain text email', 'wp-event-manager' ),
					'0' => __( 'Send rich text email', 'wp-event-manager' ),
				],
			],
		];
		$email_settings = call_user_func( [ $email_class, 'get_setting_fields' ] );
		return array_merge( $core_settings, $email_settings );
	}

	/**
	 * Get the settings for the email.
	 *
	 * @param string $email_notification_key
	 * @return array
	 */
	private static function get_email_settings( $email_notification_key ) {
		$option_name  = self::EMAIL_SETTING_PREFIX . $email_notification_key;
		$option_value = get_option( $option_name );
		if ( empty( $option_value ) || ! is_array( $option_value ) ) {
			$option_value = [];
		}
		$default_settings = self::get_email_setting_defaults( $email_notification_key );

		return array_merge( $default_settings, $option_value );
	}

	/**
	 * Gets the default values for the email notification.
	 *
	 * @param string $email_notification_key
	 * @return array
	 */
	private static function get_email_setting_defaults( $email_notification_key ) {
		$settings    = self::get_email_setting_fields( $email_notification_key );
		$email_class = self::get_email_class( $email_notification_key );

		$defaults                                = [];
		$defaults[ self::EMAIL_SETTING_ENABLED ] = call_user_func( [ $email_class, 'is_default_enabled' ] ) ? '1' : '0';

		foreach ( $settings as $setting ) {
			$defaults[ $setting['name'] ] = null;
			if ( isset( $setting['std'] ) ) {
				$defaults[ $setting['name'] ] = $setting['std'];
			}
		}

		return $defaults;
	}

	/**
	 * Get the email class from the unique key.
	 *
	 * @param string $email_notification_key
	 * @return bool|string
	 */
	private static function get_email_class( $email_notification_key ) {
		$email_notifications = self::get_email_notifications( false );

		return isset( $email_notifications[ $email_notification_key ] ) ? $email_notifications[ $email_notification_key ] : false;
	}

	/**
	 * Returns the total number of deferred notifications to be sent.
	 *
	 * Do not use. Used just in unit tests.
	 *
	 * @access private
	 *
	 * @return int
	 */
	public static function get_deferred_notification_count() {
		return count( self::$deferred_notifications );
	}

	/**
	 * Returns the hash representation of the notifications to be sent.
	 *
	 * Do not use. Used just in unit tests.
	 *
	 * @access private
	 *
	 * @return string[]
	 */
	public static function get_deferred_notification_hashes() {
		return array_map(
			function( $value ) {
				return sha1( wp_json_encode( $value ) );
			},
			self::$deferred_notifications
		);
	}

	/**
	 * Confirms an email notification is valid.
	 *
	 * @access private
	 *
	 * @param string $email_class
	 * @return bool
	 */
	private static function is_email_notification_valid( $email_class ) {
		// PHP 5.2: Using `call_user_func()` but `$email_class::get_key()` preferred.
		return is_string( $email_class )
				&& class_exists( $email_class )
				&& is_subclass_of( $email_class, 'WP_event_Manager_Email' )
				&& false !== call_user_func( [ $email_class, 'get_key' ] )
				&& false !== call_user_func( [ $email_class, 'get_name' ] );
	}

	/**
	 * Sends an email notification.
	 *
	 * @access private
	 *
	 * @param string               $email_notification_key
	 * @param WP_event_Manager_Email $email
	 * @return bool
	 */
	private static function send_email( $email_notification_key, WP_event_Manager_Email $email ) {
		if ( ! $email->is_valid() ) {
			return false;
		}

		$fields = [ 'to', 'from', 'subject', 'rich_content', 'plain_content', 'attachments', 'cc', 'headers' ];
		$args   = [];
		foreach ( $fields as $field ) {
			$method = 'get_' . $field;

			/**
			 * Filter email values for event manager notifications.
			 *
			 * @since 1.31.0
			 *
			 * @param mixed                $email_field_value Value to be filtered.
			 * @param WP_event_Manager_Email $email             Email notification object.
			 */
			$args[ $field ] = apply_filters( "event_manager_email_{$email_notification_key}_{$field}", $email->$method(), $email );
		}

		$headers = is_array( $args['headers'] ) ? $args['headers'] : [];

		if ( ! empty( $args['from'] ) ) {
			$headers[] = 'From: ' . $args['from'];
		}

		if ( ! self::send_as_plain_text( $email_notification_key ) ) {
			$headers[] = 'Content-Type: text/html';
		}

		$content = self::get_email_content( $email_notification_key, $args );

		/**
		 * Allows for short-circuiting the actual sending of email notifications.
		 *
		 * @since 1.31.0
		 *
		 * @param bool                  $do_send_notification   True if we should send the notification.
		 * @param WP_event_Manager_Email  $email                  Email notification object.
		 * @param array                 $args                   Email arguments for generating email.
		 * @param string                $content                Email content.
		 * @param array                 $headers                Email headers.
		 * @param
		 */
		if ( ! apply_filters( 'event_manager_email_do_send_notification', true, $email, $args, $content, $headers ) ) {
			return false;
		}
		return wp_mail( $args['to'], $args['subject'], $content, $headers, $args['attachments'] );
	}

	/**
	 * Generates the content for an email.
	 *
	 * @access private
	 *
	 * @param string $email_notification_key
	 * @param array  $args
	 * @return string
	 */
	private static function get_email_content( $email_notification_key, $args ) {
		$plain_text = self::send_as_plain_text( $email_notification_key );

		ob_start();

		/**
		 * Output the header for all event manager emails.
		 *
		 * @since 1.31.0
		 *
		 * @param string $email_notification_key Unique email notification key.
		 * @param array  $args                   Arguments passed for generating email.
		 * @param bool   $plain_text             True if sending plain text email.
		 */
		do_action( 'event_manager_email_header', $email_notification_key, $args, $plain_text );

		if ( $plain_text ) {
			echo wp_kses_post( html_entity_decode( wptexturize( $args['plain_content'] ) ) );
		} else {
			echo wp_kses_post( wpautop( wptexturize( $args['rich_content'] ) ) );
		}

		/**
		 * Output the footer for all event manager emails.
		 *
		 * @since 1.31.0
		 *
		 * @param string $email_notification_key Unique email notification key.
		 * @param array  $args                   Arguments passed for generating email.
		 * @param bool   $plain_text             True if sending plain text email.
		 */
		do_action( 'event_manager_email_footer', $email_notification_key, $args, $plain_text );

		$content = ob_get_clean();
		if ( ! $plain_text ) {
			$content = self::inject_styles( $content );
		}

		/**
		 * Filter the content of the email.
		 *
		 * @since 1.31.0
		 *
		 * @param string $content                Email content.
		 * @param string $email_notification_key Unique email notification key.
		 * @param array  $args                   Arguments passed for generating email.
		 * @param bool   $plain_text             True if sending plain text email.
		 */
		return apply_filters( 'event_manager_email_content', $content, $email_notification_key, $args, $plain_text );
	}

	/**
	 * Inject inline styles into email content.
	 *
	 * @param string $content
	 * @return string
	 */
	private static function inject_styles( $content ) {
		if ( class_exists( 'Emogrifier' ) ) {
			try {
				$emogrifier = new Emogrifier( $content, self::get_styles() );
				$content    = $emogrifier->emogrify();
			} catch ( Exception $e ) {
				trigger_error( 'Unable to inject styles into email notification: ' . $e->getMessage() ); // @codingStandardsIgnoreLine
			}
		}
		return $content;
	}

	/**
	 * Gets the CSS styles to be used in email notifications.
	 *
	 * @return bool|string
	 */
	private static function get_styles() {
		$email_styles_template = self::locate_template_file( 'email-styles' );
		if ( ! file_exists( $email_styles_template ) ) {
			return false;
		}
		ob_start();
		include $email_styles_template;
		return ob_get_clean();
	}

}
