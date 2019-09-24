<?php

class WP_Test_WP_event_Manager_Post_Types extends WPJM_BaseTest {
	public function setUp() {
		parent::setUp();
		$this->enable_manage_event_listings_cap();
		update_option( 'event_manager_enable_categories', 1 );
		update_option( 'event_manager_enable_types', 1 );
		$this->reregister_post_type();
		add_filter( 'event_manager_geolocation_enabled', '__return_false' );
	}

	public function tearDown() {
		parent::tearDown();
		add_filter( 'event_manager_geolocation_enabled', '__return_true' );
	}

	/**
	 * @since 1.33.0
	 * @covers WP_event_Manager_Post_Types::output_kses_post
	 */
	public function test_output_kses_post_simple() {
		$event_id = $this->factory->event_listing->create(
			[
			'post_content' => '<p>This is a simple event listing</p>',
			]
		);

		$test_content = wpjm_get_the_event_description( $event_id );

		ob_start();
		WP_event_Manager_Post_Types::output_kses_post( $test_content );
		$actual_content = ob_get_clean();

		$this->assertEquals( $test_content, $actual_content, 'No HTML should have been removed from this test.' );
	}

	/**
	 * @since 1.33.0
	 * @covers WP_event_Manager_Post_Types::output_kses_post
	 */
	public function test_output_kses_post_allow_embeds() {
		$event_id = $this->factory->event_listing->create(
			[
			'post_content' => '<p>This is a simple event listing</p><p>https://www.youtube.com/watch?v=S_GVbuddri8</p>',
			]
		);

		$test_content = wpjm_get_the_event_description( $event_id );

		ob_start();
		WP_event_Manager_Post_Types::output_kses_post( $test_content );
		$actual_content = ob_get_clean();

		$this->assertFalse( strpos( $actual_content, '<p>https://www.youtube.com/watch?v=S_GVbuddri8</p>' ), 'The YouTube link should have been expanded to an iframe' );
		$this->assertGreaterThan( 0, strpos( $actual_content, '<iframe ' ), 'The iframe should not have been filtered out' );
		$this->assertGreaterThan( 0, strpos( $actual_content, 'src="https://www.youtube.com' ), 'The iframe source should not have been filtered out' );
	}

