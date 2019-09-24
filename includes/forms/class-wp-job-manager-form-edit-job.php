<?php
/**
 * File containing the class WP_event_Manager_Form_Edit_event.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'class-wp-event-manager-form-submit-event.php';

/**
 * Handles the editing of event Listings from the public facing frontend (from within `[event_dashboard]` shortcode).
 *
 * @since 1.0.0
 * @extends WP_event_Manager_Form_Submit_event
 */
class WP_event_Manager_Form_Edit_event extends WP_event_Manager_Form_Submit_event {

	/**
	 * Form name
	 *
	 * @var string
	 */
	public $form_name = 'edit-event';

	/**
	 * Messaged shown on save.
	 *
	 * @var bool|string
	 */
	private $save_message = false;

	/**
	 * Message shown on error.
	 *
	 * @var bool|string
	 */
	private $save_error = false;

	/**
	 * Instance
	 *
	 * @access protected
	 * @var WP_event_Manager_Form_Edit_event The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main Instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'submit_handler' ] );
		add_action( 'submit_event_form_start', [ $this, 'output_submit_form_nonce_field' ] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Check happens later when possible.
		$this->event_id = ! empty( $_REQUEST['event_id'] ) ? absint( $_REQUEST['event_id'] ) : 0;

		if ( ! event_manager_user_can_edit_event( $this->event_id ) ) {
			$this->event_id = 0;
		}

		if ( ! empty( $this->event_id ) ) {
			$post_status = get_post_status( $this->event_id );
			if (
				( 'publish' === $post_status && ! wpjm_user_can_edit_published_submissions() )
				|| ( 'publish' !== $post_status && ! event_manager_user_can_edit_pending_submissions() )
			) {
				$this->event_id = 0;
			}
		}
	}

	/**
	 * Output function.
	 *
	 * @param array $atts
	 */
	public function output( $atts = [] ) {
		if ( ! empty( $this->save_message ) ) {
			echo '<div class="event-manager-message">' . wp_kses_post( $this->save_message ) . '</div>';
		}
		if ( ! empty( $this->save_error ) ) {
			echo '<div class="event-manager-error">' . wp_kses_post( $this->save_error ) . '</div>';
		}
		$this->submit();
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		$event = get_post( $this->event_id );

		if ( empty( $this->event_id ) ) {
			echo wp_kses_post( wpautop( __( 'Invalid listing', 'wp-event-manager' ) ) );
			return;
		}

		$this->init_fields();

		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( ! isset( $this->fields[ $group_key ][ $key ]['value'] ) ) {
					if ( 'event_title' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $event->post_title;

					} elseif ( 'event_description' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $event->post_content;

					} elseif ( 'company_logo' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = has_post_thumbnail( $event->ID ) ? get_post_thumbnail_id( $event->ID ) : get_post_meta( $event->ID, '_' . $key, true );

					} elseif ( ! empty( $field['taxonomy'] ) ) {
						$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $event->ID, $field['taxonomy'], [ 'fields' => 'ids' ] );

					} else {
						$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $event->ID, '_' . $key, true );
					}
				}
			}
		}

		$this->fields = apply_filters( 'submit_event_form_fields_get_event_data', $this->fields, $event );

		$this->enqueue_event_form_assets();

		$save_button_text = __( 'Save changes', 'wp-event-manager' );
		if (
			'publish' === get_post_status( $this->event_id )
			&& wpjm_published_submission_edits_require_moderation()
		) {
			$save_button_text = __( 'Submit changes for approval', 'wp-event-manager' );
		}

		$save_button_text = apply_filters( 'update_event_form_submit_button_text', $save_button_text );

		get_event_manager_template(
			'event-submit.php',
			[
				'form'               => $this->form_name,
				'event_id'             => $this->get_event_id(),
				'action'             => $this->get_action(),
				'event_fields'         => $this->get_fields( 'event' ),
				'company_fields'     => $this->get_fields( 'company' ),
				'step'               => $this->get_step(),
				'submit_button_text' => $save_button_text,
			]
		);
	}

	/**
	 * Submit Step is posted.
	 *
	 * @throws Exception When invalid fields are submitted.
	 */
	public function submit_handler() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Check happens later when possible.
		if ( empty( $_POST['submit_event'] ) ) {
			return;
		}

		$this->check_submit_form_nonce_field();

		try {

			// Get posted values.
			$values = $this->get_posted_fields();

			// Validate required.
			$validation_result = $this->validate_fields( $values );
			if ( is_wp_error( $validation_result ) ) {
				throw new Exception( $validation_result->get_error_message() );
			}

			$save_post_status = '';
			if ( wpjm_published_submission_edits_require_moderation() ) {
				$save_post_status = 'pending';
			}
			$original_post_status = get_post_status( $this->event_id );

			// Update the event.
			$this->save_event( $values['event']['event_title'], $values['event']['event_description'], $save_post_status, $values, false );
			$this->update_event_data( $values );

			// Successful.
			$save_message = __( 'Your changes have been saved.', 'wp-event-manager' );
			$post_status  = get_post_status( $this->event_id );

			update_post_meta( $this->event_id, '_event_edited', time() );

			if ( 'publish' === $post_status ) {
				$save_message = $save_message . ' <a href="' . get_permalink( $this->event_id ) . '">' . __( 'View &rarr;', 'wp-event-manager' ) . '</a>';
			} elseif ( 'publish' === $original_post_status && 'pending' === $post_status ) {
				$save_message = __( 'Your changes have been submitted and your listing will be visible again once approved.', 'wp-event-manager' );

				/**
				 * Resets the event expiration date when a user submits their event listing edit for approval.
				 * Defaults to `false`.
				 *
				 * @since 1.29.0
				 *
				 * @param bool $reset_expiration If true, reset expiration date.
				 */
				if ( apply_filters( 'event_manager_reset_listing_expiration_on_user_edit', false ) ) {
					delete_post_meta( $this->event_id, '_event_expires' );
				}
			}

			/**
			 * Fire action after the user edits a event listing.
			 *
			 * @since 1.30.0
			 *
			 * @param int    $event_id        event ID.
			 * @param string $save_message  Save message to filter.
			 * @param array  $values        Submitted values for event listing.
			 */
			do_action( 'event_manager_user_edit_event_listing', $this->event_id, $save_message, $values );

			/**
			 * Change the message that appears when a user edits a event listing.
			 *
			 * @since 1.29.0
			 *
			 * @param string $save_message  Save message to filter.
			 * @param int    $event_id        event ID.
			 * @param array  $values        Submitted values for event listing.
			 */
			$this->save_message = apply_filters( 'event_manager_update_event_listings_message', $save_message, $this->event_id, $values );

		} catch ( Exception $e ) {
			$this->save_error = $e->getMessage();
		}
	}
}
