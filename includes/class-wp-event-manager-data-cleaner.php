<?php
/**
 * File containing the class WP_event_Manager_Data_Cleaner.
 *
 * @package wp-event-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Methods for cleaning up all plugin data.
 *
 * @author Automattic
 * @since 1.31.0
 */
class WP_event_Manager_Data_Cleaner {

	/**
	 * Custom post types to be deleted.
	 *
	 * @var $custom_post_types
	 */
	private static $custom_post_types = [
		'event_listing',
	];

	/**
	 * Taxonomies to be deleted.
	 *
	 * @var $taxonomies
	 */
	private static $taxonomies = [
		'event_listing_category',
		'event_listing_type',
	];

	/** Cron events to be unscheduled.
	 *
	 * @var $cron_events
	 */
	private static $cron_events = [
		'event_manager_check_for_expired_events',
		'event_manager_delete_old_previews',
		'event_manager_email_daily_notices',
		'event_manager_usage_tracking_send_usage_data',

		// Old cron events.
		'event_manager_clear_expired_transients',
	];

	/**
	 * Options to be deleted.
	 *
	 * @var $options
	 */
	private static $options = [
		'wp_event_manager_version',
		'event_manager_installed_terms',
		'wpjm_permalinks',
		'event_manager_permalinks',
		'event_manager_helper',
		'event_manager_date_format',
		'event_manager_google_maps_api_key',
		'event_manager_usage_tracking_enabled',
		'event_manager_usage_tracking_opt_in_hide',
		'event_manager_per_page',
		'event_manager_hide_filled_positions',
		'event_manager_hide_expired',
		'event_manager_hide_expired_content',
		'event_manager_enable_categories',
		'event_manager_enable_default_category_multiselect',
		'event_manager_category_filter_type',
		'event_manager_enable_types',
		'event_manager_multi_event_type',
		'event_manager_user_requires_account',
		'event_manager_enable_registration',
		'event_manager_generate_username_from_email',
		'event_manager_use_standard_password_setup_email',
		'event_manager_registration_role',
		'event_manager_submission_requires_approval',
		'event_manager_user_can_edit_pending_submissions',
		'event_manager_user_edit_published_submissions',
		'event_manager_submission_duration',
		'event_manager_allowed_application_method',
		'event_manager_recaptcha_label',
		'event_manager_recaptcha_site_key',
		'event_manager_recaptcha_secret_key',
		'event_manager_enable_recaptcha_event_submission',
		'event_manager_submit_event_form_page_id',
		'event_manager_event_dashboard_page_id',
		'event_manager_events_page_id',
		'event_manager_submit_page_slug',
		'event_manager_event_dashboard_page_slug',
		'event_manager_delete_data_on_uninstall',
		'event_manager_email_admin_updated_event',
		'event_manager_email_admin_new_event',
		'event_manager_email_admin_expiring_event',
		'event_manager_email_employer_expiring_event',
		'event_manager_admin_notices',
		'widget_widget_featured_events',
		'widget_widget_recent_events',
	];

	/**
	 * Site options to be deleted.
	 *
	 * @var $site_options
	 */
	private static $site_options = [
		'event_manager_helper',
	];

	/**
	 * Transient names (as MySQL regexes) to be deleted. The prefixes
	 * "_transient_" and "_transient_timeout_" will be prepended.
	 *
	 * @var $transients
	 */
	private static $transients = [
		'_event_manager_activation_redirect', // Legacy transient that should still be removed.
		'get_event_listings-transient-version',
		'jm_.*',
	];

	/**
	 * Role to be removed.
	 *
	 * @var $role
	 */
	private static $role = 'employer';

	/**
	 * Capabilities to be deleted.
	 *
	 * @var $caps
	 */
	private static $caps = [
		'manage_event_listings',
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
	];

	/**
	 * User meta key names to be deleted.
	 *
	 * @var array $user_meta_keys
	 */
	private static $user_meta_keys = [
		'_company_logo',
		'_company_name',
		'_company_website',
		'_company_tagline',
		'_company_twitter',
		'_company_video',
	];

	/**
	 * Cleanup all data.
	 *
	 * @access public
	 */
	public static function cleanup_all() {
		self::cleanup_custom_post_types();
		self::cleanup_taxonomies();
		self::cleanup_pages();
		self::cleanup_cron_events();
		self::cleanup_roles_and_caps();
		self::cleanup_transients();
		self::cleanup_user_meta();
		self::cleanup_options();
		self::cleanup_site_options();
	}

