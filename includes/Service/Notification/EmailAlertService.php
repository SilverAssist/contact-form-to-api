<?php
/**
 * Email Alert Service
 *
 * Handles email notifications for high API error rates.
 * Monitors API request statistics and sends alerts when thresholds are exceeded.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Service\Notification
 * @since 1.2.0
 * @version 2.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Service\Notification;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Config\Settings;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

\defined( 'ABSPATH' ) || exit;

/**
 * Class EmailAlertService
 *
 * Monitors API error rates and sends email notifications when thresholds are exceeded.
 *
 * @since 1.2.0
 */
class EmailAlertService implements LoadableInterface {

	/**
	 * Singleton instance
	 *
	 * @var EmailAlertService|null
	 */
	private static ?EmailAlertService $instance = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Log reader instance
	 *
	 * @var LogReader|null
	 */
	private ?LogReader $log_reader = null;

	/**
	 * Get singleton instance
	 *
	 * @return EmailAlertService
	 */
	public static function instance(): EmailAlertService {
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
	 * Initialize the service
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Service is initialized but cron hook is registered in Plugin class.
		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 20; // Services priority.
	}

	/**
	 * Determine if should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true; // Always load for cron jobs.
	}

	/**
	 * Check error rates and send alert if thresholds exceeded
	 *
	 * Main method called by cron job to check if alerts should be sent.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function check_and_alert(): void {
		// Get settings.
		$settings = Settings::instance();

		// Check if alerts are enabled.
		if ( ! $settings->is_alerts_enabled() ) {
			return;
		}

		// Check if threshold alerts are enabled.
		if ( ! $settings->is_threshold_alerts_enabled() ) {
			return;
		}

		// Check cooldown period.
		if ( $this->is_in_cooldown( $settings ) ) {
			return;
		}

		// Get hourly statistics.
		$stats = $this->get_hourly_stats();

		// Check if alert should be sent.
		if ( $this->should_alert( $stats, $settings ) ) {
			$this->send_alert( $stats, $settings );
		}
	}

	/**
	 * Check if in cooldown period
	 *
	 * @since 1.2.0
	 * @param Settings $settings Settings instance.
	 * @return bool True if in cooldown, false otherwise.
	 */
	private function is_in_cooldown( Settings $settings ): bool {
		$last_sent      = $settings->get_alert_last_sent();
		$cooldown_hours = $settings->get_alert_cooldown_hours();

		// If never sent, not in cooldown.
		if ( 0 === $last_sent ) {
			return false;
		}

		// Calculate cooldown end time (HOUR_IN_SECONDS = 3600).
		$cooldown_end = $last_sent + ( $cooldown_hours * 3600 );

		// Check if still in cooldown.
		return \time() < $cooldown_end;
	}

	/**
	 * Get hourly statistics
	 *
	 * Retrieves error statistics for the last hour.
	 *
	 * @since 1.2.0
	 * @return array<string, mixed> Statistics array with errors, total, error_rate.
	 */
	private function get_hourly_stats(): array {
		$stats = new LogStatistics();

		$total_requests = $stats->get_count_last_hours( 1 );
		$error_count    = $stats->get_count_last_hours( 1, 'error' );

		// Calculate error rate.
		$error_rate = 0.0;
		if ( $total_requests > 0 ) {
			$error_rate = ( $error_count / $total_requests ) * 100;
		}

		return array(
			'total_requests' => $total_requests,
			'errors'         => $error_count,
			'error_rate'     => \round( $error_rate, 2 ),
		);
	}

	/**
	 * Determine if alert should be sent
	 *
	 * @since 1.2.0
	 * @param array<string, mixed> $stats    Statistics array.
	 * @param Settings             $settings Settings instance.
	 * @return bool True if alert should be sent, false otherwise.
	 */
	private function should_alert( array $stats, Settings $settings ): bool {
		$error_count     = (int) $stats['errors'];
		$error_rate      = (float) $stats['error_rate'];
		$error_threshold = $settings->get_alert_error_threshold();
		$rate_threshold  = $settings->get_alert_rate_threshold();

		// Alert if error count exceeds threshold OR error rate exceeds threshold.
		return $error_count >= $error_threshold || $error_rate >= $rate_threshold;
	}

