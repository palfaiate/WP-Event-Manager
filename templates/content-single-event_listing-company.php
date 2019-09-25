<?php
/**
 * Single view Company information box
 *
 * Hooked into single_event_listing_start priority 30
 *
 * This template can be overridden by copying it to yourtheme/event_manager/content-single-event_listing-company.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @since       1.14.0
 * @version     1.32.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! get_the_company_name() ) {
	return;
}
?>
<div class="company">
	<?php the_company_logo(); ?>

	<p class="name">
		<?php if ( $website = get_the_company_website() ) : ?>
			<a class="website" href="<?php echo esc_url( $website ); ?>" rel="nofollow"><?php esc_html_e( 'Website', 'wp-event-manager' ); ?></a>
		<?php endif; ?>
		<?php the_company_twitter(); ?>
		<?php the_company_name( '<strong>', '</strong>' ); ?>
	</p>
	<?php the_company_tagline( '<p class="tagline">', '</p>' ); ?>
	<?php the_company_video(); ?>
</div>
