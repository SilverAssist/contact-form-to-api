<?php
/**
 * Global Settings Controller
 *
 * Handles the global settings page for plugin-wide configuration.
 * Manages settings form submission and delegates rendering to GlobalSettingsView.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.2.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\Settings;

\defined( 'ABSPATH' ) || exit;

/**
 * Class GlobalSettingsController
 *
 * Controller for global plugin settings page.
 *
 * @since 1.2.0
 */
class GlobalSettingsController implements LoadableInterface {

	/**
	 * Settings page slug
	 *
	 * @deprecated 1.2.0 Global settings are now embedded in the main CF7 to API tab.
	 * @var string
	 */
	public const PAGE_SLUG = 'cf7-api-global-settings';

	/**
	 * Nonce action for settings form
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'cf7_api_global_settings_save';

	/**
	 * Nonce name for settings form
	 *
	 * @var string
	 */
	private const NONCE_NAME = 'cf7_api_global_settings_nonce';

	/**
	 * Singleton instance
	 *
	 * @var GlobalSettingsController|null
	 */
	private static ?GlobalSettingsController $instance = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return GlobalSettingsController
	 */
	public static function instance(): GlobalSettingsController {
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
	 * Initialize controller
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		// Handle form submission (settings form is now embedded in SettingsView).
		\add_action( 'admin_post_cf7_api_save_global_settings', array( $this, 'handle_save_settings' ) );

		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 26; // After SettingsPage (25), before RequestLogController (30).
	}

	/**
	 * Determine if should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return \is_admin();
	}

	/**
	 * Register with Settings Hub
	 *
	 * @deprecated 1.2.0 Global settings are now embedded in the main CF7 to API tab.
	 * @return void
	 */
	public function register_with_hub(): void {
		// Deprecated: Settings are now embedded in SettingsView.
	}

	/**
	 * Render settings page
	 *
	 * @deprecated 1.2.0 Global settings are now embedded in the main CF7 to API tab.
	 * @return void
	 */
	public function render_settings_page(): void {
		// Deprecated: Settings are now rendered via SettingsView.
	}

	/**
	 * Handle settings form submission
	 *
	 * @return void
	 */
	public function handle_save_settings(): void {
		// Verify user capabilities.
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access this page.', 'contact-form-to-api' ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			\wp_die( \esc_html__( 'Security check failed. Please try again.', 'contact-form-to-api' ) );
		}

		// Get settings instance.
		$settings = Settings::instance();

		// Sanitize and validate input.
		$new_settings = array(
			'max_manual_retries'   => isset( $_POST['max_manual_retries'] ) ? \absint( $_POST['max_manual_retries'] ) : 3,
			'max_retries_per_hour' => isset( $_POST['max_retries_per_hour'] ) ? \absint( $_POST['max_retries_per_hour'] ) : 10,
			'sensitive_patterns'   => $this->sanitize_patterns( isset( $_POST['sensitive_patterns'] ) ? \wp_unslash( $_POST['sensitive_patterns'] ) : '' ),
			'logging_enabled'      => isset( $_POST['logging_enabled'] ) && '1' === $_POST['logging_enabled'],
			'log_retention_days'   => isset( $_POST['log_retention_days'] ) ? \absint( $_POST['log_retention_days'] ) : 30,
		);

		// Update settings.
		$success = $settings->update( $new_settings );

		// Schedule or unschedule log cleanup based on retention setting.
		$this->update_cleanup_schedule( $new_settings['log_retention_days'] );

		// Redirect back to the main settings page with success/error message.
		$redirect_url = \add_query_arg(
			array(
				'page'    => 'cf7-api-settings',
				'updated' => $success ? '1' : '0',
			),
			\admin_url( 'admin.php' )
		);

		\wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Sanitize sensitive patterns input
	 *
	 * @param string $input Raw input string.
	 * @return array<string> Sanitized patterns array.
	 */
	private function sanitize_patterns( string $input ): array {
		// Split by newlines or commas.
		$patterns = \preg_split( '/[\r\n,]+/', $input, -1, PREG_SPLIT_NO_EMPTY );

		if ( ! \is_array( $patterns ) ) {
			return array();
		}

		// Trim and sanitize each pattern.
		$sanitized = array();
		foreach ( $patterns as $pattern ) {
			$pattern = \trim( \sanitize_text_field( $pattern ) );
			if ( ! empty( $pattern ) ) {
				$sanitized[] = $pattern;
			}
		}

		return $sanitized;
	}

	/**
	 * Update log cleanup schedule
	 *
	 * @param int $retention_days Number of days to retain logs.
	 * @return void
	 */
	private function update_cleanup_schedule( int $retention_days ): void {
		$hook = 'cf7_api_cleanup_old_logs';

		// Clear existing schedule.
		$timestamp = \wp_next_scheduled( $hook );
		if ( $timestamp ) {
			\wp_unschedule_event( $timestamp, $hook );
		}

		// Schedule daily cleanup if retention is enabled.
		if ( $retention_days > 0 ) {
			\wp_schedule_event( \time(), 'daily', $hook );
		}
	}

	/**
	 * Get admin notices from query params
	 *
	 * @deprecated 1.2.0 Global settings are now embedded in the main CF7 to API tab.
	 * @return array<int, array{type: string, message: string}> Array of notices.
	 */
	public function get_admin_notices(): array {
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
	 * @deprecated 1.2.0 Global settings are now embedded in the main CF7 to API tab.
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Deprecated: Assets are now loaded by SettingsPage controller.
	}

	/**
	 * Get nonce action for forms
	 *
	 * @return string
	 */
	public static function get_nonce_action(): string {
		return self::NONCE_ACTION;
	}

	/**
	 * Get nonce name for forms
	 *
	 * @return string
	 */
	public static function get_nonce_name(): string {
		return self::NONCE_NAME;
	}
}
