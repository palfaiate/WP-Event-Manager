<?php
/**
 * Usage tracking unit test cases
 *
 * @package Usage Tracking
 **/

/**
 * Usage tracking unit test cases
 *
 * @package Usage Tracking
 **/
class WP_Test_WP_event_Manager_Usage_Tracking_Data extends WPJM_BaseTest {
	/**
	 * IDs for event listings that are in draft status
	 *
	 * @var array
	 */
	private $draft;

	/**
	 * IDs for event listings that have expired
	 *
	 * @var array
	 */
	private $expired;

	/**
	 * IDs for event listings that are in preview status
	 *
	 * @var array
	 */
	private $preview;

	/**
	 * IDs for event listings that are pending approval
	 *
	 * @var array
	 */
	private $pending;

	/**
	 * IDs for event listings that are pending payment
	 *
	 * @var array
	 */
	private $pending_payment;

	/**
	 * IDs for event listings that are published
	 *
	 * @var array
	 */
	private $publish;

	/**
	 * Create a number of event listings with different statuses.
	 */
	private function create_default_event_listings() {
		$this->draft           = $this->factory->event_listing->create_many(
			2,
			[ 'post_status' => 'draft' ]
		);
		$this->expired         = $this->factory->event_listing->create_many(
			10,
			[ 'post_status' => 'expired' ]
		);
		$this->preview         = $this->factory->event_listing->create_many(
			1,
			[ 'post_status' => 'preview' ]
		);
		$this->pending         = $this->factory->event_listing->create_many(
			8,
			[ 'post_status' => 'pending' ]
		);
		$this->pending_payment = $this->factory->event_listing->create_many(
			3,
			[ 'post_status' => 'pending_payment' ]
		);
		$this->publish         = $this->factory->event_listing->create_many(
			15,
			[ 'post_status' => 'publish' ]
		);
	}

