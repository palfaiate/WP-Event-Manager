<?php
/**
 * Shows the `recaptcha` form field on event listing forms.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/form-fields/recaptcha-field.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.31.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
	<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
	<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
		<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $field['site_key'] ); ?>"></div>
		<?php if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo wp_kses_post( $field['description'] ); ?></small><?php endif; ?>
	</div>
</fieldset>
