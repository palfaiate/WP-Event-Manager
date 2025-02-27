<?php
/**
 * File containing the class WP_event_Manager.
 *
 * @package wp-event-manager
 * @since   1.33.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles core plugin hooks and action setup.
 *
 * @since 1.0.0
 */
class WP_event_Manager {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.26.0
	 */
	private static $instance = null;

	/**
	 * Main WP Event Manager Instance.
	 *
	 * Ensures only one instance of WP Event Manager is loaded or can be loaded.
	 *
	 * @since  1.26.0
	 * @static
	 * @see WPJM()
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
		// Includes.
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-install.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-post-types.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-ajax.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-shortcodes.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-api.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-forms.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-geocode.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-blocks.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-cache-helper.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/helper/class-wp-event-manager-helper.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/abstracts/abstract-wp-event-manager-email.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/abstracts/abstract-wp-event-manager-email-template.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-email-notifications.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-data-exporter.php';

		if ( is_admin() ) {
			include_once event_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-event-manager-admin.php';
		}

		// Load 3rd party customizations.
		include_once event_MANAGER_PLUGIN_DIR . '/includes/3rd-party/3rd-party.php';

		// Init classes.
		$this->forms      = WP_event_Manager_Forms::instance();
		$this->post_types = WP_event_Manager_Post_Types::instance();

		// Schedule cron events.
		self::maybe_schedule_cron_events();

		// Switch theme.
		add_action( 'after_switch_theme', [ 'WP_event_Manager_Ajax', 'add_endpoint' ], 10 );
		add_action( 'after_switch_theme', [ $this->post_types, 'register_post_types' ], 11 );
		add_action( 'after_switch_theme', 'flush_rewrite_rules', 15 );

		// Actions.
		add_action( 'after_setup_theme', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'after_setup_theme', [ $this, 'include_template_functions' ], 11 );
		add_action( 'widgets_init', [ $this, 'widgets_init' ] );
		add_action( 'wp_loaded', [ $this, 'register_shared_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ] );
		add_action( 'admin_init', [ $this, 'updater' ] );
		add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );
		add_action( 'wp_logout', [ $this, 'cleanup_event_posting_cookies' ] );
		add_action( 'init', [ 'WP_event_Manager_Email_Notifications', 'init' ] );
		add_action( 'rest_api_init', [ $this, 'rest_init' ] );

		// Filters.
		add_filter( 'wp_privacy_personal_data_exporters', [ 'WP_event_Manager_Data_Exporter', 'register_wpjm_user_data_exporter' ] );

		add_action( 'init', [ $this, 'usage_tracking_init' ] );

		// Defaults for WPJM core actions.
		add_action( 'wpjm_notify_new_user', 'wp_event_manager_notify_new_user', 10, 2 );
	}

	/**
	 * Performs plugin activation steps.
	 */
	public function activate() {
		WP_event_Manager_Ajax::add_endpoint();
		unregister_post_type( 'event_listing' );
		add_filter( 'pre_option_event_manager_enable_types', '__return_true' );
		$this->post_types->register_post_types();
		remove_filter( 'pre_option_event_manager_enable_types', '__return_true' );
		WP_event_Manager_Install::install();
		flush_rewrite_rules();
	}

	/**
	 * Handles tasks after plugin is updated.
	 */
	public function updater() {
		if ( version_compare( event_MANAGER_VERSION, get_option( 'wp_event_manager_version' ), '>' ) ) {
			WP_event_Manager_Install::install();

			flush_rewrite_rules();
		}
	}

	/**
	 * Adds Privacy Policy suggested content.
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			// translators: Placeholders %1$s and %2$s are the names of the two cookies used in WP Event Manager.
			__(
				'This site adds the following cookies to help users resume event submissions that they
				have started but have not completed: %1$s and %2$s',
				'wp-event-manager'
			),
			'<code>wp-event-manager-submitting-event-id</code>',
			'<code>wp-event-manager-submitting-event-key</code>'
		);

		wp_add_privacy_policy_content(
			'WP Event Manager',
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Loads textdomain for plugin.
	 */
	public function load_plugin_textdomain() {
		load_textdomain( 'wp-event-manager', WP_LANG_DIR . '/wp-event-manager/wp-event-manager-' . apply_filters( 'plugin_locale', get_locale(), 'wp-event-manager' ) . '.mo' );
		load_plugin_textdomain( 'wp-event-manager', false, event_MANAGER_PLUGIN_DIR . '/languages/' );
	}

