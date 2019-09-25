<?php
/**
 * File containing the class WP_event_Manager_Install.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the installation of the WP Event Manager plugin.
 *
 * @since 1.0.0
 */
class WP_event_Manager_Install {

	/**
	 * Installs WP Event Manager.
	 */
	public static function install() {
		global $wpdb;

		self::init_user_roles();
		self::default_terms();

		$is_new_install = false;

		// Fresh installs should be prompted to set up their instance.
		if ( ! get_option( 'wp_event_manager_version' ) ) {
			include_once event_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-event-manager-admin-notices.php';
			WP_event_Manager_Admin_Notices::add_notice( WP_event_Manager_Admin_Notices::NOTICE_CORE_SETUP );
			$is_new_install = true;
		}

		// Update featured posts ordering.
		if ( version_compare( get_option( 'wp_event_manager_version', event_MANAGER_VERSION ), '1.22.0', '<' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One time data update.
			$wpdb->query( "UPDATE {$wpdb->posts} p SET p.menu_order = 0 WHERE p.post_type='event_listing';" );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One time data update.
			$wpdb->query( "UPDATE {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id SET p.menu_order = -1 WHERE pm.meta_key = '_featured' AND pm.meta_value='1' AND p.post_type='event_listing';" );
		}

		// Update default term meta with employment types.
		if ( version_compare( get_option( 'wp_event_manager_version', event_MANAGER_VERSION ), '1.28.0', '<' ) ) {
			self::add_employment_types();
		}

		// Update legacy options.
		if ( false === get_option( 'event_manager_submit_event_form_page_id', false ) && get_option( 'event_manager_submit_page_slug' ) ) {
			$page_id = get_page_by_path( get_option( 'event_manager_submit_page_slug' ) )->ID;
			update_option( 'event_manager_submit_event_form_page_id', $page_id );
		}
		if ( false === get_option( 'event_manager_event_dashboard_page_id', false ) && get_option( 'event_manager_event_dashboard_page_slug' ) ) {
			$page_id = get_page_by_path( get_option( 'event_manager_event_dashboard_page_slug' ) )->ID;
			update_option( 'event_manager_event_dashboard_page_id', $page_id );
		}

		// Scheduled hook was removed in 1.33.4.
		if ( wp_next_scheduled( 'event_manager_clear_expired_transients' ) ) {
			wp_clear_scheduled_hook( 'event_manager_clear_expired_transients' );
		}

		if ( $is_new_install ) {
			$permalink_options                 = (array) json_decode( get_option( 'event_manager_permalinks', '[]' ), true );
			$permalink_options['events_archive'] = '';
			update_option( 'event_manager_permalinks', wp_json_encode( $permalink_options ) );
		}

		delete_transient( 'wp_event_manager_addons_html' );
		update_option( 'wp_event_manager_version', event_MANAGER_VERSION );
	}

	/**
	 * Initializes user roles.
	 */
	private static function init_user_roles() {
		$roles = wp_roles();

		if ( is_object( $roles ) ) {
			add_role(
				'employer',
				__( 'Employer', 'wp-event-manager' ),
				[
					'read'         => true,
					'edit_posts'   => false,
					'delete_posts' => false,
				]
			);

			$capabilities = self::get_core_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$roles->add_cap( 'administrator', $cap );
				}
			}
		}
	}

	/**
	 * Returns capabilities.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		return [
			'core'        => [
				'manage_event_listings',
			],
			'event_listing' => [
				'edit_event_listing',
				'read_event_listing',
				'delete_event_listing',
				'edit_event_listings',
				'edit_others_event_listings',
				'publish_event_listings',
				'read_private_event_listings',
				'delete_event_listings',
				'delete_private_event_listings',
				'delete_published_event_listings',
				'delete_others_event_listings',
				'edit_private_event_listings',
				'edit_published_event_listings',
				'manage_event_listing_terms',
				'edit_event_listing_terms',
				'delete_event_listing_terms',
				'assign_event_listing_terms',
			],
		];
	}

	/**
	 * Sets up the default WP Event Manager terms.
	 */
	private static function default_terms() {
		if ( 1 === intval( get_option( 'event_manager_installed_terms' ) ) ) {
			return;
		}

		$taxonomies = self::get_default_taxonomy_terms();
		foreach ( $taxonomies as $taxonomy => $terms ) {
			foreach ( $terms as $term => $meta ) {
				if ( ! get_term_by( 'slug', sanitize_title( $term ), $taxonomy ) ) {
					$tt_package = wp_insert_term( $term, $taxonomy );
					if ( is_array( $tt_package ) && isset( $tt_package['term_id'] ) && ! empty( $meta ) ) {
						foreach ( $meta as $meta_key => $meta_value ) {
							add_term_meta( $tt_package['term_id'], $meta_key, $meta_value );
						}
					}
				}
			}
		}

		update_option( 'event_manager_installed_terms', 1 );
	}

	/**
	 * Default taxonomy terms to set up in WP Event Manager.
	 *
	 * @return array Default taxonomy terms.
	 */
	private static function get_default_taxonomy_terms() {
		return [
			'event_listing_type' => [
				'Full Time'  => [
					'employment_type' => 'FULL_TIME',
				],
				'Part Time'  => [
					'employment_type' => 'PART_TIME',
				],
				'Temporary'  => [
					'employment_type' => 'TEMPORARY',
				],
				'Freelance'  => [
					'employment_type' => 'CONTRACTOR',
				],
				'Internship' => [
					'employment_type' => 'INTERN',
				],
			],
		];
	}

	/**
	 * Adds the employment type to default event types when updating from a previous WP Event Manager version.
	 */
	private static function add_employment_types() {
		$taxonomies = self::get_default_taxonomy_terms();
		$terms      = $taxonomies['event_listing_type'];

		foreach ( $terms as $term => $meta ) {
			$term = get_term_by( 'slug', sanitize_title( $term ), 'event_listing_type' );
			if ( $term ) {
				foreach ( $meta as $meta_key => $meta_value ) {
					if ( ! get_term_meta( (int) $term->term_id, $meta_key, true ) ) {
						add_term_meta( (int) $term->term_id, $meta_key, $meta_value );
					}
				}
			}
		}
	}
}
