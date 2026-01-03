<?php
/**
 * Global Settings Controller
 *
 * Handles global settings form submission and AJAX requests.
 * Does NOT register any admin pages - that's handled by SettingsPage.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.2.0
 * @version 1.2.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Services\EmailAlertService;

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

		// Handle AJAX test email.
		\add_action( 'wp_ajax_cf7_api_send_test_email', array( $this, 'handle_test_email' ) );

		// Enqueue admin scripts.
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

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
	 * Enqueue admin assets
	 *
	 * @since 1.2.0
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Check for both standalone and Settings Hub contexts.
		$allowed_hooks = array(
			'settings_page_contact-form-to-api',
			'silver-assist_page_contact-form-to-api',
		);

		if ( ! \in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		// Enqueue JavaScript for test email button.
		\wp_enqueue_script(
			'cf7-api-settings',
			CF7_API_URL . 'assets/js/settings-page.js',
			array( 'jquery' ),
			CF7_API_VERSION,
			true
		);

		// Localize script with AJAX URL and nonce.
		\wp_localize_script(
			'cf7-api-settings',
			'cf7ApiSettings',
			array(
				'ajaxUrl'            => \admin_url( 'admin-ajax.php' ),
				'nonce'              => \wp_create_nonce( 'cf7_api_test_email' ),
				// i18n strings for JavaScript.
				'i18n'               => array(
					'enterRecipient' => \__( 'Please enter a recipient email address.', 'contact-form-to-api' ),
					'sending'        => \__( 'Sending...', 'contact-form-to-api' ),
					'sendTestEmail'  => \__( 'Send Test Email', 'contact-form-to-api' ),
					'ajaxError'      => \__( 'An error occurred while sending the test email.', 'contact-form-to-api' ),
				),
			)
		);
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
			'max_manual_retries'      => isset( $_POST['max_manual_retries'] ) ? \absint( $_POST['max_manual_retries'] ) : 3,
			'max_retries_per_hour'    => isset( $_POST['max_retries_per_hour'] ) ? \absint( $_POST['max_retries_per_hour'] ) : 10,
			'sensitive_patterns'      => $this->sanitize_patterns( isset( $_POST['sensitive_patterns'] ) ? \wp_unslash( $_POST['sensitive_patterns'] ) : '' ),
			'logging_enabled'         => isset( $_POST['logging_enabled'] ) && '1' === $_POST['logging_enabled'],
			'log_retention_days'      => isset( $_POST['log_retention_days'] ) ? \absint( $_POST['log_retention_days'] ) : 30,
			// Email alert settings.
			'alerts_enabled'          => isset( $_POST['alerts_enabled'] ) && '1' === $_POST['alerts_enabled'],
			'alert_recipients'        => $this->sanitize_email_recipients( isset( $_POST['alert_recipients'] ) ? \wp_unslash( $_POST['alert_recipients'] ) : \get_option( 'admin_email' ) ),
			'alert_error_threshold'   => isset( $_POST['alert_error_threshold'] ) ? \absint( $_POST['alert_error_threshold'] ) : 10,
			'alert_rate_threshold'    => isset( $_POST['alert_rate_threshold'] ) ? \absint( $_POST['alert_rate_threshold'] ) : 20,
			'alert_check_interval'    => isset( $_POST['alert_check_interval'] ) ? \sanitize_text_field( \wp_unslash( $_POST['alert_check_interval'] ) ) : 'hourly',
			'alert_cooldown_hours'    => isset( $_POST['alert_cooldown_hours'] ) ? \absint( $_POST['alert_cooldown_hours'] ) : 4,
		);

		// Preserve alert_last_sent timestamp (don't reset it).
		$new_settings['alert_last_sent'] = $settings->get_alert_last_sent();

		// Update settings.
		$success = $settings->update( $new_settings );

		// Schedule or unschedule log cleanup based on retention setting.
		$this->update_cleanup_schedule( $new_settings['log_retention_days'] );

		// Schedule or unschedule email alerts based on alerts setting.
		$this->update_alert_schedule( $new_settings['alerts_enabled'], $new_settings['alert_check_interval'] );

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
	 * Sanitize comma-separated email recipients
	 *
	 * @since 1.2.0
	 * @param string $input Raw input string with comma-separated emails.
	 * @return string Sanitized comma-separated valid emails.
	 */
	private function sanitize_email_recipients( string $input ): string {
		// Split by commas.
		$emails = \array_map( 'trim', \explode( ',', $input ) );

		// Validate and sanitize each email.
		$valid_emails = array();
		foreach ( $emails as $email ) {
			$sanitized = \sanitize_email( $email );
			if ( ! empty( $sanitized ) && \is_email( $sanitized ) ) {
				$valid_emails[] = $sanitized;
			}
		}

		// Return comma-separated string, or admin email if all were invalid.
		if ( empty( $valid_emails ) ) {
			$admin_email = \get_option( 'admin_email' );
			return \is_string( $admin_email ) ? $admin_email : '';
		}

		return \implode( ', ', $valid_emails );
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
	 * Update email alert schedule
	 *
	 * @since 1.2.0
	 * @param bool   $enabled  Whether alerts are enabled.
	 * @param string $interval Check interval (hourly, twicehourly).
	 * @return void
	 */
	private function update_alert_schedule( bool $enabled, string $interval ): void {
		$hook = 'cf7_api_check_alerts';

		// Clear existing schedule.
		$timestamp = \wp_next_scheduled( $hook );
		if ( $timestamp ) {
			\wp_unschedule_event( $timestamp, $hook );
		}

		// If alerts are not enabled, do not schedule a new event.
		if ( ! $enabled ) {
			return;
		}

		// Validate interval against registered schedules before scheduling.
		$schedules = \wp_get_schedules();
		if ( ! isset( $schedules[ $interval ] ) ) {
			return;
		}

		\wp_schedule_event( \time(), $interval, $hook );
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
	 * Handle test email AJAX request
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function handle_test_email(): void {
		// Verify user capabilities.
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Permission denied', 'contact-form-to-api' ) ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ), 'cf7_api_test_email' ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Security check failed', 'contact-form-to-api' ) ) );
		}

		// Get recipient email.
		$recipient = isset( $_POST['recipient'] ) ? \sanitize_email( \wp_unslash( $_POST['recipient'] ) ) : '';
		if ( empty( $recipient ) || ! \is_email( $recipient ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Invalid email address', 'contact-form-to-api' ) ) );
		}

		// Send test email.
		if ( ! \class_exists( EmailAlertService::class ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Email service not available', 'contact-form-to-api' ) ) );
		}

		$alert_service = EmailAlertService::instance();
		$sent          = $alert_service->send_test_email( $recipient );

		if ( $sent ) {
			\wp_send_json_success( array( 'message' => \__( 'Test email sent successfully', 'contact-form-to-api' ) ) );
		} else {
			\wp_send_json_error( array( 'message' => \__( 'Failed to send test email', 'contact-form-to-api' ) ) );
		}
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
