<?php
/**
 * File containing the view for displaying the admin notice when user first activates WPJM.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="updated wpjm-message">
	<p>
		<?php
		echo wp_kses_post( __( 'You are nearly ready to start listing events with <strong>WP Event Manager</strong>.', 'wp-event-manager' ) );
		?>
	</p>
	<p class="submit">
		<a href="<?php echo esc_url( admin_url( 'index.php?page=event-manager-setup' ) ); ?>" class="button-primary"><?php esc_html_e( 'Run Setup Wizard', 'wp-event-manager' ); ?></a>
		<a class="button-secondary skip" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpjm_hide_notice', WP_event_Manager_Admin_Notices::NOTICE_CORE_SETUP ), 'event_manager_hide_notices_nonce', '_wpjm_notice_nonce' ) ); ?>"><?php esc_html_e( 'Skip Setup', 'wp-event-manager' ); ?></a>
	</p>
</div>
