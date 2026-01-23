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
 * @version 1.3.14
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

use SilverAssist\ContactFormToAPI\Core\SensitiveDataPatterns;
use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;
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
	 * Encryption service instance
	 *
	 * @var EncryptionService|null
	 */
	private ?EncryptionService $encryption = null;

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = "{$wpdb->prefix}cf7_api_logs";

		// Initialize encryption service if available.
		if ( EncryptionService::is_sodium_available() ) {
			$this->encryption = EncryptionService::instance();
			$this->encryption->init();
		}
	}

	/**
	 * Start logging an API request
	 *
	 * Creates initial log entry before sending request.
	 * Stores original request body data for retry functionality.
	 * Only redacts authorization headers for security.
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

		// Store original request body data (needed for retry functionality)
		// Only redact authorization headers for security
		$anonymized_headers = $this->anonymize_headers( $request_headers );

		// Prepare data for storage
		$prepared_data    = \is_string( $request_data ) ? $request_data : \wp_json_encode( $request_data );
		$prepared_headers = \wp_json_encode( $anonymized_headers );

		// Encrypt sensitive data if encryption is available
		$encryption_version = 0;
		if ( $this->encryption ) {
			try {
				$prepared_data      = $this->encryption->encrypt( $prepared_data );
				$prepared_headers   = $this->encryption->encrypt( $prepared_headers );
				$encryption_version = $this->encryption->get_version();
			} catch ( \Exception $e ) {
				// Log encryption failure and continue with unencrypted data.
				DebugLogger::instance()->error( 'Encryption failed during start_request: ' . $e->getMessage() );
			}
		}

		$insert_data = array(
			'form_id'            => $form_id,
			'endpoint'           => $endpoint,
			'method'             => $method,
			'status'             => 'pending',
			'request_data'       => $prepared_data,
			'request_headers'    => $prepared_headers,
			'retry_count'        => 0,
			'encryption_version' => $encryption_version,
			'created_at'         => \current_time( 'mysql' ),
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' );

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

			// Store original response body data
			// Only redact authorization headers for security
			$anonymized_headers = $this->anonymize_headers( $response_headers );

			// Determine status based on response code
			$status = $this->determine_status( $response_code );

			// Encode response data if it's an array
			$encoded_body    = \is_array( $response_body ) ? \wp_json_encode( $response_body ) : $response_body;
			$encoded_headers = \wp_json_encode( $anonymized_headers );

			// Encrypt sensitive response data if encryption is available
			if ( $this->encryption ) {
				try {
					$encoded_body    = $this->encryption->encrypt( $encoded_body );
					$encoded_headers = $this->encryption->encrypt( $encoded_headers );
				} catch ( \Exception $e ) {
					// Log encryption failure and continue with unencrypted data.
					if ( \class_exists( DebugLogger::class ) ) {
						DebugLogger::instance()->error( 'Encryption failed during complete_request: ' . $e->getMessage() );
					}
				}
			}

			$update_data = array(
				'status'           => $status,
				'response_code'    => $response_code,
				'response_data'    => $encoded_body,
				'response_headers' => $encoded_headers,
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
	 * This method is public and static to allow views to anonymize data at render time
	 * without creating logger instances.
	 *
	 * @since 1.1.0
	 * @param mixed $data Data to anonymize
	 * @return mixed Anonymized data
	 */
	public static function anonymize_data( $data ) {
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
					$data[ $key ] = self::anonymize_data( $value );
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

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i 
				WHERE form_id = %d 
				ORDER BY created_at DESC 
				LIMIT %d',
				$this->table_name,
				$form_id,
				$limit
			),
			ARRAY_A
		);

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

		$result = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i 
				WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)',
				$this->table_name,
				$days
			)
		);

		return $result ?: 0;
	}

	/**
	 * Get log statistics for a form
	 *
	 * Returns aggregated statistics about API calls.
	 * Can be filtered by date range to show statistics for specific time periods.
	 *
	 * @since 1.1.0
	 * @param int|null    $form_id    Form ID (0 or null for all forms)
	 * @param string|null $date_start Start date in Y-m-d format (null for no filter)
	 * @param string|null $date_end   End date in Y-m-d format (null for no filter)
	 * @return array<string, int|float> Statistics array
	 */
	public function get_statistics( ?int $form_id, ?string $date_start = null, ?string $date_end = null ): array {
		global $wpdb;

		// Build base query with placeholders for all dynamic values
		$base_query = 'SELECT 
				COUNT(*) as total_requests,
				SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as successful_requests,
				SUM(CASE 
					WHEN status IN (%s, %s, %s) 
					AND id NOT IN (
						SELECT DISTINCT retry_of FROM %i 
						WHERE retry_of IS NOT NULL 
						AND status = %s
					)
					THEN 1 
					ELSE 0 
				END) as failed_requests,
				AVG(execution_time) as avg_execution_time,
				MAX(retry_count) as max_retries
			FROM %i
			WHERE 1=1';

		// Base prepare values (always needed)
		$prepare_values = array(
			'success',       // For successful_requests count
			'error',         // For failed_requests IN clause
			'client_error',  // For failed_requests IN clause
			'server_error',  // For failed_requests IN clause
			$this->table_name, // For subquery FROM
			'success',       // For subquery status check
			$this->table_name, // For main FROM
		);

		// Add form filter if specified
		if ( null !== $form_id && $form_id > 0 ) {
			$base_query      .= ' AND form_id = %d';
			$prepare_values[] = $form_id;
		}

		// Add date filter if specified
		if ( null !== $date_start && null !== $date_end ) {
			$base_query      .= ' AND DATE(created_at) BETWEEN %s AND %s';
			$prepare_values[] = $date_start;
			$prepare_values[] = $date_end;
		}

		// Execute query with all values passed to single prepare() call
		$stats = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query built with placeholders, all values passed to prepare().
			$wpdb->prepare( $base_query, $prepare_values ),
			ARRAY_A
		);

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
	 * Optionally filter by status. When filtering by error status,
	 * excludes errors that have been successfully retried.
	 *
	 * @since 1.2.0
	 * @param int         $hours  Number of hours to look back
	 * @param string|null $status Optional status filter ('success', 'error', etc.)
	 * @return int Number of requests
	 */
	public function get_count_last_hours( int $hours, ?string $status = null ): int {
		global $wpdb;

		$status_condition = '';
		$exclude_retried  = '';

		if ( $status ) {
			// Handle different status types.
			if ( 'error' === $status ) {
				// Count all error types, but exclude errors with successful retries.
				$status_condition = ' AND status IN (\'error\', \'client_error\', \'server_error\')';
				$exclude_retried  = $wpdb->prepare(
					' AND id NOT IN (SELECT DISTINCT retry_of FROM %i WHERE retry_of IS NOT NULL AND status = %s)',
					$this->table_name,
					'success'
				);
			} else {
				$status_condition = $wpdb->prepare( ' AND status = %s', $status );
			}
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Variables are safely prepared above.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)' . $status_condition . $exclude_retried,
				$this->table_name,
				$hours
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return (int) ( $count ?: 0 );
	}

	/**
	 * Get success rate for the last N hours
	 *
	 * Calculates the percentage of successful requests in the specified time window.
	 * Considers errors with successful retries as successes, not failures.
	 *
	 * @since 1.2.0
	 * @param int $hours Number of hours to look back
	 * @return float Success rate as a percentage (0-100)
	 */
	public function get_success_rate_last_hours( int $hours ): float {
		global $wpdb;

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT 
					COUNT(*) as total,
					SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as successful,
					SUM(CASE 
						WHEN status IN (%s, %s, %s) 
						AND id IN (SELECT DISTINCT retry_of FROM %i WHERE retry_of IS NOT NULL AND status = %s)
						THEN 1 
						ELSE 0 
					END) as retried_successfully
				FROM %i
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)',
				'success',
				'error',
				'client_error',
				'server_error',
				$this->table_name,
				'success',
				$this->table_name,
				$hours
			),
			ARRAY_A
		);

		if ( ! $stats || 0 === (int) $stats['total'] ) {
			return 0.0;
		}

		// Successful requests + errors that were successfully retried
		$effective_successful = (int) $stats['successful'] + (int) $stats['retried_successfully'];

		return round( ( $effective_successful / (int) $stats['total'] ) * 100, 2 );
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

		$avg = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT AVG(execution_time) 
				FROM %i
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
				AND execution_time IS NOT NULL',
				$this->table_name,
				$hours
			)
		);

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
	 * Excludes errors that have been successfully retried.
	 *
	 * @since 1.2.0
	 * @param int      $limit Maximum number of errors to retrieve
	 * @param int|null $hours Optional time window in hours (null for all time)
	 * @return array<int, array<string, mixed>> Array of error log entries
	 */
	public function get_recent_errors( int $limit = 5, ?int $hours = null ): array {
		global $wpdb;

		$time_clause = '';
		if ( null !== $hours && $hours > 0 ) {
			$time_clause = $wpdb->prepare( ' AND created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)', $hours );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Time clause is safely prepared above.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i 
				WHERE status IN (%s, %s, %s)
				AND id NOT IN (
					SELECT DISTINCT retry_of FROM %i 
					WHERE retry_of IS NOT NULL 
					AND status = %s
				)' . $time_clause . '
				ORDER BY created_at DESC 
				LIMIT %d',
				$this->table_name,
				'error',
				'client_error',
				'server_error',
				$this->table_name,
				'success',
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

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

		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$this->table_name,
				$log_id
			),
			ARRAY_A
		);

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

		// Decrypt data if encrypted
		$request_headers_raw = $log['request_headers'];
		$request_data_raw    = $log['request_data'];

		if ( $this->encryption && isset( $log['encryption_version'] ) && $log['encryption_version'] > 0 ) {
			try {
				$request_headers_raw = $this->encryption->decrypt( $request_headers_raw );
				$request_data_raw    = $this->encryption->decrypt( $request_data_raw );
			} catch ( \Exception $e ) {
				// Log decryption failure.
				if ( \class_exists( DebugLogger::class ) ) {
					DebugLogger::instance()->error( 'Decryption failed during get_request_for_retry: ' . $e->getMessage() );
				}
				return null;
			}
		}

		// Decode JSON data
		$request_headers = \json_decode( $request_headers_raw, true );
		$request_data    = \json_decode( $request_data_raw, true );

		// If request_data is not valid JSON, use the raw string
		if ( null === $request_data && 'null' !== \strtolower( (string) $request_data_raw ) ) {
			$request_data = $request_data_raw;
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

		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE retry_of = %d',
				$this->table_name,
				$log_id
			)
		);

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
		try {
			$settings = Settings::instance();
			return $settings->is_logging_enabled();
		} catch ( \Exception $e ) {
			// Settings not available, default to enabled.
			unset( $e );
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
		try {
			$settings = Settings::instance();
			return $settings->get_max_manual_retries();
		} catch ( \Exception $e ) {
			// Settings not available, use constant.
			unset( $e );
		}

		// Fallback to constant.
		return self::MAX_MANUAL_RETRIES;
	}

	/**
	 * Decrypt log fields for display
	 *
	 * Decrypts encrypted log fields for viewing in admin or exports.
	 *
	 * @since 1.3.0
	 * @param array<string, mixed> $log Log entry data.
	 * @return array<string, mixed> Log entry with decrypted fields.
	 */
	public function decrypt_log_fields( array $log ): array {
		if ( ! $this->encryption ) {
			return $log;
		}

		// Only decrypt if encryption_version indicates encryption was used.
		if ( ! isset( $log['encryption_version'] ) || $log['encryption_version'] === 0 ) {
			return $log;
		}

		try {
			// Decrypt sensitive fields.
			if ( ! empty( $log['request_data'] ) ) {
				$log['request_data'] = $this->encryption->decrypt( $log['request_data'] );
			}

			if ( ! empty( $log['request_headers'] ) ) {
				$log['request_headers'] = $this->encryption->decrypt( $log['request_headers'] );
			}

			if ( ! empty( $log['response_data'] ) ) {
				$log['response_data'] = $this->encryption->decrypt( $log['response_data'] );
			}

			if ( ! empty( $log['response_headers'] ) ) {
				$log['response_headers'] = $this->encryption->decrypt( $log['response_headers'] );
			}
		} catch ( \Exception $e ) {
			// Log decryption failure (without sensitive data).
			DebugLogger::instance()->error( 'Failed to decrypt log fields for log ID ' . ( $log['id'] ?? 'unknown' ) . ': ' . $e->getMessage() );
		}

		return $log;
	}

	/**
	 * Get maximum retries per hour from settings
	 *
	 * @since 1.2.0
	 * @return int
	 */
	public static function get_max_retries_per_hour(): int {
		// Try to get from settings first.
		try {
			$settings = Settings::instance();
			return $settings->get_max_retries_per_hour();
		} catch ( \Exception $e ) {
			// Settings not available, use constant.
			unset( $e );
		}

		// Fallback to constant.
		return self::MAX_RETRIES_PER_HOUR;
	}

	/**
	 * Get all retry entries for a log
	 *
	 * Retrieves all manual retry attempts for a specific log entry.
	 * Returns array with retry details including status.
	 *
	 * @since 1.3.8
	 * @param int $log_id Original log entry ID
	 * @return array<int, array{id: string, status: string, response_code: string|null, created_at: string}> Array of retry entries with id, status, response_code, and created_at keys.
	 */
	public function get_retries_for_log( int $log_id ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, status, response_code, created_at FROM %i WHERE retry_of = %d ORDER BY created_at ASC',
				$this->table_name,
				$log_id
			),
			ARRAY_A
		);

		return $results ?: array();
	}

	/**
	 * Check if log entry has a successful manual retry
	 *
	 * Determines if a failed request has been successfully retried.
	 * Returns true if any manual retry resulted in success status.
	 *
	 * @since 1.3.8
	 * @param int $log_id Original log entry ID
	 * @return bool True if has successful retry, false otherwise
	 */
	public function has_successful_retry( int $log_id ): bool {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE retry_of = %d AND status = %s',
				$this->table_name,
				$log_id,
				'success'
			)
		);

		return ( $count ?? 0 ) > 0;
	}

	/**
	 * Get ID of successful retry entry
	 *
	 * Returns the ID of the first successful manual retry for a log entry.
	 * Used to create links from original failed entry to successful retry.
	 *
	 * @since 1.3.8
	 * @param int $log_id Original log entry ID
	 * @return int|null ID of successful retry or null if none exists
	 */
	public function get_successful_retry_id( int $log_id ): ?int {
		global $wpdb;

		$retry_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE retry_of = %d AND status = %s ORDER BY created_at ASC LIMIT 1',
				$this->table_name,
				$log_id,
				'success'
			)
		);

		return $retry_id ? (int) $retry_id : null;
	}

	/**
	 * Count error logs by resolution status
	 *
	 * Returns counts of total errors, resolved (successfully retried), and unresolved.
	 * Uses a single optimized query with LEFT JOIN to count resolved errors.
	 *
	 * @since 1.3.14
	 * @return array{total: int, resolved: int, unresolved: int} Error counts by resolution status.
	 */
	public function count_errors_by_resolution(): array {
		global $wpdb;

		// Count total errors (not including retry entries themselves).
		$total_errors = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE status IN ('error', 'client_error', 'server_error') AND retry_of IS NULL",
				$this->table_name
			)
		);

		// Count resolved errors (errors that have a successful retry).
		$resolved_errors = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT e.id) FROM %i e
				INNER JOIN %i r ON r.retry_of = e.id AND r.status = 'success'
				WHERE e.status IN ('error', 'client_error', 'server_error') AND e.retry_of IS NULL",
				$this->table_name,
				$this->table_name
			)
		);

		return array(
			'total'      => $total_errors,
			'resolved'   => $resolved_errors,
			'unresolved' => $total_errors - $resolved_errors,
		);
	}

	/**
	 * Get IDs of error logs that have been successfully retried
	 *
	 * Returns an array of log IDs that are errors with at least one successful retry.
	 * Used for filtering resolved errors in list views.
	 *
	 * @since 1.3.14
	 * @return array<int> Array of resolved error log IDs.
	 */
	public function get_resolved_error_ids(): array {
		global $wpdb;

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT e.id FROM %i e
				INNER JOIN %i r ON r.retry_of = e.id AND r.status = 'success'
				WHERE e.status IN ('error', 'client_error', 'server_error') AND e.retry_of IS NULL",
				$this->table_name,
				$this->table_name
			)
		);

		return \array_map( 'intval', $results ?: array() );
	}
}
