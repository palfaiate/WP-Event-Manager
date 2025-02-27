<?php

class WP_UnitTest_Factory_For_event_Listing extends WP_UnitTest_Factory_For_Post {
	protected $default_event_listing_meta = [];

	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_event_listing_meta       = [
			'_event_location'    => '',
			'_event_type'        => 'full-time',
			'_application'     => 'test@example.com',
			'_company_name'    => new WP_UnitTest_Generator_Sequence( 'event Listing company name %s' ),
			'_company_website' => new WP_UnitTest_Generator_Sequence( 'event Listing company website %s' ),
			'_company_tagline' => new WP_UnitTest_Generator_Sequence( 'event Listing company tagline %s' ),
			'_company_video'   => '',
			'_company_twitter' => '',
			'_company_logo'    => '',
			'_filled'          => '0',
			'_featured'        => '0',
		];
		$this->default_generation_definitions = [
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'event Listing title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'event Listing content %s' ),
			'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'event Listing excerpt %s' ),
			'post_type'    => 'event_listing',
		];
	}

	/**
	 * @param array $args
	 *
	 * @return int|WP_Error
	 */
	function create_object( $args ) {
		if ( ! isset( $args['meta_input'] ) ) {
			$args['meta_input'] = [];
		}
		$args['meta_input'] = $this->generate_args( $args['meta_input'], $this->default_event_listing_meta );
		if ( ! empty( $args['meta_input']['_featured'] ) ) {
			$args['menu_order'] = -1;
		}

		$post = wp_insert_post( $args );
		if ( isset( $args['age'] ) ) {
			$this->set_post_age( $post, $args['age'] );
		}
		return $post;
	}

	public function set_post_age( $post_id, $age ) {
		global $wpdb;
		$mod_date = date( 'Y-m-d', strtotime( $age ) );
		$wpdb->update(
			$wpdb->posts,
			[
				'post_modified'     => $mod_date,
				'post_modified_gmt' => $mod_date,
			],
			[ 'ID' => $post_id ]
		);
	}
}
