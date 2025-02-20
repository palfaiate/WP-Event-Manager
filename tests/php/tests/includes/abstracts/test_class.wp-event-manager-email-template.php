<?php
require_once WPJM_Unit_Tests_Bootstrap::instance()->includes_dir . '/stubs/class-wp-event-manager-email-template-valid.php';

/**
 * Tests for WP_event_Manager_Email_Template.
 *
 * @group email
 */
class WP_Test_WP_event_Manager_Email_Template extends WPJM_BaseTest {
	private $fake_pass = 0;

	/**
	 * @covers WP_event_Manager_Email_Template::has_template()
	 * @covers WP_event_Manager_Email_Template::locate_template()
	 * @covers WP_event_Manager_Email_Template::get_template_file_name()
	 * @covers WP_event_Manager_Email_Template::get_rich_content()
	 */
	public function test_get_rich_content() {
		$event           = get_post( $this->factory->event_listing->create() );
		$args          = [ 'event' => $event ];
		$test          = new WP_event_Manager_Email_Template_Valid( $args, $this->get_base_settings() );
		$test_expected = "<strong>Rich Test Email: {$event->post_title}</strong>";

		add_filter( 'event_manager_locate_template', [ $this, 'use_rich_test_template' ] );
		$test_value = $test->get_rich_content();
		remove_filter( 'event_manager_locate_template', [ $this, 'use_rich_test_template' ] );
		$this->assertStringStartsWith( $test_expected, $test_value );
	}

	/**
	 * @covers WP_event_Manager_Email_Template::has_template()
	 * @covers WP_event_Manager_Email_Template::locate_template()
	 * @covers WP_event_Manager_Email_Template::get_plain_content()
	 * @covers WP_event_Manager_Email_Template::get_template()
	 */
	public function test_get_plain_content() {
		$event           = get_post( $this->factory->event_listing->create() );
		$args          = [ 'event' => $event ];
		$test          = new WP_event_Manager_Email_Template_Valid( $args, $this->get_base_settings() );
		$test_expected = "Plain Test Email: {$event->post_title}";
		add_filter( 'event_manager_locate_template', [ $this, 'use_plain_test_template' ] );
		$test_value = $test->get_plain_content();
		remove_filter( 'event_manager_locate_template', [ $this, 'use_plain_test_template' ] );
		$this->assertStringStartsWith( $test_expected, $test_value );
	}

	/**
	 * @covers WP_event_Manager_Email_Template::has_template()
	 * @covers WP_event_Manager_Email_Template::locate_template()
	 * @covers WP_event_Manager_Email_Template::get_rich_content()
	 * @covers WP_event_Manager_Email_Template::get_plain_content()
	 * @covers WP_event_Manager_Email_Template::get_template()
	 */
	public function test_get_plain_content_rich_fallback() {
		$this->fake_pass = 0;
		$event             = get_post( $this->factory->event_listing->create() );
		$args            = [ 'event' => $event ];
		$test            = new WP_event_Manager_Email_Template_Valid( $args, $this->get_base_settings() );
		$test_expected   = "Rich Test Email: {$event->post_title}";
		add_filter( 'event_manager_locate_template', [ $this, 'use_fake_test_template' ] );
		$test_value = $test->get_plain_content();
		remove_filter( 'event_manager_locate_template', [ $this, 'use_fake_test_template' ] );
		$this->assertStringStartsWith( $test_expected, $test_value );
	}

	/**
	 * @covers WP_event_Manager_Email_Template::has_template()
	 * @covers WP_event_Manager_Email_Template::locate_template()
	 */
	public function test_has_template_rich() {
		$this->fake_pass = 0;
		$args            = [ 'event' => '' ];
		$test            = new WP_event_Manager_Email_Template_Valid( $args, $this->get_base_settings() );
		add_filter( 'event_manager_locate_template', [ $this, 'use_rich_test_template' ] );
		$test_value = $test->has_template();
		remove_filter( 'event_manager_locate_template', [ $this, 'use_rich_test_template' ] );
		$this->assertTrue( $test_value );
	}

	/**
	 * @covers WP_event_Manager_Email_Template::has_template()
	 * @covers WP_event_Manager_Email_Template::locate_template()
	 */
	public function test_has_template_plain_fake() {
		$this->fake_pass = 0;
		$args            = [ 'event' => '' ];
		$test            = new WP_event_Manager_Email_Template_Valid( $args, $this->get_base_settings() );
		add_filter( 'event_manager_locate_template', [ $this, 'use_fake_test_template' ] );
		$test_value = $test->has_template( true );
		remove_filter( 'event_manager_locate_template', [ $this, 'use_fake_test_template' ] );
		$this->assertFalse( $test_value );
	}

	/**
	 * @covers WP_event_Manager_Email_Template::generate_template_file_name()
	 */
	public function test_get_template_file_name_plain() {
		$template_name = md5( microtime( true ) );
		$this->assertEquals( "emails/plain/{$template_name}.php", WP_event_Manager_Email_Template::generate_template_file_name( $template_name, true ) );
	}

	/**
	 * @covers WP_event_Manager_Email_Template::generate_template_file_name()
	 */
	public function test_get_template_file_name_rich() {
		$template_name = md5( microtime( true ) );
		$this->assertEquals( "emails/{$template_name}.php", WP_event_Manager_Email_Template::generate_template_file_name( $template_name, false ) );
	}

	public function use_rich_test_template( $template ) {
		return WPJM_Unit_Tests_Bootstrap::instance()->includes_dir . '/stubs/test-template.php';
	}

	public function use_plain_test_template( $template ) {
		return WPJM_Unit_Tests_Bootstrap::instance()->includes_dir . '/stubs/plain-test-template.php';
	}

	public function use_fake_test_template( $template ) {
		$this->fake_pass++;
		if ( 1 === $this->fake_pass ) {
			return WPJM_Unit_Tests_Bootstrap::instance()->includes_dir . '/stubs/fake-test-template.php';
		}
		return WPJM_Unit_Tests_Bootstrap::instance()->includes_dir . '/stubs/test-template.php';
	}

	protected function get_base_settings() {
		return [
			'enabled'    => '1',
			'plain_text' => '0',
		];
	}
}