	/**
	 * Send alert email
	 *
	 * @since 1.2.0
	 * @param array<string, mixed> $stats    Statistics array.
	 * @param Settings             $settings Settings instance.
	 * @return void
	 */
	private function send_alert( array $stats, Settings $settings ): void {
		// Get recipients.
		$recipients_string = $settings->get_alert_recipients();
		$recipients        = \array_map( 'trim', \explode( ',', $recipients_string ) );

		// Build email subject.
		$subject = \sprintf(
			/* translators: %s: site name */
			\__( '[%s] CF7 API Alert: High Error Rate Detected', 'contact-form-to-api' ),
			\get_bloginfo( 'name' )
		);

		// Build email body.
		$message = $this->build_email_body( $stats );

		// Set content type to HTML.
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Send email to each recipient.
		foreach ( $recipients as $email ) {
			if ( \is_email( $email ) ) {
				\wp_mail( $email, $subject, $message, $headers );
			}
		}

		// Update last sent timestamp.
		$settings->update_alert_last_sent( \time() );

		// Log alert sent.
		try {
			DebugLogger::instance()->info(
				'Email alert sent for high error rate',
				array(
					'error_count' => $stats['errors'],
					'error_rate'  => $stats['error_rate'],
					'recipients'  => $recipients_string,
				)
			);
		} catch ( \Exception $e ) {
			// Silently fail if logger not available.
			unset( $e );
		}
	}

	/**
	 * Build email body HTML
	 *
	 * @since 1.2.0
	 * @param array<string, mixed> $stats Statistics array.
	 * @return string HTML email body.
	 */
	private function build_email_body( array $stats ): string {
		$log_stats     = new LogStatistics();
		$recent_errors = $log_stats->get_recent_errors( 5 );
		$logs_url      = \admin_url( 'admin.php?page=cf7-api-logs' );
		$site_name     = \get_bloginfo( 'name' );
		$timestamp     = \current_time( 'mysql' );

		// Start building HTML.
		$html  = '<html><head><style>';
		$html .= 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }';
		$html .= 'h2 { color: #d63638; border-bottom: 2px solid #d63638; padding-bottom: 10px; }';
		$html .= 'h3 { color: #135e96; margin-top: 20px; }';
		$html .= 'table { border-collapse: collapse; width: 100%; margin: 20px 0; }';
		$html .= 'td { padding: 8px; border: 1px solid #ddd; }';
		$html .= 'td:first-child { font-weight: bold; background-color: #f5f5f5; width: 30%; }';
		$html .= 'ul { list-style-type: none; padding: 0; }';
		$html .= 'li { background: #f9f9f9; padding: 10px; margin: 5px 0; border-left: 3px solid #d63638; }';
		$html .= 'a { color: #135e96; text-decoration: none; }';
		$html .= 'a:hover { text-decoration: underline; }';
		$html .= '</style></head><body>';

		$html .= '<h2>' . \esc_html__( 'CF7 API Alert', 'contact-form-to-api' ) . '</h2>';
		$html .= '<p>' . \esc_html__( 'High error rate detected on your WordPress site.', 'contact-form-to-api' ) . '</p>';

		$html .= '<table>';
		$html .= '<tr><td>' . \esc_html__( 'Site', 'contact-form-to-api' ) . '</td><td>' . \esc_html( $site_name ) . '</td></tr>';
		$html .= '<tr><td>' . \esc_html__( 'Time', 'contact-form-to-api' ) . '</td><td>' . \esc_html( $timestamp ) . '</td></tr>';
		$html .= '<tr><td>' . \esc_html__( 'Errors (last hour)', 'contact-form-to-api' ) . '</td><td>' . \esc_html( (string) $stats['errors'] ) . '</td></tr>';

		$error_rate = \number_format( (float) $stats['error_rate'], 2 );
		$html      .= '<tr><td>' . \esc_html__( 'Error Rate', 'contact-form-to-api' ) . '</td><td>' . \esc_html( $error_rate . '%' ) . '</td></tr>';
		$html      .= '<tr><td>' . \esc_html__( 'Total Requests', 'contact-form-to-api' ) . '</td><td>' . \esc_html( (string) $stats['total_requests'] ) . '</td></tr>';
		$html      .= '</table>';

		if ( ! empty( $recent_errors ) ) {
			$html .= '<h3>' . \esc_html__( 'Recent Errors', 'contact-form-to-api' ) . '</h3>';
			$html .= '<ul>';

			foreach ( $recent_errors as $error ) {
				$form_id   = (int) $error['form_id'];
				$form_name = \get_the_title( $form_id );
				if ( empty( $form_name ) ) {
					$form_name = \sprintf(
						/* translators: %d: form ID */
						\__( 'Form #%d', 'contact-form-to-api' ),
						$form_id
					);
				}

				$error_message = ! empty( $error['error_message'] ) ? $error['error_message'] : \__( 'Unknown error', 'contact-form-to-api' );
				$created_at    = ! empty( $error['created_at'] ) ? $error['created_at'] : '';

				$html .= '<li>';
				$html .= \esc_html( $form_name ) . ' - ';
				$html .= \esc_html( $error_message );
				if ( ! empty( $created_at ) ) {
					$html .= ' (' . \esc_html( $created_at ) . ')';
				}
				$html .= '</li>';
			}

			$html .= '</ul>';
		}

