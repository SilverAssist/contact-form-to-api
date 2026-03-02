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
 * @version 2.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\Plugin;
use SilverAssist\ContactFormToAPI\View\Admin\Settings\SettingsView;
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
			Plugin::SLUG,
			\__( 'Contact Form 7 to API', 'contact-form-to-api' ),
			array( $this, 'render_settings_page' ),
			array(
				'description' => \__( 'Send CF7 form submissions to external APIs with field mapping and logging.', 'contact-form-to-api' ),
				'version'     => CF7_API_VERSION,
				'capability'  => 'manage_options',
				'tab_title'   => \__( 'CF7 to API', 'contact-form-to-api' ),
				'plugin_file' => CF7_API_FILE,
				'actions'     => $actions,
			)
		);
	}

	/**
	 * Render update check script for dashboard button
	 *
	 * Delegates to wp-github-updater's built-in enqueueCheckUpdatesScript() which
	 * provides centralized JS, AJAX handling, admin notices, and auto-redirect.
	 *
	 * @since 2.1.1 Simplified to use wp-github-updater v1.3.0 built-in script.
	 * @param string $plugin_slug Plugin slug passed by Settings Hub.
	 * @return void
	 */
	public function render_update_check_script( string $plugin_slug = '' ): void {
		$updater = Plugin::instance()->get_updater();

		if ( null === $updater ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inline JavaScript function call from wp-github-updater
		echo $updater->enqueueCheckUpdatesScript();
	}

	/**
	 * Render settings/documentation page
	 *
	 * Delegates to SettingsView for HTML rendering.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$notices = $this->get_admin_notices();
		SettingsView::render_page( $notices );
	}

	/**
	 * Get admin notices from query params
	 *
	 * @return array<int, array{type: string, message: string}> Array of notices.
	 */
	private function get_admin_notices(): array {
		$notices = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for display only.
		if ( isset( $_GET['updated'] ) ) {
			if ( '1' === $_GET['updated'] ) {
				$notices[] = array(
					'type'    => 'success',
					'message' => \__( 'Settings saved successfully.', 'contact-form-to-api' ),
				);
			} else {
				$notices[] = array(
					'type'    => 'error',
					'message' => \__( 'Failed to save settings. Please try again.', 'contact-form-to-api' ),
				);
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $notices;
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
			'settings_page_' . Plugin::SLUG,
			'silver-assist_page_' . Plugin::SLUG,
		);

		if ( ! \in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		\wp_enqueue_style(
			Plugin::SLUG . '-settings',
			CF7_API_URL . 'assets/css/settings-page.css',
			array(),
			CF7_API_VERSION
		);
	}
}
