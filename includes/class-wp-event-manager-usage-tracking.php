<?php
/**
 * File containing the class WP_event_Manager_Usage_Tracking.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require dirname( __FILE__ ) . '/../lib/usage-tracking/class-usage-tracking-base.php';

/**
 * WPJM Usage Tracking subclass.
 */
class WP_event_Manager_Usage_Tracking extends WP_event_Manager_Usage_Tracking_Base {

	const WPJM_SETTING_NAME = 'event_manager_usage_tracking_enabled';

	const WPJM_TRACKING_INFO_URL = 'https://wpeventmanager.com/document/what-data-does-wpjm-track';

	/**
	 * WP_event_Manager_Usage_Tracking constructor.
	 */
	protected function __construct() {
		parent::__construct();

		// Add filter for settings.
		add_filter( 'event_manager_settings', [ $this, 'add_setting_field' ] );

		// In the setup wizard, do not display the normal opt-in dialog.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		if ( isset( $_GET['page'] ) && 'event-manager-setup' === $_GET['page'] ) {
			remove_action( 'admin_notices', [ $this, 'maybe_display_tracking_opt_in' ] );
		}
	}

	/**
	 * Gets the base data returned with system information.
	 *
	 * @return array
	 */
	protected function get_base_system_data() {
		$base_data            = [];
		$base_data['version'] = event_MANAGER_VERSION;

		return $base_data;
	}

	/**
	 * Track a WP Event Manager event.
	 *
	 * @since 1.33.0
	 *
	 * @param string $event_name The name of the event, without the `wpjm` prefix.
	 * @param array  $properties The event properties to be sent.
	 */
	public static function log_event( $event_name, $properties = [] ) {
		$properties = array_merge(
			WP_event_Manager_Usage_Tracking_Data::get_event_logging_base_fields(),
			$properties
		);

		self::get_instance()->send_event( $event_name, $properties );
	}

	/**
	 * Get the current user's primary role.
	 *
	 * @return string
	 */
	private static function get_current_role() {
		$current_user = wp_get_current_user();
		$roles        = $current_user->roles;

		if ( empty( $roles ) ) {
			return 'guest';
		}

		if ( in_array( 'administrator', $roles, true ) ) {
			return 'administrator';
		}

		if ( in_array( 'employer', $roles, true ) ) {
			return 'employer';
		}

		return array_shift( $roles );
	}


	/**
	 * Check if current request is a REST API request.
	 *
	 * @todo move this to WP_event_Manager_REST_API
	 * @return bool
	 */
	public static function is_rest_request() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Track the event submission event.
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $properties Default properties to use.
	 */
	public static function track_event_submission( $post_id, $properties = [] ) {
		// Only track the first time a event is submitted.
		if ( get_post_meta( $post_id, '_tracked_submitted' ) ) {
			return;
		}
		update_post_meta( $post_id, '_tracked_submitted', time() );

		$properties['event_id']      = intval( $post_id );
		$properties['post_status'] = get_post_status( $post_id );

		$user_role = self::get_current_role();
		if ( $user_role ) {
			$properties['user_role'] = $user_role;
		}

		self::log_event( 'event_listing_submitted', $properties );
	}

	/**
	 * Track the event approval event.
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $properties Default properties to use.
	 */
	public static function track_event_approval( $post_id, $properties = [] ) {
		// Only track the first time a event is approved.
		if ( get_post_meta( $post_id, '_tracked_approved' ) ) {
			return;
		}
		update_post_meta( $post_id, '_tracked_approved', time() );

		$post = get_post( $post_id );

		$properties['event_id'] = intval( $post_id );
		$properties['age']    = time() - strtotime( $post->post_date_gmt );

		$user_role = self::get_current_role();
		if ( $user_role ) {
			$properties['user_role'] = $user_role;
		}

		self::log_event( 'event_listing_approved', $properties );
	}

	/**
	 * Implementation for abstract functions.
	 */

	/**
	 * Return the instance of this class.
	 *
	 * @return self
	 */
	public static function get_instance() {
		return self::get_instance_for_subclass( get_class() );
	}

	/**
	 * Get prefix for the usage data setting.
	 *
	 * @return string
	 */
	protected function get_prefix() {
		return 'event_manager';
	}

	/**
	 * Get prefix for the event sent for usage tracking.
	 *
	 * @return string
	 */
	protected function get_event_prefix() {
		return 'wpjm';
	}

	/**
	 * Get the text domain used in the plugin.
	 *
	 * @return string
	 */
	protected function get_text_domain() {
		return 'wp-event-manager';
	}

	/**
	 * Get the status of usage tracking.
	 *
	 * @return bool
	 */
	public function get_tracking_enabled() {
		return get_option( self::WPJM_SETTING_NAME ) || false;
	}

	/**
	 * Set whether or not usage tracking is enabled.
	 *
	 * @param bool $enable
	 */
	public function set_tracking_enabled( $enable ) {
		update_option( self::WPJM_SETTING_NAME, $enable );
	}

	/**
	 * Check if the current user can manage usage tracking settings.
	 *
	 * @return bool
	 */
	protected function current_user_can_manage_tracking() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the text to show in the opt-in dialog.
	 *
	 * @return string
	 */
	protected function opt_in_dialog_text() {
		return sprintf(
			// translators: Placeholder %s is a URL to the document on wpeventmanager.com with info on usage tracking.
			__(
				'We\'d love if you helped us make WP Event Manager better by allowing us to collect
				<a href="%s">usage tracking data</a>. No sensitive information is
				collected, and you can opt out at any time.',
				'wp-event-manager'
			),
			self::WPJM_TRACKING_INFO_URL
		);
	}

	/**
	 * Check if we should track the status of a plugin.
	 *
	 * @param string $plugin_slug
	 * @return bool
	 */
	protected function do_track_plugin( $plugin_slug ) {
		if ( 1 === preg_match( '/^wp\-event\-manager/', $plugin_slug ) ) {
			return true;
		}
		$third_party_plugins = [
			'all-in-one-seo-pack',
			'polylang',
			'jetpack',
			'wordpress-seo', // Yoast.
			'sitepress-multilingual-cms', // WPML.
			'bibblio-related-posts', // Related Posts for WordPress.
		];
		if ( in_array( $plugin_slug, $third_party_plugins, true ) ) {
			return true;
		}

		return false;
	}


	/*
	 * Public functions.
	 */

	/**
	 * Hide the opt-in for enabling usage tracking.
	 **/
	public function hide_tracking_opt_in() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
		parent::hide_tracking_opt_in();
	}

	/**
	 * Allowed html tags, used by wp_kses, for the translated opt-in dialog
	 * text.
	 *
	 * @return array the html tags.
	 **/
	public function opt_in_dialog_text_allowed_html() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
		return parent::opt_in_dialog_text_allowed_html();
	}

	/**
	 * Get the opt-in text.
	 *
	 * @return string
	 */
	public function opt_in_checkbox_text() {
		return sprintf(

			/*
			 * translators: the href tag contains the URL for the page
			 * telling users what data WPJM tracks.
			 */
			__(
				'Help us make WP Event Manager better by allowing us to collect
				<a href="%s">usage tracking data</a>.
				No sensitive information is collected.',
				'wp-event-manager'
			),
			self::WPJM_TRACKING_INFO_URL
		);
	}


	/**
	 * Hooks.
	 */

	/**
	 * Add tracking setting field to general settings.
	 *
	 * @param array $fields
	 * @return array
	 */
	public function add_setting_field( $fields ) {
		$fields['general'][1][] = [
			'name'     => self::WPJM_SETTING_NAME,
			'std'      => '0',
			'type'     => 'checkbox',
			'desc'     => '',
			'label'    => __( 'Enable Usage Tracking', 'wp-event-manager' ),
			'cb_label' => $this->opt_in_checkbox_text(),
		];

		return $fields;
	}


	/**
	 * Helpers.
	 */

	/**
	 * Clear options used for usage tracking.
	 */
	public function clear_options() {
		delete_option( self::WPJM_SETTING_NAME );
		delete_option( $this->hide_tracking_opt_in_option_name );
	}
}
