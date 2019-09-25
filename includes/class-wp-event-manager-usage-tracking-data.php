<?php
/**
 * File containing the class WP_event_Manager_Usage_Tracking_Data.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies the usage tracking data for logging.
 *
 * @since 1.30.0
 */
class WP_event_Manager_Usage_Tracking_Data {
	/**
	 * Get the usage tracking data to send.
	 *
	 * @since 1.30.0
	 *
	 * @return array Usage data.
	 **/
	public static function get_usage_data() {
		$categories  = 0;
		$count_posts = wp_count_posts( 'event_listing' );

		if ( taxonomy_exists( 'event_listing_category' ) ) {
			$categories = wp_count_terms( 'event_listing_category', [ 'hide_empty' => false ] );
		}

		return [
			'employers'                   => self::get_employer_count(),
			'event_categories'              => $categories,
			'event_categories_desc'         => self::get_event_category_has_description_count(),
			'event_types'                   => wp_count_terms( 'event_listing_type', [ 'hide_empty' => false ] ),
			'event_types_desc'              => self::get_event_type_has_description_count(),
			'event_types_emp_type'          => self::get_event_type_has_employment_type_count(),
			'events_type'                   => self::get_event_type_count(),
			'events_logo'                   => self::get_company_logo_count(),
			'events_status_expired'         => isset( $count_posts->expired ) ? $count_posts->expired : 0,
			'events_status_pending'         => $count_posts->pending,
			'events_status_pending_payment' => isset( $count_posts->pending_payment ) ? $count_posts->pending_payment : 0,
			'events_status_preview'         => isset( $count_posts->preview ) ? $count_posts->preview : 0,
			'events_status_publish'         => $count_posts->publish,
			'events_location'               => self::get_events_count_with_meta( '_event_location' ),
			'events_app_contact'            => self::get_events_count_with_meta( '_application' ),
			'events_company_name'           => self::get_events_count_with_meta( '_company_name' ),
			'events_company_site'           => self::get_events_count_with_meta( '_company_website' ),
			'events_company_tagline'        => self::get_events_count_with_meta( '_company_tagline' ),
			'events_company_twitter'        => self::get_events_count_with_meta( '_company_twitter' ),
			'events_company_video'          => self::get_events_count_with_meta( '_company_video' ),
			'events_expiry'                 => self::get_events_count_with_meta( '_event_expires' ),
			'events_featured'               => self::get_events_count_with_checked_meta( '_featured' ),
			'events_filled'                 => self::get_events_count_with_checked_meta( '_filled' ),
			'events_freelance'              => self::get_events_by_type_count( 'freelance' ),
			'events_full_time'              => self::get_events_by_type_count( 'full-time' ),
			'events_intern'                 => self::get_events_by_type_count( 'internship' ),
			'events_part_time'              => self::get_events_by_type_count( 'part-time' ),
			'events_temp'                   => self::get_events_by_type_count( 'temporary' ),
			'events_by_guests'              => self::get_events_by_guests(),
			'official_extensions'         => self::get_official_extensions_count(),
			'licensed_extensions'         => self::get_licensed_extensions_count(),
		];
	}

	/**
	 * Get the total number of users with the "employer" role.
	 *
	 * @return int the number of "employers".
	 */
	private static function get_employer_count() {
		$employer_query = new WP_User_Query(
			[
				'fields' => 'ID',
				'role'   => 'employer',
			]
		);

		return $employer_query->total_users;
	}