		$html .= '<p><a href="' . \esc_url( $logs_url ) . '">' . \esc_html__( 'View All Logs', 'contact-form-to-api' ) . '</a></p>';
		$html .= '</body></html>';

		return $html;
	}

	/**
	 * Send test email
	 *
	 * Public method for testing email configuration from admin interface.
	 *
	 * @since 1.2.0
	 * @param string $recipient Email address to send test to.
	 * @return bool True if email sent successfully, false otherwise.
	 */
	public function send_test_email( string $recipient ): bool {
		if ( ! \is_email( $recipient ) ) {
			return false;
		}

		// Build test subject.
		$subject = \sprintf(
			/* translators: %s: site name */
			\__( '[%s] CF7 API Alert Test Email', 'contact-form-to-api' ),
			\get_bloginfo( 'name' )
		);

		// Build test message.
		$message  = '<html><head><style>';
		$message .= 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }';
		$message .= 'h2 { color: #135e96; }';
		$message .= '</style></head><body>';
		$message .= '<h2>' . \esc_html__( 'Test Email', 'contact-form-to-api' ) . '</h2>';
		$message .= '<p>' . \esc_html__( 'This is a test email from Contact Form 7 to API plugin.', 'contact-form-to-api' ) . '</p>';
		$message .= '<p>' . \esc_html__( 'If you received this email, your alert configuration is working correctly.', 'contact-form-to-api' ) . '</p>';
		$message .= '</body></html>';

		// Set content type to HTML.
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Send email.
		return \wp_mail( $recipient, $subject, $message, $headers );
	}

	/**
	 * Maybe send individual failure alert
	 *
	 * Called when a submission permanently fails after exhausting all retries.
	 * Checks settings and sends alert if individual alerts are enabled.
	 *
	 * @since 2.0.0
	 * @param int $log_id  Log entry ID.
	 * @param int $form_id Contact Form 7 form ID.
	 * @return void
	 */
	public function maybe_send_individual_alert( int $log_id, int $form_id ): void {
		$settings = Settings::instance();

		// Check if alerts are enabled globally.
		if ( ! $settings->is_alerts_enabled() ) {
			return;
		}

		// Check if individual alerts are enabled.
		if ( ! $settings->is_individual_alerts_enabled() ) {
			return;
		}

		// Check if alert was already sent for this log entry (prevent spam).
		$alert_sent_key = 'cf7api_individual_alert_sent_' . $log_id;
		if ( \get_transient( $alert_sent_key ) ) {
			return;
		}

		// Send the individual failure alert (no cooldown for event-driven alerts).
		$this->send_individual_failure_alert( $log_id, $form_id );

		// Mark alert as sent (expires after 30 days).
		\set_transient( $alert_sent_key, true, 30 * DAY_IN_SECONDS );
	}

	/**
	 * Send individual failure alert email
	 *
	 * Sends an email notification for a single submission that failed permanently.
	 *
	 * @since 2.0.0
	 * @param int $log_id  Log entry ID.
	 * @param int $form_id Contact Form 7 form ID.
	 * @return void
	 */
	private function send_individual_failure_alert( int $log_id, int $form_id ): void {
		// Initialize log reader if needed.
		if ( null === $this->log_reader ) {
			$this->log_reader = new LogReader();
		}

		// Get log entry.
		$log = $this->log_reader->get_log( $log_id );

		if ( null === $log ) {
			return;
		}

		// Get form title.
		$form_title = \get_the_title( $form_id );
		if ( empty( $form_title ) ) {
			$form_title = \sprintf(
				/* translators: %d: form ID */
				\__( 'Form #%d', 'contact-form-to-api' ),
				$form_id
			);
		}

		// Build email subject.
		$subject = \sprintf(
			/* translators: %1$s: site name, %2$s: form title */
			\__( '[%1$s] API Submission Failed: %2$s', 'contact-form-to-api' ),
			\get_bloginfo( 'name' ),
			$form_title
		);

		// Build email body.
		$message = $this->build_individual_alert_body( $log, $form_title );

		// Set content type to HTML.
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Get recipients.
		$settings          = Settings::instance();
		$recipients_string = $settings->get_alert_recipients();
		$recipients        = \array_map( 'trim', \explode( ',', $recipients_string ) );

		// Send email to each recipient.
		foreach ( $recipients as $email ) {
			if ( \is_email( $email ) ) {
				\wp_mail( $email, $subject, $message, $headers );
			}
		}

		// Log alert sent.
		try {
			DebugLogger::instance()->info(
				'Individual failure alert sent',
				array(
					'log_id'     => $log_id,
					'form_id'    => $form_id,
					'form_title' => $form_title,
					'recipients' => $recipients_string,
				)
			);
		} catch ( \Exception $e ) {
			// Silently fail if logger not available.
			unset( $e );
		}
	}

	/**
	 * Build individual failure alert email body HTML
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $log        Log entry data.
	 * @param string               $form_title Form title.
	 * @return string HTML email body.
	 */
	private function build_individual_alert_body( array $log, string $form_title ): string {
		$logs_url       = \admin_url( 'admin.php?page=cf7-api-logs' );
		$log_detail_url = \add_query_arg(
			array(
				'page'   => 'cf7-api-logs',
				'action' => 'view',
				'log_id' => $log['id'],
			),
			\admin_url( 'admin.php' )
		);
		$site_name         = \get_bloginfo( 'name' );
		$timestamp         = ! empty( $log['created_at'] ) ? $log['created_at'] : \current_time( 'mysql' );
		$endpoint          = ! empty( $log['endpoint'] ) ? $log['endpoint'] : \__( 'N/A', 'contact-form-to-api' );
		$error_message     = ! empty( $log['error_message'] ) ? $log['error_message'] : \__( 'Unknown error', 'contact-form-to-api' );
		$response_code     = ! empty( $log['response_code'] ) ? $log['response_code'] : \__( 'N/A', 'contact-form-to-api' );

		// Start building HTML.
		$html  = '<html><head><style>';
		$html .= 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }';
		$html .= 'h2 { color: #d63638; border-bottom: 2px solid #d63638; padding-bottom: 10px; }';
		$html .= 'h3 { color: #135e96; margin-top: 20px; }';
		$html .= 'table { border-collapse: collapse; width: 100%; margin: 20px 0; }';
		$html .= 'td { padding: 8px; border: 1px solid #ddd; }';
		$html .= 'td:first-child { font-weight: bold; background-color: #f5f5f5; width: 30%; }';
		$html .= '.error-box { background: #fff3cd; border-left: 4px solid #d63638; padding: 15px; margin: 20px 0; }';
		$html .= 'a { color: #135e96; text-decoration: none; }';
		$html .= 'a:hover { text-decoration: underline; }';
		$html .= 'code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }';
		$html .= '</style></head><body>';

		$html .= '<h2>' . \esc_html__( 'API Submission Failed', 'contact-form-to-api' ) . '</h2>';
		$html .= '<p>' . \esc_html__( 'A form submission has permanently failed after exhausting all retry attempts.', 'contact-form-to-api' ) . '</p>';

		$html .= '<table>';
		$html .= '<tr><td>' . \esc_html__( 'Site', 'contact-form-to-api' ) . '</td><td>' . \esc_html( $site_name ) . '</td></tr>';
		$html .= '<tr><td>' . \esc_html__( 'Form', 'contact-form-to-api' ) . '</td><td>' . \esc_html( $form_title ) . '</td></tr>';
		$html .= '<tr><td>' . \esc_html__( 'Endpoint', 'contact-form-to-api' ) . '</td><td><code>' . \esc_html( $endpoint ) . '</code></td></tr>';
		$html .= '<tr><td>' . \esc_html__( 'Time', 'contact-form-to-api' ) . '</td><td>' . \esc_html( $timestamp ) . '</td></tr>';
		$html .= '<tr><td>' . \esc_html__( 'Response Code', 'contact-form-to-api' ) . '</td><td>' . \esc_html( (string) $response_code ) . '</td></tr>';
		$html .= '</table>';

		$html .= '<div class="error-box">';
		$html .= '<strong>' . \esc_html__( 'Error Message:', 'contact-form-to-api' ) . '</strong><br>';
		$html .= \esc_html( $error_message );
		$html .= '</div>';

		$html .= '<p>';
		$html .= '<a href="' . \esc_url( $log_detail_url ) . '">' . \esc_html__( 'View Full Log Details', 'contact-form-to-api' ) . '</a>';
		$html .= ' | ';
		$html .= '<a href="' . \esc_url( $logs_url ) . '">' . \esc_html__( 'View All Logs', 'contact-form-to-api' ) . '</a>';
		$html .= '</p>';

		$html .= '</body></html>';

		return $html;
	}
}
