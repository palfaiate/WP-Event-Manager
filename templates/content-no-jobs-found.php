<?php
/**
 * Notice when no events were found in `[events]` shortcode.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/content-no-events-found.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @since       1.0.0
 * @version     1.31.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<?php if ( defined( 'DOING_AJAX' ) ) : ?>
	<li class="no_event_listings_found"><?php esc_html_e( 'There are no listings matching your search.', 'wp-event-manager' ); ?></li>
<?php else : ?>
	<p class="no_event_listings_found"><?php esc_html_e( 'There are currently no vacancies.', 'wp-event-manager' ); ?></p>
<?php endif; ?>
