<?php
/**
 * event dashboard shortcode content if user is not logged in.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-dashboard-login.php.
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
<div id="event-manager-event-dashboard">

	<p class="account-sign-in"><?php esc_html_e( 'You need to be signed in to manage your listings.', 'wp-event-manager' ); ?> <a class="button" href="<?php echo esc_url( apply_filters( 'event_manager_event_dashboard_login_url', wp_login_url( get_permalink() ) ) ); ?>"><?php esc_html_e( 'Sign in', 'wp-event-manager' ); ?></a></p>

</div>
