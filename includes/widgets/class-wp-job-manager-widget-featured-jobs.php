<?php
/**
 * File containing the class WP_Job_Manager_Widget_Featured_Jobs.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Featured Jobs widget.
 *
 * @package wp-event-manager
 * @since 1.21.0
 */
class WP_Job_Manager_Widget_Featured_Jobs extends WP_Job_Manager_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_post_types;

		// translators: Placeholder %s is the plural label for the job listing post type.
		$this->widget_name        = sprintf( __( 'Featured %s', 'wp-event-manager' ), $wp_post_types['job_listing']->labels->name );
		$this->widget_cssclass    = 'job_manager widget_featured_jobs';
		$this->widget_description = __( 'Display a list of featured listings on your site.', 'wp-event-manager' );
		$this->widget_id          = 'widget_featured_jobs';
		$this->settings           = [
			'title'     => [
				'type'  => 'text',
				// translators: Placeholder %s is the plural label for the job listing post type.
				'std'   => sprintf( __( 'Featured %s', 'wp-event-manager' ), $wp_post_types['job_listing']->labels->name ),
				'label' => __( 'Title', 'wp-event-manager' ),
			],
			'number'    => [
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 10,
				'label' => __( 'Number of listings to show', 'wp-event-manager' ),
			],
			'orderby'   => [
				'type'    => 'select',
				'std'     => 'date',
				'label'   => __( 'Sort By', 'wp-event-manager' ),
				'options' => [
					'date'          => __( 'Date', 'wp-event-manager' ),
					'title'         => __( 'Title', 'wp-event-manager' ),
					'author'        => __( 'Author', 'wp-event-manager' ),
					'rand_featured' => __( 'Random', 'wp-event-manager' ),
				],
			],
			'order'     => [
				'type'    => 'select',
				'std'     => 'DESC',
				'label'   => __( 'Sort Direction', 'wp-event-manager' ),
				'options' => [
					'ASC'  => __( 'Ascending', 'wp-event-manager' ),
					'DESC' => __( 'Descending', 'wp-event-manager' ),
				],
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
		wp_enqueue_style( 'wp-event-manager-job-listings' );

		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		$instance = array_merge( $this->get_default_instance(), $instance );

		ob_start();

		$title_instance = esc_attr( $instance['title'] );
		$number         = absint( $instance['number'] );
		$orderby        = esc_attr( $instance['orderby'] );
		$order          = esc_attr( $instance['order'] );
		$title          = apply_filters( 'widget_title', $title_instance, $instance, $this->id_base );
		$show_logo      = absint( $instance['show_logo'] );
		$jobs           = get_job_listings(
			[
				'posts_per_page' => $number,
				'orderby'        => $orderby,
				'order'          => $order,
				'featured'       => true,
			]
		);

		if ( $jobs->have_posts() ) : ?>

			<?php echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php
			if ( $title ) {
				echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<ul class="job_listings">

				<?php
				while ( $jobs->have_posts() ) :
					$jobs->the_post();
					?>

					<?php get_job_manager_template( 'content-widget-job_listing.php', [ 'show_logo' => $show_logo ] ); ?>

				<?php endwhile; ?>

			</ul>

			<?php echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php else : ?>

			<?php get_job_manager_template_part( 'content-widget', 'no-jobs-found' ); ?>

			<?php
		endif;

		wp_reset_postdata();

		$content = ob_get_clean();

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$this->cache_widget( $args, $content );
	}
}

register_widget( 'WP_Job_Manager_Widget_Featured_Jobs' );
