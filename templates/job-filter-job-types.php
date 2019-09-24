<?php
/**
 * Filter in `[events]` shortcode for event types.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-filter-event-types.php.
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
<?php if ( ! is_tax( 'event_listing_type' ) && empty( $event_types ) ) : ?>
	<ul class="event_types">
		<?php foreach ( get_event_listing_types() as $type ) : ?>
			<li><label for="event_type_<?php echo esc_attr( $type->slug ); ?>" class="<?php echo esc_attr( sanitize_title( $type->name ) ); ?>"><input type="checkbox" name="filter_event_type[]" value="<?php echo esc_attr( $type->slug ); ?>" <?php checked( in_array( $type->slug, $selected_event_types ), true ); ?> id="event_type_<?php echo esc_attr( $type->slug ); ?>" /> <?php echo esc_html( $type->name ); ?></label></li>
		<?php endforeach; ?>
	</ul>
	<input type="hidden" name="filter_event_type[]" value="" />
<?php elseif ( $event_types ) : ?>
	<?php foreach ( $event_types as $event_type ) : ?>
		<input type="hidden" name="filter_event_type[]" value="<?php echo esc_attr( sanitize_title( $event_type ) ); ?>" />
	<?php endforeach; ?>
<?php endif; ?>
