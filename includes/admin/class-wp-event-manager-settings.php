<?php
/**
 * File containing the class WP_event_Manager_Settings.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the management of plugin settings.
 *
 * @since 1.0.0
 */
class WP_event_Manager_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.26.0
	 */
	private static $instance = null;

	/**
	 * Our Settings.
	 *
	 * @var array Settings.
	 */
	protected $settings = [];

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
		$this->settings_group = 'event_manager';
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Get event Manager Settings
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( 0 === count( $this->settings ) ) {
			$this->init_settings();
		}
		return $this->settings;
	}

	/**
	 * Initializes the configuration for the plugin's setting fields.
	 *
	 * @access protected
	 */
	protected function init_settings() {
		// Prepare roles option.
		$roles         = get_editable_roles();
		$account_roles = [];

		foreach ( $roles as $key => $role ) {
			if ( 'administrator' === $key ) {
				continue;
			}
			$account_roles[ $key ] = $role['name'];
		}

		$this->settings = apply_filters(
			'event_manager_settings',
			[
				'general'        => [
					__( 'General', 'wp-event-manager' ),
					[
						[
							'name'    => 'event_manager_date_format',
							'std'     => 'relative',
							'label'   => __( 'Date Format', 'wp-event-manager' ),
							'desc'    => __( 'Choose how you want the published date for events to be displayed on the front-end.', 'wp-event-manager' ),
							'type'    => 'radio',
							'options' => [
								'relative' => __( 'Relative to the current date (e.g., 1 day, 1 week, 1 month ago)', 'wp-event-manager' ),
								'default'  => __( 'Default date format as defined in Settings', 'wp-event-manager' ),
							],
						],
						[
							'name'       => 'event_manager_google_maps_api_key',
							'std'        => '',
							'label'      => __( 'Google Maps API Key', 'wp-event-manager' ),
							// translators: Placeholder %s is URL to set up a Google Maps API key.
							'desc'       => sprintf( __( 'Google requires an API key to retrieve location information for event listings. Acquire an API key from the <a href="%s">Google Maps API developer site</a>.', 'wp-event-manager' ), 'https://developers.google.com/maps/documentation/geocoding/get-api-key' ),
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_delete_data_on_uninstall',
							'std'        => '0',
							'label'      => __( 'Delete Data On Uninstall', 'wp-event-manager' ),
							'cb_label'   => __( 'Delete WP Event Manager data when the plugin is deleted. Once removed, this data cannot be restored.', 'wp-event-manager' ),
							'desc'       => '',
							'type'       => 'checkbox',
							'attributes' => [],
						],
					],
				],
				'event_listings'   => [
					__( 'event Listings', 'wp-event-manager' ),
					[
						[
							'name'        => 'event_manager_per_page',
							'std'         => '10',
							'placeholder' => '',
							'label'       => __( 'Listings Per Page', 'wp-event-manager' ),
							'desc'        => __( 'Number of event listings to display per page.', 'wp-event-manager' ),
							'attributes'  => [],
						],
						[
							'name'       => 'event_manager_hide_filled_positions',
							'std'        => '0',
							'label'      => __( 'Filled Positions', 'wp-event-manager' ),
							'cb_label'   => __( 'Hide filled positions', 'wp-event-manager' ),
							'desc'       => __( 'Filled positions will not display in your archives.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_hide_expired',
							'std'        => get_option( 'event_manager_hide_expired_content' ) ? '1' : '0', // back compat.
							'label'      => __( 'Hide Expired Listings', 'wp-event-manager' ),
							'cb_label'   => __( 'Hide expired listings in event archives/search', 'wp-event-manager' ),
							'desc'       => __( 'Expired event listings will not be searchable.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_hide_expired_content',
							'std'        => '1',
							'label'      => __( 'Hide Expired Listings Content', 'wp-event-manager' ),
							'cb_label'   => __( 'Hide content in expired single event listings', 'wp-event-manager' ),
							'desc'       => __( 'Your site will display the titles of expired listings, but not the content of the listings. Otherwise, expired listings display their full content minus the application area.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_enable_categories',
							'std'        => '0',
							'label'      => __( 'Categories', 'wp-event-manager' ),
							'cb_label'   => __( 'Enable listing categories', 'wp-event-manager' ),
							'desc'       => __( 'This lets users select from a list of categories when submitting a event. Note: an admin has to create categories before site users can select them.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_enable_default_category_multiselect',
							'std'        => '0',
							'label'      => __( 'Multi-select Categories', 'wp-event-manager' ),
							'cb_label'   => __( 'Default to category multiselect', 'wp-event-manager' ),
							'desc'       => __( 'The category selection box will default to allowing multiple selections on the [events] shortcode. Without this, visitors will only be able to select a single category when filtering events.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'    => 'event_manager_category_filter_type',
							'std'     => 'any',
							'label'   => __( 'Category Filter Type', 'wp-event-manager' ),
							'desc'    => __( 'Determines the logic used to display events when selecting multiple categories.', 'wp-event-manager' ),
							'type'    => 'radio',
							'options' => [
								'any' => __( 'events will be shown if within ANY selected category', 'wp-event-manager' ),
								'all' => __( 'events will be shown if within ALL selected categories', 'wp-event-manager' ),
							],
						],
						[
							'name'       => 'event_manager_enable_types',
							'std'        => '1',
							'label'      => __( 'Types', 'wp-event-manager' ),
							'cb_label'   => __( 'Enable listing types', 'wp-event-manager' ),
							'desc'       => __( 'This lets users select from a list of types when submitting a event. Note: an admin has to create types before site users can select them.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_multi_event_type',
							'std'        => '0',
							'label'      => __( 'Multi-select Listing Types', 'wp-event-manager' ),
							'cb_label'   => __( 'Allow multiple types for listings', 'wp-event-manager' ),
							'desc'       => __( 'This allows users to select more than one type when submitting a event. The metabox on the post editor and the selection box on the front-end event submission form will both reflect this.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
					],
				],
				'event_submission' => [
					__( 'event Submission', 'wp-event-manager' ),
					[
						[
							'name'       => 'event_manager_user_requires_account',
							'std'        => '1',
							'label'      => __( 'Account Required', 'wp-event-manager' ),
							'cb_label'   => __( 'Require an account to submit listings', 'wp-event-manager' ),
							'desc'       => __( 'Limits event listing submissions to registered, logged-in users.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_enable_registration',
							'std'        => '1',
							'label'      => __( 'Account Creation', 'wp-event-manager' ),
							'cb_label'   => __( 'Enable account creation during submission', 'wp-event-manager' ),
							'desc'       => __( 'Includes account creation on the listing submission form, to allow non-registered users to create an account and submit a event listing simultaneously.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_generate_username_from_email',
							'std'        => '1',
							'label'      => __( 'Account Username', 'wp-event-manager' ),
							'cb_label'   => __( 'Generate usernames from email addresses', 'wp-event-manager' ),
							'desc'       => __( 'Automatically generates usernames for new accounts from the registrant\'s email address. If this is not enabled, a "username" field will display instead.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_use_standard_password_setup_email',
							'std'        => '1',
							'label'      => __( 'Account Password', 'wp-event-manager' ),
							'cb_label'   => __( 'Email new users a link to set a password', 'wp-event-manager' ),
							'desc'       => __( 'Sends an email to the user with their username and a link to set their password. If this is not enabled, a "password" field will display instead, and their email address won\'t be verified.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'    => 'event_manager_registration_role',
							'std'     => 'employer',
							'label'   => __( 'Account Role', 'wp-event-manager' ),
							'desc'    => __( 'Any new accounts created during submission will have this role. If you haven\'t enabled account creation during submission in the options above, your own method of assigning roles will apply.', 'wp-event-manager' ),
							'type'    => 'select',
							'options' => $account_roles,
						],
						[
							'name'       => 'event_manager_submission_requires_approval',
							'std'        => '1',
							'label'      => __( 'Moderate New Listings', 'wp-event-manager' ),
							'cb_label'   => __( 'Require admin approval of all new listing submissions', 'wp-event-manager' ),
							'desc'       => __( 'Sets all new submissions to "pending." They will not appear on your site until an admin approves them.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_user_can_edit_pending_submissions',
							'std'        => '0',
							'label'      => __( 'Allow Pending Edits', 'wp-event-manager' ),
							'cb_label'   => __( 'Allow editing of pending listings', 'wp-event-manager' ),
							'desc'       => __( 'Users can continue to edit pending listings until they are approved by an admin.', 'wp-event-manager' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_user_edit_published_submissions',
							'std'        => 'yes',
							'label'      => __( 'Allow Published Edits', 'wp-event-manager' ),
							'cb_label'   => __( 'Allow editing of published listings', 'wp-event-manager' ),
							'desc'       => __( 'Choose whether published event listings can be edited and if edits require admin approval. When moderation is required, the original event listings will be unpublished while edits await admin approval.', 'wp-event-manager' ),
							'type'       => 'radio',
							'options'    => [
								'no'            => __( 'Users cannot edit', 'wp-event-manager' ),
								'yes'           => __( 'Users can edit without admin approval', 'wp-event-manager' ),
								'yes_moderated' => __( 'Users can edit, but edits require admin approval', 'wp-event-manager' ),
							],
							'attributes' => [],
						],
						[
							'name'       => 'event_manager_submission_duration',
							'std'        => '30',
							'label'      => __( 'Listing Duration', 'wp-event-manager' ),
							'desc'       => __( 'Listings will display for the set number of days, then expire. Leave this field blank if you don\'t want listings to have an expiration date.', 'wp-event-manager' ),
							'attributes' => [],
						],
						[
							'name'    => 'event_manager_allowed_application_method',
							'std'     => '',
							'label'   => __( 'Application Method', 'wp-event-manager' ),
							'desc'    => __( 'Choose the application method event listers will need to provide. Specify URL or email address only, or allow listers to choose which they prefer.', 'wp-event-manager' ),
							'type'    => 'radio',
							'options' => [
								''      => __( 'Email address or website URL', 'wp-event-manager' ),
								'email' => __( 'Email addresses only', 'wp-event-manager' ),
								'url'   => __( 'Website URLs only', 'wp-event-manager' ),
							],
						],
					],
				],
				'recaptcha'      => [
					__( 'reCAPTCHA', 'wp-event-manager' ),
					[
						[
							'name'        => 'event_manager_recaptcha_label',
							'std'         => __( 'Are you human?', 'wp-event-manager' ),
							'placeholder' => '',
							'label'       => __( 'Field Label', 'wp-event-manager' ),
							'desc'        => __( 'The label used for the reCAPTCHA field on forms.', 'wp-event-manager' ),
							'attributes'  => [],
						],
						[
							'name'        => 'event_manager_recaptcha_site_key',
							'std'         => '',
							'placeholder' => '',
							'label'       => __( 'Site Key', 'wp-event-manager' ),
							// translators: Placeholder %s is URL to set up Google reCAPTCHA API key.
							'desc'        => sprintf( __( 'You can retrieve your site key from <a href="%s">Google\'s reCAPTCHA admin dashboard</a>.', 'wp-event-manager' ), 'https://www.google.com/recaptcha/admin#list' ),
							'attributes'  => [],
						],
						[
							'name'        => 'event_manager_recaptcha_secret_key',
							'std'         => '',
							'placeholder' => '',
							'label'       => __( 'Secret Key', 'wp-event-manager' ),
							// translators: Placeholder %s is URL to set up Google reCAPTCHA API key.
							'desc'        => sprintf( __( 'You can retrieve your secret key from <a href="%s">Google\'s reCAPTCHA admin dashboard</a>.', 'wp-event-manager' ), 'https://www.google.com/recaptcha/admin#list' ),
							'attributes'  => [],
						],
						[
							'name'       => 'event_manager_enable_recaptcha_event_submission',
							'std'        => '0',
							'label'      => __( 'event Submission Form', 'wp-event-manager' ),
							'cb_label'   => __( 'Display a reCAPTCHA field on event submission form.', 'wp-event-manager' ),
							'desc'       => sprintf( __( 'This will help prevent bots from submitting event listings. You must have entered a valid site key and secret key above.', 'wp-event-manager' ), 'https://www.google.com/recaptcha/admin#list' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
					],
				],
				'event_pages'      => [
					__( 'Pages', 'wp-event-manager' ),
					[
						[
							'name'  => 'event_manager_submit_event_form_page_id',
							'std'   => '',
							'label' => __( 'Submit event Form Page', 'wp-event-manager' ),
							'desc'  => __( 'Select the page where you\'ve used the [submit_event_form] shortcode. This lets the plugin know the location of the form.', 'wp-event-manager' ),
							'type'  => 'page',
						],
						[
							'name'  => 'event_manager_event_dashboard_page_id',
							'std'   => '',
							'label' => __( 'event Dashboard Page', 'wp-event-manager' ),
							'desc'  => __( 'Select the page where you\'ve used the [event_dashboard] shortcode. This lets the plugin know the location of the dashboard.', 'wp-event-manager' ),
							'type'  => 'page',
						],
						[
							'name'  => 'event_manager_events_page_id',
							'std'   => '',
							'label' => __( 'event Listings Page', 'wp-event-manager' ),
							'desc'  => __( 'Select the page where you\'ve used the [events] shortcode. This lets the plugin know the location of the event listings page.', 'wp-event-manager' ),
							'type'  => 'page',
						],
					],
				],
			]
		);
	}

	/**
	 * Registers the plugin's settings with WordPress's Settings API.
	 */
	public function register_settings() {
		$this->init_settings();

		foreach ( $this->settings as $section ) {
			foreach ( $section[1] as $option ) {
				if ( isset( $option['std'] ) ) {
					add_option( $option['name'], $option['std'] );
				}
				register_setting( $this->settings_group, $option['name'] );
			}
		}
	}

	/**
	 * Shows the plugin's settings page.
	 */
	public function output() {
		$this->init_settings();
		?>
		<div class="wrap event-manager-settings-wrap">
			<form class="event-manager-options" method="post" action="options.php">

				<?php settings_fields( $this->settings_group ); ?>

				<h2 class="nav-tab-wrapper">
					<?php
					foreach ( $this->settings as $key => $section ) {
						echo '<a href="#settings-' . esc_attr( sanitize_title( $key ) ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
					}
					?>
				</h2>

				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used for basic flow.
				if ( ! empty( $_GET['settings-updated'] ) ) {
					flush_rewrite_rules();
					echo '<div class="updated fade event-manager-updated"><p>' . esc_html__( 'Settings successfully saved', 'wp-event-manager' ) . '</p></div>';
				}

				foreach ( $this->settings as $key => $section ) {
					$section_args = isset( $section[2] ) ? (array) $section[2] : [];
					echo '<div id="settings-' . esc_attr( sanitize_title( $key ) ) . '" class="settings_panel">';
					if ( ! empty( $section_args['before'] ) ) {
						echo '<p class="before-settings">' . wp_kses_post( $section_args['before'] ) . '</p>';
					}
					echo '<table class="form-table settings parent-settings">';

					foreach ( $section[1] as $option ) {
						$value = get_option( $option['name'] );
						$this->output_field( $option, $value );
					}

					echo '</table>';
					if ( ! empty( $section_args['after'] ) ) {
						echo '<p class="after-settings">' . wp_kses_post( $section_args['after'] ) . '</p>';
					}
					echo '</div>';

				}
				?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'wp-event-manager' ); ?>" />
				</p>
			</form>
		</div>
		<script type="text/javascript">
			jQuery('.nav-tab-wrapper a').click(function() {
				if ( '#' !== jQuery(this).attr( 'href' ).substr( 0, 1 ) ) {
					return false;
				}
				jQuery('.settings_panel').hide();
				jQuery('.nav-tab-active').removeClass('nav-tab-active');
				jQuery( jQuery(this).attr('href') ).show();
				jQuery(this).addClass('nav-tab-active');
				window.location.hash = jQuery(this).attr('href');
				jQuery( 'form.event-manager-options' ).attr( 'action', 'options.php' + jQuery(this).attr( 'href' ) );
				window.scrollTo( 0, 0 );
				return false;
			});
			var goto_hash = window.location.hash;
			if ( '#' === goto_hash.substr( 0, 1 ) ) {
				jQuery( 'form.event-manager-options' ).attr( 'action', 'options.php' + jQuery(this).attr( 'href' ) );
			}
			if ( goto_hash ) {
				var the_tab = jQuery( 'a[href="' + goto_hash + '"]' );
				if ( the_tab.length > 0 ) {
					the_tab.click();
				} else {
					jQuery( '.nav-tab-wrapper a:first' ).click();
				}
			} else {
				jQuery( '.nav-tab-wrapper a:first' ).click();
			}
			var $use_standard_password_setup_email = jQuery('#setting-event_manager_use_standard_password_setup_email');
			var $generate_username_from_email = jQuery('#setting-event_manager_generate_username_from_email');
			var $event_manager_registration_role = jQuery('#setting-event_manager_registration_role');

			jQuery('#setting-event_manager_enable_registration').change(function(){
				if ( jQuery( this ).is(':checked') ) {
					$event_manager_registration_role.closest('tr').show();
					$use_standard_password_setup_email.closest('tr').show();
					$generate_username_from_email.closest('tr').show();
				} else {
					$event_manager_registration_role.closest('tr').hide();
					$use_standard_password_setup_email.closest('tr').hide();
					$generate_username_from_email.closest('tr').hide();
				}
			}).change();

			jQuery( '.sub-settings-expander' ).on( 'change', function() {
				var $expandable = jQuery(this).parent().siblings( '.sub-settings-expandable' );
				var checked = jQuery(this).is( ':checked' );
				if ( checked ) {
					$expandable.addClass( 'expanded' );
				} else {
					$expandable.removeClass( 'expanded' );
				}
			} ).trigger( 'change' );
		</script>
		<?php
	}

	/**
	 * Checkbox input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $ignored_placeholder
	 */
	protected function input_checkbox( $option, $attributes, $value, $ignored_placeholder ) {
		?>
		<label>
		<input type="hidden" name="<?php echo esc_attr( $option['name'] ); ?>" value="0" />
		<input
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			name="<?php echo esc_attr( $option['name'] ); ?>"
			type="checkbox"
			value="1"
			<?php
			echo implode( ' ', $attributes ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			checked( '1', $value );
			?>
		/> <?php echo wp_kses_post( $option['cb_label'] ); ?></label>
		<?php
		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Text area input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $placeholder
	 */
	protected function input_textarea( $option, $attributes, $value, $placeholder ) {
		?>
		<textarea
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			class="large-text"
			cols="50"
			rows="3"
			name="<?php echo esc_attr( $option['name'] ); ?>"
			<?php
			echo implode( ' ', $attributes ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		>
			<?php echo esc_textarea( $value ); ?>
		</textarea>
		<?php

		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Select input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $ignored_placeholder
	 */
	protected function input_select( $option, $attributes, $value, $ignored_placeholder ) {
		?>
		<select
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			class="regular-text"
			name="<?php echo esc_attr( $option['name'] ); ?>"
			<?php
			echo implode( ' ', $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		>
		<?php
		foreach ( $option['options'] as $key => $name ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $name ) . '</option>';
		}
		?>
		</select>
		<?php

		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Radio input field.
	 *
	 * @param array  $option
	 * @param array  $ignored_attributes
	 * @param mixed  $value
	 * @param string $ignored_placeholder
	 */
	protected function input_radio( $option, $ignored_attributes, $value, $ignored_placeholder ) {
		?>
		<fieldset>
		<legend class="screen-reader-text">
		<span><?php echo esc_html( $option['label'] ); ?></span>
		</legend>
		<?php
		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}

		foreach ( $option['options'] as $key => $name ) {
			echo '<label><input name="' . esc_attr( $option['name'] ) . '" type="radio" value="' . esc_attr( $key ) . '" ' . checked( $value, $key, false ) . ' />' . esc_html( $name ) . '</label><br>';
		}
		?>
		</fieldset>
		<?php
	}

	/**
	 * Page input field.
	 *
	 * @param array  $option
	 * @param array  $ignored_attributes
	 * @param mixed  $value
	 * @param string $ignored_placeholder
	 */
	protected function input_page( $option, $ignored_attributes, $value, $ignored_placeholder ) {
		$args = [
			'name'             => $option['name'],
			'id'               => $option['name'],
			'sort_column'      => 'menu_order',
			'sort_order'       => 'ASC',
			'show_option_none' => __( '--no page--', 'wp-event-manager' ),
			'echo'             => false,
			'selected'         => absint( $value ),
		];

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe output.
		echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'wp-event-manager' ) . "' id=", wp_dropdown_pages( $args ) );

		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Hidden input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $ignored_placeholder
	 */
	protected function input_hidden( $option, $attributes, $value, $ignored_placeholder ) {
		$human_value = $value;
		if ( $option['human_value'] ) {
			$human_value = $option['human_value'];
		}
		?>
		<input
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			type="hidden"
			name="<?php echo esc_attr( $option['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php
			echo implode( ' ', $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		/><strong><?php echo esc_html( $human_value ); ?></strong>
		<?php

		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Password input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $placeholder
	 */
	protected function input_password( $option, $attributes, $value, $placeholder ) {
		?>
		<input
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			class="regular-text"
			type="password"
			name="<?php echo esc_attr( $option['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php
			echo implode( ' ', $attributes ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		/>
		<?php

		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Number input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $placeholder
	 */
	protected function input_number( $option, $attributes, $value, $placeholder ) {
		echo isset( $option['before'] ) ? wp_kses_post( $option['before'] ) : '';
		?>
		<input
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			class="small-text"
			type="number"
			name="<?php echo esc_attr( $option['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php
			echo implode( ' ', $attributes ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		/>
		<?php
		echo isset( $option['after'] ) ? wp_kses_post( $option['after'] ) : '';
		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Text input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $placeholder
	 */
	protected function input_text( $option, $attributes, $value, $placeholder ) {
		?>
		<input
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			class="regular-text"
			type="text"
			name="<?php echo esc_attr( $option['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php
			echo implode( ' ', $attributes ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		/>
		<?php

		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Outputs the field row.
	 *
	 * @param array $option
	 * @param mixed $value
	 */
	protected function output_field( $option, $value ) {
		$placeholder    = ( ! empty( $option['placeholder'] ) ) ? 'placeholder="' . esc_attr( $option['placeholder'] ) . '"' : '';
		$class          = ! empty( $option['class'] ) ? $option['class'] : '';
		$option['type'] = ! empty( $option['type'] ) ? $option['type'] : 'text';
		$attributes     = [];
		if ( ! empty( $option['attributes'] ) && is_array( $option['attributes'] ) ) {
			foreach ( $option['attributes'] as $attribute_name => $attribute_value ) {
				$attributes[] = esc_attr( $attribute_name ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		echo '<tr valign="top" class="' . esc_attr( $class ) . '">';

		if ( ! empty( $option['label'] ) ) {
			echo '<th scope="row"><label for="setting-' . esc_attr( $option['name'] ) . '">' . esc_html( $option['label'] ) . '</a></th><td>';
		} else {
			echo '<td colspan="2">';
		}

		$method_name = 'input_' . $option['type'];
		if ( method_exists( $this, $method_name ) ) {
			$this->$method_name( $option, $attributes, $value, $placeholder );
		} else {
			/**
			 * Allows for custom fields in admin setting panes.
			 *
			 * @since 1.14.0
			 *
			 * @param string $option     Field name.
			 * @param array  $attributes Array of attributes.
			 * @param mixed  $value      Field value.
			 * @param string $value      Placeholder text.
			 */
			do_action( 'wp_event_manager_admin_field_' . $option['type'], $option, $attributes, $value, $placeholder );
		}
		echo '</td></tr>';
	}

	/**
	 * Multiple settings stored in one setting array that are shown when the `enable` setting is checked.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param array  $values
	 * @param string $placeholder
	 */
	protected function input_multi_enable_expand( $option, $attributes, $values, $placeholder ) {
		echo '<div class="setting-enable-expand">';
		$enable_option               = $option['enable_field'];
		$enable_option['name']       = $option['name'] . '[' . $enable_option['name'] . ']';
		$enable_option['type']       = 'checkbox';
		$enable_option['attributes'] = [ 'class="sub-settings-expander"' ];
		$this->input_checkbox( $enable_option, $enable_option['attributes'], $values[ $option['enable_field']['name'] ], null );

		echo '<div class="sub-settings-expandable">';
		$this->input_multi( $option, $attributes, $values, $placeholder );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Multiple settings stored in one setting array.
	 *
	 * @param array  $option
	 * @param array  $ignored_attributes
	 * @param array  $values
	 * @param string $ignored_placeholder
	 */
	protected function input_multi( $option, $ignored_attributes, $values, $ignored_placeholder ) {
		echo '<table class="form-table settings child-settings">';
		foreach ( $option['settings'] as $sub_option ) {
			$value              = isset( $values[ $sub_option['name'] ] ) ? $values[ $sub_option['name'] ] : $sub_option['std'];
			$sub_option['name'] = $option['name'] . '[' . $sub_option['name'] . ']';
			$this->output_field( $sub_option, $value );
		}
		echo '</table>';
	}

	/**
	 * Proxy for text input field.
	 *
	 * @param array  $option
	 * @param array  $attributes
	 * @param mixed  $value
	 * @param string $placeholder
	 */
	protected function input_input( $option, $attributes, $value, $placeholder ) {
		$this->input_text( $option, $attributes, $value, $placeholder );
	}
}
