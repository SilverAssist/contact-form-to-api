<?php
/**
 * Main Plugin Class
 *
 * Central controller for the Contact Form 7 to API plugin.
 * Manages component loading, initialization, and integration with SilverAssist packages.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 1.0.0
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;
use SilverAssist\WpGithubUpdater\Updater;
use SilverAssist\WpGithubUpdater\UpdaterConfig;

\defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Main plugin controller implementing singleton pattern and LoadableInterface
 * for consistent initialization and component management.
 */
class Plugin implements LoadableInterface {
	/**
	 * Plugin instance
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Loaded components
	 *
	 * @var LoadableInterface[]
	 */
	private array $components = array();

	/**
	 * Plugin settings
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = array();

	/**
	 * GitHub updater instance
	 *
	 * @var Updater|null
	 */
	private ?Updater $updater = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		// Empty - initialization happens in init().
	}

	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		// Load plugin settings.
		$this->load_settings();

		// Initialize GitHub Updater.
		$this->init_updater();

		// Load plugin components.
		$this->load_components();

		// Initialize WordPress hooks.
		$this->init_hooks();

		// Load plugin textdomain.
		$this->load_textdomain();

		// Mark as initialized.
		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10; // High priority for core plugin.
	}

	/**
	 * Determine if plugin should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		// Check if Contact Form 7 is available.
		if ( ! \class_exists( 'WPCF7_ContactForm' ) ) {
			return false;
		}

		// Check minimum WordPress version.
		global $wp_version;
		if ( \version_compare( $wp_version, CF7_API_MIN_WP_VERSION, '<' ) ) {
			return false;
		}

		// Check minimum PHP version.
		if ( \version_compare( PHP_VERSION, CF7_API_MIN_PHP_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Load plugin components
	 *
	 * Loads components using the Loader pattern for better organization.
	 * Each layer (ContactForm, Admin, Utils) has its own Loader that manages its components.
	 *
	 * @return void
	 */
	private function load_components(): void {
		// Load Settings first (priority 10 - Core).
		if ( \class_exists( Settings::class ) ) {
			try {
				$settings = Settings::instance();
				if ( $settings->should_load() ) {
					$settings->init();
					$this->components[] = $settings;
				}
			} catch ( \Exception $e ) {
				if ( \class_exists( DebugLogger::class ) ) {
					DebugLogger::instance()->error( 'Failed to load Settings - ' . $e->getMessage() );
				}
			}
		}

		// Load Utils Logger (priority 40 - but initialized early for error logging).
		if ( \class_exists( DebugLogger::class ) ) {
			try {
				$logger = DebugLogger::instance();
				if ( $logger->should_load() ) {
					$logger->init();
					$this->components[] = $logger;
				}
			} catch ( \Exception $e ) {
				// Fallback to error_log if Logger fails.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				\error_log( 'Contact Form to API: Failed to load Logger - ' . $e->getMessage() );
			}
		}

		// Load Services Loader (priority 20 - Services).
		if ( \class_exists( '\\SilverAssist\\ContactFormToAPI\\Services\\Loader' ) ) {
			try {
				$services_loader = \SilverAssist\ContactFormToAPI\Services\Loader::instance();
				if ( $services_loader->should_load() ) {
					$services_loader->init();
					$this->components[] = $services_loader;
				}
			} catch ( \Exception $e ) {
				$this->log_error( 'Services\\Loader', $e->getMessage() );
			}
		}

		// Load ContactForm integration (priority 20 - Services).
		if ( \class_exists( '\\SilverAssist\\ContactFormToAPI\\ContactForm\\Integration' ) ) {
			try {
				$integration = \SilverAssist\ContactFormToAPI\ContactForm\Integration::instance();
				if ( $integration->should_load() ) {
					$integration->init();
					$this->components[] = $integration;
				}
			} catch ( \Exception $e ) {
				$this->log_error( 'ContactForm\\Integration', $e->getMessage() );
			}
		}

		// Load Admin Loader (priority 30 - Admin components).
		// The Admin\Loader manages SettingsPage and RequestLogController internally.
		if ( \class_exists( '\\SilverAssist\\ContactFormToAPI\\Admin\\Loader' ) ) {
			try {
				$admin_loader = \SilverAssist\ContactFormToAPI\Admin\Loader::instance();
				if ( $admin_loader->should_load() ) {
					$admin_loader->init();
					$this->components[] = $admin_loader;
				}
			} catch ( \Exception $e ) {
				$this->log_error( 'Admin\\Loader', $e->getMessage() );
			}
		}
	}

	/**
	 * Log component loading error
	 *
	 * Uses the Utils\Logger if available, falls back to error_log.
	 *
	 * @param string $component Component name.
	 * @param string $message   Error message.
	 * @return void
	 */
	private function log_error( string $component, string $message ): void {
		$full_message = "Failed to load {$component} - {$message}";

		// Try to use the Logger if available.
		if ( \class_exists( DebugLogger::class ) ) {
			try {
				DebugLogger::instance()->error( $full_message, array( 'component' => $component ) );
				return;
			} catch ( \Exception $e ) {
				// Logger unavailable, fall through to error_log.
				unset( $e );
			}
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		\error_log( 'Contact Form to API: ' . $full_message );
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		\add_action( 'init', array( $this, 'handle_init' ) );
		\add_action( 'admin_init', array( $this, 'handle_admin_init' ) );
		\add_filter( 'plugin_action_links_' . CF7_API_BASENAME, array( $this, 'add_action_links' ) );

		// Register cron job for log cleanup.
		\add_action( 'cf7_api_cleanup_old_logs', array( $this, 'cleanup_old_logs' ) );
	}

	/**
	 * Load plugin settings
	 *
	 * @return void
	 */
	private function load_settings(): void {
		$this->settings = \get_option( 'cf7_api_settings', array() );
	}

	/**
	 * Initialize GitHub Updater
	 *
	 * Sets up automatic updates from GitHub releases.
	 *
	 * @return void
	 */
	private function init_updater(): void {
		// Only initialize updater if package is available.
		if ( ! \class_exists( Updater::class ) ) {
			return;
		}

		// Only initialize in admin context.
		if ( ! \is_admin() ) {
			return;
		}

		// Create updater configuration.
		$config = new UpdaterConfig(
			CF7_API_FILE,
			'SilverAssist/contact-form-to-api',
			array(
				'plugin_slug'        => 'contact-form-to-api',
				'plugin_name'        => 'Contact Form 7 to API',
				'requires_wordpress' => CF7_API_MIN_WP_VERSION,
				'requires_php'       => CF7_API_MIN_PHP_VERSION,
				'asset_pattern'      => 'contact-form-to-api-v{version}.zip',
				'ajax_action'        => 'cf7_api_check_version',
				'ajax_nonce'         => 'cf7_api_version_nonce',
				'text_domain'        => 'contact-form-to-api',
			)
		);

		$this->updater = new Updater( $config );
	}

	/**
	 * Handle WordPress init action
	 *
	 * @return void
	 */
	public function handle_init(): void {
		// Register any additional post types or taxonomies if needed.
		\do_action( 'cf7_api_init' );
	}

	/**
	 * Handle admin init action
	 *
	 * @return void
	 */
	public function handle_admin_init(): void {
		// Admin-specific initialization.
		\do_action( 'cf7_api_admin_init' );
	}

	/**
	 * Load plugin textdomain
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		\load_plugin_textdomain(
			'contact-form-to-api',
			false,
			\dirname( CF7_API_BASENAME ) . '/languages'
		);
	}

	/**
	 * Add plugin action links
	 *
	 * Adds "Settings" link to plugin list page.
	 *
	 * @param array<string> $links Existing action links.
	 * @return array<string> Modified action links.
	 */
	public function add_action_links( array $links ): array {
		// Settings link would point to CF7 form editor.
		$settings_link = \sprintf(
			'<a href="%s">%s</a>',
			\admin_url( 'admin.php?page=wpcf7' ),
			\esc_html__( 'Settings', 'contact-form-to-api' )
		);

		\array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Get loaded components
	 *
	 * @return LoadableInterface[]
	 */
	public function get_components(): array {
		return $this->components;
	}

	/**
	 * Get GitHub Updater instance
	 *
	 * @return Updater|null
	 */
	public function get_updater(): ?Updater {
		return $this->updater;
	}

	/**
	 * Get Logger instance
	 *
	 * @return DebugLogger|null
	 */
	public function get_logger(): ?DebugLogger {
		if ( \class_exists( DebugLogger::class ) ) {
			return DebugLogger::instance();
		}
		return null;
	}

	/**
	 * Get plugin settings
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		return $this->settings;
	}

	/**
	 * Get specific setting value
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default_value Default value if setting doesn't exist.
	 * @return mixed Setting value.
	 */
	public function get_setting( string $key, $default_value = null ) {
		return $this->settings[ $key ] ?? $default_value;
	}

	/**
	 * Cleanup old logs based on retention settings
	 *
	 * Scheduled via WP-Cron to run daily.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function cleanup_old_logs(): void {
		// Get retention days from settings.
		if ( ! \class_exists( Settings::class ) ) {
			return;
		}

		$settings       = Settings::instance();
		$retention_days = $settings->get_log_retention_days();

		// Don't delete if retention is disabled (0 days).
		if ( $retention_days <= 0 ) {
			return;
		}

		// Use RequestLogger to clean old logs.
		$logger  = new RequestLogger();
		$deleted = $logger->clean_old_logs( $retention_days );

		// Log cleanup result if debug logger is available.
		if ( $deleted > 0 && \class_exists( DebugLogger::class ) ) {
			try {
				DebugLogger::instance()->info(
					"Cleaned up {$deleted} old API logs (retention: {$retention_days} days)",
					array(
						'deleted_count'  => $deleted,
						'retention_days' => $retention_days,
					)
				);
			} catch ( \Exception $e ) {
				// Silently fail if logger not available.
				unset( $e );
			}
		}
	}
}