	/**
	 * Cleanup data for custom post types.
	 *
	 * @access private
	 */
	private static function cleanup_custom_post_types() {
		foreach ( self::$custom_post_types as $post_type ) {
			$items = get_posts(
				[
					'post_type'   => $post_type,
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				]
			);

			foreach ( $items as $item ) {
				wp_trash_post( $item );
			}
		}
	}

	/**
	 * Cleanup data for taxonomies.
	 *
	 * @access private
	 */
	private static function cleanup_taxonomies() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( self::$taxonomies as $taxonomy ) {
			$terms = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT term_id, term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s",
					$taxonomy
				)
			);

			// Delete all data for each term.
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_relationships, [ 'term_taxonomy_id' => $term->term_taxonomy_id ] );
				$wpdb->delete( $wpdb->term_taxonomy, [ 'term_taxonomy_id' => $term->term_taxonomy_id ] );
				$wpdb->delete( $wpdb->terms, [ 'term_id' => $term->term_id ] );
				$wpdb->delete( $wpdb->termmeta, [ 'term_id' => $term->term_id ] );
			}

			if ( function_exists( 'clean_taxonomy_cache' ) ) {
				clean_taxonomy_cache( $taxonomy );
			}
		}

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Cleanup data for pages.
	 *
	 * @access private
	 */
	private static function cleanup_pages() {
		// Trash the Submit event page.
		$submit_event_form_page_id = get_option( 'event_manager_submit_event_form_page_id' );
		if ( $submit_event_form_page_id ) {
			wp_trash_post( $submit_event_form_page_id );
		}

		// Trash the event Dashboard page.
		$event_dashboard_page_id = get_option( 'event_manager_event_dashboard_page_id' );
		if ( $event_dashboard_page_id ) {
			wp_trash_post( $event_dashboard_page_id );
		}

		// Trash the events page.
		$events_page_id = get_option( 'event_manager_events_page_id' );
		if ( $events_page_id ) {
			wp_trash_post( $events_page_id );
		}
	}

	/**
	 * Cleanup data for options.
	 *
	 * @access private
	 */
	private static function cleanup_options() {
		foreach ( self::$options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Cleanup data for site options.
	 *
	 * @access private
	 */
	private static function cleanup_site_options() {
		foreach ( self::$site_options as $option ) {
			delete_site_option( $option );
		}
	}

	/**
	 * Cleanup transients from the database.
	 *
	 * @access private
	 */
	private static function cleanup_transients() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( [ '_transient_', '_transient_timeout_' ] as $prefix ) {
			foreach ( self::$transients as $transient ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $wpdb->options WHERE option_name RLIKE %s",
						$prefix . $transient
					)
				);
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Cleanup data for roles and caps.
	 *
	 * @access private
	 */
	private static function cleanup_roles_and_caps() {
		global $wp_roles;

		// Remove caps from roles.
		$role_names = array_keys( $wp_roles->roles );
		foreach ( $role_names as $role_name ) {
			$role = get_role( $role_name );
			self::remove_all_event_manager_caps( $role );
		}

		// Remove caps and role from users.
		$users = get_users( [] );
		foreach ( $users as $user ) {
			self::remove_all_event_manager_caps( $user );
			$user->remove_role( self::$role );
		}

		// Remove role.
		remove_role( self::$role );
	}

	/**
	 * Helper method to remove WPJM caps from a user or role object.
	 *
	 * @param (WP_User|WP_Role) $object the user or role object.
	 */
	private static function remove_all_event_manager_caps( $object ) {
		foreach ( self::$caps as $cap ) {
			$object->remove_cap( $cap );
		}
	}

	/**
	 * Cleanup user meta from the database.
	 *
	 * @access private
	 */
	private static function cleanup_user_meta() {
		global $wpdb;

		foreach ( self::$user_meta_keys as $meta_key ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Delete data across all users.
			$wpdb->delete( $wpdb->usermeta, [ 'meta_key' => $meta_key ] );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	/**
	 * Cleanup cron events. Note that this should be done on deactivation, but
	 * doing it here as well for safety.
	 *
	 * @access private
	 */
	private static function cleanup_cron_events() {
		foreach ( self::$cron_events as $event ) {
			wp_clear_scheduled_hook( $event );
		}
	}
}
