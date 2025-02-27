<?php
/**
 * Defines a class to test the data exporter
 *
 * @package wp-event-manager-test
 * @since 1.31.1
 */

/**
 * Handles the user data export.
 *
 * @package
 * @since
 */
class WP_event_Manager_Data_Exporter_Test extends WPJM_BaseTest {
	/**
	 * Setup user metadata
	 *
	 * @param array $args The metadata to be added.
	 * @param array $expected The expected output.
	 */
	private function setupUserMeta( $args, &$expected ) {
		$user_id = $this->factory()->user->create(
			[
				'user_login' => 'johndoe',
				'user_email' => 'johndoe@example.com',
				'role'       => 'subscriber',
			]
		);

		if ( isset( $args['_company_logo'] ) ) {
			$args['_company_logo']                   = $this->factory()->post->create(
				[ 'post_type' => 'attachment' ]
			);
			$expected['data'][0]['data'][0]['value'] = $args['_company_logo'];
		}

		foreach ( $args as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}
	}

	/**
	 * Test the data exporter for an invalid email
	 */
	public function test_data_exporter_for_invalid_email() {
		// ACT.
		$result = WP_event_Manager_Data_Exporter::user_data_exporter( 'this-is-an-invalid-email' );

		// ASSERT.
		$this->assertTrue( is_array( $result ) );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertEmpty( $result['data'] );
	}

	/**
	 * Test the user data exporter method
	 *
	 * @dataProvider data_provider
	 * @param array $args The metadata to be added.
	 * @param array $expected The expected output.
	 */
	public function test_user_data_exporter( $args, $expected ) {
		// ARRANGE.
		$this->setupUserMeta( $args, $expected );
		$id = email_exists( 'johndoe@example.com' );
		if ( false !== $id ) {
			/**
			 * We need to do this because the item_id depends on the user ID
			 * which can't be provided by the dataProvider before the dummy
			 * user is created.
			 */
			$expected['data'][0]['item_id'] = "wpjm-user-data-{$id}";
		}
		if ( array_key_exists( '_company_logo', $args ) ) {
			/**
			 * This is required because the logo is saved as an attachment and
			 * its ID is stored in the user_meta table.
			 * Since the ID of the post can't be determined in the dataProvider,
			 * it's value is set to true to indicate that a dummy attachment
			 * needs to be created.
			 */
			$expected['data'][0]['data'][0]['value'] = wp_get_attachment_url( $expected['data'][0]['data'][0]['value'] );
		}

		// ACT.
		$result = WP_event_Manager_Data_Exporter::user_data_exporter( 'johndoe@example.com' );

		// ASSERT.
		$this->assertEquals( $expected, $result );
	}

	/**
	 * The data provider method
	 *
	 * @return array
	 */
	public function data_provider() {
		return [
			[
				[
					'_company_logo'    => 'https://example.com/company/logo',
					'_company_name'    => 'Example',
					'_company_website' => 'https://example.com/',
					'_company_tagline' => 'Just another tagline',
					'_company_twitter' => 'https://twitter.com/example?ref_src=twsrc%5Egoogle%7Ctwcamp%5Eserp%7Ctwgr%5Eauthor',
					'_company_video'   => 'https://example.com/company/video',
				],
				[
					'data' => [
						[
							'group_id'    => 'wpjm-user-data',
							'group_label' => __( 'WP Event Manager User Data' ),
							'item_id'     => '', // the item_id depends on the ID of the user.
							'data'        => [
								[
									'name'  => 'Company Logo',
									'value' => true, // specify that attachment should be created.
								],
								[
									'name'  => 'Company Name',
									'value' => 'Example',
								],
								[
									'name'  => 'Company Website',
									'value' => 'https://example.com/',
								],
								[
									'name'  => 'Company Tagline',
									'value' => 'Just another tagline',
								],
								[
									'name'  => 'Company Twitter',
									'value' => 'https://twitter.com/example?ref_src=twsrc%5Egoogle%7Ctwcamp%5Eserp%7Ctwgr%5Eauthor',
								],
								[
									'name'  => 'Company Video',
									'value' => 'https://example.com/company/video',
								],
							],
						],
					],
					'done' => true,
				],
			], // End of first set of parameters.
			[
				[
					'_company_name'    => 'Example',
					'_company_website' => 'https://example.com/',
					'_company_tagline' => 'Just another tagline',
				],
				[
					'data' => [
						[
							'group_id'    => 'wpjm-user-data',
							'group_label' => __( 'WP Event Manager User Data' ),
							'data'        => [
								[
									'name'  => 'Company Name',
									'value' => 'Example',
								],
								[
									'name'  => 'Company Website',
									'value' => 'https://example.com/',
								],
								[
									'name'  => 'Company Tagline',
									'value' => 'Just another tagline',
								],
							],
						],
					],
					'done' => true,
				],
			], // End of second set of parameters.
			[
				[
					'_company_logo',
					'_company_name',
					'_company_website',
					'_company_tagline',
					'_company_twitter',
					'_company_video',
				],
				[
					'data' => [
						[
							'group_id'    => 'wpjm-user-data',
							'group_label' => __( 'WP Event Manager User Data' ),
							'item_id'     => '', // the item_id depends on the ID of the user.
							'data'        => [],
						],
					],
					'done' => true,
				],
			], // End of third set of parameters.
		];
	}
}
