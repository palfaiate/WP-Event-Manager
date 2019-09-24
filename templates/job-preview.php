<?php
/**
 * event listing preview when submitting event listings.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-preview.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.32.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<form method="post" id="event_preview" action="<?php echo esc_url( $form->get_action() ); ?>">
	<?php
	/**
	 * Fires at the top of the preview event form.
	 *
	 * @since 1.32.2
	 */
	do_action( 'preview_event_form_start' );
	?>
	<div class="event_listing_preview_title">
		<input type="submit" name="continue" id="event_preview_submit_button" class="button event-manager-button-submit-listing" value="<?php echo esc_attr( apply_filters( 'submit_event_step_preview_submit_text', __( 'Submit Listing', 'wp-event-manager' ) ) ); ?>" />
		<input type="submit" name="edit_event" class="button event-manager-button-edit-listing" value="<?php esc_attr_e( 'Edit listing', 'wp-event-manager' ); ?>" />
		<h2><?php esc_html_e( 'Preview', 'wp-event-manager' ); ?></h2>
	</div>
	<div class="event_listing_preview single_event_listing">
		<h1><?php wpjm_the_event_title(); ?></h1>

		<?php get_event_manager_template_part( 'content-single', 'event_listing' ); ?>

		<input type="hidden" name="event_id" value="<?php echo esc_attr( $form->get_event_id() ); ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ); ?>" />
		<input type="hidden" name="event_manager_form" value="<?php echo esc_attr( $form->get_form_name() ); ?>" />
	</div>
	<?php
	/**
	 * Fires at the bottom of the preview event form.
	 *
	 * @since 1.32.2
	 */
	do_action( 'preview_event_form_end' );
	?>
</form>
