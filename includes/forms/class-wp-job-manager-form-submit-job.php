<?php
/**
 * File containing the class WP_event_Manager_Form_Submit_event.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the editing of event Listings from the public facing frontend (from within `[submit_event_form]` shortcode).
 *
 * @extends WP_event_Manager_Form
 * @since 1.0.0
 */
class WP_event_Manager_Form_Submit_event extends WP_event_Manager_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'submit-event';

	/**
	 * event listing ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $event_id;

	/**
	 * Preview event (unused)
	 *
	 * @access protected
	 * @var string
	 */
	protected $preview_event;

	/**
	 * Stores static instance of class.
	 *
	 * @access protected
	 * @var WP_event_Manager_Form_Submit_event The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Returns static instance of class.
	 *
	 * @return self
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
		add_action( 'wp', [ $this, 'process' ] );
		add_action( 'submit_event_form_start', [ $this, 'output_submit_form_nonce_field' ] );
		add_action( 'preview_event_form_start', [ $this, 'output_preview_form_nonce_field' ] );
		add_action( 'event_manager_event_submitted', [ $this, 'track_event_submission' ] );

		if ( $this->use_recaptcha_field() ) {
			add_action( 'submit_event_form_end', [ $this, 'display_recaptcha_field' ] );
			add_filter( 'submit_event_form_validate_fields', [ $this, 'validate_recaptcha_field' ] );
			add_filter( 'submit_draft_event_form_validate_fields', [ $this, 'validate_recaptcha_field' ] );
		}

		$this->steps = (array) apply_filters(
			'submit_event_steps',
			[
				'submit'  => [
					'name'     => __( 'Submit Details', 'wp-event-manager' ),
					'view'     => [ $this, 'submit' ],
					'handler'  => [ $this, 'submit_handler' ],
					'priority' => 10,
				],
				'preview' => [
					'name'     => __( 'Preview', 'wp-event-manager' ),
					'view'     => [ $this, 'preview' ],
					'handler'  => [ $this, 'preview_handler' ],
					'priority' => 20,
				],
				'done'    => [
					'name'     => __( 'Done', 'wp-event-manager' ),
					'before'   => [ $this, 'done_before' ],
					'view'     => [ $this, 'done' ],
					'priority' => 30,
				],
			]
		);

		uasort( $this->steps, [ $this, 'sort_by_priority' ] );

		// phpcs:disable WordPress.Security.NonceVerification.Missing,  WordPress.Security.NonceVerification.Recommended -- Check happens later when possible. Input is used safely.
		// Get step/event.
		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( intval( $_POST['step'] ), array_keys( $this->steps ), true );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( intval( $_GET['step'] ), array_keys( $this->steps ), true );
		}

		$this->event_id = ! empty( $_REQUEST['event_id'] ) ? absint( $_REQUEST['event_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing,  WordPress.Security.NonceVerification.Recommended

		if ( ! event_manager_user_can_edit_event( $this->event_id ) ) {
			$this->event_id = 0;
		}

		// Allow resuming from cookie.
		$this->resume_edit = false;
		if (
			! isset( $_GET['new'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
			&& (
				'before' === get_option( 'event_manager_paid_listings_flow' )
				|| ! $this->event_id
			)
			&& ! empty( $_COOKIE['wp-event-manager-submitting-event-id'] )
			&& ! empty( $_COOKIE['wp-event-manager-submitting-event-key'] )
		) {
			$event_id     = absint( $_COOKIE['wp-event-manager-submitting-event-id'] );
			$event_status = get_post_status( $event_id );

			if (
				(
					'preview' === $event_status
					|| 'pending_payment' === $event_status
				)
				&& get_post_meta( $event_id, '_submitting_key', true ) === $_COOKIE['wp-event-manager-submitting-event-key']
			) {
				$this->event_id      = $event_id;
				$this->resume_edit = get_post_meta( $event_id, '_submitting_key', true );
			}
		}

		// Load event details.
		if ( $this->event_id ) {
			$event_status = get_post_status( $this->event_id );
			if ( 'expired' === $event_status ) {
				if ( ! event_manager_user_can_edit_event( $this->event_id ) ) {
					$this->event_id = 0;
					$this->step   = 0;
				}
			} elseif ( ! in_array( $event_status, apply_filters( 'event_manager_valid_submit_event_statuses', [ 'preview', 'draft' ] ), true ) ) {
				$this->event_id = 0;
				$this->step   = 0;
			}
		}
	}

	/**
	 * Gets the submitted event ID.
	 *
	 * @return int
	 */
	public function get_event_id() {
		return absint( $this->event_id );
	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$allowed_application_method = get_option( 'event_manager_allowed_application_method', '' );
		switch ( $allowed_application_method ) {
			case 'email':
				$application_method_label       = __( 'Application email', 'wp-event-manager' );
				$application_method_placeholder = __( 'you@example.com', 'wp-event-manager' );
				$application_method_sanitizer   = 'email';
				break;
			case 'url':
				$application_method_label       = __( 'Application URL', 'wp-event-manager' );
				$application_method_placeholder = __( 'https://', 'wp-event-manager' );
				$application_method_sanitizer   = 'url';
				break;
			default:
				$application_method_label       = __( 'Application email/URL', 'wp-event-manager' );
				$application_method_placeholder = __( 'Enter an email address or website URL', 'wp-event-manager' );
				$application_method_sanitizer   = 'url_or_email';
				break;
		}

		if ( event_manager_multi_event_type() ) {
			$event_type = 'term-multiselect';
		} else {
			$event_type = 'term-select';
		}
		$this->fields = apply_filters(
			'submit_event_form_fields',
			[
				'event'     => [
					'event_title'       => [
						'label'       => __( 'event Title', 'wp-event-manager' ),
						'type'        => 'text',
						'required'    => true,
						'placeholder' => '',
						'priority'    => 1,
					],
					'event_location'    => [
						'label'       => __( 'Location', 'wp-event-manager' ),
						'description' => __( 'Leave this blank if the location is not important', 'wp-event-manager' ),
						'type'        => 'text',
						'required'    => false,
						'placeholder' => __( 'e.g. "London"', 'wp-event-manager' ),
						'priority'    => 2,
					],
					'event_type'        => [
						'label'       => __( 'event type', 'wp-event-manager' ),
						'type'        => $event_type,
						'required'    => true,
						'placeholder' => __( 'Choose event type&hellip;', 'wp-event-manager' ),
						'priority'    => 3,
						'default'     => 'full-time',
						'taxonomy'    => 'event_listing_type',
					],
					'event_category'    => [
						'label'       => __( 'event category', 'wp-event-manager' ),
						'type'        => 'term-multiselect',
						'required'    => true,
						'placeholder' => '',
						'priority'    => 4,
						'default'     => '',
						'taxonomy'    => 'event_listing_category',
					],
					'event_description' => [
						'label'    => __( 'Description', 'wp-event-manager' ),
						'type'     => 'wp-editor',
						'required' => true,
						'priority' => 5,
					],
					'application'     => [
						'label'       => $application_method_label,
						'type'        => 'text',
						'sanitizer'   => $application_method_sanitizer,
						'required'    => true,
						'placeholder' => $application_method_placeholder,
						'priority'    => 6,
					],
				],
				'company' => [
					'company_name'    => [
						'label'       => __( 'Company name', 'wp-event-manager' ),
						'type'        => 'text',
						'required'    => true,
						'placeholder' => __( 'Enter the name of the company', 'wp-event-manager' ),
						'priority'    => 1,
					],
					'company_website' => [
						'label'       => __( 'Website', 'wp-event-manager' ),
						'type'        => 'text',
						'sanitizer'   => 'url',
						'required'    => false,
						'placeholder' => __( 'http://', 'wp-event-manager' ),
						'priority'    => 2,
					],
					'company_tagline' => [
						'label'       => __( 'Tagline', 'wp-event-manager' ),
						'type'        => 'text',
						'required'    => false,
						'placeholder' => __( 'Briefly describe your company', 'wp-event-manager' ),
						'maxlength'   => 64,
						'priority'    => 3,
					],
					'company_video'   => [
						'label'       => __( 'Video', 'wp-event-manager' ),
						'type'        => 'text',
						'sanitizer'   => 'url',
						'required'    => false,
						'placeholder' => __( 'A link to a video about your company', 'wp-event-manager' ),
						'priority'    => 4,
					],
					'company_twitter' => [
						'label'       => __( 'Twitter username', 'wp-event-manager' ),
						'type'        => 'text',
						'required'    => false,
						'placeholder' => __( '@yourcompany', 'wp-event-manager' ),
						'priority'    => 5,
					],
					'company_logo'    => [
						'label'              => __( 'Logo', 'wp-event-manager' ),
						'type'               => 'file',
						'required'           => false,
						'placeholder'        => '',
						'priority'           => 6,
						'ajax'               => true,
						'multiple'           => false,
						'allowed_mime_types' => [
							'jpg'  => 'image/jpeg',
							'jpeg' => 'image/jpeg',
							'gif'  => 'image/gif',
							'png'  => 'image/png',
						],
					],
				],
			]
		);

		if ( ! get_option( 'event_manager_enable_categories' ) || 0 === intval( wp_count_terms( 'event_listing_category' ) ) ) {
			unset( $this->fields['event']['event_category'] );
		}
		if ( ! get_option( 'event_manager_enable_types' ) || 0 === intval( wp_count_terms( 'event_listing_type' ) ) ) {
			unset( $this->fields['event']['event_type'] );
		}
	}

	/**
	 * Use reCAPTCHA field on the form?
	 *
	 * @return bool
	 */
	public function use_recaptcha_field() {
		if ( ! $this->is_recaptcha_available() ) {
			return false;
		}
		return 1 === absint( get_option( 'event_manager_enable_recaptcha_event_submission' ) );
	}

	/**
	 * Validates the posted fields.
	 *
	 * @param array $values
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 * @throws Exception Uploaded file is not a valid mime-type or other validation error.
	 */
	protected function validate_fields( $values ) {
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					// translators: Placeholder %s is the label for the required field.
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-event-manager' ), $field['label'] ) );
				}
				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], [ 'term-checklist', 'term-select', 'term-multiselect' ], true ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = $values[ $group_key ][ $key ];
					} else {
						$check_value = empty( $values[ $group_key ][ $key ] ) ? [] : [ $values[ $group_key ][ $key ] ];
					}
					foreach ( $check_value as $term ) {
						if ( ! term_exists( $term, $field['taxonomy'] ) ) {
							// translators: Placeholder %s is the field label that is did not validate.
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-event-manager' ), $field['label'] ) );
						}
					}
				}
				if ( 'file' === $field['type'] ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( [ $values[ $group_key ][ $key ] ] );
					}
					if ( ! empty( $check_value ) ) {
						foreach ( $check_value as $file_url ) {
							if ( is_numeric( $file_url ) ) {
								continue;
							}
							$file_url = esc_url( $file_url, [ 'http', 'https' ] );
							if ( empty( $file_url ) ) {
								throw new Exception( __( 'Invalid attachment provided.', 'wp-event-manager' ) );
							}
						}
					}
				}
				if ( 'file' === $field['type'] && ! empty( $field['allowed_mime_types'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( [ $values[ $group_key ][ $key ] ] );
					}
					if ( ! empty( $check_value ) ) {
						foreach ( $check_value as $file_url ) {
							$file_url  = current( explode( '?', $file_url ) );
							$file_info = wp_check_filetype( $file_url );

							if ( ! is_numeric( $file_url ) && $file_info && ! in_array( $file_info['type'], $field['allowed_mime_types'], true ) ) {
								// translators: Placeholder %1$s is field label; %2$s is the file mime type; %3$s is the allowed mime-types.
								throw new Exception( sprintf( __( '"%1$s" (filetype %2$s) needs to be one of the following file types: %3$s', 'wp-event-manager' ), $field['label'], $file_info['ext'], implode( ', ', array_keys( $field['allowed_mime_types'] ) ) ) );
							}
						}
					}
				}
				if ( empty( $field['file_limit'] ) && empty( $field['multiple'] ) ) {
					$field['file_limit'] = 1;
				}
				if ( 'file' === $field['type'] && ! empty( $field['file_limit'] ) ) {
					$file_limit = intval( $field['file_limit'] );
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( [ $values[ $group_key ][ $key ] ] );
					}
					if ( count( $check_value ) > $file_limit ) {
						// translators: Placeholder %d is the number of files to that users are limited to.
						$message = esc_html__( 'You are only allowed to upload a maximum of %d files.', 'wp-event-manager' );
						if ( ! empty( $field['file_limit_message'] ) ) {
							$message = $field['file_limit_message'];
						}

						throw new Exception( esc_html( sprintf( $message, $file_limit ) ) );
					}
				}
			}
		}

		// Application method.
		if ( isset( $values['event']['application'] ) && ! empty( $values['event']['application'] ) ) {
			$allowed_application_method   = get_option( 'event_manager_allowed_application_method', '' );
			$values['event']['application'] = str_replace( ' ', '+', $values['event']['application'] );
			switch ( $allowed_application_method ) {
				case 'email':
					if ( ! is_email( $values['event']['application'] ) ) {
						throw new Exception( __( 'Please enter a valid application email address', 'wp-event-manager' ) );
					}
					break;
				case 'url':
					// Prefix http if needed.
					if ( ! strstr( $values['event']['application'], 'http:' ) && ! strstr( $values['event']['application'], 'https:' ) ) {
						$values['event']['application'] = 'http://' . $values['event']['application'];
					}
					if ( ! filter_var( $values['event']['application'], FILTER_VALIDATE_URL ) ) {
						throw new Exception( __( 'Please enter a valid application URL', 'wp-event-manager' ) );
					}
					break;
				default:
					if ( ! is_email( $values['event']['application'] ) ) {
						// Prefix http if needed.
						if ( ! strstr( $values['event']['application'], 'http:' ) && ! strstr( $values['event']['application'], 'https:' ) ) {
							$values['event']['application'] = 'http://' . $values['event']['application'];
						}
						if ( ! filter_var( $values['event']['application'], FILTER_VALIDATE_URL ) ) {
							throw new Exception( __( 'Please enter a valid application email address or URL', 'wp-event-manager' ) );
						}
					}
					break;
			}
		}

		/**
		 * Perform additional validation on the event submission fields.
		 *
		 * @since 1.0.4
		 *
		 * @param bool  $is_valid Whether the fields are valid.
		 * @param array $fields   Array of all fields being validated.
		 * @param array $values   Submitted input values.
		 */
		return apply_filters( 'submit_event_form_validate_fields', true, $this->fields, $values );
	}

	/**
	 * Enqueues scripts and styles for editing and posting a event listing.
	 */
	protected function enqueue_event_form_assets() {
		wp_enqueue_script( 'wp-event-manager-event-submission' );
		wp_enqueue_style( 'wp-event-manager-event-submission', event_MANAGER_PLUGIN_URL . '/assets/css/event-submission.css', [], event_MANAGER_VERSION );

		// Register datepicker JS. It will be enqueued if needed when a date.
		// field is rendered.
		wp_register_script( 'wp-event-manager-datepicker', event_MANAGER_PLUGIN_URL . '/assets/js/datepicker.min.js', [ 'jquery', 'jquery-ui-datepicker' ], event_MANAGER_VERSION, true );

		// Localize scripts after the fields are rendered.
		add_action( 'submit_event_form_end', [ $this, 'localize_event_form_scripts' ] );
	}

	/**
	 * Localize frontend scripts that have been enqueued. This should be called
	 * after the fields are rendered, in case some of them enqueue new scripts.
	 */
	public function localize_event_form_scripts() {
		if ( function_exists( 'wp_localize_jquery_ui_datepicker' ) ) {
			wp_localize_jquery_ui_datepicker();
		} else {
			wp_localize_script(
				'wp-event-manager-datepicker',
				'event_manager_datepicker',
				[
					/* translators: jQuery date format, see http://api.jqueryui.com/datepicker/#utility-formatDate */
					'date_format' => _x( 'yy-mm-dd', 'Date format for jQuery datepicker.', 'wp-event-manager' ),
				]
			);
		}
	}

	/**
	 * Returns an array of the event types indexed by slug. (Unused)
	 *
	 * @return array
	 */
	private function event_types() {
		$options = [];
		$terms   = get_event_listing_types();
		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}
		return $options;
	}

	/**
	 * Displays the form.
	 */
	public function submit() {
		$this->init_fields();

		// Load data if necessary.
		if ( $this->event_id ) {
			$event = get_post( $this->event_id );
			foreach ( $this->fields as $group_key => $group_fields ) {
				foreach ( $group_fields as $key => $field ) {
					switch ( $key ) {
						case 'event_title':
							$this->fields[ $group_key ][ $key ]['value'] = $event->post_title;
							break;
						case 'event_description':
							$this->fields[ $group_key ][ $key ]['value'] = $event->post_content;
							break;
						case 'event_type':
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $event->ID, 'event_listing_type', [ 'fields' => 'ids' ] );
							if ( ! event_manager_multi_event_type() ) {
								$this->fields[ $group_key ][ $key ]['value'] = current( $this->fields[ $group_key ][ $key ]['value'] );
							}
							break;
						case 'event_category':
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $event->ID, 'event_listing_category', [ 'fields' => 'ids' ] );
							break;
						case 'company_logo':
							$this->fields[ $group_key ][ $key ]['value'] = has_post_thumbnail( $event->ID ) ? get_post_thumbnail_id( $event->ID ) : get_post_meta( $event->ID, '_' . $key, true );
							break;
						default:
							$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $event->ID, '_' . $key, true );
							break;
					}
				}
			}

			$this->fields = apply_filters( 'submit_event_form_fields_get_event_data', $this->fields, $event );

			// Get user meta.
		} elseif ( is_user_logged_in() && empty( $_POST['submit_event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Safe input.
			if ( ! empty( $this->fields['company'] ) ) {
				foreach ( $this->fields['company'] as $key => $field ) {
					$this->fields['company'][ $key ]['value'] = get_user_meta( get_current_user_id(), '_' . $key, true );
				}
			}
			if ( ! empty( $this->fields['event']['application'] ) ) {
				$allowed_application_method = get_option( 'event_manager_allowed_application_method', '' );
				if ( 'url' !== $allowed_application_method ) {
					$current_user                                = wp_get_current_user();
					$this->fields['event']['application']['value'] = $current_user->user_email;
				}
			}
			$this->fields = apply_filters( 'submit_event_form_fields_get_user_data', $this->fields, get_current_user_id() );
		}

		$this->enqueue_event_form_assets();
		get_event_manager_template(
			'event-submit.php',
			[
				'form'               => $this->form_name,
				'event_id'             => $this->get_event_id(),
				'resume_edit'        => $this->resume_edit,
				'action'             => $this->get_action(),
				'event_fields'         => $this->get_fields( 'event' ),
				'company_fields'     => $this->get_fields( 'company' ),
				'step'               => $this->get_step(),
				'can_continue_later' => $this->can_continue_later(),
				'submit_button_text' => apply_filters( 'submit_event_form_submit_button_text', __( 'Preview', 'wp-event-manager' ) ),
			]
		);
	}

	/**
	 * Handles the submission of form data.
	 *
	 * @throws Exception On validation error.
	 */
	public function submit_handler() {
		try {
			// Init fields.
			$this->init_fields();

			// Get posted values.
			$values = $this->get_posted_fields();

			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Input is used safely. Nonce checked below when possible.
			$input_create_account_username        = isset( $_POST['create_account_username'] ) ? sanitize_text_field( wp_unslash( $_POST['create_account_username'] ) ) : false;
			$input_create_account_password        = isset( $_POST['create_account_password'] ) ? sanitize_text_field( wp_unslash( $_POST['create_account_password'] ) ) : false;
			$input_create_account_password_verify = isset( $_POST['create_account_password_verify'] ) ? sanitize_text_field( wp_unslash( $_POST['create_account_password_verify'] ) ) : false;
			$input_create_account_email           = isset( $_POST['create_account_email'] ) ? sanitize_text_field( wp_unslash( $_POST['create_account_email'] ) ) : false;
			$is_saving_draft                      = $this->can_continue_later() && ! empty( $_POST['save_draft'] );

			if ( empty( $_POST['submit_event'] ) && ! $is_saving_draft ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$this->check_submit_form_nonce_field();

			// Validate fields.
			if ( $is_saving_draft ) {
				/**
				 * Perform additional validation on the event submission fields when saving drafts.
				 *
				 * @since 1.33.1
				 *
				 * @param bool  $is_valid Whether the fields are valid.
				 * @param array $fields   Array of all fields being validated.
				 * @param array $values   Submitted input values.
				 */
				$validation_status = apply_filters( 'submit_draft_event_form_validate_fields', true, $this->fields, $values );
			} else {
				$validation_status = $this->validate_fields( $values );
			}

			if ( is_wp_error( $validation_status ) ) {
				throw new Exception( $validation_status->get_error_message() );
			}

			// Account creation.
			if ( ! is_user_logged_in() ) {
				$create_account = false;

				if ( event_manager_enable_registration() ) {
					if ( event_manager_user_requires_account() ) {
						if ( ! event_manager_generate_username_from_email() && empty( $input_create_account_username ) ) {
							throw new Exception( __( 'Please enter a username.', 'wp-event-manager' ) );
						}
						if ( ! wpjm_use_standard_password_setup_email() ) {
							if ( empty( $input_create_account_password ) ) {
								throw new Exception( __( 'Please enter a password.', 'wp-event-manager' ) );
							}
						}
						if ( empty( $input_create_account_email ) ) {
							throw new Exception( __( 'Please enter your email address.', 'wp-event-manager' ) );
						}
					}

					if ( ! wpjm_use_standard_password_setup_email() && ! empty( $input_create_account_password ) ) {
						if ( empty( $input_create_account_password_verify ) || $input_create_account_password_verify !== $input_create_account_password ) {
							throw new Exception( __( 'Passwords must match.', 'wp-event-manager' ) );
						}
						if ( ! wpjm_validate_new_password( sanitize_text_field( wp_unslash( $input_create_account_password ) ) ) ) {
							$password_hint = wpjm_get_password_rules_hint();
							if ( $password_hint ) {
								// translators: Placeholder %s is the password hint.
								throw new Exception( sprintf( __( 'Invalid Password: %s', 'wp-event-manager' ), $password_hint ) );
							} else {
								throw new Exception( __( 'Password is not valid.', 'wp-event-manager' ) );
							}
						}
					}

					if ( ! empty( $input_create_account_email ) ) {
						$create_account = wp_event_manager_create_account(
							[
								'username' => ( event_manager_generate_username_from_email() || empty( $input_create_account_username ) ) ? '' : $input_create_account_username,
								'password' => ( wpjm_use_standard_password_setup_email() || empty( $input_create_account_password ) ) ? '' : $input_create_account_password,
								'email'    => sanitize_text_field( wp_unslash( $input_create_account_email ) ),
								'role'     => get_option( 'event_manager_registration_role' ),
							]
						);
					}
				}

				if ( is_wp_error( $create_account ) ) {
					throw new Exception( $create_account->get_error_message() );
				}
			}

			if ( event_manager_user_requires_account() && ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be signed in to post a new listing.', 'wp-event-manager' ) );
			}

			$post_status = '';
			if ( $is_saving_draft ) {
				$post_status = 'draft';
			} elseif ( ! $this->event_id || 'draft' === get_post_status( $this->event_id ) ) {
				$post_status = 'preview';
			}

			// Update the event.
			$this->save_event( $values['event']['event_title'], $values['event']['event_description'], $post_status, $values );
			$this->update_event_data( $values );

			if ( $this->event_id ) {
				// Reset the `_filled` flag.
				update_post_meta( $this->event_id, '_filled', 0 );
			}

			if ( $is_saving_draft ) {
				$event_dashboard_page_id = get_option( 'event_manager_event_dashboard_page_id', false );

				// translators: placeholder is the URL to the event dashboard page.
				$this->add_message( sprintf( __( 'Draft was saved. event listing drafts can be resumed from the <a href="%s">event dashboard</a>.', 'wp-event-manager' ), get_permalink( $event_dashboard_page_id ) ) );
			} else {
				// Successful, show next step.
				$this->step++;
			}
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Updates or creates a event listing from posted data.
	 *
	 * @param  string $post_title
	 * @param  string $post_content
	 * @param  string $status
	 * @param  array  $values
	 * @param  bool   $update_slug
	 */
	protected function save_event( $post_title, $post_content, $status = 'preview', $values = [], $update_slug = true ) {
		$event_data = [
			'post_title'     => $post_title,
			'post_content'   => $post_content,
			'post_type'      => 'event_listing',
			'comment_status' => 'closed',
		];

		if ( $update_slug ) {
			$event_slug = [];

			// Prepend with company name.
			if ( apply_filters( 'submit_event_form_prefix_post_name_with_company', true ) && ! empty( $values['company']['company_name'] ) ) {
				$event_slug[] = $values['company']['company_name'];
			}

			// Prepend location.
			if ( apply_filters( 'submit_event_form_prefix_post_name_with_location', true ) && ! empty( $values['event']['event_location'] ) ) {
				$event_slug[] = $values['event']['event_location'];
			}

			// Prepend with event type.
			if ( apply_filters( 'submit_event_form_prefix_post_name_with_event_type', true ) && ! empty( $values['event']['event_type'] ) ) {
				if ( ! event_manager_multi_event_type() ) {
					$event_slug[] = $values['event']['event_type'];
				} else {
					$terms = $values['event']['event_type'];

					foreach ( $terms as $term ) {
						$term = get_term_by( 'id', intval( $term ), 'event_listing_type' );

						if ( $term ) {
							$event_slug[] = $term->slug;
						}
					}
				}
			}

			$event_slug[]            = $post_title;
			$event_data['post_name'] = sanitize_title( implode( '-', $event_slug ) );
		}

		if ( $status ) {
			$event_data['post_status'] = $status;
		}

		$event_data = apply_filters( 'submit_event_form_save_event_data', $event_data, $post_title, $post_content, $status, $values );

		if ( $this->event_id ) {
			$event_data['ID'] = $this->event_id;
			wp_update_post( $event_data );
		} else {
			$this->event_id = wp_insert_post( $event_data );

			if ( ! headers_sent() ) {
				$submitting_key = uniqid();

				setcookie( 'wp-event-manager-submitting-event-id', $this->event_id, false, COOKIEPATH, COOKIE_DOMAIN, false );
				setcookie( 'wp-event-manager-submitting-event-key', $submitting_key, false, COOKIEPATH, COOKIE_DOMAIN, false );

				update_post_meta( $this->event_id, '_submitting_key', $submitting_key );
			}
		}
	}

	/**
	 * Creates a file attachment.
	 *
	 * @param  string $attachment_url
	 * @return int attachment id.
	 */
	protected function create_attachment( $attachment_url ) {
		include_once ABSPATH . 'wp-admin/includes/image.php';
		include_once ABSPATH . 'wp-admin/includes/media.php';

		$upload_dir     = wp_upload_dir();
		$attachment_url = esc_url( $attachment_url, [ 'http', 'https' ] );
		if ( empty( $attachment_url ) ) {
			return 0;
		}

		$attachment_url_parts = wp_parse_url( $attachment_url );

		// Relative paths aren't allowed.
		if ( false !== strpos( $attachment_url_parts['path'], '../' ) ) {
			return 0;
		}

		$attachment_url = sprintf( '%s://%s%s', $attachment_url_parts['scheme'], $attachment_url_parts['host'], $attachment_url_parts['path'] );

		$attachment_url = str_replace( [ $upload_dir['baseurl'], WP_CONTENT_URL, site_url( '/' ) ], [ $upload_dir['basedir'], WP_CONTENT_DIR, ABSPATH ], $attachment_url );
		if ( empty( $attachment_url ) || ! is_string( $attachment_url ) ) {
			return 0;
		}

		$attachment = [
			'post_title'   => wpjm_get_the_event_title( $this->event_id ),
			'post_content' => '',
			'post_status'  => 'inherit',
			'post_parent'  => $this->event_id,
			'guid'         => $attachment_url,
		];

		$info = wp_check_filetype( $attachment_url );
		if ( $info ) {
			$attachment['post_mime_type'] = $info['type'];
		}

		$attachment_id = wp_insert_attachment( $attachment, $attachment_url, $this->event_id );

		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $attachment_url ) );
			return $attachment_id;
		}

		return 0;
	}

	/**
	 * Sets event meta and terms based on posted values.
	 *
	 * @param  array $values
	 */
	protected function update_event_data( $values ) {
		// Set defaults.
		add_post_meta( $this->event_id, '_filled', 0, true );
		add_post_meta( $this->event_id, '_featured', 0, true );

		$maybe_attach = [];

		// Loop fields and save meta and term data.
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Save taxonomies.
				if ( ! empty( $field['taxonomy'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						wp_set_object_terms( $this->event_id, $values[ $group_key ][ $key ], $field['taxonomy'], false );
					} else {
						wp_set_object_terms( $this->event_id, [ $values[ $group_key ][ $key ] ], $field['taxonomy'], false );
					}

					// Company logo is a featured image.
				} elseif ( 'company_logo' === $key ) {
					$attachment_id = is_numeric( $values[ $group_key ][ $key ] ) ? absint( $values[ $group_key ][ $key ] ) : $this->create_attachment( $values[ $group_key ][ $key ] );
					if ( empty( $attachment_id ) ) {
						delete_post_thumbnail( $this->event_id );
					} else {
						set_post_thumbnail( $this->event_id, $attachment_id );
					}
					update_user_meta( get_current_user_id(), '_company_logo', $attachment_id );

					// Save meta data.
				} else {
					update_post_meta( $this->event_id, '_' . $key, $values[ $group_key ][ $key ] );

					// Handle attachments.
					if ( 'file' === $field['type'] ) {
						if ( is_array( $values[ $group_key ][ $key ] ) ) {
							foreach ( $values[ $group_key ][ $key ] as $file_url ) {
								$maybe_attach[] = $file_url;
							}
						} else {
							$maybe_attach[] = $values[ $group_key ][ $key ];
						}
					}
				}
			}
		}

		$maybe_attach = array_filter( $maybe_attach );

		// Handle attachments.
		if ( count( $maybe_attach ) && apply_filters( 'event_manager_attach_uploaded_files', true ) ) {
			// Get attachments.
			$attachments     = get_posts( 'post_parent=' . $this->event_id . '&post_type=attachment&fields=ids&numberposts=-1' );
			$attachment_urls = [];

			// Loop attachments already attached to the event.
			foreach ( $attachments as $attachment_id ) {
				$attachment_urls[] = wp_get_attachment_url( $attachment_id );
			}

			foreach ( $maybe_attach as $attachment_url ) {
				if ( ! in_array( $attachment_url, $attachment_urls, true ) ) {
					$this->create_attachment( $attachment_url );
				}
			}
		}

		// And user meta to save time in future.
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), '_company_name', isset( $values['company']['company_name'] ) ? $values['company']['company_name'] : '' );
			update_user_meta( get_current_user_id(), '_company_website', isset( $values['company']['company_website'] ) ? $values['company']['company_website'] : '' );
			update_user_meta( get_current_user_id(), '_company_tagline', isset( $values['company']['company_tagline'] ) ? $values['company']['company_tagline'] : '' );
			update_user_meta( get_current_user_id(), '_company_twitter', isset( $values['company']['company_twitter'] ) ? $values['company']['company_twitter'] : '' );
			update_user_meta( get_current_user_id(), '_company_video', isset( $values['company']['company_video'] ) ? $values['company']['company_video'] : '' );
		}

		do_action( 'event_manager_update_event_data', $this->event_id, $values );
	}

	/**
	 * Displays preview of event Listing.
	 */
	public function preview() {
		global $post, $event_preview;

		if ( $this->event_id ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- event preview depends on temporary override. Reset below.
			$post              = get_post( $this->event_id );
			$event_preview       = true;
			$post->post_status = 'preview';

			setup_postdata( $post );

			get_event_manager_template(
				'event-preview.php',
				[
					'form' => $this,
				]
			);

			wp_reset_postdata();
		}
	}

	/**
	 * Handles the preview step form response.
	 */
	public function preview_handler() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Input is used safely.
		if ( empty( $_POST ) ) {
			return;
		}

		$this->check_preview_form_nonce_field();

		// Edit = show submit form again.
		if ( ! empty( $_POST['edit_event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Input is used safely.
			$this->step --;
		}

		// Continue = change event status then show next screen.
		if ( ! empty( $_POST['continue'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Input is used safely.
			$event = get_post( $this->event_id );

			if ( in_array( $event->post_status, [ 'preview', 'expired' ], true ) ) {
				// Reset expiry.
				delete_post_meta( $event->ID, '_event_expires' );

				// Update event listing.
				$update_event                  = [];
				$update_event['ID']            = $event->ID;
				$update_event['post_status']   = apply_filters( 'submit_event_post_status', get_option( 'event_manager_submission_requires_approval' ) ? 'pending' : 'publish', $event );
				$update_event['post_date']     = current_time( 'mysql' );
				$update_event['post_date_gmt'] = current_time( 'mysql', 1 );
				$update_event['post_author']   = get_current_user_id();

				wp_update_post( $update_event );
			}

			$this->step ++;
		}
	}

	/**
	 * Output the nonce field on event submission form.
	 */
	public function output_submit_form_nonce_field() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		wp_nonce_field( 'submit-event-' . $this->event_id, '_wpjm_nonce' );
	}

	/**
	 * Check the nonce field on the submit form.
	 */
	public function check_submit_form_nonce_field() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if (
			empty( $_REQUEST['_wpjm_nonce'] )
			|| ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpjm_nonce'] ), 'submit-event-' . $this->event_id ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce should not be modified.
		) {
			wp_nonce_ays( 'submit-event-' . $this->event_id );
			die();
		}
	}

	/**
	 * Output the nonce field on event preview form.
	 */
	public function output_preview_form_nonce_field() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		wp_nonce_field( 'preview-event-' . $this->event_id, '_wpjm_nonce' );
	}

	/**
	 * Check the nonce field on the preview form.
	 */
	public function check_preview_form_nonce_field() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if (
			empty( $_REQUEST['_wpjm_nonce'] )
			|| ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpjm_nonce'] ), 'preview-event-' . $this->event_id ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce should not be modified.
		) {
			wp_nonce_ays( 'preview-event-' . $this->event_id );
			die();
		}
	}

	/**
	 * Displays the final screen after a event listing has been submitted.
	 */
	public function done() {
		get_event_manager_template( 'event-submitted.php', [ 'event' => get_post( $this->event_id ) ] );
	}

	/**
	 * Handles the event submissions before the view is called.
	 */
	public function done_before() {
		do_action( 'event_manager_event_submitted', $this->event_id );
	}

	/**
	 * Checks if we can resume submission later.
	 *
	 * @return bool
	 */
	protected function can_continue_later() {
		$can_continue_later    = false;
		$event_dashboard_page_id = get_option( 'event_manager_event_dashboard_page_id', false );

		if ( ! $event_dashboard_page_id ) {
			// For now, we're going to block resuming later if no event dashboard page has been set.
			$can_continue_later = false;
		} elseif ( is_user_logged_in() ) {
			// If they're logged in, we can assume they can access the event dashboard to resume later.
			$can_continue_later = true;
		} elseif ( event_manager_user_requires_account() && event_manager_enable_registration() ) {
			// If these are enabled, we know an account will be created on save.
			$can_continue_later = true;
		}

		/**
		 * Override if visitor can resume event submission later.
		 *
		 * @param bool $can_continue_later True if they can resume event later.
		 */
		return apply_filters( 'submit_event_form_can_continue_later', $can_continue_later );
	}

	/**
	 * Send usage tracking event for event submission.
	 *
	 * @param int $post_id Post ID.
	 */
	public function track_event_submission( $post_id ) {
		WP_event_Manager_Usage_Tracking::track_event_submission(
			$post_id,
			[
				'source'     => 'frontend',
				'old_status' => 'preview',
			]
		);
	}
}
