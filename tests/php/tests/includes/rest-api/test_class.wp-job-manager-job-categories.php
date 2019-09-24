<?php
/**
 * Routes:
 * OPTIONS /wp-json/wp/v2/event-categories
 * GET /wp-json/wp/v2/event-categories
 * POST /wp-json/wp/v2/event-categories
 *
 * OPTIONS /wp-json/wp/v2/event-categories/{id}
 * GET /wp-json/wp/v2/event-categories/{id}
 * POST /wp-json/wp/v2/event-categories/{id}
 * PATCH /wp-json/wp/v2/event-categories/{id} (Alias for `POST /wp-json/wp/v2/event-categories/{id}`)
 * PUT /wp-json/wp/v2/event-categories/{id} (Alias for `POST /wp-json/wp/v2/event-categories/{id}`)
 * DELETE /wp-json/wp/v2/event-categories/{id}?force=1
 *
 * @see https://developer.wordpress.org/rest-api/reference/categories/
 * @group rest-api
 */
class WP_Test_WP_event_Manager_event_Categories_Test extends WPJM_REST_TestCase {

	public function test_guest_get_event_categories_success() {
		$this->logout();
		$response = $this->get( '/wp/v2/event-categories' );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_guest_get_event_category_success() {
		$this->logout();
		$term_id  = $this->get_event_category();
		$response = $this->get( sprintf( '/wp/v2/event-categories/%d', $term_id ) );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_guest_delete_event_categories_fail() {
		$this->logout();
		$term_id  = $this->get_event_category();
		$response = $this->delete( sprintf( '/wp/v2/event-categories/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 401 );
	}

	public function test_guest_post_event_categories_fail() {
		$this->logout();
		$response = $this->post(
			'/wp/v2/event-categories',
			[
				'name'   => 'Software Engineer',
				'slug'   => 'software-engineer',
			]
		);

		$this->assertResponseStatus( $response, 401 );
	}

	public function test_guest_put_event_categories_fail() {
		$term_id = $this->get_event_category();
		$this->logout();
		$response = $this->put(
			sprintf( '/wp/v2/event-categories/%d', $term_id ),
			[
				'name'   => 'Software Engineer 2',
			]
		);

		$this->assertResponseStatus( $response, 401 );
	}

	public function test_employer_get_event_categories_success() {
		$this->login_as_employer();
		$response = $this->get( '/wp/v2/event-categories' );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_employer_get_event_category_success() {
		$this->login_as_employer();
		$term_id  = $this->get_event_category();
		$response = $this->get( sprintf( '/wp/v2/event-categories/%d', $term_id ) );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_employer_delete_event_categories_fail() {
		$this->login_as_employer();
		$term_id  = $this->get_event_category();
		$response = $this->delete( sprintf( '/wp/v2/event-categories/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 403 );
	}

	public function test_employer_post_event_categories_fail() {
		$this->login_as_employer();
		$response = $this->post(
			'/wp/v2/event-categories',
			[
				'name'   => 'Software Engineer',
				'slug'   => 'software-engineer',
			]
		);

		$this->assertResponseStatus( $response, 403 );
	}

	public function test_employer_put_event_categories_fail() {
		$term_id = $this->get_event_category();
		$this->login_as_employer();
		$response = $this->put(
			sprintf( '/wp/v2/event-categories/%d', $term_id ),
			[
				'name'   => 'Software Engineer 2',
			]
		);

		$this->assertResponseStatus( $response, 403 );
	}

	public function test_get_success_when_guest() {
		$this->logout();
		$response = $this->get( '/wp/v2/event-categories' );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_post_fail_when_guest() {
		$this->logout();
		$response = $this->post(
			'/wp/v2/event-categories',
			[
				'name' => 'REST Test' . microtime( true ),
			]
		);
		$this->assertResponseStatus( $response, 401 );
	}

	public function test_post_success_when_admin() {
		$this->login_as_admin();
		$response = $this->post(
			'/wp/v2/event-categories',
			[
				'name' => 'REST Test' . microtime( true ),
			]
		);
		$this->assertResponseStatus( $response, 201 );
	}

	public function test_post_fail_when_default_user() {
		$this->login_as_default_user();
		$response = $this->post(
			'/wp/v2/event-categories',
			[
				'name' => 'REST Test' . microtime( true ),
			]
		);
		$this->assertResponseStatus( $response, 401 );
	}

	public function test_delete_fail_not_implemented() {
		$this->login_as_admin();
		$term_id  = $this->get_event_category();
		$response = $this->delete( '/wp/v2/event-categories/' . $term_id );
		$this->assertResponseStatus( $response, 501 );
	}

	public function test_delete_fail_as_default_user() {
		$this->login_as_default_user();
		$term_id  = $this->get_event_category();
		$response = $this->delete( sprintf( '/wp/v2/event-categories/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 401 );
	}

	public function test_delete_succeed_as_admin_user() {
		$this->login_as_admin();
		$term_id  = $this->get_event_category();
		$response = $this->delete( sprintf( '/wp/v2/event-categories/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_wp_v2_has_event_categories_route() {
		$this->login_as_default_user();
		$response = $this->get( '/wp/v2' );
		$this->assertResponseStatus( $response, 200 );
		$data = $response->get_data();

		$routes = array_keys( $data['routes'] );
		$this->assertTrue( in_array( '/wp/v2/event-categories', $routes ) );
	}

	public function test_get_event_categories_success() {
		$this->login_as_default_user();
		$response = $this->get( '/wp/v2/event-categories' );
		$this->assertResponseStatus( $response, 200 );
	}

	protected function get_event_category() {
		return $this->factory->term->create( [ 'taxonomy' => 'event_listing_category' ] );
	}
}
