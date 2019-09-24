<?php
/**
 * File containing the view used in the header of the setup pages.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wp_job_manager wp_job_manager_addons_wrap">
	<h2><?php esc_html_e( 'WP Event Manager Setup', 'wp-event-manager' ); ?></h2>

	<ul class="wp-event-manager-setup-steps">
		<?php
		$step_classes          = array_fill( 1, 3, '' );
		$step_classes[ $step ] = 'wp-event-manager-setup-active-step';
		?>
		<li class="<?php echo sanitize_html_class( $step_classes[1] ); ?>"><?php esc_html_e( '1. Introduction', 'wp-event-manager' ); ?></li>
		<li class="<?php echo sanitize_html_class( $step_classes[2] ); ?>"><?php esc_html_e( '2. Page Setup', 'wp-event-manager' ); ?></li>
		<li class="<?php echo sanitize_html_class( $step_classes[3] ); ?>"><?php esc_html_e( '3. Done', 'wp-event-manager' ); ?></li>
	</ul>
