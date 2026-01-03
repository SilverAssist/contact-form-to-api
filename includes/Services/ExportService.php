<?php
/**
 * Export Service
 *
 * Handles exporting API request logs to CSV and JSON formats.
 * Provides data sanitization and streaming support for large exports.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Services
 * @since 1.2.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Services;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;

\defined( "ABSPATH" ) || exit;

/**
 * Class ExportService
 *
 * Service for exporting log data in various formats.
 * Implements streaming for large datasets and data sanitization.
 *
 * @since 1.2.0
 */
class ExportService implements LoadableInterface {

	/**
	 * Singleton instance
	 *
	 * @var ExportService|null
	 */
	private static ?ExportService $instance = null;

	/**
	 * Sensitive field patterns to sanitize in exports
	 *
	 * @var array<string>
	 */
	private array $sensitive_patterns = array(
		"password",
		"passwd",
		"secret",
		"api_key",
		"api-key",
		"apikey",
		"token",
		"auth",
		"authorization",
		"bearer",
		"ssn",
		"social_security",
		"credit_card",
		"card_number",
	);

	/**
	 * Get singleton instance
	 *
	 * @return ExportService
	 */
	public static function instance(): ExportService {
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
		// No hooks needed for export service.
		// Methods are called directly by controller.
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
		return \is_admin();
	}

	/**
	 * Export logs as CSV
	 *
	 * Generates CSV content with proper escaping and UTF-8 BOM for Excel compatibility.
	 *
	 * @param array<int, array<string, mixed>> $logs Array of log entries.
	 * @return string CSV content.
	 */
	public function export_csv( array $logs ): string {
		// Create memory stream for CSV generation.
		$output = \fopen( "php://temp", "r+" );

		if ( false === $output ) {
			return "";
		}

		// Add UTF-8 BOM for Excel compatibility.
		\fwrite( $output, "\xEF\xBB\xBF" );

		// Write headers.
		$headers = $this->get_csv_headers();
		\fputcsv( $output, $headers );

		// Write data rows.
		foreach ( $logs as $log ) {
			$sanitized = $this->sanitize_for_export( $log );
			$row       = array(
				$sanitized["id"],
				$sanitized["form_id"],
				$sanitized["endpoint"],
				$sanitized["method"],
				$sanitized["status"],
				$sanitized["response_code"] ?? "",
				$sanitized["execution_time"] ?? "",
				$sanitized["retry_count"] ?? "0",
				$sanitized["error_message"] ?? "",
				$sanitized["created_at"],
			);
			\fputcsv( $output, $row );
		}

		// Get CSV content.
		\rewind( $output );
		$csv = \stream_get_contents( $output );
		\fclose( $output );

		return $csv !== false ? $csv : "";
	}

	/**
	 * Export logs as JSON
	 *
	 * Generates JSON content with pretty printing and sanitized data.
	 *
	 * @param array<int, array<string, mixed>> $logs Array of log entries.
	 * @return string JSON content.
	 */
	public function export_json( array $logs ): string {
		$sanitized_logs = array();

		foreach ( $logs as $log ) {
			$sanitized_logs[] = $this->sanitize_for_export( $log );
		}

		$json = \wp_json_encode( $sanitized_logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		return $json !== false ? $json : "[]";
	}

	/**
	 * Sanitize log data for export
	 *
	 * Removes sensitive information from log entries before export.
	 *
	 * @param array<string, mixed> $log Log entry.
	 * @return array<string, mixed> Sanitized log entry.
	 */
	private function sanitize_for_export( array $log ): array {
		$sanitized = $log;

		// Sanitize request headers.
		if ( isset( $sanitized["request_headers"] ) ) {
			$sanitized["request_headers"] = $this->sanitize_headers_field( $sanitized["request_headers"] );
		}

		// Sanitize request data.
		if ( isset( $sanitized["request_data"] ) ) {
			$sanitized["request_data"] = $this->sanitize_data_field( $sanitized["request_data"] );
		}

		// Sanitize response headers.
		if ( isset( $sanitized["response_headers"] ) ) {
			$sanitized["response_headers"] = $this->sanitize_headers_field( $sanitized["response_headers"] );
		}

		// Sanitize response data.
		if ( isset( $sanitized["response_data"] ) ) {
			$sanitized["response_data"] = $this->sanitize_data_field( $sanitized["response_data"] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize headers field
	 *
	 * @param string $headers_json JSON string of headers.
	 * @return string Sanitized JSON string.
	 */
	private function sanitize_headers_field( string $headers_json ): string {
		$headers = \json_decode( $headers_json, true );

		if ( ! \is_array( $headers ) ) {
			return $headers_json;
		}

		foreach ( $headers as $key => $value ) {
			$key_lower = \strtolower( $key );

			foreach ( $this->sensitive_patterns as $pattern ) {
				if ( \strpos( $key_lower, $pattern ) !== false ) {
					$headers[ $key ] = "***REDACTED***";
					break;
				}
			}
		}

		$sanitized = \wp_json_encode( $headers );
		return $sanitized !== false ? $sanitized : $headers_json;
	}

	/**
	 * Sanitize data field
	 *
	 * @param string $data_json JSON string of data.
	 * @return string Sanitized JSON string.
	 */
	private function sanitize_data_field( string $data_json ): string {
		$data = \json_decode( $data_json, true );

		if ( ! \is_array( $data ) ) {
			return $data_json;
		}

		$data = $this->sanitize_array_recursive( $data );

		$sanitized = \wp_json_encode( $data );
		return $sanitized !== false ? $sanitized : $data_json;
	}

	/**
	 * Recursively sanitize array data
	 *
	 * @param array<string, mixed> $data Data array.
	 * @return array<string, mixed> Sanitized array.
	 */
	private function sanitize_array_recursive( array $data ): array {
		foreach ( $data as $key => $value ) {
			$key_lower = \strtolower( (string) $key );

			$is_sensitive = false;
			foreach ( $this->sensitive_patterns as $pattern ) {
				if ( \strpos( $key_lower, $pattern ) !== false ) {
					$is_sensitive = true;
					break;
				}
			}

			if ( $is_sensitive ) {
				$data[ $key ] = "***REDACTED***";
			} elseif ( \is_array( $value ) ) {
				$data[ $key ] = $this->sanitize_array_recursive( $value );
			}
		}

		return $data;
	}

	/**
	 * Get CSV column headers
	 *
	 * @return array<int, string> Array of column headers.
	 */
	private function get_csv_headers(): array {
		return array(
			\__( "ID", CF7_API_TEXT_DOMAIN ),
			\__( "Form ID", CF7_API_TEXT_DOMAIN ),
			\__( "Endpoint", CF7_API_TEXT_DOMAIN ),
			\__( "Method", CF7_API_TEXT_DOMAIN ),
			\__( "Status", CF7_API_TEXT_DOMAIN ),
			\__( "Response Code", CF7_API_TEXT_DOMAIN ),
			\__( "Execution Time (s)", CF7_API_TEXT_DOMAIN ),
			\__( "Retry Count", CF7_API_TEXT_DOMAIN ),
			\__( "Error Message", CF7_API_TEXT_DOMAIN ),
			\__( "Created At", CF7_API_TEXT_DOMAIN ),
		);
	}

	/**
	 * Get export filename
	 *
	 * Generates filename with timestamp.
	 *
	 * @param string $format Export format (csv or json).
	 * @return string Filename.
	 */
	public function get_export_filename( string $format ): string {
		$timestamp = \gmdate( "Y-m-d_H-i-s" );
		return "cf7-api-logs_{$timestamp}.{$format}";
	}
}
