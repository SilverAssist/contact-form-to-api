<?php
/**
 * API Request Logger
 *
 * Handles comprehensive logging of all API requests and responses,
 * inspired by Flamingo plugin's approach to data tracking.
 * Provides detailed insights into API interactions for debugging and auditing.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 1.1.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

use SilverAssist\ContactFormToAPI\Core\SensitiveDataPatterns;
use SilverAssist\ContactFormToAPI\Core\Settings;
use WP_Error;

\defined( 'ABSPATH' ) || exit;

/**
 * Class RequestLogger
 *
 * Advanced logging system for API requests and responses.
 * Tracks all data sent to APIs and received responses for complete traceability.
 *
 * @since 1.1.0
 */
class RequestLogger {

	/**
	 * Maximum number of manual retries allowed per log entry
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public const MAX_MANUAL_RETRIES = 3;

	/**
	 * Maximum number of retries allowed per hour (global rate limit)
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public const MAX_RETRIES_PER_HOUR = 10;

	/**
	 * Table name for logs
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Log entry ID
	 *
	 * @var int|null
	 */
	private ?int $log_id = null;

	/**
	 * Start time for execution tracking
	 *
	 * @var float|null
	 */
	private ?float $start_time = null;

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = "{$wpdb->prefix}cf7_api_logs";
	}

	/**
	 * Start logging an API request
	 *
	 * Creates initial log entry before sending request.
	 *
	 * @since 1.1.0
	 * @param int                    $form_id         Contact Form 7 form ID
	 * @param string                 $endpoint        API endpoint URL
	 * @param string                 $method          HTTP method (GET, POST, etc.)
	 * @param mixed                  $request_data    Request body data
	 * @param array<string, string>  $request_headers Request headers
	 * @param int|null               $retry_of        Original log ID if this is a retry
	 * @return int|false Log entry ID or false on failure
	 */
	public function start_request( int $form_id, string $endpoint, string $method, $request_data, array $request_headers = array(), ?int $retry_of = null ) {
		// Check if logging is enabled via settings.
		if ( ! $this->is_logging_enabled() ) {
			return false;
		}

		global $wpdb;

		$this->start_time = \microtime( true );

		// Anonymize sensitive data
		$anonymized_data    = $this->anonymize_data( $request_data );
		$anonymized_headers = $this->anonymize_headers( $request_headers );

		// Prepare data for storage
		$prepared_data    = \is_string( $anonymized_data ) ? $anonymized_data : \wp_json_encode( $anonymized_data );
		$prepared_headers = \wp_json_encode( $anonymized_headers );

		$insert_data = array(
			'form_id'         => $form_id,
			'endpoint'        => $endpoint,
			'method'          => $method,
			'status'          => 'pending',
			'request_data'    => $prepared_data,
			'request_headers' => $prepared_headers,
			'retry_count'     => 0,
			'created_at'      => \current_time( 'mysql' ),
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' );

		// Add retry_of if this is a retry attempt
		if ( null !== $retry_of ) {
			$insert_data['retry_of'] = $retry_of;
			$format[]                = '%d';
		}

		$result = $wpdb->insert(
			$this->table_name,
			$insert_data,
			$format
		);

		if ( $result ) {
			$this->log_id = $wpdb->insert_id;
			return $this->log_id ?: false;
		}

		return false;
	}

	/**
	 * Complete logging with response data
	 *
	 * Updates log entry with response information and execution time.
	 *
	 * @since 1.1.0
	 * @param array<string, mixed>|WP_Error $response      API response or error
	 * @param int|null                       $retry_count   Number of retry attempts
	 * @return bool True on success, false on failure
	 */
	public function complete_request( $response, ?int $retry_count = 0 ): bool {
		if ( ! $this->log_id ) {
			return false;
		}

		global $wpdb;

		$execution_time = $this->start_time ? \microtime( true ) - $this->start_time : 0;

		// Handle WP_Error responses
		if ( \is_wp_error( $response ) ) {
			$update_data = array(
				'status'         => 'error',
				'error_message'  => $response->get_error_message(),
				'execution_time' => $execution_time,
				'retry_count'    => $retry_count,
			);
			$format      = array( '%s', '%s', '%f', '%d' );
		} else {
			// Handle successful responses
			$response_code    = \wp_remote_retrieve_response_code( $response );
			$response_body    = \wp_remote_retrieve_body( $response );
			$response_headers = \wp_remote_retrieve_headers( $response );

			// Anonymize response data
			$anonymized_body    = $this->anonymize_data( $response_body );
			$anonymized_headers = $this->anonymize_headers( $response_headers );

			// Determine status based on response code
			$status = $this->determine_status( $response_code );

			// Encode response data if it's an array
			$encoded_body = \is_array( $anonymized_body ) ? \wp_json_encode( $anonymized_body ) : $anonymized_body;

			$update_data = array(
				'status'           => $status,
				'response_code'    => $response_code,
				'response_data'    => $encoded_body,
				'response_headers' => \wp_json_encode( $anonymized_headers ),
				'execution_time'   => $execution_time,
				'retry_count'      => $retry_count,
			);
			$format      = array( '%s', '%d', '%s', '%s', '%f', '%d' );
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $this->log_id ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Log retry attempt
	 *
	 * Updates retry count for current log entry.
	 *
	 * @since 1.1.0
	 * @param int $retry_count Current retry attempt number
	 * @return bool True on success, false on failure
	 */
	public function log_retry( int $retry_count ): bool {
		if ( ! $this->log_id ) {
			return false;
		}

		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'retry_count' => $retry_count ),
			array( 'id' => $this->log_id ),
			array( '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Anonymize sensitive data
	 *
	 * Removes or masks sensitive information from logged data.
	 *
	 * @since 1.1.0
	 * @param mixed $data Data to anonymize
	 * @return mixed Anonymized data
	 */
	private function anonymize_data( $data ) {
		// If string, try to decode as JSON first
		if ( \is_string( $data ) ) {
			$decoded = \json_decode( $data, true );
			if ( \json_last_error() === JSON_ERROR_NONE && \is_array( $decoded ) ) {
				$data = $decoded;
			} else {
				// Not JSON, return as is
				return $data;
			}
		}

		// If array, anonymize recursively
		if ( \is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( SensitiveDataPatterns::is_sensitive( $key ) ) {
					$data[ $key ] = '***REDACTED***';
				} elseif ( \is_array( $value ) ) {
					$data[ $key ] = $this->anonymize_data( $value );
				}
			}
		}

		return $data;
	}

	/**
	 * Anonymize request/response headers
	 *
	 * Removes or masks sensitive headers.
	 *
	 * @since 1.1.0
	 * @param array<string, string>|\WpOrg\Requests\Utility\CaseInsensitiveDictionary $headers Headers to anonymize
	 * @return array<string, string> Anonymized headers
	 */
	private function anonymize_headers( $headers ): array {
		if ( ! \is_array( $headers ) && ! $headers instanceof \ArrayAccess ) {
			return array();
		}

		$anonymized = array();
		foreach ( $headers as $key => $value ) {
			$anonymized[ $key ] = SensitiveDataPatterns::is_sensitive( $key ) ? '***REDACTED***' : $value;
		}

		return $anonymized;
	}

	/**
	 * Determine status from response code
	 *
	 * @since 1.1.0
	 * @param int $code HTTP response code
	 * @return string Status string
	 */
	private function determine_status( int $code ): string {
		if ( $code >= 200 && $code < 300 ) {
			return 'success';
		} elseif ( $code >= 400 && $code < 500 ) {
			return 'client_error';
		} elseif ( $code >= 500 ) {
			return 'server_error';
		}

		return 'unknown';
	}

	/**
	 * Get recent logs for a form
	 *
	 * Retrieves recent API call logs for debugging.
	 *
	 * @since 1.1.0
	 * @param int|null $form_id Form ID to get logs for (null for new forms)
	 * @param int      $limit   Maximum number of logs to retrieve
	 * @return array<int, array<string, mixed>> Array of log entries
	 */
	public function get_recent_logs( ?int $form_id, int $limit = 10 ): array {
		if ( null === $form_id || $form_id <= 0 ) {
			return array();
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE form_id = %d 
				ORDER BY created_at DESC 
				LIMIT %d",
				$form_id,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $results ?: array();
	}

	/**
	 * Clean old logs
	 *
	 * Removes logs older than specified days.
	 *
	 * @since 1.1.0
	 * @param int $days Number of days to keep logs
	 * @return int Number of deleted rows
	 */
	public function clean_old_logs( int $days = 30 ): int {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} 
				WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result ?: 0;
	}

	/**
	 * Get log statistics for a form
	 *
	 * Returns aggregated statistics about API calls.
	 *
	 * @since 1.1.0
	 * @param int|null $form_id Form ID (0 or null for all forms)
	 * @return array<string, int|float> Statistics array
	 */
	public function get_statistics( ?int $form_id ): array {
		global $wpdb;

		if ( null !== $form_id && $form_id > 0 ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
			$stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT 
						COUNT(*) as total_requests,
						SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_requests,
						SUM(CASE WHEN status IN ('error', 'client_error', 'server_error') THEN 1 ELSE 0 END) as failed_requests,
						AVG(execution_time) as avg_execution_time,
						MAX(retry_count) as max_retries
					FROM {$this->table_name}
					WHERE form_id = %d",
					$form_id
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			// Get statistics for all forms
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
			$stats = $wpdb->get_row(
				"SELECT 
					COUNT(*) as total_requests,
					SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_requests,
					SUM(CASE WHEN status IN ('error', 'client_error', 'server_error') THEN 1 ELSE 0 END) as failed_requests,
					AVG(execution_time) as avg_execution_time,
					MAX(retry_count) as max_retries
				FROM {$this->table_name}",
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $stats ?: array(
			'total_requests'      => 0,
			'successful_requests' => 0,
			'failed_requests'     => 0,
			'avg_execution_time'  => 0,
			'max_retries'         => 0,
		);
	}

	/**
	 * Get count of logs in the last N hours
	 *
	 * Retrieves the count of API requests in the specified time window.
	 * Optionally filter by status.
	 *
	 * @since 1.2.0
	 * @param int         $hours  Number of hours to look back
	 * @param string|null $status Optional status filter ('success', 'error', etc.)
	 * @return int Number of requests
	 */
	public function get_count_last_hours( int $hours, ?string $status = null ): int {
		global $wpdb;

		$status_condition = '';

		if ( $status ) {
			// Handle different status types.
			if ( 'error' === $status ) {
				// Count all error types.
				$status_condition = " AND status IN ('error', 'client_error', 'server_error')";
			} else {
				$status_condition = $wpdb->prepare( ' AND status = %s', $status );
			}
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table_name is a safe class property, status_condition is prepared above.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)" . $status_condition,
				$hours
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return (int) ( $count ?: 0 );
	}

	/**
	 * Get success rate for the last N hours
	 *
	 * Calculates the percentage of successful requests in the specified time window.
	 *
	 * @since 1.2.0
	 * @param int $hours Number of hours to look back
	 * @return float Success rate as a percentage (0-100)
	 */
	public function get_success_rate_last_hours( int $hours ): float {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as total,
					SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful
				FROM {$this->table_name}
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
				$hours
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $stats || 0 === (int) $stats['total'] ) {
			return 0.0;
		}

		return round( ( (int) $stats['successful'] / (int) $stats['total'] ) * 100, 2 );
	}

	/**
	 * Get average response time for the last N hours
	 *
	 * Calculates the average execution time in milliseconds for requests
	 * in the specified time window.
	 *
	 * @since 1.2.0
	 * @param int $hours Number of hours to look back
	 * @return float Average response time in milliseconds
	 */
	public function get_avg_response_time_last_hours( int $hours ): float {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
		$avg = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(execution_time) 
				FROM {$this->table_name}
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
				AND execution_time IS NOT NULL",
				$hours
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $avg ) {
			return 0.0;
		}

		// Convert seconds to milliseconds and round to 0 decimal places
		return round( (float) $avg * 1000, 0 );
	}

	/**
	 * Get recent error logs
	 *
	 * Retrieves the most recent failed API requests for quick diagnostics.
	 *
	 * @since 1.2.0
	 * @param int $limit Maximum number of errors to retrieve
	 * @return array<int, array<string, mixed>> Array of error log entries
	 */
	public function get_recent_errors( int $limit = 5 ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE status IN ('error', 'client_error', 'server_error')
				ORDER BY created_at DESC 
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $results ?: array();
	}

	/**
	 * Get log entry by ID
	 *
	 * Retrieves a single log entry for detailed inspection.
	 *
	 * @since 1.2.0
	 * @param int $log_id Log entry ID
	 * @return array<string, mixed>|null Log entry data or null if not found
	 */
	public function get_log( int $log_id ): ?array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
		$log = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$log_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $log ?: null;
	}

	/**
	 * Get request data for retry
	 *
	 * Retrieves complete request data needed to replay a failed API request.
	 * Returns null if log entry doesn't exist or is not retryable.
	 *
	 * @since 1.2.0
	 * @param int $log_id Log entry ID to retry
	 * @return array<string, mixed>|null Request data or null if not retryable
	 */
	public function get_request_for_retry( int $log_id ): ?array {
		$log = $this->get_log( $log_id );

		if ( ! $log ) {
			return null;
		}

		// Only retry failed requests (pending excluded as they haven't completed yet)
		$retryable_statuses = array( 'error', 'client_error', 'server_error' );
		if ( ! \in_array( $log['status'], $retryable_statuses, true ) ) {
			return null;
		}

		// Decode JSON data
		$request_headers = \json_decode( $log['request_headers'], true );
		$request_data    = \json_decode( $log['request_data'], true );

		// If request_data is not valid JSON, use the raw string
		if ( null === $request_data && 'null' !== \strtolower( (string) $log['request_data'] ) ) {
			$request_data = $log['request_data'];
		}

		return array(
			'url'             => $log['endpoint'],
			'method'          => $log['method'],
			'headers'         => \is_array( $request_headers ) ? $request_headers : array(),
			'body'            => $request_data,
			'form_id'         => (int) $log['form_id'],
			'original_log_id' => $log_id,
		);
	}

	/**
	 * Count retries for a specific log entry
	 *
	 * Counts how many times a specific log entry has been retried.
	 *
	 * @since 1.2.0
	 * @param int $log_id Original log entry ID
	 * @return int Number of retry attempts
	 */
	public function count_retries( int $log_id ): int {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is a safe class property
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE retry_of = %d",
				$log_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) ( $count ?: 0 );
	}

	/**
	 * Check if logging is enabled via settings
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function is_logging_enabled(): bool {
		// Try to get settings instance.
		if ( \class_exists( Settings::class ) ) {
			try {
				$settings = Settings::instance();
				return $settings->is_logging_enabled();
			} catch ( \Exception $e ) {
				// Settings not available, default to enabled.
				unset( $e );
			}
		}

		// Default to enabled if settings not available.
		return true;
	}

	/**
	 * Get maximum manual retries from settings
	 *
	 * @since 1.2.0
	 * @return int
	 */
	public static function get_max_manual_retries(): int {
		// Try to get from settings first.
		if ( \class_exists( Settings::class ) ) {
			try {
				$settings = Settings::instance();
				return $settings->get_max_manual_retries();
			} catch ( \Exception $e ) {
				// Settings not available, use constant.
				unset( $e );
			}
		}

		// Fallback to constant.
		return self::MAX_MANUAL_RETRIES;
	}

	/**
	 * Get maximum retries per hour from settings
	 *
	 * @since 1.2.0
	 * @return int
	 */
	public static function get_max_retries_per_hour(): int {
		// Try to get from settings first.
		if ( \class_exists( Settings::class ) ) {
			try {
				$settings = Settings::instance();
				return $settings->get_max_retries_per_hour();
			} catch ( \Exception $e ) {
				// Settings not available, use constant.
				unset( $e );
			}
		}

		// Fallback to constant.
		return self::MAX_RETRIES_PER_HOUR;
	}
}
