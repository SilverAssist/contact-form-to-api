<?php
/**
 * Email Alert Service
 *
 * Handles email notifications for high API error rates.
 * Monitors API request statistics and sends alerts when thresholds are exceeded.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Services
 * @since 1.2.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Services;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use SilverAssist\ContactFormToAPI\Core\Settings;
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

		// Calculate cooldown end time.
		$cooldown_end = $last_sent + ( $cooldown_hours * HOUR_IN_SECONDS );

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
		$logger = new RequestLogger();

		$total_requests = $logger->get_count_last_hours( 1 );
		$error_count    = $logger->get_count_last_hours( 1, 'error' );

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
		if ( \class_exists( DebugLogger::class ) ) {
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
	}

	/**
	 * Build email body HTML
	 *
	 * @since 1.2.0
	 * @param array<string, mixed> $stats Statistics array.
	 * @return string HTML email body.
	 */
	private function build_email_body( array $stats ): string {
		$logger        = new RequestLogger();
		$recent_errors = $logger->get_recent_errors( 5 );
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
		$html .= '<tr><td>' . \esc_html__( 'Error Rate', 'contact-form-to-api' ) . '</td><td>' . \esc_html( $stats['error_rate'] ) . '%</td></tr>';
		$html .= '<tr><td>' . \esc_html__( 'Total Requests', 'contact-form-to-api' ) . '</td><td>' . \esc_html( (string) $stats['total_requests'] ) . '</td></tr>';
		$html .= '</table>';

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
}
