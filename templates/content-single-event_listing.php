<?php
/**
 * Single event listing.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/content-single-event_listing.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @since       1.0.0
 * @version     1.28.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $post;
?>
<div class="single_event_listing">
	<?php if ( get_option( 'event_manager_hide_expired_content', 1 ) && 'expired' === $post->post_status ) : ?>
		<div class="event-manager-info"><?php _e( 'This listing has expired.', 'wp-event-manager' ); ?></div>
	<?php else : ?>
		<?php
			/**
			 * single_event_listing_start hook
			 *
			 * @hooked event_listing_meta_display - 20
			 * @hooked event_listing_company_display - 30
			 */
			do_action( 'single_event_listing_start' );
		?>

		<div class="event_description">
			<?php wpjm_the_event_description(); ?>
		</div>

		<?php if ( candidates_can_apply() ) : ?>
			<?php get_event_manager_template( 'event-application.php' ); ?>
		<?php endif; ?>

		<?php
			/**
			 * single_event_listing_end hook
			 */
			do_action( 'single_event_listing_end' );
		?>
	<?php endif; ?>
</div>
