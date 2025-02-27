<?php
/**
 * File containing the view for step 3 of the setup wizard.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3><?php esc_html_e( 'You\'re ready to start using WP Event Manager!', 'wp-event-manager' ); ?></h3>

<p><?php esc_html_e( 'Wondering what to do now? Here are some of the most common next steps:', 'wp-event-manager' ); ?></p>

<ul class="wp-event-manager-next-steps">
	<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=event_listing&page=event-manager-settings' ) ); ?>"><?php esc_html_e( 'Tweak your settings', 'wp-event-manager' ); ?></a></li>
	<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event_listing' ) ); ?>"><?php esc_html_e( 'Add a event using the admin dashboard', 'wp-event-manager' ); ?></a></li>
	<?php
	$permalink = event_manager_get_permalink( 'events' );
	if ( $permalink ) {
		?>
		<li><a href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'View submitted event listings', 'wp-event-manager' ); ?></a></li>
	<?php } else { ?>
		<li><a href="https://wpeventmanager.com/document/shortcode-reference/#section-1"><?php esc_html_e( 'Add event listings to a page using the [events] shortcode', 'wp-event-manager' ); ?></a></li>
	<?php } ?>

	<?php
	$permalink = event_manager_get_permalink( 'submit_event_form' );
	if ( $permalink ) {
		?>
		<li><a href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'Add a event via the front-end', 'wp-event-manager' ); ?></a></li>
	<?php } else { ?>
		<li><a href="https://wpeventmanager.com/document/the-event-submission-form/"><?php esc_html_e( 'Learn to use the front-end event submission board', 'wp-event-manager' ); ?></a></li>
	<?php } ?>

	<?php
	$permalink = event_manager_get_permalink( 'event_dashboard' );
	if ( $permalink ) {
		?>
		<li><a href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'View the event dashboard', 'wp-event-manager' ); ?></a></li>
	<?php } else { ?>
		<li><a href="https://wpeventmanager.com/document/the-event-dashboard/"><?php esc_html_e( 'Learn to use the front-end event dashboard', 'wp-event-manager' ); ?></a></li>
	<?php } ?>
</ul>

<p>
	<?php
	echo wp_kses_post(
		sprintf(
			// translators: %1$s is the URL to WPJM support documentation; %2$s is the URL to WPJM support forums.
			__(
				'If you need help, you can find more detail in our
							<a href="%1$s">support documentation</a> or post your question on the
							<a href="%2$s">WP Event Manager support forums</a>. Happy hiring!',
				'wp-event-manager'
			),
			'https://wpeventmanager.com/documentation/',
			'https://wordpress.org/support/plugin/wp-event-manager'
		)
	);
	?>
</p>

<div class="wp-event-manager-support-the-plugin">
	<h3><?php esc_html_e( 'Support WP Event Manager\'s Ongoing Development', 'wp-event-manager' ); ?></h3>
	<p><?php esc_html_e( 'There are lots of ways you can support open source software projects like this one: contributing code, fixing a bug, assisting with non-English translation, or just telling your friends about WP Event Manager to help spread the word. We appreciate your support!', 'wp-event-manager' ); ?></p>
	<ul>
		<li class="icon-review"><a href="https://wordpress.org/support/view/plugin-reviews/wp-event-manager#postform"><?php esc_html_e( 'Leave a positive review', 'wp-event-manager' ); ?></a></li>
		<li class="icon-localization"><a href="https://translate.wordpress.org/projects/wp-plugins/wp-event-manager"><?php esc_html_e( 'Contribute a localization', 'wp-event-manager' ); ?></a></li>
		<li class="icon-code"><a href="https://github.com/mikejolley/wp-event-manager"><?php esc_html_e( 'Contribute code or report a bug', 'wp-event-manager' ); ?></a></li>
		<li class="icon-forum"><a href="https://wordpress.org/support/plugin/wp-event-manager"><?php esc_html_e( 'Help other users on the forums', 'wp-event-manager' ); ?></a></li>
	</ul>
</div>
