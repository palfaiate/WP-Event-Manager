<?php
/**
 * event dashboard shortcode content.
 *
 * This template can be overridden by copying it to yourtheme/event_manager/event-dashboard.php.
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
<div id="event-manager-event-dashboard">
	<p><?php esc_html_e( 'Your listings are shown in the table below.', 'wp-event-manager' ); ?></p>
	<table class="event-manager-events">
		<thead>
			<tr>
				<?php foreach ( $event_dashboard_columns as $key => $column ) : ?>
					<th class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $events ) : ?>
				<tr>
					<td colspan="<?php echo intval( count( $event_dashboard_columns ) ); ?>"><?php esc_html_e( 'You do not have any active listings.', 'wp-event-manager' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $events as $event ) : ?>
					<tr>
						<?php foreach ( $event_dashboard_columns as $key => $column ) : ?>
							<td class="<?php echo esc_attr( $key ); ?>">
								<?php if ('event_title' === $key ) : ?>
									<?php if ( $event->post_status == 'publish' ) : ?>
										<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"><?php wpjm_the_event_title( $event ); ?></a>
									<?php else : ?>
										<?php wpjm_the_event_title( $event ); ?> <small>(<?php the_event_status( $event ); ?>)</small>
									<?php endif; ?>
									<?php echo is_position_featured( $event ) ? '<span class="featured-event-icon" title="' . esc_attr__( 'Featured event', 'wp-event-manager' ) . '"></span>' : ''; ?>
									<ul class="event-dashboard-actions">
										<?php
											$actions = [];

											switch ( $event->post_status ) {
												case 'publish' :
													if ( wpjm_user_can_edit_published_submissions() ) {
														$actions[ 'edit' ] = [ 'label' => __( 'Edit', 'wp-event-manager' ), 'nonce' => false ];
													}
													if ( is_position_filled( $event ) ) {
														$actions['mark_not_filled'] = [ 'label' => __( 'Mark not filled', 'wp-event-manager' ), 'nonce' => true ];
													} else {
														$actions['mark_filled'] = [ 'label' => __( 'Mark filled', 'wp-event-manager' ), 'nonce' => true ];
													}

													$actions['duplicate'] = [ 'label' => __( 'Duplicate', 'wp-event-manager' ), 'nonce' => true ];
													break;
												case 'expired' :
													if ( event_manager_get_permalink( 'submit_event_form' ) ) {
														$actions['relist'] = [ 'label' => __( 'Relist', 'wp-event-manager' ), 'nonce' => true ];
													}
													break;
												case 'pending_payment' :
												case 'pending' :
													if ( event_manager_user_can_edit_pending_submissions() ) {
														$actions['edit'] = [ 'label' => __( 'Edit', 'wp-event-manager' ), 'nonce' => false ];
													}
												break;
												case 'draft' :
												case 'preview' :
													$actions['continue'] = [ 'label' => __( 'Continue Submission', 'wp-event-manager' ), 'nonce' => true ];
													break;
											}

											$actions['delete'] = [ 'label' => __( 'Delete', 'wp-event-manager' ), 'nonce' => true ];
											$actions           = apply_filters( 'event_manager_my_event_actions', $actions, $event );

											foreach ( $actions as $action => $value ) {
												$action_url = add_query_arg( [ 'action' => $action, 'event_id' => $event->ID ] );
												if ( $value['nonce'] ) {
													$action_url = wp_nonce_url( $action_url, 'event_manager_my_event_actions' );
												}
												echo '<li><a href="' . esc_url( $action_url ) . '" class="event-dashboard-action-' . esc_attr( $action ) . '">' . esc_html( $value['label'] ) . '</a></li>';
											}
										?>
									</ul>
								<?php elseif ('date' === $key ) : ?>
									<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->post_date ) ) ); ?>
								<?php elseif ('expires' === $key ) : ?>
									<?php echo esc_html( $event->_event_expires ? date_i18n( get_option( 'date_format' ), strtotime( $event->_event_expires ) ) : '&ndash;' ); ?>
								<?php elseif ('filled' === $key ) : ?>
									<?php echo is_position_filled( $event ) ? '&#10004;' : '&ndash;'; ?>
								<?php else : ?>
									<?php do_action( 'event_manager_event_dashboard_column_' . $key, $event ); ?>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<?php get_event_manager_template( 'pagination.php', [ 'max_num_pages' => $max_num_pages ] ); ?>
</div>
