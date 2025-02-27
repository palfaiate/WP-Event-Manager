<?php
/**
 * File containing the class WP_event_Manager_Admin.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles front admin page for WP Event Manager.
 *
 * @since 1.0.0
 */
class WP_event_Manager_Admin {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.26.0
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.26.0
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
		global $wp_version;

		include_once dirname( __FILE__ ) . '/class-wp-event-manager-admin-notices.php';
		include_once dirname( __FILE__ ) . '/class-wp-event-manager-cpt.php';
		WP_event_Manager_CPT::instance();

		include_once dirname( __FILE__ ) . '/class-wp-event-manager-settings.php';
		include_once dirname( __FILE__ ) . '/class-wp-event-manager-writepanels.php';
		include_once dirname( __FILE__ ) . '/class-wp-event-manager-setup.php';

		$this->settings_page = WP_event_Manager_Settings::instance();

		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'current_screen', [ $this, 'conditional_includes' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 12 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Set up actions during admin initialization.
	 */
	public function admin_init() {
		include_once dirname( __FILE__ ) . '/class-wp-event-manager-taxonomy-meta.php';
	}

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		switch ( $screen->id ) {
			case 'options-permalink':
				include 'class-wp-event-manager-permalink-settings.php';
				break;
		}
	}

	/**
	 * Enqueues CSS and JS assets.
	 */
	public function admin_enqueue_scripts() {
		WP_event_Manager::register_select2_assets();

		$screen = get_current_screen();
		if ( in_array( $screen->id, apply_filters( 'event_manager_admin_screen_ids', [ 'edit-event_listing', 'plugins', 'event_listing', 'event_listing_page_event-manager-settings', 'event_listing_page_event-manager-addons' ] ), true ) ) {
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'select2' );
			wp_enqueue_style( 'event_manager_admin_css', event_MANAGER_PLUGIN_URL . '/assets/css/admin.css', [], event_MANAGER_VERSION );
			wp_register_script( 'jquery-tiptip', event_MANAGER_PLUGIN_URL . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', [ 'jquery' ], event_MANAGER_VERSION, true );
			wp_enqueue_script( 'event_manager_datepicker_js', event_MANAGER_PLUGIN_URL . '/assets/js/datepicker.min.js', [ 'jquery', 'jquery-ui-datepicker' ], event_MANAGER_VERSION, true );
			wp_enqueue_script( 'event_manager_admin_js', event_MANAGER_PLUGIN_URL . '/assets/js/admin.min.js', [ 'jquery', 'jquery-tiptip', 'select2' ], event_MANAGER_VERSION, true );

			wp_localize_script(
				'event_manager_admin_js',
				'event_manager_admin_params',
				[
					'user_selection_strings' => [
						'no_matches'        => _x( 'No matches found', 'user selection', 'wp-event-manager' ),
						'ajax_error'        => _x( 'Loading failed', 'user selection', 'wp-event-manager' ),
						'input_too_short_1' => _x( 'Please enter 1 or more characters', 'user selection', 'wp-event-manager' ),
						'input_too_short_n' => _x( 'Please enter %qty% or more characters', 'user selection', 'wp-event-manager' ),
						'load_more'         => _x( 'Loading more results&hellip;', 'user selection', 'wp-event-manager' ),
						'searching'         => _x( 'Searching&hellip;', 'user selection', 'wp-event-manager' ),
					],
					'ajax_url'               => admin_url( 'admin-ajax.php' ),
					'search_users_nonce'     => wp_create_nonce( 'search-users' ),
				]
			);

			if ( ! function_exists( 'wp_localize_jquery_ui_datepicker' ) || ! has_action( 'admin_enqueue_scripts', 'wp_localize_jquery_ui_datepicker' ) ) {
				wp_localize_script(
					'event_manager_datepicker_js',
					'event_manager_datepicker',
					[
						/* translators: jQuery date format, see http://api.jqueryui.com/datepicker/#utility-formatDate */
						'date_format' => _x( 'yy-mm-dd', 'Date format for jQuery datepicker.', 'wp-event-manager' ),
					]
				);
			}
		}

		wp_enqueue_style( 'event_manager_admin_menu_css', event_MANAGER_PLUGIN_URL . '/assets/css/menu.css', [], event_MANAGER_VERSION );
	}

	/**
	 * Adds pages to admin menu.
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=event_listing', __( 'Settings', 'wp-event-manager' ), __( 'Settings', 'wp-event-manager' ), 'manage_options', 'event-manager-settings', [ $this->settings_page, 'output' ] );

		if ( WP_event_Manager_Helper::instance()->has_licenced_products() || apply_filters( 'event_manager_show_addons_page', true ) ) {
			add_submenu_page( 'edit.php?post_type=event_listing', __( 'WP Event Manager Add-ons', 'wp-event-manager' ), __( 'Add-ons', 'wp-event-manager' ), 'manage_options', 'event-manager-addons', [ $this, 'addons_page' ] );
		}
	}

	/**
	 * Displays addons page.
	 */
	public function addons_page() {
		$addons = include 'class-wp-event-manager-addons.php';
		$addons->output();
	}
}

WP_event_Manager_Admin::instance();
