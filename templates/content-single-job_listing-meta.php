<?php
/**
 * Single view event meta box.
 *
 * Hooked into single_event_listing_start priority 20
 *
 * This template can be overridden by copying it to yourtheme/event_manager/content-single-event_listing-meta.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @since       1.14.0
 * @version     1.28.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $post;

do_action( 'single_event_listing_meta_before' ); ?>

<ul class="event-listing-meta meta">
	<?php do_action( 'single_event_listing_meta_start' ); ?>

	<?php if ( get_option( 'event_manager_enable_types' ) ) { ?>
		<?php $types = wpjm_get_the_event_types(); ?>
		<?php if ( ! empty( $types ) ) : foreach ( $types as $type ) : ?>

			<li class="event-type <?php echo esc_attr( sanitize_title( $type->slug ) ); ?>"><?php echo esc_html( $type->name ); ?></li>

		<?php endforeach; endif; ?>
	<?php } ?>

	<li class="location"><?php the_event_location(); ?></li>

	<li class="date-posted"><?php the_event_publish_date(); ?></li>

	<?php if ( is_position_filled() ) : ?>
		<li class="position-filled"><?php _e( 'This position has been filled', 'wp-event-manager' ); ?></li>
	<?php elseif ( ! candidates_can_apply() && 'preview' !== $post->post_status ) : ?>
		<li class="listing-expired"><?php _e( 'Applications have closed', 'wp-event-manager' ); ?></li>
	<?php endif; ?>

	<?php do_action( 'single_event_listing_meta_end' ); ?>
</ul>

<?php do_action( 'single_event_listing_meta_after' ); ?>
