<?php
/**
 * Plugin Activator
 *
 * Handles plugin activation and deactivation tasks including database setup,
 * option initialization, and cleanup operations.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 1.0.0
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

\defined( 'ABSPATH' ) || exit;

/**
 * Class Activator
 *
 * Manages plugin lifecycle events including activation, deactivation,
 * and uninstall procedures.
 */
class Activator {
	/**
	 * Plugin activation handler
	 *
	 * Performs necessary tasks when the plugin is activated:
	 * - Creates database tables if needed
	 * - Sets default options
	 * - Checks system requirements
	 * - Initializes plugin data
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Verify minimum requirements.
		self::check_requirements();

		// Create database tables.
		self::create_tables();

		// Set plugin version.
		\update_option( 'cf7_api_version', CF7_API_VERSION );

		// Initialize default settings.
		self::init_default_settings();

		// Set activation flag for first-time setup.
		\update_option( 'cf7_api_activated', time() );

		// Clear any cached data.
		\wp_cache_flush();
	}

	/**
	 * Plugin deactivation handler
	 *
	 * Performs cleanup tasks when the plugin is deactivated:
	 * - Removes temporary data
	 * - Clears caches
	 * - Unregisters scheduled events
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear any scheduled cron events.
		\wp_clear_scheduled_hook( 'cf7_api_cleanup' );
		\wp_clear_scheduled_hook( 'cf7_api_cleanup_old_logs' );

		// Clear cached data.
		\wp_cache_flush();

		// Set deactivation timestamp.
		\update_option( 'cf7_api_deactivated', time() );
	}

	/**
	 * Plugin uninstall handler
	 *
	 * Completely removes plugin data when uninstalled (if configured to do so).
	 * This method should only be called from the uninstall.php file.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		// Check if user wants to keep data.
		$keep_data = \get_option( 'cf7_api_keep_data_on_uninstall', false );

		if ( ! $keep_data ) {
			// Remove plugin options.
			\delete_option( 'cf7_api_version' );
			\delete_option( 'cf7_api_settings' );
			\delete_option( 'cf7_api_activated' );
			\delete_option( 'cf7_api_deactivated' );
			\delete_option( 'cf7_api_keep_data_on_uninstall' );

			// Drop database tables.
			self::drop_tables();

			// Clear any cached data.
			\wp_cache_flush();
		}
	}

	/**
	 * Create database tables
	 *
	 * Creates all required database tables for the plugin.
	 * Public static method to allow reuse in test environments.
	 *
	 * Uses dbDelta() which intelligently:
	 * - Creates table if it doesn't exist
	 * - Updates structure if schema changed (adds columns, modifies indexes)
	 * - Preserves existing data
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'cf7_api_logs';
		$charset_collate = $wpdb->get_charset_collate();

		// Note: dbDelta requires specific formatting:
		// - NO "IF NOT EXISTS" (dbDelta handles this)
		// - Exactly 2 spaces before PRIMARY KEY
		// - Each field on its own line
		// - Spaces around parentheses.
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id bigint(20) UNSIGNED NOT NULL,
			endpoint varchar(500) NOT NULL,
			method varchar(10) NOT NULL,
			status varchar(20) NOT NULL,
			request_data longtext NOT NULL,
			request_headers longtext DEFAULT NULL,
			response_data longtext DEFAULT NULL,
			response_headers longtext DEFAULT NULL,
			response_code int(11) UNSIGNED DEFAULT NULL,
			error_message text DEFAULT NULL,
			execution_time decimal(10,4) DEFAULT NULL,
			retry_count int(3) UNSIGNED DEFAULT 0,
			retry_of bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY created_at (created_at),
			KEY status (status),
			KEY retry_of (retry_of)
		) {$charset_collate};";

		// Try to load dbDelta function.
		if ( ! \function_exists( 'dbDelta' ) && \defined( 'ABSPATH' ) ) {
			$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
			if ( \file_exists( $upgrade_file ) ) {
				require_once $upgrade_file;
			}
		}

		// Use dbDelta if available, otherwise use direct query.
		if ( \function_exists( 'dbDelta' ) ) {
			\dbDelta( $sql );
		} else {
			// Fallback for test environments where dbDelta might not be available.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( \str_replace( 'CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $sql ) );
		}
	}

	/**
	 * Drop database tables
	 *
	 * Removes all plugin database tables during uninstall.
	 *
	 * @return void
	 */
	private static function drop_tables(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
	}

	/**
	 * Check plugin requirements
	 *
	 * Verifies that the system meets minimum requirements for the plugin.
	 *
	 * @throws \Exception If requirements are not met.
	 * @return void
	 */
	private static function check_requirements(): void {
		// Check PHP version.
		if ( \version_compare( PHP_VERSION, CF7_API_MIN_PHP_VERSION, '<' ) ) {
			if ( \defined( 'CF7_API_BASENAME' ) ) {
				\deactivate_plugins( CF7_API_BASENAME );
			}

			throw new \Exception(
				\sprintf(
					/* translators: %s: required PHP version */
					\esc_html__( 'Contact Form 7 to API requires PHP %s or higher.', 'contact-form-to-api' ),
					\esc_html( CF7_API_MIN_PHP_VERSION )
				)
			);
		}

		// Check WordPress version.
		global $wp_version;
		if ( \version_compare( $wp_version, CF7_API_MIN_WP_VERSION, '<' ) ) {
			if ( \defined( 'CF7_API_BASENAME' ) ) {
				\deactivate_plugins( CF7_API_BASENAME );
			}

			throw new \Exception(
				\sprintf(
					/* translators: %s: required WordPress version */
					\esc_html__( 'Contact Form 7 to API requires WordPress %s or higher.', 'contact-form-to-api' ),
					\esc_html( CF7_API_MIN_WP_VERSION )
				)
			);
		}

		// Check Contact Form 7 availability.
		if ( ! \class_exists( 'WPCF7_ContactForm' ) ) {
			if ( \defined( 'CF7_API_BASENAME' ) ) {
				\deactivate_plugins( CF7_API_BASENAME );
			}

			throw new \Exception(
				\esc_html__( 'Contact Form 7 to API requires Contact Form 7 to be active.', 'contact-form-to-api' )
			);
		}
	}

	/**
	 * Initialize default settings
	 *
	 * Sets up default plugin configuration if no settings exist.
	 *
	 * @return void
	 */
	private static function init_default_settings(): void {
		// Legacy settings (kept for backward compatibility).
		$default_settings = array(
			'debug_mode'         => false,
			'log_errors'         => true,
			'max_log_entries'    => 100,
			'log_retention_days' => 30,
		);

		// Only set defaults if settings don't exist.
		if ( \get_option( 'cf7_api_settings' ) === false ) {
			\update_option( 'cf7_api_settings', $default_settings );
		}

		// Initialize global settings using Settings class.
		if ( \class_exists( Settings::class ) ) {
			if ( \get_option( 'cf7_api_global_settings' ) === false ) {
				$settings = Settings::instance();
				$settings->init();
				\update_option( 'cf7_api_global_settings', $settings::get_defaults() );

				// Schedule daily log cleanup if retention is enabled and not already scheduled.
				$retention_days = $settings->get_log_retention_days();
				if ( $retention_days > 0 && ! \wp_next_scheduled( 'cf7_api_cleanup_old_logs' ) ) {
					\wp_schedule_event( \time(), 'daily', 'cf7_api_cleanup_old_logs' );
				}
			}
		}
	}
}
