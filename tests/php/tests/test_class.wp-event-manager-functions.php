<?php

class WP_Test_WP_event_Manager_Functions extends WPJM_BaseTest {
	public function setUp() {
		parent::setUp();
		$this->enable_manage_event_listings_cap();
		update_option( 'event_manager_enable_categories', 1 );
		update_option( 'event_manager_enable_types', 1 );
		add_theme_support( 'event-manager-templates' );
		$this->reregister_post_type();
		add_filter( 'event_manager_geolocation_enabled', '__return_false' );
		$this->disable_event_listing_cache();
	}

	public function tearDown() {
		parent::tearDown();
		add_filter( 'event_manager_geolocation_enabled', '__return_true' );
		$this->enable_event_listing_cache();
	}

	/**
	 * @since 1.27.0
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_keywords() {
		$keywords                = [
			'saurkraut' => [],
			'dinosaur'  => [],
			'saur'      => [],
			'boom'      => [],
		];
		$keywords['saurkraut'][] = $keywords['saur'][] = $keywords['boom'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'A Saurkraut Boom',
			]
		);
		$keywords['dinosaur'][]  = $keywords['saur'][] = $keywords['boom'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Boom',
			]
		);
		$keywords['dinosaur'][]  = $keywords['saur'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur',
			]
		);

		$dinosaur_event_listings = get_event_listings( [ 'search_keywords' => 'Dinosaur' ] );
		$saur_event_listings     = get_event_listings( [ 'search_keywords' => 'Saur' ] );
		$boom_event_listings     = get_event_listings( [ 'search_keywords' => 'Boom' ] );

		$this->assertEqualSets( $keywords['dinosaur'], wp_list_pluck( $dinosaur_event_listings->posts, 'ID' ) );
		$this->assertEqualSets( $keywords['saur'], wp_list_pluck( $saur_event_listings->posts, 'ID' ) );
		$this->assertEqualSets( $keywords['boom'], wp_list_pluck( $boom_event_listings->posts, 'ID' ) );
	}

	/**
	 * @since 1.27.0
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_location() {
		$locations               = [
			'seattle'  => [],
			'portland' => [],
			'oregon'   => [],
			'london'   => [],
		];
		$locations['seattle'][]  = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Seattle-A',
				'meta_input' => [
					'_event_location' => 'Seattle, WA, USA',
				],
			]
		);
		$locations['seattle'][]  = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Seattle-B',
				'meta_input' => [
					'_event_location' => 'seattle, wa',
				],
			]
		);
		$locations['seattle'][]  = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Seattle-C',
				'meta_input' => [
					'_event_location' => 'Seattle, Washington',
				],
			]
		);
		$locations['portland'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Portland-A',
				'meta_input' => [
					'_event_location' => 'Portland, Maine',
				],
			]
		);
		$locations['portland'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Portland-B',
				'meta_input' => [
					'_event_location' => 'Portland, OR',
				],
			]
		);
		$locations['portland'][] = $locations['oregon'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Portland-C',
				'meta_input' => [
					'_event_location' => 'Portland, Oregon',
				],
			]
		);
		$locations['london'][]   = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test London',
				'meta_input' => [
					'_event_location' => 'London, UK',
				],
			]
		);
		$this->factory->event_listing->create(
			[
				'post_title' => 'Test London',
				'meta_input' => [
					'_event_location' => 'London, UK',
				],
			]
		);
		$this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Seattle',
			]
		);

		$seattle_event_listings  = get_event_listings(
			[
				'search_keywords' => 'Dinosaur',
				'search_location' => 'Seattle',
			]
		);
		$portland_event_listings = get_event_listings(
			[
				'search_keywords' => 'Dinosaur',
				'search_location' => 'Portland',
			]
		);
		$london_event_listings   = get_event_listings(
			[
				'search_keywords' => 'Dinosaur',
				'search_location' => 'London',
			]
		);

		$this->assertEqualSets( $locations['seattle'], wp_list_pluck( $seattle_event_listings->posts, 'ID' ) );
		$this->assertEqualSets( $locations['portland'], wp_list_pluck( $portland_event_listings->posts, 'ID' ) );
		$this->assertEqualSets( $locations['london'], wp_list_pluck( $london_event_listings->posts, 'ID' ) );
	}

	/**
	 * @since 1.27.0
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_post_status() {
		$post_statuses              = [
			'publish' => [],
			'expired' => [],
			'preview' => [],
		];
		$post_statuses['publish'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Pub-A',
			]
		);
		$post_statuses['publish'][] = $this->factory->event_listing->create(
			[
				'post_title' => 'Dinosaur Test Pub-B',
			]
		);
		$post_statuses['expired'][] = $this->factory->event_listing->create(
			[
				'post_title'  => 'Dinosaur Test Ex-A',
				'post_status' => 'expired',
			]
		);
		$post_statuses['expired'][] = $this->factory->event_listing->create(
			[
				'post_title'  => 'Dinosaur Test Ex-B',
				'post_status' => 'expired',
			]
		);
		$post_statuses['expired'][] = $this->factory->event_listing->create(
			[
				'post_title'  => 'Dinosaur Test Ex-C',
				'post_status' => 'expired',
			]
		);
		$post_statuses['preview'][] = $this->factory->event_listing->create(
			[
				'post_title'  => 'Dinosaur Test Preview-A',
				'post_status' => 'preview',
			]
		);

		$published_event_listings         = get_event_listings(
			[
				'search_keywords' => 'Dinosaur',
				'post_status'     => [ 'publish' ],
			]
		);
		$published_preview_event_listings = get_event_listings(
			[
				'search_keywords' => 'Dinosaur',
				'post_status'     => [ 'publish', 'preview' ],
			]
		);
		$expired_event_listings           = get_event_listings(
			[
				'search_keywords' => 'Dinosaur',
				'post_status'     => [ 'expired' ],
			]
		);

		$this->assertEqualSets( $post_statuses['publish'], wp_list_pluck( $published_event_listings->posts, 'ID' ) );
		$this->assertEqualSets( array_merge( $post_statuses['publish'], $post_statuses['preview'] ), wp_list_pluck( $published_preview_event_listings->posts, 'ID' ) );
		$this->assertEqualSets( $post_statuses['expired'], wp_list_pluck( $expired_event_listings->posts, 'ID' ) );
	}

	/**
	 * @since 1.27.0
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_categories() {
		$this->assertTrue( taxonomy_exists( 'event_listing_category' ) );
		$this->assertTrue( current_user_can( get_taxonomy( 'event_listing_category' )->cap->assign_terms ) );
		$categories      = [
			'main'  => [],
			'weird' => [],
			'happy' => [],
			'all'   => [],
			'none'  => [],
		];
		$terms           = [];
		$terms['jazz']   = $categories['main'][] = $categories['all'][] = wp_create_term( 'jazz', 'event_listing_category' );
		$terms['swim']   = $categories['main'][] = $categories['all'][] = wp_create_term( 'swim', 'event_listing_category' );
		$terms['dev']    = $categories['main'][] = $categories['all'][] = wp_create_term( 'dev', 'event_listing_category' );
		$terms['potato'] = $categories['weird'][] = $categories['all'][] = wp_create_term( 'potato', 'event_listing_category' );
		$terms['coffee'] = $categories['happy'][] = $categories['all'][] = wp_create_term( 'coffee', 'event_listing_category' );
		foreach ( $categories as $k => $category ) {
			$categories[ $k ] = wp_list_pluck( $category, 'term_id' );
		}

		$post_categories = [
			'empty' => [],
			'main'  => [],
			'weird' => [],
			'all'   => [],
		];

		$post_categories['main']  = $this->factory->event_listing->create_many(
			3,
			[
				'tax_input' => [
					'event_listing_category' => $categories['main'],
				],
			]
		);
		$post_categories['weird'] = $this->factory->event_listing->create_many(
			2,
			[
				'tax_input' => [
					'event_listing_category' => $categories['weird'],
				],
			]
		);
		$post_categories['happy'] = $this->factory->event_listing->create_many(
			2,
			[
				'tax_input' => [
					'event_listing_category' => $categories['happy'],
				],
			]
		);
		$post_categories['none']  = $this->factory->event_listing->create_many( 5 );
		$results                  = [];
		$results['jazz']          = [
			'expected' => array_merge( $post_categories['all'], $post_categories['main'] ),
			'results'  => get_event_listings(
				[
					'search_keywords'   => '',
					'search_categories' => [ 'jazz' ],
				]
			),
		];
		$results['potato']        = [
			'expected' => $post_categories['weird'],
			'results'  => get_event_listings(
				[
					'search_keywords'   => '',
					'search_categories' => [ 'potato' ],
				]
			),
		];
		update_option( 'event_manager_category_filter_type', 'some' );
		$results['potato_coffee_some'] = [
			'expected' => array_merge( $post_categories['weird'], $post_categories['happy'] ),
			'results'  => get_event_listings(
				[
					'search_keywords'   => '',
					'search_categories' => [ 'potato', 'coffee' ],
				]
			),
		];
		$results['potato_coffee_some'] = [
			'expected' => $post_categories['main'],
			'results'  => get_event_listings(
				[
					'search_keywords'   => '',
					'search_categories' => [ 'jazz', 'swim' ],
				]
			),
		];
		update_option( 'event_manager_category_filter_type', 'all' );
		$results['potato_coffee_all'] = [
			'expected' => [],
			'results'  => get_event_listings(
				[
					'search_keywords'   => '',
					'search_categories' => [ 'potato', 'coffee' ],
				]
			),
		];
		$results['potato_coffee_all'] = [
			'expected' => $post_categories['main'],
			'results'  => get_event_listings(
				[
					'search_keywords'   => '',
					'search_categories' => [ 'jazz', 'swim' ],
				]
			),
		];

		foreach ( $results as $key => $result ) {
			$this->assertEqualSets( $result['expected'], wp_list_pluck( $result['results']->posts, 'ID' ), "{$key} doesn't match" );
		}
	}

	/**
	 * @since 1.27.0
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_event_types() {
		$this->assertTrue( taxonomy_exists( 'event_listing_type' ) );
		$this->assertTrue( current_user_can( get_taxonomy( 'event_listing_type' )->cap->assign_terms ) );
		$tags            = [
			'main'  => [],
			'weird' => [],
			'happy' => [],
			'all'   => [],
			'none'  => [],
		];
		$terms           = [];
		$terms['jazz']   = $tags['main'][] = $tags['all'][] = wp_create_term( 'jazz', 'event_listing_type' );
		$terms['swim']   = $tags['main'][] = $tags['all'][] = wp_create_term( 'swim', 'event_listing_type' );
		$terms['dev']    = $tags['main'][] = $tags['all'][] = wp_create_term( 'dev', 'event_listing_type' );
		$terms['potato'] = $tags['weird'][] = $tags['all'][] = wp_create_term( 'potato', 'event_listing_type' );
		$terms['coffee'] = $tags['happy'][] = $tags['all'][] = wp_create_term( 'coffee', 'event_listing_type' );
		foreach ( $tags as $k => $category ) {
			$tags[ $k ] = wp_list_pluck( $category, 'term_id' );
		}

		$post_event_types = [
			'empty' => [],
			'main'  => [],
			'weird' => [],
			'all'   => [],
		];

		$post_event_types['main']   = $this->factory->event_listing->create_many(
			3,
			[
				'tax_input' => [
					'event_listing_type' => $tags['main'],
				],
			]
		);
		$post_event_types['weird']  = $this->factory->event_listing->create_many(
			2,
			[
				'tax_input' => [
					'event_listing_type' => $tags['weird'],
				],
			]
		);
		$post_event_types['happy']  = $this->factory->event_listing->create_many(
			2,
			[
				'tax_input' => [
					'event_listing_type' => $tags['happy'],
				],
			]
		);
		$post_event_types['none']   = $this->factory->event_listing->create_many( 5 );
		$results                  = [];
		$results['jazz']          = [
			'expected' => array_merge( $post_event_types['all'], $post_event_types['main'] ),
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'event_types'       => [ 'jazz' ],
				]
			),
		];
		$results['potato']        = [
			'expected' => $post_event_types['weird'],
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'event_types'       => [ 'potato' ],
				]
			),
		];
		$results['potato_coffee'] = [
			'expected' => array_merge( $post_event_types['weird'], $post_event_types['happy'] ),
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'event_types'       => [ 'potato', 'coffee' ],
				]
			),
		];
		$results['potato_coffee'] = [
			'expected' => $post_event_types['main'],
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'event_types'       => [ 'jazz', 'swim' ],
				]
			),
		];

		foreach ( $results as $key => $result ) {
			$this->assertEqualSets( $result['expected'], wp_list_pluck( $result['results']->posts, 'ID' ), "{$key} doesn't match" );
		}
	}

	/**
	 * @since 1.27.0
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_featured() {
		$featured_flag = [
			'featured'     => [],
			'not-featured' => [],
		];

		$featured_flag['featured']     = $this->factory->event_listing->create_many(
			3,
			[
				'meta_input' => [
					'_featured' => 1,
				],
			]
		);
		$featured_flag['not-featured'] = $this->factory->event_listing->create_many(
			2,
			[
				'meta_input' => [
					'_featured' => 0,
				],
			]
		);
		$results                       = [];
		$results['featured']           = [
			'expected' => $featured_flag['featured'],
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'featured'        => true,
				]
			),
		];
		$results['not-featured']       = [
			'expected' => $featured_flag['not-featured'],
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'featured'        => false,
				]
			),
		];

		foreach ( $results as $key => $result ) {
			$this->assertEqualSets( $result['expected'], wp_list_pluck( $result['results']->posts, 'ID' ), "{$key} doesn't match" );
		}
	}

	/**
	 * @since 1.29.1
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_featured_rand_cache() {
		$this->enable_event_listing_cache();
		$featured_flag = [
			'featured'     => [],
			'not-featured' => [],
		];

		$featured_flag['featured']     = $this->factory->event_listing->create_many(
			5,
			[
				'post_title' => 'Featured Post',
				'meta_input' => [
					'_featured' => 1,
				],
			]
		);
		$featured_flag['not-featured'] = $this->factory->event_listing->create_many(
			5,
			[
				'post_title' => 'Not Featured Post',
				'meta_input' => [
					'_featured' => 0,
				],
			]
		);

		// Try 10x, verfying first 5 are always event listings.
		for ( $i = 1; $i <= 10; $i++ ) {
			$results = get_event_listings(
				[
					'search_keywords' => '',
					'orderby'         => 'rand_featured',
				]
			);
			$tc      = 0;
			foreach ( $results->posts as $result ) {
				if ( $tc < 5 ) {
					$this->assertEquals( 1, $result->_featured );
					$this->assertEquals( -1, $result->menu_order );
				} else {
					$this->assertEquals( 0, $result->_featured );
					$this->assertEquals( 0, $result->menu_order );
				}
				$tc++;
			}
		}
	}

	/**
	 * @since 1.29.1
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_featured_rand_no_cache() {
		$this->disable_event_listing_cache();
		$featured_flag = [
			'featured'     => [],
			'not-featured' => [],
		];

		$featured_flag['featured']     = $this->factory->event_listing->create_many(
			5,
			[
				'post_title' => 'Featured Post',
				'meta_input' => [
					'_featured' => 1,
				],
			]
		);
		$featured_flag['not-featured'] = $this->factory->event_listing->create_many(
			5,
			[
				'post_title' => 'Not Featured Post',
				'meta_input' => [
					'_featured' => 0,
				],
			]
		);

		// Try 10x, verifying first 5 are always event listings.
		for ( $i = 1; $i <= 10; $i++ ) {
			$results = get_event_listings(
				[
					'search_keywords' => '',
					'orderby'         => 'rand_featured',
				]
			);
			$tc      = 0;
			foreach ( $results->posts as $result ) {
				if ( $tc < 5 ) {
					$this->assertEquals( 1, $result->_featured );
					$this->assertEquals( -1, $result->menu_order );
				} else {
					$this->assertEquals( 0, $result->_featured );
					$this->assertEquals( 0, $result->menu_order );
				}
				$tc++;
			}
		}
	}

	/**
	 * @since 1.27.0
	 * @covers ::get_event_listings
	 */
	public function test_get_event_listings_filled() {
		$featured_flag = [
			'featured'     => [],
			'not-featured' => [],
		];

		$featured_flag['filled']     = $this->factory->event_listing->create_many(
			3,
			[
				'meta_input' => [
					'_filled' => 1,
				],
			]
		);
		$featured_flag['not-filled'] = $this->factory->event_listing->create_many(
			2,
			[
				'meta_input' => [
					'_filled' => 0,
				],
			]
		);
		$results                     = [];
		$results['filled']           = [
			'expected' => $featured_flag['filled'],
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'filled'          => true,
				]
			),
		];
		$results['not-filled']       = [
			'expected' => $featured_flag['not-filled'],
			'results'  => get_event_listings(
				[
					'search_keywords' => '',
					'filled'          => false,
				]
			),
		];

		foreach ( $results as $key => $result ) {
			$this->assertEqualSets( $result['expected'], wp_list_pluck( $result['results']->posts, 'ID' ), "{$key} doesn't match" );
		}
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 */
	public function test_is_wpjm_no_request() {
		$this->assertFalse( is_wpjm() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_page
	 */
	public function test_is_wpjm_event_listing_archive_request() {
		$this->assertFalse( is_wpjm() );
		$this->assertFalse( is_wpjm_page() );
		$this->set_up_request_page();
		$this->assertTrue( is_wpjm() );
		$this->assertTrue( is_wpjm_page() );
		$this->assertFalse( is_wpjm_event_listing() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_page
	 * @covers ::has_wpjm_shortcode
	 */
	public function test_is_wpjm_event_listing_events_shortcode_request() {
		$this->assertFalse( is_wpjm() );
		$this->assertFalse( is_wpjm_page() );
		$this->assertFalse( has_wpjm_shortcode( null, 'events' ) );
		$page_id = $this->set_up_request_shortcode( 'events' );
		update_option( 'event_manager_events_page_id', $page_id, true );
		$this->assertTrue( is_wpjm() );
		$this->assertTrue( is_wpjm_page() );
		$this->assertTrue( has_wpjm_shortcode( null, 'events' ) );
		$this->assertFalse( has_wpjm_shortcode( null, 'event' ) );
		$this->assertFalse( is_wpjm_event_listing() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_page
	 * @covers ::has_wpjm_shortcode
	 */
	public function test_is_wpjm_event_listing_events_dashboard_shortcode_request() {
		$this->assertFalse( is_wpjm() );
		$this->assertFalse( is_wpjm_page() );
		$this->assertFalse( has_wpjm_shortcode( null, 'event_dashboard' ) );
		$page_id = $this->set_up_request_shortcode( 'event_dashboard' );
		update_option( 'event_manager_event_dashboard_page_id', $page_id, true );
		$this->assertTrue( is_wpjm() );
		$this->assertTrue( is_wpjm_page() );
		$this->assertTrue( has_wpjm_shortcode( null, 'event_dashboard' ) );
		$this->assertFalse( has_wpjm_shortcode( null, 'event' ) );
		$this->assertFalse( is_wpjm_event_listing() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_page
	 * @covers ::has_wpjm_shortcode
	 */
	public function test_is_wpjm_event_listing_submit_events_shortcode_request() {
		$this->assertFalse( is_wpjm() );
		$this->assertFalse( is_wpjm_page() );
		$this->assertFalse( has_wpjm_shortcode( null, 'submit_event_form' ) );
		$page_id = $this->set_up_request_shortcode( 'submit_event_form' );
		update_option( 'event_manager_submit_event_form_page_id', $page_id, true );
		$this->assertTrue( is_wpjm() );
		$this->assertTrue( is_wpjm_page() );
		$this->assertTrue( has_wpjm_shortcode( null, 'submit_event_form' ) );
		$this->assertFalse( has_wpjm_shortcode( null, 'event' ) );
		$this->assertFalse( is_wpjm_event_listing() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_page
	 * @covers ::is_wpjm_event_listing
	 */
	public function test_is_wpjm_event_listing_request() {
		$this->assertFalse( is_wpjm_event_listing() );
		$this->set_up_request_event_listing();
		$this->assertTrue( is_wpjm() );
		$this->assertFalse( is_wpjm_page() );
		$this->assertTrue( is_wpjm_event_listing() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_page
	 * @covers ::is_wpjm_event_listing
	 */
	public function test_is_wpjm_not_event_listing_request() {
		$this->assertFalse( is_wpjm_event_listing() );
		$this->set_up_request_normal_page();
		$this->assertFalse( is_wpjm() );
		$this->assertFalse( is_wpjm_page() );
		$this->assertFalse( is_wpjm_event_listing() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_page
	 * @covers ::is_wpjm_event_listing
	 */
	public function test_is_wpjm_not_event_listing_home_request() {
		$this->assertFalse( is_wpjm_event_listing() );
		$this->set_up_request_home_page();
		$this->assertFalse( is_wpjm() );
		$this->assertFalse( is_wpjm_page() );
		$this->assertFalse( is_wpjm_event_listing() );
	}

	/**
	 * @since 1.30.0
	 * @covers ::is_wpjm
	 * @covers ::is_wpjm_taxonomy
	 */
	public function test_is_wpjm_taxonomy_success() {
		$this->assertFalse( is_wpjm_taxonomy() );
		$this->assertFalse( is_wpjm() );
		$this->assertTrue( taxonomy_exists( 'event_listing_category' ) );
		$this->assertTrue( current_user_can( get_taxonomy( 'event_listing_category' )->cap->assign_terms ) );
		$this->set_up_request_taxonomy();
		$this->assertTrue( is_wpjm_taxonomy() );
		$this->assertTrue( is_wpjm() );
	}

	protected function set_up_request_page() {
		$this->go_to( get_post_type_archive_link( 'event_listing' ) );
	}

	protected function set_up_request_shortcode( $tag = 'events' ) {
		$page = $this->create_shortcode_page( ucfirst( $tag ), $tag );
		$this->go_to( get_post_permalink( $page ) );
		$GLOBALS['wp_query']->is_page = true;
		return $page;
	}

	protected function set_up_request_event_listing() {
		$event_listing = $this->factory->event_listing->create();
		$this->go_to( get_post_permalink( $event_listing ) );
	}

	protected function set_up_request_normal_page() {
		$page = $this->factory->post->create(
			[
				'post_type'    => 'page',
				'post_title'   => 'Cool',
				'post_content' => 'Awesome',
			]
		);
		$this->go_to( get_post_permalink( $page ) );
	}

	protected function set_up_request_home_page() {
		$this->go_to( home_url() );
	}

	protected function set_up_request_taxonomy() {
		$term = wp_create_term( 'jazz', 'event_listing_category' );
		$this->go_to( get_term_link( $term['term_id'] ) );
	}

	protected function create_shortcode_page( $title, $tag ) {
		return $this->factory->post->create(
			[
				'post_type'    => 'page',
				'post_title'   => $title,
				'post_content' => '[' . $tag . ']',
			]
		);
	}

	protected function disable_event_listing_cache() {
		remove_filter( 'get_event_listings_cache_results', '__return_true' );
		add_filter( 'get_event_listings_cache_results', '__return_false' );
	}

	protected function enable_event_listing_cache() {
		remove_filter( 'get_event_listings_cache_results', '__return_false' );
		add_filter( 'get_event_listings_cache_results', '__return_true' );
	}
}