	/**
	 * Get the number of event categories that have a description.
	 *
	 * @since 1.30.0
	 *
	 * @return int Number of event categories with a description.
	 **/
	private static function get_event_category_has_description_count() {
		if ( ! taxonomy_exists( 'event_listing_category' ) ) {
			return 0;
		}

		$count = 0;
		$terms = get_terms(
			[
				'taxonomy'   => 'event_listing_category',
				'hide_empty' => false,
			]
		);

		foreach ( $terms as $term ) {
			$description = isset( $term->description ) ? trim( $term->description ) : '';

			if ( ! empty( $description ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get the number of event types that have a description.
	 *
	 * @since 1.30.0
	 *
	 * @return int Number of event types with a description.
	 **/
	private static function get_event_type_has_description_count() {
		$count = 0;
		$terms = get_terms(
			[
				'taxonomy'   => 'event_listing_type',
				'hide_empty' => false,
			]
		);

		foreach ( $terms as $term ) {
			$description = isset( $term->description ) ? trim( $term->description ) : '';

			if ( ! empty( $description ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get the number of event types that have Employment Type set.
	 *
	 * @since 1.30.0
	 *
	 * @return int Number of event types with an employment type.
	 **/
	private static function get_event_type_has_employment_type_count() {
		$count = 0;
		$terms = get_terms(
			[
				'taxonomy'   => 'event_listing_type',
				'hide_empty' => false,
			]
		);

		foreach ( $terms as $term ) {
			$employment_type = get_term_meta( $term->term_id, 'employment_type', true );

			if ( ! empty( $employment_type ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get the total number of published or expired events for a particular event type.
	 *
	 * @since 1.30.0
	 *
	 * @param string $event_type event type to search for.
	 *
	 * @return int Number of published or expired events for a particular event type.
	 **/
	private static function get_events_by_type_count( $event_type ) {
		$query = new WP_Query(
			[
				'post_type'   => 'event_listing',
				'post_status' => [ 'expired', 'publish' ],
				'fields'      => 'ids',
				'tax_query'   => [
					[
						'field'    => 'slug',
						'taxonomy' => 'event_listing_type',
						'terms'    => $event_type,
					],
				],
			]
		);

		return $query->found_posts;
	}

	/**
	 * Get the number of event listings that have a company logo.
	 *
	 * @since 1.30.0
	 *
	 * @return int Number of event listings with a company logo.
	 */
	private static function get_company_logo_count() {
		$query = new WP_Query(
			[
				'post_type'   => 'event_listing',
				'post_status' => [ 'expired', 'publish' ],
				'fields'      => 'ids',
				'meta_query'  => [
					[
						'key'     => '_thumbnail_id',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		return $query->found_posts;
	}

	/**
	 * Get the total number of event listings that have one or more event types selected.
	 *
	 * @since 1.30.0
	 *
	 * @return int Number of event listings associated with at least one event type.
	 **/
	private static function get_event_type_count() {
		$query = new WP_Query(
			[
				'post_type'   => 'event_listing',
				'post_status' => [ 'expired', 'publish' ],
				'fields'      => 'ids',
				'tax_query'   => [
					[
						'taxonomy' => 'event_listing_type',
						'operator' => 'EXISTS',
					],
				],
			]
		);

		return $query->found_posts;
	}

	/**
	 * Get the number of event listings where the given meta value is non-empty.
	 *
	 * @param string $meta_key the key for the meta value to check.
	 *
	 * @return int the number of event listings.
	 */
	private static function get_events_count_with_meta( $meta_key ) {
		$query = new WP_Query(
			[
				'post_type'   => 'event_listing',
				'post_status' => [ 'publish', 'expired' ],
				'fields'      => 'ids',
				'meta_query'  => [
					[
						'key'     => $meta_key,
						'value'   => '[^[:space:]]',
						'compare' => 'REGEXP',
					],
				],
			]
		);

		return $query->found_posts;
	}

	/**
	 * Get the number of event listings where the given checkbox meta value is
	 * checked.
	 *
	 * @param string $meta_key the key for the meta value to check.
	 *
	 * @return int the number of event listings.
	 */
	private static function get_events_count_with_checked_meta( $meta_key ) {
		$query = new WP_Query(
			[
				'post_type'   => 'event_listing',
				'post_status' => [ 'publish', 'expired' ],
				'fields'      => 'ids',
				'meta_query'  => [
					[
						'key'   => $meta_key,
						'value' => '1',
					],
				],
			]
		);

		return $query->found_posts;
	}

	/**
	 * Get the number of event listings posted by guests.
	 *
	 * @return int the number of event listings.
	 */
	private static function get_events_by_guests() {
		$query = new WP_Query(
			[
				'post_type'   => 'event_listing',
				'post_status' => [ 'publish', 'expired' ],
				'fields'      => 'ids',
				'author__in'  => [ 0 ],
			]
		);

		return $query->found_posts;
	}

	/**
	 * Get the official extensions that are installed.
	 *
	 * @param bool $licensed_only Return only official extensions with an active license.
	 *
	 * @return array
	 */
	private static function get_official_extensions( $licensed_only ) {
		if ( ! class_exists( 'WP_event_Manager_Helper' ) ) {
			include_once event_MANAGER_PLUGIN_DIR . '/includes/helper/class-wp-event-manager-helper.php';
		}

		$helper         = WP_event_Manager_Helper::instance();
		$active_plugins = $helper->get_installed_plugins( true );

		if ( $licensed_only ) {
			foreach ( $active_plugins as $plugin_slug => $data ) {
				if ( ! $helper->has_plugin_licence( $plugin_slug ) ) {
					unset( $active_plugins[ $plugin_slug ] );
				}
			}
		}

		return $active_plugins;
	}

	/**
	 * Gets the count of all official extensions that are installed and activated.
	 */
	private static function get_official_extensions_count() {
		return count( self::get_official_extensions( false ) );
	}

	/**
	 * Gets the count of all official extensions that are installed, activated, and have active license.
	 */
	private static function get_licensed_extensions_count() {
		return count( self::get_official_extensions( true ) );
	}

	/**
	 * Checks if we have paid extensions installed and activated. Right now, all of our official extensions are paid.
	 *
	 * @return bool
	 */
	private static function has_paid_extensions() {
		return self::get_official_extensions_count() > 0;
	}

	/**
	 * Get the base fields to be sent for event logging.
	 *
	 * @since 1.33.0
	 *
	 * @return array
	 */
	public static function get_event_logging_base_fields() {
		$base_fields = [
			'event_listings' => wp_count_posts( 'event_listing' )->publish,
			'paid'         => self::has_paid_extensions() ? 1 : 0,
		];

		/**
		 * Filter the fields that should be sent with every event that is logged.
		 *
		 * @param array $base_fields The default base fields.
		 */
		return apply_filters( 'event_manager_event_logging_base_fields', $base_fields );
	}
}
