<?php
/**
 * Shows the `textarea` form field on event listing forms.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/form-fields/textarea-field.php.
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
<textarea cols="20" rows="3" class="input-text" name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>" id="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo empty( $field['placeholder'] ) ? '' : esc_attr( $field['placeholder'] ); ?>" maxlength="<?php echo esc_attr( ! empty( $field['maxlength'] ) ? $field['maxlength'] : '' ); ?>" <?php if ( ! empty( $field['required'] ) ) echo 'required'; ?>><?php echo isset( $field['value'] ) ? esc_textarea( html_entity_decode( $field['value'] ) ) : ''; ?></textarea>
<?php if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo wp_kses_post( $field['description'] ); ?></small><?php endif; ?>
