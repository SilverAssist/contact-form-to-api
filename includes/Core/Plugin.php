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
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;

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
	 * @var \SilverAssist\WpGithubUpdater\Updater|null
	 */
	private ?\SilverAssist\WpGithubUpdater\Updater $updater = null;

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
		if ( \version_compare( $wp_version, CONTACT_FORM_TO_API_MIN_WP_VERSION, '<' ) ) {
			return false;
		}

		// Check minimum PHP version.
		if ( \version_compare( PHP_VERSION, CONTACT_FORM_TO_API_MIN_PHP_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Load plugin components
	 *
	 * @return void
	 */
	private function load_components(): void {
		// Load ContactForm integration.
		if ( \class_exists( '\\SilverAssist\\ContactFormToAPI\\ContactForm\\Integration' ) ) {
			try {
				$integration = \SilverAssist\ContactFormToAPI\ContactForm\Integration::instance();
				if ( $integration->should_load() ) {
					$integration->init();
					$this->components[] = $integration;
				}
			} catch ( \Exception $e ) {
				\error_log( "Contact Form to API: Failed to load ContactForm integration - {$e->getMessage()}" );
			}
		}
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		\add_action( 'init', array( $this, 'handle_init' ) );
		\add_action( 'admin_init', array( $this, 'handle_admin_init' ) );
		\add_filter( 'plugin_action_links_' . CONTACT_FORM_TO_API_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Load plugin settings
	 *
	 * @return void
	 */
	private function load_settings(): void {
		$this->settings = \get_option( 'contact_form_to_api_settings', array() );
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
		if ( ! \class_exists( '\\SilverAssist\\WpGithubUpdater\\Updater' ) ) {
			return;
		}

		// Only initialize in admin context.
		if ( ! \is_admin() ) {
			return;
		}

		// Create updater configuration.
		$config = new \SilverAssist\WpGithubUpdater\UpdaterConfig(
			array(
				'plugin_file'        => CONTACT_FORM_TO_API_FILE,
				'github_repository'  => 'SilverAssist/contact-form-to-api',
				'plugin_slug'        => 'contact-form-to-api',
				'plugin_name'        => 'Contact Form 7 to API',
				'requires_wordpress' => CONTACT_FORM_TO_API_MIN_WP_VERSION,
				'requires_php'       => CONTACT_FORM_TO_API_MIN_PHP_VERSION,
				'asset_pattern'      => 'contact-form-to-api-v{version}.zip',
				'ajax_action'        => 'cf7_api_check_version',
				'ajax_nonce'         => 'cf7_api_version_nonce',
				'text_domain'        => 'contact-form-to-api',
			)
		);

		$this->updater = new \SilverAssist\WpGithubUpdater\Updater( $config );
	}

	/**
	 * Handle WordPress init action
	 *
	 * @return void
	 */
	public function handle_init(): void {
		// Register any additional post types or taxonomies if needed.
		\do_action( 'contact_form_to_api_init' );
	}

	/**
	 * Handle admin init action
	 *
	 * @return void
	 */
	public function handle_admin_init(): void {
		// Admin-specific initialization.
		\do_action( 'contact_form_to_api_admin_init' );
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
			\dirname( CONTACT_FORM_TO_API_BASENAME ) . '/languages'
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
	 * @param mixed  $default Default value if setting doesn't exist.
	 * @return mixed Setting value.
	 */
	public function get_setting( string $key, $default = null ) {
		return $this->settings[ $key ] ?? $default;
	}
}