	/**
	 * Tests that get_usage_data() returns the correct number of users with the
	 * "employer" role.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_employers() {
		$employer_count   = 3;
		$subscriber_count = 2;

		$this->factory->user->create_many(
			$employer_count,
			[ 'role' => 'employer' ]
		);
		$this->factory->user->create_many(
			$subscriber_count,
			[ 'role' => 'subscriber' ]
		);

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $employer_count, $data['employers'] );
	}

	/**
	 * Count of event categories.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_event_categories_count() {
		$terms = $this->factory->term->create_many( 14, [ 'taxonomy' => 'event_listing_category' ] );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 14, $data['event_categories'] );
	}

	/**
	 * Count of event categories.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_no_event_categories_count() {
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 0, $data['event_categories'] );
	}

	/**
	 * Count of event categories that have a description.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_event_category_has_description_count
	 */
	public function test_get_event_category_has_description_count() {
		// Create some terms with varying descriptions.
		$valid   = $this->factory->term->create_many(
			2,
			[
				'taxonomy'    => 'event_listing_category',
				'description' => ' Valid description ',
			]
		);
		$invalid = $this->factory->term->create(
			[
				'taxonomy'    => 'event_listing_category',
				'description' => "\t\n",
			]
		);

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 2, $data['event_categories_desc'] );
	}

	/**
	 * Count of event categories that have a description.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_event_category_has_description_count
	 */
	public function test_get_no_event_category_has_description_count() {
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 0, $data['event_categories_desc'] );
	}

	/**
	 * Count of event types.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_event_types_count() {
		$terms = $this->factory->term->create_many( 14, [ 'taxonomy' => 'event_listing_type' ] );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 14, $data['event_types'] );
	}

	/**
	 * Count of event types that have a description.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_event_type_has_description_count
	 */
	public function test_get_event_type_has_description_count() {
		// Create some terms with varying descriptions.
		$valid   = $this->factory->term->create_many(
			2,
			[
				'taxonomy'    => 'event_listing_type',
				'description' => ' Valid description ',
			]
		);
		$invalid = $this->factory->term->create(
			[
				'taxonomy'    => 'event_listing_type',
				'description' => "\t\n",
			]
		);

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 2, $data['event_types_desc'] );
	}

	/**
	 * Count of event types that have en employment type.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_event_type_has_employment_type_count
	 */
	public function test_get_event_type_has_employment_type_count() {
		$terms = $this->factory->term->create_many( 5, [ 'taxonomy' => 'event_listing_type' ] );

		// Set the employment type for some terms.
		add_term_meta( $terms[1], 'employment_type', 'FULL_TIME' );
		add_term_meta( $terms[2], 'employment_type', 'VOLUNTEER' );
		add_term_meta( $terms[4], 'employment_type', 'TEMPORARY' );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 3, $data['event_types_emp_type'] );
	}

	/**
	 * Count of freelance events.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_events_by_type_count
	 */
	public function test_get_freelance_events_count() {
		$this->create_default_event_listings();

		wp_set_object_terms( $this->draft[0], 'freelance', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[5], 'freelance', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[6], 'freelance', 'event_listing_type', false );
		wp_set_object_terms( $this->preview[0], 'freelance', 'event_listing_type', false );
		wp_set_object_terms( $this->pending[3], 'freelance', 'event_listing_type', false );
		wp_set_object_terms( $this->publish[9], 'freelance', 'event_listing_type', false );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 3, $data['events_freelance'], 'Freelance' );
	}

	/**
	 * Count of full-time events.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_events_by_type_count
	 */
	public function test_get_full_time_events_count() {
		$this->create_default_event_listings();

		wp_set_object_terms( $this->draft[0], 'full-time', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[5], 'full-time', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[6], 'full-time', 'event_listing_type', false );
		wp_set_object_terms( $this->preview[0], 'full-time', 'event_listing_type', false );
		wp_set_object_terms( $this->pending[3], 'full-time', 'event_listing_type', false );
		wp_set_object_terms( $this->publish[9], 'full-time', 'event_listing_type', false );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 3, $data['events_full_time'], 'Full Time' );
	}

	/**
	 * Count of internship events.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_events_by_type_count
	 */
	public function test_get_internship_events_count() {
		$this->create_default_event_listings();

		wp_set_object_terms( $this->draft[0], 'internship', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[5], 'internship', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[6], 'internship', 'event_listing_type', false );
		wp_set_object_terms( $this->preview[0], 'internship', 'event_listing_type', false );
		wp_set_object_terms( $this->pending[3], 'internship', 'event_listing_type', false );
		wp_set_object_terms( $this->publish[9], 'internship', 'event_listing_type', false );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 3, $data['events_intern'], 'Internship' );
	}

	/**
	 * Count of part-time events.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_events_by_type_count
	 */
	public function test_get_part_time_events_count() {
		$this->create_default_event_listings();

		wp_set_object_terms( $this->draft[0], 'part-time', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[5], 'part-time', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[6], 'part-time', 'event_listing_type', false );
		wp_set_object_terms( $this->preview[0], 'part-time', 'event_listing_type', false );
		wp_set_object_terms( $this->pending[3], 'part-time', 'event_listing_type', false );
		wp_set_object_terms( $this->publish[9], 'part-time', 'event_listing_type', false );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 3, $data['events_part_time'], 'Part Time' );
	}

	/**
	 * Count of temporary events.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_events_by_type_count
	 */
	public function test_get_temporary_events_count() {
		$this->create_default_event_listings();

		wp_set_object_terms( $this->draft[0], 'temporary', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[5], 'temporary', 'event_listing_type', false );
		wp_set_object_terms( $this->expired[6], 'temporary', 'event_listing_type', false );
		wp_set_object_terms( $this->preview[0], 'temporary', 'event_listing_type', false );
		wp_set_object_terms( $this->pending[3], 'temporary', 'event_listing_type', false );
		wp_set_object_terms( $this->publish[9], 'temporary', 'event_listing_type', false );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 3, $data['events_temp'], 'Temporary' );
	}

	/**
	 * Expired events count.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_get_usage_data_expired_events() {
		$this->create_default_event_listings();
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( count( $this->expired ), $data['events_status_expired'] );
	}

	/**
	 * Pending events count.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_get_usage_data_pending_events() {
		$this->create_default_event_listings();
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( count( $this->pending ), $data['events_status_pending'] );
	}

	/**
	 * Pending payment events count.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_get_usage_data_pending_payment_events() {
		$this->create_default_event_listings();
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( count( $this->pending_payment ), $data['events_status_pending_payment'] );
	}

	/**
	 * Preview events count.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_get_usage_data_preview_events() {
		$this->create_default_event_listings();
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( count( $this->preview ), $data['events_status_preview'] );
	}

	/**
	 * Published events count.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_get_usage_data_publish_events() {
		$this->create_default_event_listings();
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( count( $this->publish ), $data['events_status_publish'] );
	}

	/**
	 * events with a company logo count.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_company_logo_count
	 */
	public function test_get_company_logo_count() {
		$this->create_default_event_listings();

		// Create some media attachments.
		$media = $this->factory->attachment->create_many(
			6,
			[
				'post_type'   => 'event_listing',
				'post_status' => 'publish',
			]
		);

		// Add logos to some listings with varying statuses.
		add_post_meta( $this->draft[0], '_thumbnail_id', $media[0] );
		add_post_meta( $this->expired[5], '_thumbnail_id', $media[1] );
		add_post_meta( $this->expired[6], '_thumbnail_id', $media[2] );
		add_post_meta( $this->preview[0], '_thumbnail_id', $media[3] );
		add_post_meta( $this->pending[3], '_thumbnail_id', $media[4] );
		add_post_meta( $this->publish[9], '_thumbnail_id', $media[5] );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		// 2 expired + 1 publish.
		$this->assertEquals( 3, $data['events_logo'] );
	}

	/**
	 * events with a company logo count.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_event_type_count
	 */
	public function test_get_event_type_count() {
		$this->create_default_event_listings();
		$terms = $this->factory->term->create_many( 6, [ 'taxonomy' => 'event_listing_type' ] );

		// Assign event types to some events.
		wp_set_object_terms( $this->draft[0], $terms[0], 'event_listing_type', false );
		wp_set_object_terms( $this->expired[5], $terms[1], 'event_listing_type', false );
		wp_set_object_terms( $this->expired[6], $terms[2], 'event_listing_type', false );
		wp_set_object_terms( $this->preview[0], $terms[3], 'event_listing_type', false );
		wp_set_object_terms( $this->pending[3], $terms[4], 'event_listing_type', false );
		wp_set_object_terms( $this->publish[9], $terms[5], 'event_listing_type', false );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();

		// 2 expired + 1 publish.
		$this->assertEquals( 3, $data['events_type'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with a location.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_location() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_event_location', 'Toronto', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_location'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with an application email or URL.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_application_contact() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_application', 'email@example.com', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_app_contact'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with a company name.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_company_name() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_company_name', 'Automattic', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_company_name'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with a company website.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_company_website() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_company_website', 'automattic.com', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_company_site'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with a company tagline.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_company_tagline() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_company_tagline', 'We are passionate about making the web a better place.', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_company_tagline'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with a company twitter handle.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_company_twitter() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_company_twitter', '@automattic', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_company_twitter'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with a company video.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_company_video() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_company_video', 'youtube.com/1234', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_company_video'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with an expiry date.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_expiry() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_event_expires', '2018-01-01', $published, $expired );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_expiry'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with the Position Filled box checked.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_filled() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_filled', '1', $published, $expired, [ '0' ] );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_filled'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * with the Featured Listing box checked.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_featured() {
		$published = 3;
		$expired   = 2;

		$this->create_event_listings_with_meta( '_featured', '1', $published, $expired, [ '0' ] );

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published + $expired, $data['events_featured'] );
	}

	/**
	 * Tests that get_usage_data() returns the correct number of event listings
	 * posted by guests.
	 *
	 * @since 1.30.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_usage_data
	 */
	public function test_events_by_guests() {
		$published_by_guest = 3;
		$expired_by_guest   = 2;

		// Create published listings.
		$this->factory->event_listing->create_many(
			$published_by_guest,
			[
				'post_author' => '0',
			]
		);

		// Create expired listings.
		$this->factory->event_listing->create_many(
			$expired_by_guest,
			[
				'post_author' => '0',
				'post_status' => 'expired',
			]
		);

		// Create guest listings with other statuses.
		$statuses = [ 'future', 'draft', 'pending', 'private', 'trash' ];
		foreach ( $statuses as $status ) {
			$params = [
				'post_author' => '0',
				'post_status' => $status,
			];

			if ( 'future' === $status ) {
				$params['post_date'] = '3018-02-15 00:00:00';
			}

			$this->factory->event_listing->create( $params );
		}

		// Create listings with other author.
		$all_statuses = array_merge( $statuses, [ 'publish', 'expired' ] );
		$author_id    = $this->factory->user->create();
		foreach ( $all_statuses as $status ) {
			$params = [
				'post_author' => $author_id,
				'post_status' => $status,
			];

			if ( 'future' === $status ) {
				$params['post_date'] = '3018-02-15 00:00:00';
			}

			$this->factory->event_listing->create( $params );
		}

		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( $published_by_guest + $expired_by_guest, $data['events_by_guests'] );
	}

	/**
	 * Checks count of official plugins and licensed extensions when none are licensed.
	 *
	 * @since 1.33.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_official_extensions_count
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_licensed_extensions_count
	 */
	public function test_get_official_no_license_plugin_count() {
		$this->set_fake_plugins();
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->restore_default_plugins();

		$this->assertEquals( 2, $data['official_extensions'] );
		$this->assertEquals( 0, $data['licensed_extensions'] );
	}

	/**
	 * Checks count of official plugins and licensed extensions when one of the two plugins are licensed.
	 *
	 * @since 1.33.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_official_extensions_count
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_licensed_extensions_count
	 */
	public function test_get_official_with_license_plugin_count() {
		$this->set_fake_plugins();
		$this->set_fake_license();
		$data = WP_event_Manager_Usage_Tracking_Data::get_usage_data();
		$this->restore_default_plugins();
		$this->remove_fake_license();

		$this->assertEquals( 2, $data['official_extensions'] );
		$this->assertEquals( 1, $data['licensed_extensions'] );
	}

	/**
	 * Checks paid flag is 0 when there are no official extensions.
	 *
	 * @since 1.33.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_event_logging_base_fields
	 */
	public function test_get_event_logging_base_fields_paid_without_extensions() {
		$base_fields = WP_event_Manager_Usage_Tracking_Data::get_event_logging_base_fields();

		$this->assertEquals( 0, $base_fields['paid'] );
	}

	/**
	 * Checks paid flag is 0 when there are official extensions.
	 *
	 * @since 1.33.0
	 * @covers WP_event_Manager_Usage_Tracking_Data::get_event_logging_base_fields
	 */
	public function test_get_event_logging_base_fields_paid_with_extensions() {
		$this->set_fake_plugins();
		$base_fields = WP_event_Manager_Usage_Tracking_Data::get_event_logging_base_fields();
		$this->restore_default_plugins();

		$this->assertEquals( 1, $base_fields['paid'] );
	}

	/**
	 * Adds fake license to one of the products.
	 */
	private function set_fake_license() {
		WP_event_Manager_Helper_Options::update( 'wp-event-manager-official-licensed-tester', 'licence_key', 'FAKE-LICENSE' );
		WP_event_Manager_Helper_Options::update( 'wp-event-manager-official-licensed-tester', 'email', 'fake@example.com' );
		WP_event_Manager_Helper_Options::update( 'wp-event-manager-official-licensed-tester', 'errors', [] );
	}

	/**
	 * Removes fake license to one of the products.
	 */
	private function remove_fake_license() {
		WP_event_Manager_Helper_Options::delete( 'wp-event-manager-official-licensed-tester', 'licence_key' );
		WP_event_Manager_Helper_Options::delete( 'wp-event-manager-official-licensed-tester', 'email' );
		WP_event_Manager_Helper_Options::delete( 'wp-event-manager-official-licensed-tester', 'errors' );
	}

	/**
	 * Restores the default plugins.
	 */
	private function restore_default_plugins() {
		wp_clean_plugins_cache();
		update_option( 'active_plugins', [] );
		remove_filter( 'event_manager_clear_plugin_cache', '__return_false' );
	}

	/**
	 * Sets up some fake plugins, including fake official extensions.
	 */
	private function set_fake_plugins() {
		add_filter( 'event_manager_clear_plugin_cache', '__return_false' );
		$plugins =  [
			'hello.php' =>  [
				'WPJM-Product' => '',
				'Name' => 'Hello Dolly',
				'PluginURI' => 'http://wordpress.org/plugins/hello-dolly/',
				'Version' => '1.7.2',
				'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
				'Author' => 'Matt Mullenweg',
				'AuthorURI' => 'http://ma.tt/',
				'TextDomain' => '',
				'DomainPath' => '',
				'Network' => false,
				'Title' => 'Hello Dolly',
				'AuthorName' => 'Matt Mullenweg',
			],
			'wp-event-manager-tester/wp-event-manager-tester.php' =>  [
				'WPJM-Product' => '',
				'Name' => 'WP Event Manager Tester',
				'PluginURI' => 'http://wordpress.org/plugins/wp-event-manager-tester/',
				'Version' => '1.0.0',
				'Description' => 'Just a test plugin.',
				'Author' => 'Example',
				'AuthorURI' => 'http://example.com/',
				'TextDomain' => 'wp-event-manager-tester',
				'DomainPath' => '',
				'Network' => false,
				'Title' => 'WP Event Manager Tester',
				'AuthorName' => 'Example',
			],
			'wp-event-manager-official-tester/wp-event-manager-official-tester.php' =>  [
				'WPJM-Product' => 'wp-event-manager-official-tester',
				'Name' => 'WP Event Manager Official Tester',
				'PluginURI' => 'http://wpeventmanager.com',
				'Version' => '1.0.0',
				'Description' => 'Just a test plugin.',
				'Author' => 'Example',
				'AuthorURI' => 'http://example.com/',
				'TextDomain' => 'wp-event-manager-official-tester',
				'DomainPath' => '',
				'Network' => false,
				'Title' => 'WP Event Manager Official Tester',
				'AuthorName' => 'Example',
			],
			'wp-event-manager-official-licensed-tester/wp-event-manager-official-licensed-tester.php' =>  [
				'WPJM-Product' => 'wp-event-manager-official-licensed-tester',
				'Name' => 'WP Event Manager Official Licensed Tester',
				'PluginURI' => 'http://wpeventmanager.com',
				'Version' => '1.0.0',
				'Description' => 'Just a test plugin.',
				'Author' => 'Example',
				'AuthorURI' => 'http://example.com/',
				'TextDomain' => 'wp-event-manager-official-licensed-tester',
				'DomainPath' => '',
				'Network' => false,
				'Title' => 'WP Event Manager Official Licensed Tester',
				'AuthorName' => 'Example',
			],
		];

		update_option( 'active_plugins', array_keys( $plugins ) );
		wp_cache_set( 'plugins', [ '' => $plugins ], 'plugins' );
	}

	/**
	 * Creates event listings with the given meta values. This will also create
	 * some listings with values for the meta parameter that should be
	 * considered empty (e.g. spaces) and some entries with other statuses
	 * (such as draft). For tracking data, only the published and expired
	 * entries should be counted.
	 *
	 * @param string $meta_name the name of the meta parameter to set.
	 * @param string $meta_value the desired value of the meta parameter.
	 * @param int    $published the number of published listings to create.
	 * @param int    $expired the number of expired listings to create.
	 * @param int    $other_values other values for which to create listings (optional).
	 */
	private function create_event_listings_with_meta( $meta_name, $meta_value, $published, $expired, $other_values = [] ) {
		// Create published listings.
		$this->factory->event_listing->create_many(
			$published,
			[
				'meta_input' => [
					$meta_name => $meta_value,
				],
			]
		);

		// Create expired listings.
		$this->factory->event_listing->create_many(
			$expired,
			[
				'post_status' => 'expired',
				'meta_input'  => [
					$meta_name => $meta_value,
				],
			]
		);

		// Create listings with empty values.
		$empty_values = [ '', '   ', "\n\t", " \n \t " ];
		foreach ( $empty_values as $val ) {
			$this->factory->event_listing->create(
				[
					'meta_input' => [
						$meta_name => $val,
					],
				]
			);
		}

		// Create listings with other statuses.
		$statuses = [ 'future', 'draft', 'pending', 'private', 'trash' ];
		foreach ( $statuses as $status ) {
			$params = [
				'post_status' => $status,
				'meta_input'  => [
					$meta_name => $meta_value,
				],
			];

			if ( 'future' === $status ) {
				$params['post_date'] = '3018-02-15 00:00:00';
			}

			$this->factory->event_listing->create( $params );
		}

		// Create listings with other values.
		foreach ( $other_values as $val ) {
			$this->factory->event_listing->create(
				[
					'meta_input' => [
						$meta_name => $val,
					],
				]
			);
		}
	}
}