	/**
	 * Loads plugin's core helper template functions.
	 */
	public function include_template_functions() {
		include_once event_MANAGER_PLUGIN_DIR . '/wp-event-manager-deprecated.php';
		include_once event_MANAGER_PLUGIN_DIR . '/wp-event-manager-functions.php';
		include_once event_MANAGER_PLUGIN_DIR . '/wp-event-manager-template.php';
	}

	/**
	 * Loads the REST API functionality.
	 */
	public function rest_init() {
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-rest-api.php';
		WP_event_Manager_REST_API::init();
	}

	/**
	 * Loads plugin's widgets.
	 */
	public function widgets_init() {
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-widget.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/widgets/class-wp-event-manager-widget-recent-events.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/widgets/class-wp-event-manager-widget-featured-events.php';
	}

	/**
	 * Initialize the Usage Tracking system.
	 */
	public function usage_tracking_init() {
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-usage-tracking.php';
		include_once event_MANAGER_PLUGIN_DIR . '/includes/class-wp-event-manager-usage-tracking-data.php';

		WP_event_Manager_Usage_Tracking::get_instance()->set_callback(
			[ 'WP_event_Manager_Usage_Tracking_Data', 'get_usage_data' ]
		);

		if ( is_admin() ) {
			WP_event_Manager_Usage_Tracking::get_instance()->schedule_tracking_task();
		}
	}

	/**
	 * Cleanup the Usage Tracking system for plugin deactivation.
	 */
	public function usage_tracking_cleanup() {
		WP_event_Manager_Usage_Tracking::get_instance()->unschedule_tracking_task();
	}

	/**
	 * Schedule cron events for WPJM events.
	 */
	public static function maybe_schedule_cron_events() {
		if ( ! wp_next_scheduled( 'event_manager_check_for_expired_events' ) ) {
			wp_schedule_event( time(), 'hourly', 'event_manager_check_for_expired_events' );
		}
		if ( ! wp_next_scheduled( 'event_manager_delete_old_previews' ) ) {
			wp_schedule_event( time(), 'daily', 'event_manager_delete_old_previews' );
		}
		if ( ! wp_next_scheduled( 'event_manager_email_daily_notices' ) ) {
			wp_schedule_event( time(), 'daily', 'event_manager_email_daily_notices' );
		}
	}

	/**
	 * Unschedule cron events. This is run on plugin deactivation.
	 */
	public static function unschedule_cron_events() {
		wp_clear_scheduled_hook( 'event_manager_check_for_expired_events' );
		wp_clear_scheduled_hook( 'event_manager_delete_old_previews' );
		wp_clear_scheduled_hook( 'event_manager_email_daily_notices' );
	}

	/**
	 * Cleanup event posting cookies.
	 */
	public function cleanup_event_posting_cookies() {
		if ( isset( $_COOKIE['wp-event-manager-submitting-event-id'] ) ) {
			setcookie( 'wp-event-manager-submitting-event-id', '', 0, COOKIEPATH, COOKIE_DOMAIN, false );
		}
		if ( isset( $_COOKIE['wp-event-manager-submitting-event-key'] ) ) {
			setcookie( 'wp-event-manager-submitting-event-key', '', 0, COOKIEPATH, COOKIE_DOMAIN, false );
		}
	}

