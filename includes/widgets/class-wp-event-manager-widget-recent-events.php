<?php
/**
 * File containing the class WP_event_Manager_Widget_Recent_events.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recent events widget.
 *
 * @package wp-event-manager
 * @since 1.0.0
 */
class WP_event_Manager_Widget_Recent_events extends WP_event_Manager_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_post_types;

		// translators: Placeholder %s is the plural label for the event listing post type.
		$this->widget_name        = sprintf( __( 'Recent %s', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->name );
		$this->widget_cssclass    = 'event_manager widget_recent_events';
		$this->widget_description = __( 'Display a list of recent listings on your site, optionally matching a keyword and location.', 'wp-event-manager' );
		$this->widget_id          = 'widget_recent_events';
		$this->settings           = [
			'title'     => [
				'type'  => 'text',
				// translators: Placeholder %s is the plural label for the event listing post type.
				'std'   => sprintf( __( 'Recent %s', 'wp-event-manager' ), $wp_post_types['event_listing']->labels->name ),
				'label' => __( 'Title', 'wp-event-manager' ),
			],
			'keyword'   => [
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Keyword', 'wp-event-manager' ),
			],
			'location'  => [
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Location', 'wp-event-manager' ),
			],
			'number'    => [
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 10,
				'label' => __( 'Number of listings to show', 'wp-event-manager' ),
			],
			'show_logo' => [
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => esc_html__( 'Show Company Logo', 'wp-event-manager' ),
			],
		];

		parent::__construct();
	}

	/**
	 * Echoes the widget content.
	 *
	 * @see WP_Widget
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		wp_enqueue_style( 'wp-event-manager-event-listings' );

		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		$instance = array_merge( $this->get_default_instance(), $instance );

		ob_start();

		$title     = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number    = absint( $instance['number'] );
		$events      = get_event_listings(
			[
				'search_location' => $instance['location'],
				'search_keywords' => $instance['keyword'],
				'posts_per_page'  => $number,
				'orderby'         => 'date',
				'order'           => 'DESC',
			]
		);
		$show_logo = absint( $instance['show_logo'] );

		/**
		 * Runs before Recent events widget content.
		 *
		 * @since 1.29.1
		 *
		 * @param array    $args
		 * @param array    $instance
		 * @param WP_Query $events
		 */
		do_action( 'event_manager_recent_events_widget_before', $args, $instance, $events );

		if ( $events->have_posts() ) : ?>

			<?php echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php
			if ( $title ) {
				echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<ul class="event_listings">

				<?php
				while ( $events->have_posts() ) :
					$events->the_post();
					?>

					<?php get_event_manager_template( 'content-widget-event_listing.php', [ 'show_logo' => $show_logo ] ); ?>

				<?php endwhile; ?>

			</ul>

			<?php echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php else : ?>

			<?php get_event_manager_template_part( 'content-widget', 'no-events-found' ); ?>

			<?php
		endif;

		/**
		 * Runs after Recent events widget content.
		 *
		 * @since 1.29.1
		 *
		 * @param array    $args
		 * @param array    $instance
		 * @param WP_Query $events
		 */
		do_action( 'event_manager_recent_events_widget_after', $args, $instance, $events );

		wp_reset_postdata();

		$content = ob_get_clean();

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$this->cache_widget( $args, $content );
	}
}

register_widget( 'WP_event_Manager_Widget_Recent_events' );
