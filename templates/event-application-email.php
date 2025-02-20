<?php
/**
 * Apply by email content.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-application-email.php.
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
<p><?php printf( wp_kses_post( __( 'To apply for this event <strong>email your details to</strong> <a class="event_application_email" href="mailto:%1$s%2$s">%1$s</a>', 'wp-event-manager' ) ), esc_html( $apply->email ), '?subject=' . rawurlencode( $apply->subject ) ); ?></p>