	/**
	 * Registers assets used in both the frontend and WP admin.
	 */
	public function register_shared_assets() {
		global $wp_scripts;

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
		wp_register_style( 'jquery-ui', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.min.css', [], $jquery_version );
	}

	/**
	 * Registers select2 assets when needed.
	 */
	public static function register_select2_assets() {
		wp_register_script( 'select2', event_MANAGER_PLUGIN_URL . '/assets/js/select2/select2.full.min.js', [ 'jquery' ], '4.0.5', false );
		wp_register_style( 'select2', event_MANAGER_PLUGIN_URL . '/assets/js/select2/select2.min.css', [], '4.0.5' );
	}

	/**
	 * Registers and enqueues scripts and CSS.
	 *
	 * Note: For enhanced select, 1.32.0 moved to Select2. Chosen is currently packaged but will be removed in an
	 * upcoming release.
	 */
	public function frontend_scripts() {
		$ajax_url         = WP_event_Manager_Ajax::get_endpoint();
		$ajax_filter_deps = [ 'jquery', 'jquery-deserialize' ];
		$ajax_data        = [
			'ajax_url'                => $ajax_url,
			'is_rtl'                  => is_rtl() ? 1 : 0,
			'i18n_load_prev_listings' => __( 'Load previous listings', 'wp-event-manager' ),
		];

		/**
		 * Retrieves the current language for use when caching requests.
		 *
		 * @since 1.26.0
		 *
		 * @param string|null $lang
		 */
		$ajax_data['lang'] = apply_filters( 'wpjm_lang', null );

		$enhanced_select_shortcodes   = [ 'submit_event_form', 'event_dashboard', 'events' ];
		$enhanced_select_used_on_page = has_wpjm_shortcode( null, $enhanced_select_shortcodes );

		/**
		 * Set the constant `event_MANAGER_DISABLE_CHOSEN_LEGACY_COMPAT` to true to test for future behavior once
		 * this legacy code is removed and `chosen` is no longer packaged with the plugin.
		 */
		if ( ! defined( 'event_MANAGER_DISABLE_CHOSEN_LEGACY_COMPAT' ) || ! event_MANAGER_DISABLE_CHOSEN_LEGACY_COMPAT ) {
			if ( is_wpjm_taxonomy() || is_wpjm_event_listing() || is_wpjm_page() ) {
				$enhanced_select_used_on_page = true;
			}

			// Register the script for dependencies that still require it.
			if ( ! wp_script_is( 'chosen', 'registered' ) ) {
				wp_register_script( 'chosen', event_MANAGER_PLUGIN_URL . '/assets/js/jquery-chosen/chosen.jquery.min.js', [ 'jquery' ], '1.1.0', true );
				wp_register_style( 'chosen', event_MANAGER_PLUGIN_URL . '/assets/css/chosen.css', [], '1.1.0' );
			}

			// Backwards compatibility for third-party themes/plugins while they transition to Select2.
			wp_localize_script(
				'chosen',
				'event_manager_chosen_multiselect_args',
				apply_filters(
					'event_manager_chosen_multiselect_args',
					[
						'search_contains' => true,
					]
				)
			);

			/**
			 * Filter the use of the deprecated chosen library. Themes and plugins should migrate to Select2. This will be
			 * removed in an upcoming major release.
			 *
			 * @since 1.19.0
			 * @deprecated 1.32.0 Migrate to event_manager_select2_enabled and enable only on pages that need it.
			 *
			 * @param bool $chosen_used_on_page
			 */
			if ( apply_filters( 'event_manager_chosen_enabled', false ) ) {
				_deprecated_hook( 'event_manager_chosen_enabled', '1.32.0', 'event_manager_select2_enabled' );

				// Assume if this filter returns true that the current page should have the multi-select scripts.
				$enhanced_select_used_on_page = true;

				wp_enqueue_script( 'chosen' );
				wp_enqueue_style( 'chosen' );
			}
		}

		/**
		 * Filter the use of the enhanced select.
		 *
		 * Note: Don't depend on `select2` being registered/enqueued in customizations.
		 *
		 * @since 1.32.0
		 *
		 * @param bool $enhanced_select_used_on_page Defaults to only when there are known shortcodes on the page.
		 */
		if ( apply_filters( 'event_manager_enhanced_select_enabled', $enhanced_select_used_on_page ) ) {
			self::register_select2_assets();
			wp_register_script( 'wp-event-manager-term-multiselect', event_MANAGER_PLUGIN_URL . '/assets/js/term-multiselect.min.js', [ 'jquery', 'select2' ], event_MANAGER_VERSION, true );
			wp_register_script( 'wp-event-manager-multiselect', event_MANAGER_PLUGIN_URL . '/assets/js/multiselect.min.js', [ 'jquery', 'select2' ], event_MANAGER_VERSION, true );
			wp_enqueue_style( 'select2' );

			$ajax_filter_deps[] = 'select2';

			$select2_args = [];
			if ( is_rtl() ) {
				$select2_args['dir'] = 'rtl';
			}

			$select2_args['width'] = '100%';

			wp_localize_script(
				'select2',
				'event_manager_select2_args',
				apply_filters( 'event_manager_select2_args', $select2_args )
			);
		}

		if ( event_manager_user_can_upload_file_via_ajax() ) {
			wp_register_script( 'jquery-iframe-transport', event_MANAGER_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.iframe-transport.js', array( 'jquery' ), '10.1.0', true );
			wp_register_script( 'jquery-fileupload', event_MANAGER_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.fileupload.js', array( 'jquery', 'jquery-iframe-transport', 'jquery-ui-widget' ), '10.1.0', true );
			wp_register_script( 'wp-event-manager-ajax-file-upload', event_MANAGER_PLUGIN_URL . '/assets/js/ajax-file-upload.min.js', array( 'jquery', 'jquery-fileupload' ), event_MANAGER_VERSION, true );

			ob_start();
			get_event_manager_template(
				'form-fields/uploaded-file-html.php',
				[
					'name'      => '',
					'value'     => '',
					'extension' => 'jpg',
				]
			);
			$js_field_html_img = ob_get_clean();

			ob_start();
			get_event_manager_template(
				'form-fields/uploaded-file-html.php',
				[
					'name'      => '',
					'value'     => '',
					'extension' => 'zip',
				]
			);
			$js_field_html = ob_get_clean();

			wp_localize_script(
				'wp-event-manager-ajax-file-upload',
				'event_manager_ajax_file_upload',
				[
					'ajax_url'               => $ajax_url,
					'js_field_html_img'      => esc_js( str_replace( "\n", '', $js_field_html_img ) ),
					'js_field_html'          => esc_js( str_replace( "\n", '', $js_field_html ) ),
					'i18n_invalid_file_type' => esc_html__( 'Invalid file type. Accepted types:', 'wp-event-manager' ),
				]
			);
		}

		wp_register_script( 'jquery-deserialize', event_MANAGER_PLUGIN_URL . '/assets/js/jquery-deserialize/jquery.deserialize.js', [ 'jquery' ], '1.2.1', true );
		wp_register_script( 'wp-event-manager-ajax-filters', event_MANAGER_PLUGIN_URL . '/assets/js/ajax-filters.min.js', $ajax_filter_deps, event_MANAGER_VERSION, true );
		wp_register_script( 'wp-event-manager-event-dashboard', event_MANAGER_PLUGIN_URL . '/assets/js/event-dashboard.min.js', [ 'jquery' ], event_MANAGER_VERSION, true );
		wp_register_script( 'wp-event-manager-event-application', event_MANAGER_PLUGIN_URL . '/assets/js/event-application.min.js', [ 'jquery' ], event_MANAGER_VERSION, true );
		wp_register_script( 'wp-event-manager-event-submission', event_MANAGER_PLUGIN_URL . '/assets/js/event-submission.min.js', [ 'jquery' ], event_MANAGER_VERSION, true );
		wp_localize_script( 'wp-event-manager-ajax-filters', 'event_manager_ajax_filters', $ajax_data );

		wp_localize_script(
			'wp-event-manager-event-submission',
			'event_manager_event_submission',
			[
				// translators: Placeholder %d is the number of files to that users are limited to.
				'i18n_over_upload_limit' => esc_html__( 'You are only allowed to upload a maximum of %d files.', 'wp-event-manager' ),
			]
		);

		wp_localize_script(
			'wp-event-manager-event-dashboard',
			'event_manager_event_dashboard',
			[
				'i18n_confirm_delete' => esc_html__( 'Are you sure you want to delete this listing?', 'wp-event-manager' ),
			]
		);

		wp_localize_script(
			'wp-event-manager-event-submission',
			'event_manager_event_submission',
			[
				'i18n_required_field' => __( 'This field is required.', 'wp-event-manager' ),
			]
		);

		/**
		 * Filter whether to enqueue WPJM core's frontend scripts. By default, they will only be enqueued on WPJM related
		 * pages.
		 *
		 * If your theme or plugin depend on `frontend.css` from WPJM core, you can use the
		 * `event_manager_enqueue_frontend_style` filter.
		 *
		 * Example code for a custom shortcode that depends on the frontend style:
		 *
		 * add_filter( 'event_manager_enqueue_frontend_style', function( $frontend_used_on_page ) {
		 *   global $post;
		 *   if ( is_singular()
		 *        && is_a( $post, 'WP_Post' )
		 *        && has_shortcode( $post->post_content, 'resumes' )
		 *   ) {
		 *     $frontend_used_on_page = true;
		 *   }
		 *   return $frontend_used_on_page;
		 * } );
		 *
		 * @since 1.30.0
		 *
		 * @param bool $is_frontend_style_enabled
		 */
		if ( apply_filters( 'event_manager_enqueue_frontend_style', is_wpjm() ) ) {
			wp_enqueue_style( 'wp-event-manager-frontend', event_MANAGER_PLUGIN_URL . '/assets/css/frontend.css', [], event_MANAGER_VERSION );
		} else {
			wp_register_style( 'wp-event-manager-event-listings', event_MANAGER_PLUGIN_URL . '/assets/css/event-listings.css', [], event_MANAGER_VERSION );
		}
	}
}
