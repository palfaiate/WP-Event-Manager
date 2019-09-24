<?php
/**
 * Apply using link to website.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-application-url.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.32.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<p><?php esc_html_e( 'To apply for this event please visit', 'wp-event-manager' ); ?> <a href="<?php echo esc_url( $apply->url ); ?>" rel="nofollow"><?php echo esc_html( wp_parse_url( $apply->url, PHP_URL_HOST ) ); ?></a>.</p>
