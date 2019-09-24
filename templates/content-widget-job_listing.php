<?php
/**
 * Single event listing widget content.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/content-widget-event_listing.php.
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
<li <?php event_listing_class(); ?>>
	<a href="<?php the_event_permalink(); ?>">
		<?php if ( isset( $show_logo ) && $show_logo ) { ?>
		<div class="image">
			<?php the_company_logo(); ?>
		</div>
		<?php } ?>
		<div class="content">
			<div class="position">
				<h3><?php wpjm_the_event_title(); ?></h3>
			</div>
			<ul class="meta">
				<li class="location"><?php the_event_location( false ); ?></li>
				<li class="company"><?php the_company_name(); ?></li>
				<?php if ( get_option( 'event_manager_enable_types' ) ) { ?>
					<?php $types = wpjm_get_the_event_types(); ?>
					<?php if ( ! empty( $types ) ) : foreach ( $types as $type ) : ?>
						<li class="event-type <?php echo esc_attr( sanitize_title( $type->slug ) ); ?>"><?php echo esc_html( $type->name ); ?></li>
					<?php endforeach; endif; ?>
				<?php } ?>
			</ul>
		</div>
	</a>
</li>
