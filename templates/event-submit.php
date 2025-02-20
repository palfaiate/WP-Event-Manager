<?php
/**
 * Content for event submission (`[submit_event_form]`) shortcode.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-submit.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.33.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $event_manager;
?>
<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-event-form" class="event-manager-form" enctype="multipart/form-data">

	<?php
	if ( isset( $resume_edit ) && $resume_edit ) {
		printf( '<p><strong>' . esc_html__( "You are editing an existing event. %s", 'wp-event-manager' ) . '</strong></p>', '<a href="?new=1&key=' . esc_attr( $resume_edit ) . '">' . esc_html__( 'Create A New event', 'wp-event-manager' ) . '</a>' );
	}
	?>

	<?php do_action( 'submit_event_form_start' ); ?>

	<?php if ( apply_filters( 'submit_event_form_show_signin', true ) ) : ?>

		<?php get_event_manager_template( 'account-signin.php' ); ?>

	<?php endif; ?>

	<?php if ( event_manager_user_can_post_event() || event_manager_user_can_edit_event( $event_id ) ) : ?>

		<!-- event Information Fields -->
		<?php do_action( 'submit_event_form_event_fields_start' ); ?>

		<?php foreach ( $event_fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?> fieldset-type-<?php echo esc_attr( $field['type'] ); ?>">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $field['label'] ) . wp_kses_post( apply_filters( 'submit_event_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)', 'wp-event-manager' ) . '</small>', $field ) ); ?></label>
				<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
					<?php get_event_manager_template( 'form-fields/' . $field['type'] . '-field.php', [ 'key' => $key, 'field' => $field ] ); ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<?php do_action( 'submit_event_form_event_fields_end' ); ?>

		<!-- Company Information Fields -->
		<?php if ( $company_fields ) : ?>
			<h2><?php esc_html_e( 'Company Details', 'wp-event-manager' ); ?></h2>

			<?php do_action( 'submit_event_form_company_fields_start' ); ?>

			<?php foreach ( $company_fields as $key => $field ) : ?>
				<fieldset class="fieldset-<?php echo esc_attr( $key ); ?> fieldset-type-<?php echo esc_attr( $field['type'] ); ?>">
					<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $field['label'] ) . wp_kses_post( apply_filters( 'submit_event_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)', 'wp-event-manager' ) . '</small>', $field ) ); ?></label>
					<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
						<?php get_event_manager_template( 'form-fields/' . $field['type'] . '-field.php', [ 'key' => $key, 'field' => $field ] ); ?>
					</div>
				</fieldset>
			<?php endforeach; ?>

			<?php do_action( 'submit_event_form_company_fields_end' ); ?>
		<?php endif; ?>

		<?php do_action( 'submit_event_form_end' ); ?>

		<p>
			<input type="hidden" name="event_manager_form" value="<?php echo esc_attr( $form ); ?>" />
			<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />
			<input type="submit" name="submit_event" class="button" value="<?php echo esc_attr( $submit_button_text ); ?>" />
			<?php
			if ( isset( $can_continue_later ) && $can_continue_later ) {
				echo '<input type="submit" name="save_draft" class="button secondary save_draft" value="' . esc_attr__( 'Save Draft', 'wp-event-manager' ) . '" formnovalidate />';
			}
			?>
			<span class="spinner" style="background-image: url(<?php echo esc_url( includes_url( 'images/spinner.gif' ) ); ?>);"></span>
		</p>

	<?php else : ?>

		<?php do_action( 'submit_event_form_disabled' ); ?>

	<?php endif; ?>
</form>
