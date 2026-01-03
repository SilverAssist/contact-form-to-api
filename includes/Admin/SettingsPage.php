<?php
/**
 * Settings Hub Integration
 *
 * Integrates with Silver Assist Settings Hub for centralized plugin management.
 * Controller that handles registration and delegates rendering to SettingsView.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.1.0
 * @version 1.1.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Admin\Views\SettingsView;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\Plugin;
use SilverAssist\SettingsHub\SettingsHub;

\defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsPage
 *
 * Handles Settings Hub integration for the Contact Form 7 to API plugin.
 * Provides documentation page and update checking via Silver Assist dashboard.
 *
 * @since 1.1.0
 */
class SettingsPage implements LoadableInterface {

	/**
	 * Plugin slug for settings hub
	 *
	 * @var string
	 */
	private const SLUG = 'contact-form-to-api';

	/**
	 * Singleton instance
	 *
	 * @var SettingsPage|null
	 */
	private static ?SettingsPage $instance = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return SettingsPage
	 */
	public static function instance(): SettingsPage {
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
	 * Initialize Settings Hub integration
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		// Register with Settings Hub on admin_menu with priority 4 (before hub's priority 5).
		\add_action( 'admin_menu', array( $this, 'register_with_hub' ), 4 );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 25; // After core, before API log admin.
	}

	/**
	 * Determine if should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return \is_admin() && \class_exists( SettingsHub::class );
	}

	/**
	 * Register plugin with Settings Hub
	 *
	 * @return void
	 */
	public function register_with_hub(): void {
		if ( ! \class_exists( SettingsHub::class ) ) {
			return;
		}

		$hub = SettingsHub::get_instance();

		// Prepare actions array.
		$actions = array();

		// Add update checker button if wp-github-updater is available.
		if ( null !== Plugin::instance()->get_updater() ) {
			$actions[] = array(
				'label'    => \__( 'Check Updates', 'contact-form-to-api' ),
				'callback' => array( $this, 'render_update_check_script' ),
				'class'    => 'button',
			);
		}

		// Register plugin with the hub.
		$hub->register_plugin(
			self::SLUG,
			\__( 'Contact Form 7 to API', 'contact-form-to-api' ),
			array( $this, 'render_settings_page' ),
			array(
				'description' => \__( 'Send CF7 form submissions to external APIs with field mapping and logging.', 'contact-form-to-api' ),
				'version'     => CF7_API_VERSION,
				'capability'  => 'manage_options',
				'tab_title'   => \__( 'CF7 to API', 'contact-form-to-api' ),
				'actions'     => $actions,
			)
		);
	}

	/**
	 * Render update check script for dashboard button
	 *
	 * CRITICAL: This callback must:
	 * 1. Enqueue external JavaScript file with logic
	 * 2. Localize script with configuration data
	 * 3. ECHO a JavaScript function call that Settings Hub injects into onclick handler
	 *
	 * @param string $plugin_slug Plugin slug passed by Settings Hub.
	 * @return void
	 */
	public function render_update_check_script( string $plugin_slug = '' ): void {
		// Enqueue external JavaScript file.
		\wp_enqueue_script(
			'cf7-api-check-updates',
			CF7_API_URL . 'assets/js/admin-check-updates.js',
			array( 'jquery' ),
			CF7_API_VERSION,
			true
		);

		// Localize script with configuration data.
		\wp_localize_script(
			'cf7-api-check-updates',
			'cf7ApiCheckUpdatesData',
			array(
				'ajaxurl'   => \admin_url( 'admin-ajax.php' ),
				'nonce'     => \wp_create_nonce( 'cf7_api_version_nonce' ),
				'action'    => 'cf7_api_check_version',
				'updateUrl' => \admin_url( 'update-core.php' ),
				'strings'   => array(
					'checking'        => \__( 'Checking...', 'contact-form-to-api' ),
					/* translators: %s: Version number */
					'updateAvailable' => \__( 'Update available: v%s! Redirecting...', 'contact-form-to-api' ),
					'upToDate'        => \__( 'Plugin is up to date!', 'contact-form-to-api' ),
					'checkError'      => \__( 'Error checking updates. Please try again.', 'contact-form-to-api' ),
					'connectError'    => \__( 'Error connecting to update server.', 'contact-form-to-api' ),
				),
			)
		);

		// Echo JavaScript that will be executed by Settings Hub action button.
		// Settings Hub injects this into addEventListener('click', ...) handler.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inline JavaScript function call
		echo 'cf7ApiCheckUpdates(); return false;';
	}

	/**
	 * Render settings/documentation page
	 *
	 * Delegates to SettingsView for HTML rendering.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		SettingsView::render_page();
	}

	/**
	 * Enqueue assets for settings page
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Check for both standalone and Settings Hub contexts.
		$allowed_hooks = array(
			'settings_page_' . self::SLUG,
			'silver-assist_page_' . self::SLUG,
		);

		if ( ! \in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		\wp_enqueue_style(
			self::SLUG . '-settings',
			CF7_API_URL . 'assets/css/settings-page.css',
			array(),
			CF7_API_VERSION
		);
	}
}
