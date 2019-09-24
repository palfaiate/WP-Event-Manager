<?php
/**
 * Show event application when viewing a single event listing.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-application.php.
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
<?php if ( $apply = get_the_event_application_method() ) :
	wp_enqueue_script( 'wp-event-manager-event-application' );
	?>
	<div class="event_application application">
		<?php do_action( 'event_application_start', $apply ); ?>

		<input type="button" class="application_button button" value="<?php esc_attr_e( 'Apply for event', 'wp-event-manager' ); ?>" />

		<div class="application_details">
			<?php
				/**
				 * event_manager_application_details_email or event_manager_application_details_url hook
				 */
				do_action( 'event_manager_application_details_' . $apply->type, $apply );
			?>
		</div>
		<?php do_action( 'event_application_end', $apply ); ?>
	</div>
<?php endif; ?>
