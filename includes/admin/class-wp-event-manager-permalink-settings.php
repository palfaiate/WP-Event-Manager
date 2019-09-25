<?php
/**
 * File containing the class WP_event_Manager_Permalink_Settings.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles front admin page for WP Event Manager.
 *
 * @see https://github.com/woocommerce/woocommerce/blob/3.0.8/includes/admin/class-wc-admin-permalink-settings.php  Based on WooCommerce's implementation.
 * @since 1.27.0
 */
class WP_event_Manager_Permalink_Settings {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.27.0
	 */
	private static $instance = null;

	/**
	 * Permalink settings.
	 *
	 * @var array
	 * @since 1.27.0
	 */
	private $permalinks = [];

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.27.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setup_fields();
		$this->settings_save();
		$this->permalinks = WP_event_Manager_Post_Types::get_permalink_structure();
	}

	/**
	 * Add setting fields related to permalinks.
	 */
	public function setup_fields() {
		add_settings_field(
			'wpjm_event_base_slug',
			__( 'event base', 'wp-event-manager' ),
			[ $this, 'event_base_slug_input' ],
			'permalink',
			'optional'
		);
		add_settings_field(
			'wpjm_event_category_slug',
			__( 'event category base', 'wp-event-manager' ),
			[ $this, 'event_category_slug_input' ],
			'permalink',
			'optional'
		);
		add_settings_field(
			'wpjm_event_type_slug',
			__( 'event type base', 'wp-event-manager' ),
			[ $this, 'event_type_slug_input' ],
			'permalink',
			'optional'
		);
		if ( current_theme_supports( 'event-manager-templates' ) ) {
			add_settings_field(
				'wpjm_event_listings_archive_slug',
				__( 'event listing archive page', 'wp-event-manager' ),
				[ $this, 'event_listings_archive_slug_input' ],
				'permalink',
				'optional'
			);
		}
	}

	/**
	 * Show a slug input box for event listing archive slug.
	 */
	public function event_listings_archive_slug_input() {
		?>
		<input name="wpjm_event_listings_archive_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['events_archive'] ); ?>" placeholder="<?php echo esc_attr( $this->permalinks['events_archive_rewrite_slug'] ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box for event post type slug.
	 */
	public function event_base_slug_input() {
		?>
		<input name="wpjm_event_base_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['event_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'event', 'event permalink - resave permalinks after changing this', 'wp-event-manager' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box for event category slug.
	 */
	public function event_category_slug_input() {
		?>
		<input name="wpjm_event_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'event-category', 'event category slug - resave permalinks after changing this', 'wp-event-manager' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box for event type slug.
	 */
	public function event_type_slug_input() {
		?>
		<input name="wpjm_event_type_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['type_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'event-type', 'event type slug - resave permalinks after changing this', 'wp-event-manager' ); ?>" />
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP core handles nonce check for settings save.
		if ( ! isset( $_POST['permalink_structure'] ) ) {
			// We must not be saving permalinks.
			return;
		}

		if ( function_exists( 'switch_to_locale' ) ) {
			switch_to_locale( get_locale() );
		}

		$permalink_settings = WP_event_Manager_Post_Types::get_raw_permalink_settings();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WP core handles nonce check for settings save.
		$permalink_settings['event_base']      = isset( $_POST['wpjm_event_base_slug'] ) ? sanitize_title_with_dashes( wp_unslash( $_POST['wpjm_event_base_slug'] ) ) : '';
		$permalink_settings['category_base'] = isset( $_POST['wpjm_event_category_slug'] ) ? sanitize_title_with_dashes( wp_unslash( $_POST['wpjm_event_category_slug'] ) ) : '';
		$permalink_settings['type_base']     = isset( $_POST['wpjm_event_type_slug'] ) ? sanitize_title_with_dashes( wp_unslash( $_POST['wpjm_event_type_slug'] ) ) : '';

		if ( isset( $_POST['wpjm_event_listings_archive_slug'] ) ) {
			$permalink_settings['events_archive'] = sanitize_title_with_dashes( wp_unslash( $_POST['wpjm_event_listings_archive_slug'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		update_option( WP_event_Manager_Post_Types::PERMALINK_OPTION_NAME, wp_json_encode( $permalink_settings ) );

		if ( function_exists( 'restore_current_locale' ) ) {
			restore_current_locale();
		}
	}
}

WP_event_Manager_Permalink_Settings::instance();
