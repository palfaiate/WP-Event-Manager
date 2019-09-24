<?php
/**
 * File containing the view for step 2 of the setup wizard.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3><?php esc_html_e( 'Page Setup', 'wp-event-manager' ); ?></h3>

<p><?php esc_html_e( 'With WP Event Manager, employers and applicants can post, manage, and browse event listings right on your website. Tell us which of these common pages you\'d like your site to have and we\'ll create and configure them for you.', 'wp-event-manager' ); ?></p>
<p>
	<?php
	echo wp_kses_post(
		sprintf(
			// translators: %1$s is URL to WordPress core shortcode documentation. %2$s is URL to WPJM specific shortcode reference.
			__(
				'(These pages are created using <a href="%1$s" title="What is a shortcode?" class="help-page-link">shortcodes</a>,
								which we take care of in this step. If you\'d like to build these pages yourself or want to add one of these options to an existing
								page on your site, you can skip this step and take a look at <a href="%2$s" class="help-page-link">shortcode documentation</a> for detailed instructions.)',
				'wp-event-manager'
			),
			'http://codex.wordpress.org/Shortcode',
			'https://wpeventmanager.com/document/shortcode-reference/'
		)
	);
	?>
</p>

<form action="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" method="post">
	<?php wp_nonce_field( 'step_3', 'setup_wizard' ); ?>
	<table class="wp-event-manager-shortcodes widefat">
		<thead>
		<tr>
			<th>&nbsp;</th>
			<th><?php esc_html_e( 'Page Title', 'wp-event-manager' ); ?></th>
			<th><?php esc_html_e( 'Page Description', 'wp-event-manager' ); ?></th>
			<th><?php esc_html_e( 'Content Shortcode', 'wp-event-manager' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><input type="checkbox" checked="checked" name="wp-event-manager-create-page[submit_event_form]" /></td>
			<td><input type="text" value="<?php echo esc_attr( _x( 'Post a event', 'Default page title (wizard)', 'wp-event-manager' ) ); ?>" name="wp-event-manager-page-title[submit_event_form]" /></td>
			<td>
				<p><?php esc_html_e( 'Creates a page that allows employers to post new events directly from a page on your website, instead of requiring them to log in to an admin area. If you\'d rather not allow this -- for example, if you want employers to use the admin dashboard only -- you can uncheck this setting.', 'wp-event-manager' ); ?></p>
			</td>
			<td><code>[submit_event_form]</code></td>
		</tr>
		<tr>
			<td><input type="checkbox" checked="checked" name="wp-event-manager-create-page[event_dashboard]" /></td>
			<td><input type="text" value="<?php echo esc_attr( _x( 'event Dashboard', 'Default page title (wizard)', 'wp-event-manager' ) ); ?>" name="wp-event-manager-page-title[event_dashboard]" /></td>
			<td>
				<p><?php esc_html_e( 'Creates a page that allows employers to manage their event listings directly from a page on your website, instead of requiring them to log in to an admin area. If you want to manage all event listings from the admin dashboard only, you can uncheck this setting.', 'wp-event-manager' ); ?></p>
			</td>
			<td><code>[event_dashboard]</code></td>
		</tr>
		<tr>
			<td><input type="checkbox" checked="checked" name="wp-event-manager-create-page[events]" /></td>
			<td><input type="text" value="<?php echo esc_attr( _x( 'events', 'Default page title (wizard)', 'wp-event-manager' ) ); ?>" name="wp-event-manager-page-title[events]" /></td>
			<td><?php esc_html_e( 'Creates a page where visitors can browse, search, and filter event listings.', 'wp-event-manager' ); ?></td>
			<td><code>[events]</code></td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<th colspan="4">
				<input type="submit" class="button button-primary" value="Create selected pages" />
				<a href="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" class="button"><?php esc_html_e( 'Skip this step', 'wp-event-manager' ); ?></a>
			</th>
		</tr>
		</tfoot>
	</table>
</form>
