<?php
/**
 * Shows info for an uploaded file on event listing forms.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/form-fields/uploaded-file-html.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.30.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="event-manager-uploaded-file">
	<?php
	if ( is_numeric( $value ) ) {
		$image_src = wp_get_attachment_image_src( absint( $value ) );
		$image_src = $image_src ? $image_src[0] : '';
	} else {
		$image_src = $value;
	}
	$extension = ! empty( $extension ) ? $extension : substr( strrchr( $image_src, '.' ), 1 );
	if ( 'image' === wp_ext2type( $extension ) ) : ?>
		<span class="event-manager-uploaded-file-preview"><img src="<?php echo esc_url( $image_src ); ?>" /> <a class="event-manager-remove-uploaded-file" href="#">[<?php _e( 'remove', 'wp-event-manager' ); ?>]</a></span>
	<?php else : ?>
		<span class="event-manager-uploaded-file-name"><code><?php echo esc_html( basename( $image_src ) ); ?></code> <a class="event-manager-remove-uploaded-file" href="#">[<?php _e( 'remove', 'wp-event-manager' ); ?>]</a></span>
	<?php endif; ?>

	<input type="hidden" class="input-text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
</div>
