<?php

class WP_Test_WP_event_Manager extends WPJM_BaseTest {
	/**
	 * Tests the global $event_manager object.
	 *
	 * @since 1.26.0
	 */
	public function test_wp_event_manager_global_object() {
		// setup the test.
		global $event_manager;

		// test if the global event manager object is loaded.
		$this->assertTrue( isset( $event_manager ), 'event Manager global object loaded' );

		// check the class.
		$this->assertInstanceOf( 'WP_event_Manager', $event_manager, 'event Manager object is instance of WP_event_Manager class' );

		// check it matches result of global function.
		$this->assertSame( WPJM(), $event_manager, 'event Manager global must be equal to result of WPJM()' );
	}

	/**
	 * Tests the WPJM() always returns the same `WP_event_Manager` instance.
	 *
	 * @since 1.26.0
	 * @covers ::WPJM
	 */
	public function test_wp_event_manager_global_function() {
		$event_manager_instance = WPJM();
		$this->assertSame( WPJM(), $event_manager_instance, 'WPJM() must always provide the same instance of WP_event_Manager' );
		$this->assertTrue( $event_manager_instance instanceof WP_event_Manager, 'event Manager object is instance of WP_event_Manager class' );
	}

	/**
	 * Tests the WP_event_Manager::instance() always returns the same `WP_event_Manager` instance.
	 *
	 * @since 1.26.0
	 * @covers WP_event_Manager::instance
	 */
	public function test_wp_event_manager_instance() {
		$event_manager_instance = WP_event_Manager::instance();
		$this->assertSame( WP_event_Manager::instance(), $event_manager_instance, 'WP_event_Manager::instance() must always provide the same instance of WP_event_Manager' );
		$this->assertInstanceOf( 'WP_event_Manager', $event_manager_instance, 'WP_event_Manager::instance() must always provide the same instance of WP_event_Manager' );
	}

	/**
	 * Tests classes of object properties.
	 *
	 * @since 1.26.0
	 */
	public function test_classes_of_object_properties() {
		$this->assertInstanceOf( 'WP_event_Manager_Forms', WPJM()->forms );
		$this->assertInstanceOf( 'WP_event_Manager_Post_Types', WPJM()->post_types );
	}

	/**
	 * Checks constants are defined when constructing
	 *
	 * @since 1.26.0
	 */
	public function test_class_defined_constants() {
		WPJM();
		$this->assertTrue( defined( 'event_MANAGER_VERSION' ) );
		$this->assertTrue( defined( 'event_MANAGER_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'event_MANAGER_PLUGIN_URL' ) );
	}
}
