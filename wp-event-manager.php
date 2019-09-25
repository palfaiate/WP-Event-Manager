<?php
/**
 * Plugin Name: WP Event Manager
 * Plugin URI: https://wpeventmanager.com/
 * Description: Manage event listings from the WordPress admin panel, and allow users to post events directly to your site.
 * Version: 1.34.0-beta.1
 * Author: Automattic
 * Author URI: https://wpeventmanager.com/
 * Requires at least: 4.9
 * Tested up to: 5.2
 * Requires PHP: 5.6
 * Text Domain: wp-event-manager
 * Domain Path: /languages/
 * License: GPL2+
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
define( 'event_MANAGER_VERSION', '1.34.0-beta.1' );
define( 'event_MANAGER_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'event_MANAGER_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'event_MANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once dirname( __FILE__ ) . '/includes/class-wp-event-manager-dependency-checker.php';
if ( ! WP_event_Manager_Dependency_Checker::check_dependencies() ) {
	return;
}

require_once dirname( __FILE__ ) . '/includes/class-wp-event-manager.php';

/**
 * Main instance of WP Event Manager.
 *
 * Returns the main instance of WP Event Manager to prevent the need to use globals.
 *
 * @since  1.26
 * @return WP_event_Manager
 */
function WPJM() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
	return WP_event_Manager::instance();
}

$GLOBALS['event_manager'] = WPJM();

// Activation - works with symlinks.
register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( WPJM(), 'activate' ) );

// Cleanup on deactivation.
register_deactivation_hook( __FILE__, array( WPJM(), 'unschedule_cron_events' ) );
register_deactivation_hook( __FILE__, array( WPJM(), 'usage_tracking_cleanup' ) );
