<?php
/**
 * File containing the view for license key notices.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="updated">
	<p class="wpjm-updater-dismiss" style="float:right;"><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'dismiss-wpjm-licence-notice', $product_slug ), 'dismiss-wpjm-licence-notice', '_wpjm_nonce' ) ); ?>"><?php esc_html_e( 'Hide notice', 'wp-event-manager' ); ?></a></p>
	<p><?php printf( '<a href="%s">Please enter your license key</a> to get updates for "%s".', esc_url( admin_url( 'edit.php?post_type=event_listing&page=event-manager-addons&section=helper#' . sanitize_title( $product_slug . '_row' ) ) ), esc_html( $plugin_data['Name'] ) ); ?></p>
</div>
