<?php
/**
 * Global Settings Manager
 *
 * Centralized settings management for plugin-wide configuration.
 * Provides defaults, getters, and setters for all global settings.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 1.2.0
 * @version 1.2.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * Manages plugin global settings with defaults and validation.
 *
 * @since 1.2.0
 */
class Settings implements LoadableInterface {

	/**
	 * Settings option name
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'cf7_api_global_settings';

	/**
	 * Singleton instance
	 *
	 * @var Settings|null
	 */
	private static ?Settings $instance = null;

	/**
	 * Cached settings
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = array();

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return Settings
	 */
	public static function instance(): Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		// Empty - initialization happens in init().
	}

	/**
	 * Initialize settings
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		$this->load_settings();
		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10; // Core priority - load early.
	}

	/**
	 * Determine if should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true; // Always load settings.
	}

	/**
	 * Get default settings
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return array(
			'max_manual_retries'      => 3,
			'max_retries_per_hour'    => 10,
			'sensitive_patterns'      => array( 'password', 'token', 'secret', 'api_key', 'apikey', 'api-key' ),
			'logging_enabled'         => true,
			'log_retention_days'      => 30,
			// Email alert settings.
			'alerts_enabled'          => false,
			'alert_recipients'        => \get_option( 'admin_email' ),
			'alert_error_threshold'   => 10,
			'alert_rate_threshold'    => 20,
			'alert_check_interval'    => 'hourly',
			'alert_cooldown_hours'    => 4,
			'alert_last_sent'         => 0,
		);
	}

	/**
	 * Load settings from database
	 *
	 * @return void
	 */
	private function load_settings(): void {
		$saved_settings = \get_option( self::OPTION_NAME, array() );
		$defaults       = self::get_defaults();

		// Merge with defaults to ensure all keys exist.
		$this->settings = \wp_parse_args( $saved_settings, $defaults );
	}

	/**
	 * Get all settings
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		return $this->settings;
	}

	/**
	 * Get specific setting value
	 *
	 * @param string $key          Setting key.
	 * @param mixed  $default      Default value if setting doesn't exist.
	 * @return mixed Setting value.
	 */
	public function get( string $key, $default = null ) {
		if ( ! $this->initialized ) {
			$this->init();
		}

		return $this->settings[ $key ] ?? $default;
	}

	/**
	 * Update specific setting value
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value ): bool {
		$this->settings[ $key ] = $value;
		return \update_option( self::OPTION_NAME, $this->settings );
	}

	/**
	 * Update multiple settings at once
	 *
	 * @param array<string, mixed> $settings Settings to update.
	 * @return bool True on success, false on failure.
	 */
	public function update( array $settings ): bool {
		$this->settings = \wp_parse_args( $settings, $this->settings );
		return \update_option( self::OPTION_NAME, $this->settings );
	}

	/**
	 * Reset settings to defaults
	 *
	 * @return bool True on success, false on failure.
	 */
	public function reset(): bool {
		$this->settings = self::get_defaults();
		return \update_option( self::OPTION_NAME, $this->settings );
	}

	/**
	 * Delete all settings
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(): bool {
		$this->settings = self::get_defaults();
		return \delete_option( self::OPTION_NAME );
	}

	/**
	 * Get maximum manual retries per entry
	 *
	 * @return int
	 */
	public function get_max_manual_retries(): int {
		return (int) $this->get( 'max_manual_retries', 3 );
	}

	/**
	 * Get maximum retries per hour
	 *
	 * @return int
	 */
	public function get_max_retries_per_hour(): int {
		return (int) $this->get( 'max_retries_per_hour', 10 );
	}

	/**
	 * Get sensitive data patterns
	 *
	 * @return array<string>
	 */
	public function get_sensitive_patterns(): array {
		$patterns = $this->get( 'sensitive_patterns', array() );
		return \is_array( $patterns ) ? $patterns : array();
	}

	/**
	 * Check if logging is enabled
	 *
	 * @return bool
	 */
	public function is_logging_enabled(): bool {
		return (bool) $this->get( 'logging_enabled', true );
	}

	/**
	 * Get log retention days
	 *
	 * @return int Number of days to retain logs (0 = never delete).
	 */
	public function get_log_retention_days(): int {
		return (int) $this->get( 'log_retention_days', 30 );
	}

	/**
	 * Check if email alerts are enabled
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public function is_alerts_enabled(): bool {
		return (bool) $this->get( 'alerts_enabled', false );
	}

	/**
	 * Get alert recipients
	 *
	 * @since 1.2.0
	 * @return string Comma-separated email addresses.
	 */
	public function get_alert_recipients(): string {
		return (string) $this->get( 'alert_recipients', \get_option( 'admin_email' ) );
	}

	/**
	 * Get error count threshold
	 *
	 * @since 1.2.0
	 * @return int Number of errors per hour to trigger alert.
	 */
	public function get_alert_error_threshold(): int {
		return (int) $this->get( 'alert_error_threshold', 10 );
	}

	/**
	 * Get error rate threshold
	 *
	 * @since 1.2.0
	 * @return int Error percentage to trigger alert.
	 */
	public function get_alert_rate_threshold(): int {
		return (int) $this->get( 'alert_rate_threshold', 20 );
	}

	/**
	 * Get alert check interval
	 *
	 * @since 1.2.0
	 * @return string Cron interval ('hourly', 'twicehourly').
	 */
	public function get_alert_check_interval(): string {
		return (string) $this->get( 'alert_check_interval', 'hourly' );
	}

	/**
	 * Get alert cooldown hours
	 *
	 * @since 1.2.0
	 * @return int Hours between alerts.
	 */
	public function get_alert_cooldown_hours(): int {
		return (int) $this->get( 'alert_cooldown_hours', 4 );
	}

	/**
	 * Get timestamp of last alert sent
	 *
	 * @since 1.2.0
	 * @return int Unix timestamp.
	 */
	public function get_alert_last_sent(): int {
		return (int) $this->get( 'alert_last_sent', 0 );
	}

	/**
	 * Update last alert sent timestamp
	 *
	 * @since 1.2.0
	 * @param int $timestamp Unix timestamp.
	 * @return bool True on success, false on failure.
	 */
	public function update_alert_last_sent( int $timestamp ): bool {
		return $this->set( 'alert_last_sent', $timestamp );
	}
}