	/**
	 * Tests the WP_event_Manager_Post_Types::instance() always returns the same `WP_event_Manager_API` instance.
	 *
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::instance
	 */
	public function test_wp_event_manager_post_types_instance() {
		$instance = WP_event_Manager_Post_Types::instance();
		// check the class.
		$this->assertInstanceOf( 'WP_event_Manager_Post_Types', $instance, 'event Manager Post Types object is instance of WP_event_Manager_Post_Types class' );

		// check it always returns the same object.
		$this->assertSame( WP_event_Manager_Post_Types::instance(), $instance, 'WP_event_Manager_Post_Types::instance() must always return the same object' );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::event_content
	 */
	public function test_event_content() {
		global $wp_query;
		$instance = WP_event_Manager_Post_Types::instance();
		$event_id   = $this->factory->event_listing->create();
		$post_id  = $this->factory->post->create();

		$events = $wp_query = new WP_Query(
			[
				'p'         => $event_id,
				'post_type' => 'event_listing',
			]
		);
		$this->assertEquals( 1, $events->post_count );
		$this->assertTrue( $events->is_single );

		// First test out of the loop and verify it just returns the original content.
		$post                    = $events->posts[0];
		$post_content_unfiltered = $instance->event_content( $post->post_content );
		$this->assertEquals( $post->post_content, $post_content_unfiltered );

		while ( $events->have_posts() ) {
			$events->the_post();
			$post = get_post();
			$this->assertTrue( is_singular( 'event_listing' ), 'Is singular === true' );
			$this->assertTrue( in_the_loop(), 'In the loop' );
			$this->assertEquals( 'event_listing', $post->post_type, 'Result is a event listing' );

			$post_content_filtered = $instance->event_content( $post->post_content );
			$this->assertNotEquals( $post->post_content, $post_content_filtered );
			$this->assertContains( '<div class="single_event_listing"', $post_content_filtered );

			ob_start();
			the_content();
			$post_content_filtered = ob_get_clean();
			$this->assertNotEquals( $post->post_content, $post_content_filtered );
			$this->assertContains( '<div class="single_event_listing"', $post_content_filtered );
			$this->assertContains( $post->post_content, $post_content_filtered );
		}
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::event_feed
	 * @runInSeparateProcess
	 */
	public function test_event_feed_rss2() {
		$this->factory->event_listing->create_many( 5 );
		$feed = $this->do_event_feed();
		$xml  = xml_to_array( $feed );
		$this->assertNotEmpty( $xml );
		// Get all the <item> child elements of the <channel> element.
		$items = xml_find( $xml, 'rss', 'channel', 'item' );
		$this->assertEquals( 5, count( $items ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::event_feed
	 * @runInSeparateProcess
	 */
	public function test_event_feed_rss2_2inrow() {
		$this->factory->event_listing->create_many( 5 );
		$feed = $this->do_event_feed();
		$xml  = xml_to_array( $feed );
		$this->assertNotEmpty( $xml );
		// Get all the <item> child elements of the <channel> element.
		$items = xml_find( $xml, 'rss', 'channel', 'item' );
		$this->assertEquals( 5, count( $items ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::event_feed
	 * @runInSeparateProcess
	 */
	public function test_event_feed_location_search() {
		$this->factory->event_listing->create_many(
			5,
			[
				'meta_input' => [
					'_event_location' => 'Portland, OR, USA',
				],
			]
		);
		$seattle_event_id = $this->factory->event_listing->create(
			[
				'meta_input' => [
					'_event_location' => 'Seattle, WA, USA',
				],
			]
		);
		$chicago_event_id = $this->factory->event_listing->create(
			[
				'meta_input' => [
					'_event_location' => 'Chicago, IL, USA',
				],
			]
		);

		$_GET['search_location'] = 'Seattle';
		$feed                    = $this->do_event_feed();
		unset( $_GET['search_location'] );
		$xml = xml_to_array( $feed );
		$this->assertNotEmpty( $xml );
		// Get all the <item> child elements of the <channel> element.
		$items = xml_find( $xml, 'rss', 'channel', 'item' );
		$this->assertEquals( 1, count( $items ) );
		$this->assertHasRssItem( $items, $seattle_event_id );
		$this->assertNotHasRssItem( $items, $chicago_event_id );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::event_feed
	 * @runInSeparateProcess
	 */
	public function test_event_feed_keyword_search() {
		$this->factory->event_listing->create_many( 3 );
		$dog_event_id  = $this->factory->event_listing->create(
			[
				'post_title' => 'Dog Whisperer',
			]
		);
		$dino_event_id = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Whisperer Pro',
			]
		);

		$_GET['search_keywords'] = 'Dinosaur';
		$feed                    = $this->do_event_feed();
		unset( $_GET['search_keywords'] );
		$xml = xml_to_array( $feed );
		$this->assertNotEmpty( $xml );
		// Get all the <item> child elements of the <channel> element.
		$items = xml_find( $xml, 'rss', 'channel', 'item' );
		$this->assertEquals( 1, count( $items ) );
		$this->assertHasRssItem( $items, $dino_event_id );
		$this->assertNotHasRssItem( $items, $dog_event_id );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::add_feed_query_args
	 */
	public function test_add_feed_query_args() {
		$instance = WP_event_Manager_Post_Types::instance();
		$wp       = new WP_Query();
		$this->assertEmpty( $wp->query_vars );
		$wp->query_vars['feed'] = 'event_feed';
		$wp->is_feed            = true;
		$instance->add_feed_query_args( $wp );
		$this->assertCount( 2, $wp->query_vars );
		$this->assertArrayHasKey( 'post_type', $wp->query_vars );
		$this->assertEquals( 'event_listing', $wp->query_vars['post_type'] );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::add_feed_query_args
	 */
	public function test_add_feed_query_args_if_not_feed() {
		$instance = WP_event_Manager_Post_Types::instance();
		$wp       = new WP_Query();
		$this->assertEmpty( $wp->query_vars );
		$wp->query_vars['feed'] = 'event_feed';
		$wp->is_feed            = false;
		$instance->add_feed_query_args( $wp );
		$this->assertCount( 1, $wp->query_vars );
		$this->assertArrayHasKey( 'feed', $wp->query_vars );

		$wp = new WP_Query();
		$this->assertEmpty( $wp->query_vars );
		$wp->query_vars['feed'] = 'something-else';
		$wp->is_feed            = true;
		$instance->add_feed_query_args( $wp );
		$this->assertCount( 1, $wp->query_vars );
		$this->assertArrayHasKey( 'feed', $wp->query_vars );
		$this->assertArrayNotHasKey( 'post_type', $wp->query_vars );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::event_feed_namespace
	 */
	public function test_event_feed_namespace() {
		$site_url = site_url();
		$instance = WP_event_Manager_Post_Types::instance();
		ob_start();
		$instance->event_feed_namespace();
		$result = ob_get_clean();
		$this->assertEquals( 'xmlns:event_listing="' . $site_url . '"' . "\n", $result );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::event_feed_item
	 */
	public function test_event_feed_item() {
		$instance       = WP_event_Manager_Post_Types::instance();
		$new_events       = [];
		$type_a         = wp_create_term( 'event Type A', 'event_listing_type' );
		$type_b         = wp_create_term( 'event Type B', 'event_listing_type' );
		$new_event_args   = [];
		$new_event_args[] = [
			'meta_input' => [
				'_company_name' => 'Custom Company A',
			],
			'tax_input'  => [
				'event_listing_type' => $type_a['term_id'],
			],
		];
		$new_event_args[] = [
			'meta_input' => [
				'_event_location' => 'Custom Location B',
				'_company_name' => '',
			],
			'tax_input'  => [
				'event_listing_type' => $type_b['term_id'],
			],
		];
		$new_event_args[] = [
			'meta_input' => [
				'_event_location' => 'Custom Location A',
				'_company_name' => 'Custom Company B',
			],
			'tax_input'  => [],
		];
		$new_events[]     = $this->factory->event_listing->create( $new_event_args[0] );
		$new_events[]     = $this->factory->event_listing->create( $new_event_args[1] );
		$new_events[]     = $this->factory->event_listing->create( $new_event_args[2] );
		$events           = $wp_query = new WP_Query(
			[
				'post_type' => 'event_listing',
				'orderby'   => 'ID',
				'order'     => 'ASC',
			]
		);
		$this->assertEquals( count( $new_events ), $events->post_count );

		$index = 0;
		while ( $events->have_posts() ) {
			$has_location = ! empty( $new_event_args[ $index ]['meta_input']['_event_location'] );
			$has_company  = ! empty( $new_event_args[ $index ]['meta_input']['_company_name'] );
			$has_event_type = ! empty( $new_event_args[ $index ]['tax_input']['event_listing_type'] );
			$index++;

			$events->the_post();
			$post = get_post();
			ob_start();
			$instance->event_feed_item();
			$result = ob_get_clean();
			$this->assertNotEmpty( $result );
			$result     = '<item>' . $result . '</item>';
			$result_arr = xml_to_array( $result );
			$this->assertNotEmpty( $result_arr );
			$this->assertTrue( isset( $result_arr[0]['child'] ) );
			$this->assertCount( 2, $result_arr[0]['child'] );

			if ( $has_location ) {
				$event_location = get_the_event_location( $post );
				$this->assertContains( 'event_listing:location', $result );
				$this->assertContains( $event_location, $result );
			} else {
				$this->assertNotContains( 'event_listing:location', $result );
			}

			if ( $has_event_type ) {
				$event_type = current( wpjm_get_the_event_types( $post ) );
				$this->assertContains( 'event_listing:event_type', $result );
				$this->assertContains( $event_type->name, $result );
			} else {
				$this->assertNotContains( 'event_listing:event_type', $result );
			}

			if ( $has_company ) {
				$company_name = get_the_company_name( $post );
				$this->assertContains( $company_name, $result );
				$this->assertContains( 'event_listing:company', $result );
			} else {
				$this->assertNotContains( 'event_listing:company', $result );
			}
		}
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::check_for_expired_events
	 */
	public function test_check_for_expired_events() {
		$new_events                 = [];
		$new_events['none']        = $this->factory->event_listing->create();
		delete_post_meta( $new_events['none'], '_event_expires' );
		$new_events['empty']         = $this->factory->event_listing->create();
		update_post_meta( $new_events['empty'], '_event_expires', '' );
		$new_events['invalid-none'] = $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => '0000-00-00' ] ] );
		$new_events['today']        = $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => date( 'Y-m-d' ) ] ] );
		$new_events['yesterday']    = $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => date( 'Y-m-d', strtotime( '-1 day' ) ) ] ] );
		$new_events['ancient']      = $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => date( 'Y-m-d', strtotime( '-100 day' ) ) ] ] );
		$new_events['tomorrow']     = $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => date( 'Y-m-d', strtotime( '+1 day' ) ) ] ] );
		$new_events['30daysago']    = $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => date( 'Y-m-d', strtotime( '-30 day' ) ) ] ] );
		$new_events['31daysago']    = $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => date( 'Y-m-d', strtotime( '-31 day' ) ) ] ] );

		$instance = WP_event_Manager_Post_Types::instance();
		$this->assertNotExpired( $new_events['none'] );
		$this->assertNotExpired( $new_events['empty'] );
		$this->assertNotExpired( $new_events['invalid-none'] );
		$this->assertNotExpired( $new_events['yesterday'] );
		$this->assertNotExpired( $new_events['today'] );
		$this->assertNotExpired( $new_events['ancient'] );
		$this->assertNotExpired( $new_events['tomorrow'] );
		$instance->check_for_expired_events();
		$this->assertNotExpired( $new_events['none'] );
		$this->assertNotExpired( $new_events['empty'] );
		$this->assertNotExpired( $new_events['invalid-none'] );
		$this->assertNotExpired( $new_events['today'] );
		$this->assertExpired( $new_events['yesterday'] );
		$this->assertExpired( $new_events['ancient'] );
		$this->assertNotExpired( $new_events['tomorrow'] );

		$this->factory->event_listing->set_post_age( $new_events['ancient'], '-100 days' );
		$this->factory->event_listing->set_post_age( $new_events['yesterday'], '-1 day' );
		$this->factory->event_listing->set_post_age( $new_events['30daysago'], '-30 days' );
		$this->factory->event_listing->set_post_age( $new_events['31daysago'], '-31 days' );
		$this->factory->event_listing->set_post_age( $new_events['tomorrow'], '+1 day' );

		$instance->check_for_expired_events();
		$this->assertNotTrashed( $new_events['ancient'] );

		add_filter( 'event_manager_delete_expired_events', '__return_true' );
		$instance->check_for_expired_events();
		remove_filter( 'event_manager_delete_expired_events', '__return_true' );

		$this->assertTrashed( $new_events['ancient'] );
		$this->assertTrashed( $new_events['31daysago'] );
		$this->assertNotTrashed( $new_events['yesterday'] );
		$this->assertNotTrashed( $new_events['30daysago'] );
		$this->assertNotTrashed( $new_events['today'] );
		$this->assertNotTrashed( $new_events['tomorrow'] );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::delete_old_previews
	 */
	public function test_delete_old_previews() {
		$new_events              = [];
		$new_events['now']       = $this->factory->event_listing->create( [ 'post_status' => 'preview' ] );
		$new_events['yesterday'] = $this->factory->event_listing->create(
			[
				'post_status' => 'preview',
				'age'         => '-1 day',
			]
		);
		$new_events['29days']    = $this->factory->event_listing->create(
			[
				'post_status' => 'preview',
				'age'         => '-29 days',
			]
		);
		$new_events['30days']    = $this->factory->event_listing->create(
			[
				'post_status' => 'preview',
				'age'         => '-30 days',
			]
		);
		$new_events['31days']    = $this->factory->event_listing->create(
			[
				'post_status' => 'preview',
				'age'         => '-31 days',
			]
		);
		$new_events['60days']    = $this->factory->event_listing->create(
			[
				'post_status' => 'preview',
				'age'         => '-60 days',
			]
		);
		$this->assertPostStatus( 'preview', $new_events['now'] );
		$this->assertPostStatus( 'preview', $new_events['yesterday'] );
		$this->assertPostStatus( 'preview', $new_events['29days'] );
		$this->assertPostStatus( 'preview', $new_events['30days'] );
		$this->assertPostStatus( 'preview', $new_events['31days'] );
		$this->assertPostStatus( 'preview', $new_events['60days'] );

		$instance = WP_event_Manager_Post_Types::instance();
		$instance->delete_old_previews();

		$this->assertPostStatus( 'preview', $new_events['now'] );
		$this->assertPostStatus( 'preview', $new_events['yesterday'] );
		$this->assertPostStatus( 'preview', $new_events['29days'] );
		$this->assertPostStatus( 'preview', $new_events['30days'] );
		$this->assertEmpty( get_post( $new_events['31days'] ) );
		$this->assertEmpty( get_post( $new_events['60days'] ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::set_expirey
	 */
	public function test_set_expirey() {
		$post = get_post( $this->factory->event_listing->create() );
		$this->setExpectedDeprecated( 'WP_event_Manager_Post_Types::set_expirey' );
		$instance = WP_event_Manager_Post_Types::instance();
		$instance->set_expirey( $post );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::set_expiry
	 */
	public function test_set_expiry_post() {
		$post                  = get_post( $this->factory->event_listing->create() );
		$instance              = WP_event_Manager_Post_Types::instance();
		$_POST['_event_expires'] = $expire_date = date( 'Y-m-d', strtotime( '+10 days', current_time( 'timestamp' ) ) );
		$instance->set_expiry( $post );
		unset( $_POST['_event_expires'] );
		$this->assertEquals( $expire_date, get_post_meta( $post->ID, '_event_expires', true ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::set_expiry
	 */
	public function test_set_expiry_calculate() {
		$post             = get_post( $this->factory->event_listing->create( [ 'meta_input' => [ '_event_duration' => 77 ] ] ) );
		$instance         = WP_event_Manager_Post_Types::instance();
		$expire_date      = date( 'Y-m-d', strtotime( '+77 days', current_time( 'timestamp' ) ) );
		$expire_date_calc = calculate_event_expiry( $post->ID );
		$this->assertEquals( $expire_date, $expire_date_calc );
		$instance->set_expiry( $post );
		$this->assertEquals( $expire_date, get_post_meta( $post->ID, '_event_expires', true ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::set_expiry
	 */
	public function test_set_expiry_past() {
		$post     = get_post( $this->factory->event_listing->create( [ 'meta_input' => [ '_event_expires' => '2008-01-01' ] ] ) );
		$instance = WP_event_Manager_Post_Types::instance();
		$instance->set_expiry( $post );
		$this->assertEquals( '', get_post_meta( $post->ID, '_event_expires', true ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::fix_post_name
	 */
	public function test_fix_post_name() {
		$instance = WP_event_Manager_Post_Types::instance();
		// Legit.
		$data                 = [
			'post_type'   => 'event_listing',
			'post_status' => 'pending',
			'post_name'   => 'Bad ABC',
		];
		$postarr              = [];
		$postarr['post_name'] = 'TEST 123';
		$data_fixed           = $instance->fix_post_name( $data, $postarr );
		$this->assertEquals( $postarr['post_name'], $data_fixed['post_name'] );

		// Bad Post Type.
		$data                 = [
			'post_type'   => 'post',
			'post_status' => 'pending',
			'post_name'   => 'Bad ABC',
		];
		$postarr              = [];
		$postarr['post_name'] = 'TEST 123';
		$data_fixed           = $instance->fix_post_name( $data, $postarr );
		$this->assertEquals( $data['post_name'], $data_fixed['post_name'] );

		// Bad Post Status.
		$data                 = [
			'post_type'   => 'event_listing',
			'post_status' => 'publish',
			'post_name'   => 'Bad ABC',
		];
		$postarr              = [];
		$postarr['post_name'] = 'TEST 123';
		$data_fixed           = $instance->fix_post_name( $data, $postarr );
		$this->assertEquals( $data['post_name'], $data_fixed['post_name'] );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::maybe_add_geolocation_data
	 */
	public function test_get_permalink_structure() {
		$permalink_test = [
			'event_base'      => 'event-test-a',
			'category_base' => 'event-cat-b',
			'type_base'     => 'event-type-c',
		];
		update_option( WP_event_Manager_Post_Types::PERMALINK_OPTION_NAME, wp_json_encode( $permalink_test ) );
		$permalinks = WP_event_Manager_Post_Types::get_permalink_structure();
		delete_option( WP_event_Manager_Post_Types::PERMALINK_OPTION_NAME );
		$this->assertEquals( 'event-test-a', $permalinks['event_rewrite_slug'] );
		$this->assertEquals( 'event-cat-b', $permalinks['category_rewrite_slug'] );
		$this->assertEquals( 'event-type-c', $permalinks['type_rewrite_slug'] );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::update_post_meta
	 */
	public function test_update_post_meta() {
		$instance = WP_event_Manager_Post_Types::instance();
		$bad_post = get_post(
			$this->factory->post->create(
				[
					'menu_order' => 10,
					'meta_input' => [ '_featured' => 0 ],
				]
			)
		);

		$post = get_post(
			$this->factory->event_listing->create(
				[
					'menu_order' => 10,
					'meta_input' => [ '_featured' => 0 ],
				]
			)
		);

		$instance->update_post_meta( 0, $bad_post->ID, '_featured', '1' );
		$bad_post = get_post( $bad_post->ID );
		$this->assertEquals( '10', $bad_post->menu_order );

		$instance->update_post_meta( 0, $post->ID, '_featured', '1' );
		$post = get_post( $post->ID );
		$this->assertEquals( '-1', $post->menu_order );

		$instance->update_post_meta( 0, $post->ID, '_featured', '0' );
		$post = get_post( $post->ID );
		$this->assertEquals( '0', $post->menu_order );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::maybe_update_geolocation_data
	 */
	public function test_maybe_update_geolocation_data() {
		global $wp_actions;
		$instance = WP_event_Manager_Post_Types::instance();
		$post     = get_post(
			$this->factory->event_listing->create(
				[
					'menu_order' => 10,
					'meta_input' => [ '_featured' => 0 ],
				]
			)
		);
		unset( $wp_actions['event_manager_event_location_edited'] );
		$this->assertEquals( 0, did_action( 'event_manager_event_location_edited' ) );
		$instance->maybe_update_geolocation_data( 0, $post->ID, 'whatever', 1 );
		$this->assertEquals( 1, did_action( 'event_manager_event_location_edited' ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::maybe_update_menu_order
	 */
	public function test_maybe_update_menu_order() {
		$instance = WP_event_Manager_Post_Types::instance();
		$post     = get_post(
			$this->factory->event_listing->create(
				[
					'menu_order' => 10,
					'meta_input' => [ '_featured' => 0 ],
				]
			)
		);

		$instance->maybe_update_menu_order( 0, $post->ID, '_featured', '1' );
		$post = get_post( $post->ID );
		$this->assertEquals( '-1', $post->menu_order );

		$instance->maybe_update_menu_order( 0, $post->ID, '_featured', '0' );
		$post = get_post( $post->ID );
		$this->assertEquals( '0', $post->menu_order );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::maybe_generate_geolocation_data
	 */
	public function test_maybe_generate_geolocation_data() {
		$post = get_post( $this->factory->event_listing->create() );
		$this->setExpectedDeprecated( 'WP_event_Manager_Post_Types::maybe_generate_geolocation_data' );
		$instance = WP_event_Manager_Post_Types::instance();
		$instance->maybe_generate_geolocation_data( 0, 0, 0, 0 );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::maybe_add_default_meta_data
	 */
	public function test_maybe_add_default_meta_data() {
		$instance = WP_event_Manager_Post_Types::instance();
		$post     = wp_insert_post(
			[
				'post_type'  => 'event_listing',
				'post_title' => 'Hello A',
			]
		);
		delete_post_meta( $post, '_featured' );
		delete_post_meta( $post, '_filled' );
		$this->assertFalse( metadata_exists( 'post', $post, '_filled' ) );
		$this->assertFalse( metadata_exists( 'post', $post, '_featured' ) );
		$instance->maybe_add_default_meta_data( $post, get_post( $post ) );
		$this->assertTrue( metadata_exists( 'post', $post, '_filled' ) );
		$this->assertTrue( metadata_exists( 'post', $post, '_featured' ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::maybe_add_default_meta_data
	 */
	public function test_maybe_add_default_meta_data_non_event_listing() {
		$instance = WP_event_Manager_Post_Types::instance();
		$post     = wp_insert_post(
			[
				'post_type'  => 'post',
				'post_title' => 'Hello B',
			]
		);
		delete_post_meta( $post, '_featured' );
		delete_post_meta( $post, '_filled' );
		$this->assertFalse( metadata_exists( 'post', $post, '_filled' ) );
		$this->assertFalse( metadata_exists( 'post', $post, '_featured' ) );
		$instance->maybe_add_default_meta_data( $post, get_post( $post ) );
		$this->assertFalse( metadata_exists( 'post', $post, '_filled' ) );
		$this->assertFalse( metadata_exists( 'post', $post, '_featured' ) );
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::noindex_expired_filled_event_listings
	 */
	public function test_noindex_expired_filled_event_listings() {
		global $wp_query;
		$instance = WP_event_Manager_Post_Types::instance();
		$event_id   = $this->factory->event_listing->create();
		$post_id  = $this->factory->post->create();

		$events = $wp_query = new WP_Query(
			[
				'p'         => $event_id,
				'post_type' => 'event_listing',
			]
		);
		$this->assertEquals( 1, $events->post_count );
		$this->assertTrue( $events->is_single );

		while ( $events->have_posts() ) {
			$events->the_post();
			$post = get_post();
			ob_start();
			$instance->noindex_expired_filled_event_listings();
			$result = ob_get_clean();
			$this->assertEmpty( $result );
		}
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::noindex_expired_filled_event_listings
	 */
	public function test_noindex_expired_filled_event_listings_expired() {
		global $wp_query;
		$instance = WP_event_Manager_Post_Types::instance();
		$event_id   = $this->factory->event_listing->create( [ 'post_status' => 'expired ' ] );
		$post_id  = $this->factory->post->create();

		$events = $wp_query = new WP_Query(
			[
				'p'         => $event_id,
				'post_type' => 'event_listing',
			]
		);
		$this->assertEquals( 1, $events->post_count );
		$this->assertTrue( $events->is_single );
		$desired_result = $this->get_wp_no_robots();
		while ( $events->have_posts() ) {
			$events->the_post();
			$post = get_post();
			ob_start();
			$instance->noindex_expired_filled_event_listings();
			$result = ob_get_clean();
			$this->assertEquals( $desired_result, $result );
		}
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::output_structured_data
	 */
	public function test_output_structured_data() {
		global $wp_query;
		$instance = WP_event_Manager_Post_Types::instance();
		$event_id   = $this->factory->event_listing->create();
		$post_id  = $this->factory->post->create();

		$events = $wp_query = new WP_Query(
			[
				'p'         => $event_id,
				'post_type' => 'event_listing',
			]
		);
		$this->assertEquals( 1, $events->post_count );
		$this->assertTrue( $events->is_single );
		while ( $events->have_posts() ) {
			$events->the_post();
			$post            = get_post();
			$structured_data = wpjm_get_event_listing_structured_data( $post );
			$json_data       = wpjm_esc_json( wp_json_encode( $structured_data ), true );
			ob_start();
			$instance->output_structured_data();
			$result = ob_get_clean();
			$this->assertContains( '<script type="application/ld+json">', $result );
			$this->assertContains( $json_data, $result );
		}
	}

	/**
	 * @since 1.28.0
	 * @covers WP_event_Manager_Post_Types::output_structured_data
	 */
	public function test_output_structured_data_expired() {
		global $wp_query;
		$instance = WP_event_Manager_Post_Types::instance();
		$event_id   = $this->factory->event_listing->create( [ 'post_status' => 'expired ' ] );
		$post_id  = $this->factory->post->create();

		$events = $wp_query = new WP_Query(
			[
				'p'         => $event_id,
				'post_type' => 'event_listing',
			]
		);
		$this->assertEquals( 1, $events->post_count );
		$this->assertTrue( $events->is_single );
		while ( $events->have_posts() ) {
			$events->the_post();
			$post = get_post();
			ob_start();
			$instance->output_structured_data();
			$result = ob_get_clean();
			$this->assertEmpty( $result );
		}
	}

	protected function get_wp_no_robots() {
		ob_start();
		wp_no_robots();
		return ob_get_clean();
	}

	protected function assertNotHasRssItem( $items, $post_id ) {
		$this->assertHasRssItem( $items, $post_id, true );
	}

	protected function assertHasRssItem( $items, $post_id, $not_found = false ) {
		$found = false;
		$guid  = get_the_guid( $post_id );
		$this->assertNotEmpty( $guid );
		foreach ( $items as $item ) {
			foreach ( $item['child'] as $child ) {
				if ( 'guid' === $child['name'] && $guid === $child['content'] ) {
					$found = true;
					break 2;
				}
			}
		}
		if ( ! $not_found ) {
			$this->assertTrue( $found );
		} else {
			$this->assertFalse( $found );
		}
	}

	private function do_event_feed() {
		if ( function_exists( 'header_remove' ) ) {
			header_remove();
		}
		ob_start();
		$instance = WP_event_Manager_Post_Types::instance();
		try {
			@$instance->event_feed();
			$out = ob_get_clean();
		} catch ( Exception $e ) {
			$out = ob_get_clean();
			throw $e;
		}
		return $out;
	}

	/**
	 * @covers WP_event_Manager_Post_Types::sanitize_meta_field_based_on_input_type
	 */
	public function test_sanitize_meta_field_based_on_input_type_text() {
		$strings = [
			[
				'expected' => 'This is a test.',
				'test'     => 'This is a test. <script>alert("bad");</script>',
			],
			[
				'expected' => 0,
				'test'     => 0,
			],
			[
				'expected' => '',
				'test'     => false,
			],
			[
				'expected' => '',
				'test'     => '%AB%BC%DE',
			],
			[
				'expected' => 'САПР',
				'test'     => 'САПР',
			],
			[
				'expected' => 'Standard String',
				'test'     => 'Standard String',
			],
			[
				'expected' => 'My iframe:',
				'test'     => 'My iframe: <iframe src="http://example.com"></iframe>',
			],
		];

		$this->set_up_custom_event_listing_data_feilds();
		$results = [];
		foreach ( $strings as $str ) {
			$results[] = [
				'expected' => $str['expected'],
				'result'   =>  WP_event_Manager_Post_Types::sanitize_meta_field_based_on_input_type( $str['test'], '_text' ),
			];
		}

		foreach ( $results as $result ) {
			$this->assertEquals( $result['expected'], $result['result'] );
		}
	}

	/**
	 * @covers WP_event_Manager_Post_Types::sanitize_meta_field_based_on_input_type
	 */
	public function test_sanitize_meta_field_based_on_input_type_textarea() {
		$strings = [
			[
				'expected' => 'This is a test. alert("bad");',
				'test'     => 'This is a test. <script>alert("bad");</script>',
			],
			[
				'expected' => 0,
				'test'     => 0,
			],
			[
				'expected' => '',
				'test'     => false,
			],
			[
				'expected' => '%AB%BC%DE',
				'test'     => '%AB%BC%DE',
			],
			[
				'expected' => 'САПР',
				'test'     => 'САПР',
			],
			[
				'expected' => 'Standard String',
				'test'     => 'Standard String',
			],
			[
				'expected' => 'My iframe: ',
				'test'     => 'My iframe: <iframe src="http://example.com"></iframe>',
			],
		];

		$this->set_up_custom_event_listing_data_feilds();
		$results = [];
		foreach ( $strings as $str ) {
			$results[] = [
				'expected' => $str['expected'],
				'result'   =>  WP_event_Manager_Post_Types::sanitize_meta_field_based_on_input_type( $str['test'], '_textarea' ),
			];
		}

		foreach ( $results as $result ) {
			$this->assertEquals( $result['expected'], $result['result'] );
		}
	}

	/**
	 * @covers WP_event_Manager_Post_Types::sanitize_meta_field_based_on_input_type
	 */
	public function test_sanitize_meta_field_based_on_input_type_checkbox() {
		$strings = [
			[
				'expected' => 1,
				'test'     => 'false',
			],
			[
				'expected' => 0,
				'test'     => '',
			],
			[
				'expected' => 0,
				'test'     => false,
			],
			[
				'expected' => 1,
				'test'     => true,
			],
		];

		$this->set_up_custom_event_listing_data_feilds();
		$results = [];
		foreach ( $strings as $str ) {
			$results[] = [
				'expected' => $str['expected'],
				'result'   =>  WP_event_Manager_Post_Types::sanitize_meta_field_based_on_input_type( $str['test'], '_checkbox' ),
			];
		}
		$this->remove_custom_event_listing_data_feilds();

		foreach ( $results as $result ) {
			$this->assertEquals( $result['expected'], $result['result'] );
		}
	}

	/**
	 * @covers WP_event_Manager_Post_Types::sanitize_meta_field_application
	 */
	public function test_sanitize_meta_field_application() {
		$strings = [
			[
				'expected' => 'http://test%20email@example.com',
				'test'     => 'test email@example.com',
			],
			[
				'expected' => 'http://awesome',
				'test'     => 'awesome',
			],
			[
				'expected' => 'https://example.com',
				'test'     => 'https://example.com',
			],
			[
				'expected' => 'example@example.com',
				'test'     => 'example@example.com',
			],
		];

		$this->set_up_custom_event_listing_data_feilds();
		$results = [];
		foreach ( $strings as $str ) {
			$results[] = [
				'expected' => $str['expected'],
				'result'   =>  WP_event_Manager_Post_Types::sanitize_meta_field_application( $str['test'], '_application' ),
			];
		}
		$this->remove_custom_event_listing_data_feilds();

		foreach ( $results as $result ) {
			$this->assertEquals( $result['expected'], $result['result'] );
		}
	}

	/**
	 * @covers WP_event_Manager_Post_Types::sanitize_meta_field_url
	 */
	public function test_sanitize_meta_field_url() {
		$strings = [
			[
				'expected' => 'http://example.com',
				'test'     => 'http://example.com',
			],
			[
				'expected' => '',
				'test'     => 'slack://custom-url',
			],
			[
				'expected' => 'http://example.com',
				'test'     => 'example.com',
			],
			[
				'expected' => 'http://example.com/?baz=bar&foo%5Bbar%5D=baz',
				'test'     => 'http://example.com/?baz=bar&foo[bar]=baz',
			],
		];

		$this->set_up_custom_event_listing_data_feilds();
		$results = [];
		foreach ( $strings as $str ) {
			$results[] = [
				'expected' => $str['expected'],
				'result'   =>  WP_event_Manager_Post_Types::sanitize_meta_field_url( $str['test'] ),
			];
		}
		$this->remove_custom_event_listing_data_feilds();

		foreach ( $results as $result ) {
			$this->assertEquals( $result['expected'], $result['result'] );
		}
	}

	/**
	 * @covers WP_event_Manager_Post_Types::sanitize_meta_field_date
	 */
	public function test_sanitize_meta_field_date() {
		$strings = [
			[
				'expected' => '',
				'test'     => 'http://example.com',
			],
			[
				'expected' => '',
				'test'     => 'January 1, 2019',
			],
			[
				'expected' => '',
				'test'     => '01-01-2019',
			],
			[
				'expected' => '2019-01-01',
				'test'     => '2019-01-01',
			],
		];

		$this->set_up_custom_event_listing_data_feilds();
		$results = [];
		foreach ( $strings as $str ) {
			$results[] = [
				'expected' => $str['expected'],
				'result'   =>  WP_event_Manager_Post_Types::sanitize_meta_field_date( $str['test'] ),
			];
		}
		$this->remove_custom_event_listing_data_feilds();

		foreach ( $results as $result ) {
			$this->assertEquals( $result['expected'], $result['result'] );
		}
	}

	private function set_up_custom_event_listing_data_feilds() {
		add_filter( 'event_manager_event_listing_data_fields', [ $this, 'custom_event_listing_data_fields' ] );
	}

	private function remove_custom_event_listing_data_feilds() {
		remove_filter( 'event_manager_event_listing_data_fields', [ $this, 'custom_event_listing_data_fields' ] );
	}

	public function custom_event_listing_data_fields() {
		return [
			'_text'    => [
				'label'         => 'Text Field',
				'placeholder'   => 'Text Field',
				'description'   => 'Text Field',
				'priority'      => 1,
				'type'          => 'text',
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_textarea'    => [
				'label'         => 'Textarea Field',
				'placeholder'   => 'Textarea Field',
				'description'   => 'Textarea Field',
				'priority'      => 1,
				'type'          => 'textarea',
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_url'    => [
				'label'         => 'URL Field',
				'placeholder'   => 'URL Field',
				'description'   => 'URL Field',
				'priority'      => 1,
				'type'          => 'text',
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
				'sanitize_callback' => [ 'WP_event_Manager_Post_Types', 'sanitize_meta_field_url' ],
			],
			'_checkbox'    => [
				'label'         => 'Checkbox Field',
				'placeholder'   => 'Checkbox Field',
				'description'   => 'Checkbox Field',
				'priority'      => 1,
				'type'          => 'checkbox',
				'data_type'     => 'integer',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_date'    => [
				'label'             => 'Checkbox Field',
				'placeholder'       => 'Checkbox Field',
				'description'       => 'Checkbox Field',
				'priority'          => 1,
				'show_in_admin'     => true,
				'show_in_rest'      => true,
				'classes'           => [ 'event-manager-datepicker' ],
				'sanitize_callback' => [ 'WP_event_Manager_Post_Types', 'sanitize_meta_field_date' ],
			],
			'_application'    => [
				'label'             => 'Application Field',
				'placeholder'       => 'Application Field',
				'description'       => 'Application Field',
				'priority'          => 1,
				'show_in_admin'     => true,
				'show_in_rest'      => true,
				'sanitize_callback' => [ 'WP_event_Manager_Post_Types', 'sanitize_meta_field_application' ],
			],
		];
	}
}
