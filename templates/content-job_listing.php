<?php
/**
 * event listing in the loop.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/content-event_listing.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @since       1.0.0
 * @version     1.34.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $post;
?>
<li <?php event_listing_class(); ?> data-longitude="<?php echo esc_attr( $post->geolocation_long ); ?>" data-latitude="<?php echo esc_attr( $post->geolocation_lat ); ?>">
	<a href="<?php the_event_permalink(); ?>">
		<?php the_company_logo(); ?>
		<div class="position">
			<h3><?php wpjm_the_event_title(); ?></h3>
			<div class="company">
				<?php the_company_name( '<strong>', '</strong> ' ); ?>
				<?php the_company_tagline( '<span class="tagline">', '</span>' ); ?>
			</div>
		</div>
		<div class="location">
			<?php the_event_location( false ); ?>
		</div>
		<ul class="meta">
			<?php do_action( 'event_listing_meta_start' ); ?>

			<?php if ( get_option( 'event_manager_enable_types' ) ) { ?>
				<?php $types = wpjm_get_the_event_types(); ?>
				<?php if ( ! empty( $types ) ) : foreach ( $types as $type ) : ?>
					<li class="event-type <?php echo esc_attr( sanitize_title( $type->slug ) ); ?>"><?php echo esc_html( $type->name ); ?></li>
				<?php endforeach; endif; ?>
			<?php } ?>

			<li class="date"><?php the_event_publish_date(); ?></li>

			<?php do_action( 'event_listing_meta_end' ); ?>
		</ul>
	</a>
</li>
