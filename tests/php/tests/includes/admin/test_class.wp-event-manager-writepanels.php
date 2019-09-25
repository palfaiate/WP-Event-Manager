<?php

require event_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-event-manager-writepanels.php';

class WP_Test_WP_event_Manager_Writepanels extends WPJM_BaseTest {

	public function setUp() {
		parent::setUp();
		$this->enable_manage_event_listings_cap();
	}

	public function data_provider_test_save_event_data_auto_expire() {
		$expired_date = date( 'Y-m-d', strtotime( '-2 months', current_time( 'timestamp' ) ) );
		$future_date  = date( 'Y-m-d', strtotime( '+2 months', current_time( 'timestamp' ) ) );
		$duration     = absint( get_option( 'event_manager_submission_duration' ) );
		$auto_date    = date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );

		return [
			/**
			 * Tests to make sure auto-expiring works.
			 */
			'autoexpire_publish_future_publish'      => [
				// On published post, set to future date and expect published.
				[
					'original' => 'publish',
					'new'      => null,
					'expected' => 'publish',
				],
				[
					'original' => $future_date,
					'new'      => $future_date,
					'expected' => $future_date,
				],
			],
			'autoexpire_publish_past_expired'        => [
				// On published post, set to past date and expect expired.
				[
					'original' => 'publish',
					'new'      => 'publish',
					'expected' => 'expired',
				],
				[
					'original' => $future_date,
					'new'      => $expired_date,
					'expected' => $expired_date,
				],
			],
			'autoexpire_draft_past_expired'          => [
				// On draft post, set to past date and expect expired.
				[
					'original' => 'draft',
					'new'      => 'publish',
					'expected' => 'expired',
				],
				[
					'original' => $future_date,
					'new'      => $expired_date,
					'expected' => $expired_date,
				],
			],
			'autoexpire_draft_future_publish'        => [
				// On draft post, set to future date and expect expired.
				[
					'original' => 'draft',
					'new'      => 'publish',
					'expected' => 'publish',
				],
				[
					'original' => $future_date,
					'new'      => $future_date,
					'expected' => $future_date,
				],
			],
			'autoexpire_expired_future_keep_expired' => [
				// On expired post, set to future date and expect expired to be preserved.
				[
					'original' => 'expired',
					'new'      => null,
					'expected' => 'expired',
				],
				[
					'original' => $expired_date,
					'new'      => $future_date,
					'expected' => $future_date,
				],
			],

			/**
			 * Tests to make sure changes to draft is preserved.
			*/
			'draft_publish_draft'                    => [
				// From publish to draft (not touching expiration date) we should get a draft.
				[
					'original' => 'publish',
					'new'      => 'draft',
					'expected' => 'draft',
				],
				null,
			],
			'draft_expired_draft'                    => [
				// From expired to draft (not touching expiration date) we should get a draft.
				[
					'original' => 'expired',
					'new'      => 'draft',
					'expected' => 'draft',
				],
				null,
			],
			'draft_publish_draft_set_expired_date'   => [
				// From publish to draft (setting an expired expiration date) we should get a draft.
				[
					'original' => 'publish',
					'new'      => 'draft',
					'expected' => 'draft',
				],
				[
					'original' => $future_date,
					'new'      => $expired_date,
					'expected' => $expired_date,
				],
			],
			'draft_publish_draft_keep_expired_date'  => [
				// From publish to draft (keeping an expired expiration date) we should get a draft.
				[
					'original' => 'publish',
					'new'      => 'draft',
					'expected' => 'draft',
				],
				[
					'original' => $expired_date,
					'new'      => $expired_date,
					'expected' => $expired_date,
				],
			],
			'draft_expired_draft_set_expired'        => [
				// From expired to draft (setting an expired expiration date) we should get a draft.
				[
					'original' => 'expired',
					'new'      => 'draft',
					'expected' => 'draft',
				],
				[
					'original' => $future_date,
					'new'      => $expired_date,
					'expected' => $expired_date,
				],
			],
			'draft_expired_draft_keep_expired'       => [
				// From expired to draft (keeping an expired expiration date) we should get a draft.
				[
					'original' => 'expired',
					'new'      => 'draft',
					'expected' => 'draft',
				],
				[
					'original' => $expired_date,
					'new'      => $expired_date,
					'expected' => $expired_date,
				],
			],
		];
	}

	/**
	 * @covers WP_event_Manager_Writepanels::save_event_listing_data
	 * @dataProvider data_provider_test_save_event_data_auto_expire
	 */
	public function test_save_event_data_auto_expire( $status_data = null, $expires_data = null ) {
		$writepanels = WP_event_Manager_Writepanels::instance();

		$this->login_as_admin();
		$original_event_data = [];
		if ( null !== $status_data && null !== $status_data['original'] ) {
			$original_event_data['post_status'] = $status_data['original'];
		}
		if ( null !== $expires_data && null !== $expires_data['original'] ) {
			$original_event_data['meta_input'] = [ '_event_expires' => $expires_data['original'] ];
		}
		if ( null !== $status_data ) {
			$new_event_data = [
				'original_post_status' => $status_data['original'],
				'post_status'          => $status_data['new'],
			];
		}
		if ( null !== $expires_data && null !== $expires_data['new'] ) {
			$new_event_data['_event_expires'] = $expires_data['new'];
		}
		$event = $this->mock_writepanel_save_request( $new_event_data, $original_event_data );
		if ( null !== $status_data && null !== $status_data['new'] ) {
			wp_update_post(
				[
					'ID'          => $event->ID,
					'post_status' => $status_data['new'],
				]
			);
		}

		$writepanels->save_event_listing_data( $event->ID, $event );
		if ( $status_data ) {
			$this->assertEquals( $status_data['expected'], get_post_status( $event->ID ), sprintf( 'Expected post status of %s after emulating a save where the original post status was %s and new post status was %s', $status_data['expected'], $status_data['original'], $status_data['new'] ) );
		}
		if ( $expires_data ) {
			$this->assertEquals( $expires_data['expected'], get_post_meta( $event->ID, '_event_expires', true ), sprintf( 'Expected event expiration of %s after emulating a save where the original expiration was %s and the new expiration is %s', $expires_data['expected'], $expires_data['original'], $expires_data['new'] ) );
		}
	}

	private function mock_writepanel_save_request( $new_event_data = [], $original_event_data = [] ) {
		global $post;
		$event_id = $this->factory->event_listing->create( $original_event_data );
		$event    = get_post( $event_id );
		$post   = $event;

		$_POST                     = [];
		$_POST['_event_expires']     = $event->_event_expires;
		$_POST['_event_location']    = $event->_event_location;
		$_POST['_event_author']      = $event->_event_author;
		$_POST['_application']     = $event->_application;
		$_POST['_company_name']    = $event->_company_name;
		$_POST['_company_website'] = $event->_company_website;
		$_POST['_company_tagline'] = $event->_company_tagline;
		$_POST['_company_twitter'] = $event->_company_twitter;
		$_POST['_company_video']   = $event->_company_video;
		$_POST['_filled']          = $event->_filled;
		$_POST['_featured']        = $event->_featured;

		$_POST['post_status']          = 'publish';
		$_POST['original_post_status'] = $event->post_status;

		$_POST = array_merge( $_POST, $new_event_data );

		return $event;
	}
}
