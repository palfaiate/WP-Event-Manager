<?php
/**
 * Routes:
 * OPTIONS /wp-json/wp/v2/event-types
 * GET /wp-json/wp/v2/event-types
 * POST /wp-json/wp/v2/event-types
 *
 * OPTIONS /wp-json/wp/v2/event-types/{id}
 * GET /wp-json/wp/v2/event-types/{id}
 * POST /wp-json/wp/v2/event-types/{id}
 * PATCH /wp-json/wp/v2/event-types/{id} (Alias for `POST /wp-json/wp/v2/event-types/{id}`)
 * PUT /wp-json/wp/v2/event-types/{id} (Alias for `POST /wp-json/wp/v2/event-types/{id}`)
 * DELETE /wp-json/wp/v2/event-types/{id}?force=1
 *
 * @see https://developer.wordpress.org/rest-api/reference/categories/
 * @group rest-api
 */
class WP_Test_WP_event_Manager_event_Types_Test extends WPJM_REST_TestCase {

	public function test_wp_v2_has_event_types_route() {
		$this->login_as_default_user();
		$response = $this->get( '/wp/v2' );
		$this->assertResponseStatus( $response, 200 );
		$data = $response->get_data();

		$routes = array_keys( $data['routes'] );
		$this->assertTrue( in_array( '/wp/v2/event-types', $routes ) );
	}

	public function test_guest_get_event_types_success() {
		$this->logout();
		$response = $this->get( '/wp/v2/event-types' );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_guest_get_event_type_success() {
		$this->logout();
		$term_id  = $this->get_event_type();
		$response = $this->get( sprintf( '/wp/v2/event-types/%d', $term_id ) );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_guest_delete_event_types_fail() {
		$this->logout();
		$term_id  = $this->get_event_type();
		$response = $this->delete( sprintf( '/wp/v2/event-types/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 401 );
	}

	public function test_guest_post_event_types_fail() {
		$this->logout();
		$response = $this->post(
			'/wp/v2/event-types',
			[
				'name'   => 'Software Engineer',
				'slug'   => 'software-engineer',
			]
		);

		$this->assertResponseStatus( $response, 401 );
	}

	public function test_guest_put_event_types_fail() {
		$term_id = $this->get_event_type();
		$this->logout();
		$response = $this->put(
			sprintf( '/wp/v2/event-types/%d', $term_id ),
			[
				'name'   => 'Software Engineer 2',
			]
		);

		$this->assertResponseStatus( $response, 401 );
	}

	public function test_employer_get_event_types_success() {
		$this->login_as_employer();
		$response = $this->get( '/wp/v2/event-types' );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_employer_get_event_type_success() {
		$this->login_as_employer();
		$term_id  = $this->get_event_type();
		$response = $this->get( sprintf( '/wp/v2/event-types/%d', $term_id ) );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_employer_delete_event_types_fail() {
		$this->login_as_employer();
		$term_id  = $this->get_event_type();
		$response = $this->delete( sprintf( '/wp/v2/event-types/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 403 );
	}

	public function test_employer_post_event_types_fail() {
		$this->login_as_employer();
		$response = $this->post(
			'/wp/v2/event-types',
			[
				'name'   => 'Software Engineer',
				'slug'   => 'software-engineer',
			]
		);

		$this->assertResponseStatus( $response, 403 );
	}

	public function test_employer_put_event_types_fail() {
		$term_id = $this->get_event_type();
		$this->login_as_employer();
		$response = $this->put(
			sprintf( '/wp/v2/event-types/%d', $term_id ),
			[
				'name'   => 'Software Engineer 2',
			]
		);

		$this->assertResponseStatus( $response, 403 );
	}

	public function test_get_event_types_success() {
		$this->login_as_default_user();
		$response = $this->get( '/wp/v2/event-types' );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_delete_fail_as_default_user() {
		$this->login_as_default_user();
		$term_id  = $this->get_event_type();
		$response = $this->delete( sprintf( '/wp/v2/event-types/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 401 );
	}

	public function test_delete_succeed_as_admin_user() {
		$this->login_as_admin();
		$term_id  = $this->get_event_type();
		$response = $this->delete( sprintf( '/wp/v2/event-types/%d', $term_id ), [ 'force' => 1 ] );
		$this->assertResponseStatus( $response, 200 );
	}

	public function test_post_event_types_succeed_if_valid_employment_type() {
		/**
		 * @see https://core.trac.wordpress.org/ticket/44834
		 */
		if ( version_compare( '4.9.7', $GLOBALS['wp_version'], '<' ) && version_compare( '4.9.9', $GLOBALS['wp_version'], '>' ) ) {
			$this->markTestSkipped( 'Bug in 4.9.8 prevents correct role check for term editing.' );
			return;
		}

		$this->login_as_admin();
		$response = $this->post(
			'/wp/v2/event-types',
			[
				'name'   => 'Software Engineer',
				'slug'   => 'software-engineer',
				'meta' => [
					'employment_type' => 'FULL_TIME',
				],
			]
		);

		$this->assertResponseStatus( $response, 201 );
	}

	public function test_post_event_types_save_employment_type() {
		/**
		 * @see https://core.trac.wordpress.org/ticket/44834
		 */
		if ( version_compare( '4.9.7', $GLOBALS['wp_version'], '<' ) && version_compare( '4.9.9', $GLOBALS['wp_version'], '>' ) ) {
			$this->markTestSkipped( 'Bug in 4.9.8 prevents correct role check for term editing.' );
			return;
		}
		$this->login_as_admin();
		$response = $this->post(
			'/wp/v2/event-types',
			[
				'name'   => 'Software Engineer',
				'slug'   => 'software-engineer',
				'meta' => [
					'employment_type' => 'FULL_TIME',
				],
			]
		);

		$this->assertResponseStatus( $response, 201 );
		$data = $response->get_data();
		$this->assertTrue( array_key_exists( 'meta', $data ) );
		$meta = $data['meta'];
		$this->assertTrue( array_key_exists( 'employment_type', $meta ) );
		$event_type_employment_type = $meta['employment_type'];
		$this->assertSame( 'FULL_TIME', $event_type_employment_type );
	}

	protected function get_event_type() {
		return $this->factory->term->create( [ 'taxonomy' => 'event_listing_type' ] );
	}
}
