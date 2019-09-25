<?php
/**
 * event listing summary
 *
 * This template can be overridden by copying it to yourtheme/event_manager/content-summary-event_listing.php.
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

global $event_manager;
?>

<a href="<?php the_permalink(); ?>">
	<?php if ( get_option( 'event_manager_enable_types' ) ) { ?>
		<?php $types = wpjm_get_the_event_types(); ?>
		<?php if ( ! empty( $types ) ) : foreach ( $types as $type ) : ?>

			<div class="event-type <?php echo esc_attr( sanitize_title( $type->slug ) ); ?>"><?php echo esc_html( $type->name ); ?></div>

		<?php endforeach; endif; ?>
	<?php } ?>

	<?php if ( $logo = get_the_company_logo() ) : ?>
		<img src="<?php echo esc_url( $logo ); ?>" alt="<?php the_company_name(); ?>" title="<?php the_company_name(); ?> - <?php the_company_tagline(); ?>" />
	<?php endif; ?>

	<div class="event_summary_content">

		<h2 class="event_summary_title"><?php wpjm_the_event_title(); ?></h2>

		<p class="meta"><?php the_event_location( false ); ?> &mdash; <?php the_event_publish_date(); ?></p>

	</div>
</a>
