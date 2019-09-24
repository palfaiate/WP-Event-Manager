<?php
/**
 * Filters in `[events]` shortcode.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-filters.php.
 *
 * @see         https://wpeventmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-event-manager
 * @category    Template
 * @version     1.33.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_script( 'wp-event-manager-ajax-filters' );

do_action( 'event_manager_event_filters_before', $atts );
?>

<form class="event_filters">
	<?php do_action( 'event_manager_event_filters_start', $atts ); ?>

	<div class="search_events">
		<?php do_action( 'event_manager_event_filters_search_events_start', $atts ); ?>

		<div class="search_keywords">
			<label for="search_keywords"><?php esc_html_e( 'Keywords', 'wp-event-manager' ); ?></label>
			<input type="text" name="search_keywords" id="search_keywords" placeholder="<?php esc_attr_e( 'Keywords', 'wp-event-manager' ); ?>" value="<?php echo esc_attr( $keywords ); ?>" />
		</div>

		<div class="search_location">
			<label for="search_location"><?php esc_html_e( 'Location', 'wp-event-manager' ); ?></label>
			<input type="text" name="search_location" id="search_location" placeholder="<?php esc_attr_e( 'Location', 'wp-event-manager' ); ?>" value="<?php echo esc_attr( $location ); ?>" />
		</div>

		<div style="clear: both"></div>

		<?php if ( $categories ) : ?>
			<?php foreach ( $categories as $category ) : ?>
				<input type="hidden" name="search_categories[]" value="<?php echo esc_attr( sanitize_title( $category ) ); ?>" />
			<?php endforeach; ?>
		<?php elseif ( $show_categories && ! is_tax( 'event_listing_category' ) && get_terms( [ 'taxonomy' => 'event_listing_category' ] ) ) : ?>
			<div class="search_categories">
				<label for="search_categories"><?php esc_html_e( 'Category', 'wp-event-manager' ); ?></label>
				<?php if ( $show_category_multiselect ) : ?>
					<?php event_manager_dropdown_categories( [ 'taxonomy' => 'event_listing_category', 'hierarchical' => 1, 'name' => 'search_categories', 'orderby' => 'name', 'selected' => $selected_category, 'hide_empty' => true ] ); ?>
				<?php else : ?>
					<?php event_manager_dropdown_categories( [ 'taxonomy' => 'event_listing_category', 'hierarchical' => 1, 'show_option_all' => __( 'Any category', 'wp-event-manager' ), 'name' => 'search_categories', 'orderby' => 'name', 'selected' => $selected_category, 'multiple' => false, 'hide_empty' => true ] ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		/**
		 * Show the submit button on the event filters form.
		 *
		 * @since 1.33.0
		 *
		 * @param bool $show_submit_button Whether to show the button. Defaults to true.
		 * @return bool
		 */
		if ( apply_filters( 'event_manager_event_filters_show_submit_button', true ) ) :
		?>
			<div class="search_submit">
				<input type="submit" value="<?php esc_attr_e( 'Search events', 'wp-event-manager' ); ?>">
			</div>
		<?php endif; ?>

		<?php do_action( 'event_manager_event_filters_search_events_end', $atts ); ?>
	</div>

	<?php do_action( 'event_manager_event_filters_end', $atts ); ?>
</form>

<?php do_action( 'event_manager_event_filters_after', $atts ); ?>

<noscript><?php esc_html_e( 'Your browser does not support JavaScript, or it is disabled. JavaScript must be enabled in order to view listings.', 'wp-event-manager' ); ?></noscript>
