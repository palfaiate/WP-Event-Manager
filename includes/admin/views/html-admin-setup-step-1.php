<?php
/**
 * File containing the view for step 1 of the setup wizard.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3><?php esc_html_e( 'Welcome to the Setup Wizard!', 'wp-event-manager' ); ?></h3>

<p><?php echo wp_kses_post( __( 'Thanks for installing <em>WP Event Manager</em>! Let\'s get your site ready to accept event listings.', 'wp-event-manager' ) ); ?></p>
<p><?php echo wp_kses_post( __( 'This setup wizard will walk you through the process of creating pages for event submissions, management, and listings.', 'wp-event-manager' ) ); ?></p>
<p>
	<?php
	// translators: Placeholder %s is the path to WPJM documentation site.
	echo wp_kses_post( sprintf( __( 'If you\'d prefer to skip this and set up your pages manually, our <a href="%s">documentation</a> will walk you through each step.', 'wp-event-manager' ), 'https://wpeventmanager.com/documentation/' ) );
	?>
</p>

<form method="post" action="<?php echo esc_url( add_query_arg( 'step', 2 ) ); ?>">
	<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'enable-usage-tracking' ) ); ?>" />

	<?php $this->maybe_output_opt_in_checkbox(); ?>

	<p class="submit">
		<input type="submit" value="<?php esc_html_e( 'Start setup', 'wp-event-manager' ); ?>" class="button button-primary" />
		<a href="<?php echo esc_url( add_query_arg( 'skip-event-manager-setup', 1, admin_url( 'index.php?page=event-manager-setup&step=3' ) ) ); ?>" class="button"><?php esc_html_e( 'Skip setup. I will set up the plugin manually.', 'wp-event-manager' ); ?></a>
	</p>
</form>
