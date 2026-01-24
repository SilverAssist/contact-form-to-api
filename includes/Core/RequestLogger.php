<?php
/**
 * API Request Logger (Facade)
 *
 * DEPRECATED: This class now acts as a facade for the new specialized logging services.
 * New code should use the Service\Logging classes directly.
 *
 * Handles comprehensive logging of all API requests and responses,
 * inspired by Flamingo plugin's approach to data tracking.
 * Provides detailed insights into API interactions for debugging and auditing.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 1.1.0
 * @version 2.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

use SilverAssist\ContactFormToAPI\Core\SensitiveDataPatterns;
use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;
use WP_Error;

\defined( 'ABSPATH' ) || exit;

/**
 * Class RequestLogger
 *
 * FACADE: This class delegates to specialized logging services.
 * Maintained for backward compatibility during transition period.
 *
 * Advanced logging system for API requests and responses.
 * Tracks all data sent to APIs and received responses for complete traceability.
 *
 * @since 1.1.0
 * @deprecated 2.0.0 Use Service\Logging\LogWriter, LogReader, LogStatistics, or RetryManager instead.
 */
class RequestLogger {

	/**
	 * Maximum number of manual retries allowed per log entry
	 *
	 * @since 1.2.0
	 * @var int
	 * @deprecated 2.0.0 Use RetryManager::MAX_MANUAL_RETRIES instead.
	 */
	public const MAX_MANUAL_RETRIES = 3;

	/**
	 * Maximum number of retries allowed per hour (global rate limit)
	 *
	 * @since 1.2.0
	 * @var int
	 * @deprecated 2.0.0 Use RetryManager::MAX_RETRIES_PER_HOUR instead.
	 */
	public const MAX_RETRIES_PER_HOUR = 10;

	/**
	 * Log entry ID
	 *
	 * @var int|null
	 */
	private ?int $log_id = null;

	/**
	 * Encryption service instance
	 *
	 * @var EncryptionService|null
	 */
	private ?EncryptionService $encryption = null;

	/**
	 * LogWriter service instance
	 *
	 * @var LogWriter
	 */
	private LogWriter $log_writer;

	/**
	 * LogReader service instance
	 *
	 * @var LogReader
	 */
	private LogReader $log_reader;

	/**
	 * LogStatistics service instance
	 *
	 * @var LogStatistics
	 */
	private LogStatistics $log_statistics;

	/**
	 * RetryManager service instance
	 *
	 * @var RetryManager
	 */
	private RetryManager $retry_manager;

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		// Initialize encryption service if available.
		if ( EncryptionService::is_sodium_available() ) {
			$this->encryption = EncryptionService::instance();
			$this->encryption->init();
		}

		// Initialize new logging services.
		$this->log_writer     = new LogWriter();
		$this->log_reader     = new LogReader();
		$this->log_statistics = new LogStatistics();
		$this->retry_manager  = new RetryManager();
	}

	/**
	 * Start logging an API request
	 *
	 * Creates initial log entry before sending request.
	 * Stores original request body data for retry functionality.
	 * Only redacts authorization headers for security.
	 *
	 * @since 1.1.0
	 * @deprecated 2.0.0 Use LogWriter::start_request() instead.
	 * @param int                    $form_id         Contact Form 7 form ID.
	 * @param string                 $endpoint        API endpoint URL.
	 * @param string                 $method          HTTP method (GET, POST, etc.).
	 * @param mixed                  $request_data    Request body data.
	 * @param array<string, string>  $request_headers Request headers.
	 * @param int|null               $retry_of        Original log ID if this is a retry.
	 * @return int|false Log entry ID or false on failure.
	 */
	public function start_request( int $form_id, string $endpoint, string $method, $request_data, array $request_headers = array(), ?int $retry_of = null ) {
		// Delegate to LogWriter service.
		$log_id = $this->log_writer->start_request( $form_id, $endpoint, $method, $request_data, $request_headers, $retry_of );

		if ( $log_id ) {
			$this->log_id = $log_id;
		}

		return $log_id;
	}

	/**
	 * Complete logging with response data
	 *
	 * Updates log entry with response information and execution time.
	 *
	 * @since 1.1.0
	 * @deprecated 2.0.0 Use LogWriter::complete_request() instead.
	 * @param array<string, mixed>|WP_Error $response      API response or error.
	 * @param int|null                       $retry_count   Number of retry attempts.
	 * @return bool True on success, false on failure.
	 */
	public function complete_request( $response, ?int $retry_count = 0 ): bool {
		if ( ! $this->log_id ) {
			return false;
		}

		// Delegate to LogWriter service.
		return $this->log_writer->complete_request( $this->log_id, $response, $retry_count );
	}

	/**
	 * Log retry attempt
	 *
	 * Updates retry count for current log entry.
	 *
	 * @since 1.1.0
	 * @deprecated 2.0.0 Use LogWriter::update_retry_count() instead.
	 * @param int $retry_count Current retry attempt number.
	 * @return bool True on success, false on failure.
	 */
	public function log_retry( int $retry_count ): bool {
		if ( ! $this->log_id ) {
			return false;
		}

		// Delegate to LogWriter service.
		return $this->log_writer->update_retry_count( $this->log_id, $retry_count );
	}

	/**
	 * Anonymize sensitive data
	 *
	 * Removes or masks sensitive information from logged data.
	 * This method is public and static to allow views to anonymize data at render time
	 * without creating logger instances.
	 *
	 * @since 1.1.0
	 * @param mixed $data Data to anonymize.
	 * @return mixed Anonymized data.
	 */
	public static function anonymize_data( $data ) {
		// If string, try to decode as JSON first.
		if ( \is_string( $data ) ) {
			$decoded = \json_decode( $data, true );
			if ( \json_last_error() === JSON_ERROR_NONE && \is_array( $decoded ) ) {
				$data = $decoded;
			} else {
				// Not JSON, return as is.
				return $data;
			}
		}

		// If array, anonymize recursively.
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
	 * Get recent logs for a form
	 *
	 * Retrieves recent API call logs for debugging.
	 *
	 * @since 1.1.0
	 * @deprecated 2.0.0 Use LogReader::get_recent_logs() instead.
	 * @param int|null $form_id Form ID to get logs for (null for new forms).
	 * @param int      $limit   Maximum number of logs to retrieve.
	 * @return array<int, array<string, mixed>> Array of log entries.
	 */
	public function get_recent_logs( ?int $form_id, int $limit = 10 ): array {
		// Delegate to LogReader service.
		return $this->log_reader->get_recent_logs( $form_id, $limit );
	}

	/**
	 * Clean old logs
	 *
	 * Removes logs older than specified days.
	 *
	 * @since 1.1.0
	 * @deprecated 2.0.0 Use LogWriter::delete_old_logs() instead.
	 * @param int $days Number of days to keep logs.
	 * @return int Number of deleted rows.
	 */
	public function clean_old_logs( int $days = 30 ): int {
		// Delegate to LogWriter service.
		return $this->log_writer->delete_old_logs( $days );
	}

	/**
	 * Get log statistics for a form
	 *
	 * Returns aggregated statistics about API calls.
	 * Can be filtered by date range to show statistics for specific time periods.
	 *
	 * @since 1.1.0
	 * @deprecated 2.0.0 Use LogStatistics::get_statistics() instead.
	 * @param int|null    $form_id    Form ID (0 or null for all forms).
	 * @param string|null $date_start Start date in Y-m-d format (null for no filter).
	 * @param string|null $date_end   End date in Y-m-d format (null for no filter).
	 * @return array<string, int|float> Statistics array.
	 */
	public function get_statistics( ?int $form_id, ?string $date_start = null, ?string $date_end = null ): array {
		// Delegate to LogStatistics service.
		return $this->log_statistics->get_statistics( $form_id, $date_start, $date_end );
	}

	/**
	 * Get count of logs in the last N hours
	 *
	 * Retrieves the count of API requests in the specified time window.
	 * Optionally filter by status. When filtering by error status,
	 * excludes errors that have been successfully retried.
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use LogStatistics::get_count_last_hours() instead.
	 * @param int         $hours  Number of hours to look back.
	 * @param string|null $status Optional status filter ('success', 'error', etc.).
	 * @return int Number of requests.
	 */
	public function get_count_last_hours( int $hours, ?string $status = null ): int {
		// Delegate to LogStatistics service.
		return $this->log_statistics->get_count_last_hours( $hours, $status );
	}

	/**
	 * Get success rate for the last N hours
	 *
	 * Calculates the percentage of successful requests in the specified time window.
	 * Considers errors with successful retries as successes, not failures.
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use LogStatistics::get_success_rate_last_hours() instead.
	 * @param int $hours Number of hours to look back.
	 * @return float Success rate as a percentage (0-100).
	 */
	public function get_success_rate_last_hours( int $hours ): float {
		// Delegate to LogStatistics service.
		return $this->log_statistics->get_success_rate_last_hours( $hours );
	}

	/**
	 * Get average response time for the last N hours
	 *
	 * Calculates the average execution time in milliseconds for requests
	 * in the specified time window.
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use LogStatistics::get_avg_response_time_last_hours() instead.
	 * @param int $hours Number of hours to look back.
	 * @return float Average response time in milliseconds.
	 */
	public function get_avg_response_time_last_hours( int $hours ): float {
		// Delegate to LogStatistics service.
		return $this->log_statistics->get_avg_response_time_last_hours( $hours );
	}

	/**
	 * Get recent error logs
	 *
	 * Retrieves the most recent failed API requests for quick diagnostics.
	 * Excludes errors that have been successfully retried.
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use LogStatistics::get_recent_errors() instead.
	 * @param int      $limit Maximum number of errors to retrieve.
	 * @param int|null $hours Optional time window in hours (null for all time).
	 * @return array<int, array<string, mixed>> Array of error log entries.
	 */
	public function get_recent_errors( int $limit = 5, ?int $hours = null ): array {
		// Delegate to LogStatistics service.
		return $this->log_statistics->get_recent_errors( $limit, $hours );
	}

	/**
	 * Get log entry by ID
	 *
	 * Retrieves a single log entry for detailed inspection.
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use LogReader::get_log() instead.
	 * @param int $log_id Log entry ID.
	 * @return array<string, mixed>|null Log entry data or null if not found.
	 */
	public function get_log( int $log_id ): ?array {
		// Delegate to LogReader service.
		return $this->log_reader->get_log( $log_id );
	}

	/**
	 * Get request data for retry
	 *
	 * Retrieves complete request data needed to replay a failed API request.
	 * Returns null if log entry doesn't exist or is not retryable.
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use LogReader::get_request_for_retry() instead.
	 * @param int $log_id Log entry ID to retry.
	 * @return array<string, mixed>|null Request data or null if not retryable.
	 */
	public function get_request_for_retry( int $log_id ): ?array {
		// Delegate to LogReader service.
		return $this->log_reader->get_request_for_retry( $log_id );
	}

	/**
	 * Count retries for a specific log entry
	 *
	 * Counts how many times a specific log entry has been retried.
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use RetryManager::count_retries() instead.
	 * @param int $log_id Original log entry ID.
	 * @return int Number of retry attempts.
	 */
	public function count_retries( int $log_id ): int {
		// Delegate to RetryManager service.
		return $this->retry_manager->count_retries( $log_id );
	}

	/**
	 * Get maximum manual retries from settings
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use RetryManager::get_max_manual_retries() instead.
	 * @return int
	 */
	public static function get_max_manual_retries(): int {
		return RetryManager::get_max_manual_retries();
	}

	/**
	 * Decrypt log fields for display
	 *
	 * Decrypts encrypted log fields for viewing in admin or exports.
	 *
	 * @since 1.3.0
	 * @deprecated 2.0.0 Use LogReader::decrypt_log_fields() instead.
	 * @param array<string, mixed> $log Log entry data.
	 * @return array<string, mixed> Log entry with decrypted fields.
	 */
	public function decrypt_log_fields( array $log ): array {
		// Delegate to LogReader service.
		return $this->log_reader->decrypt_log_fields( $log );
	}

	/**
	 * Get maximum retries per hour from settings
	 *
	 * @since 1.2.0
	 * @deprecated 2.0.0 Use RetryManager::get_max_retries_per_hour() instead.
	 * @return int
	 */
	public static function get_max_retries_per_hour(): int {
		return RetryManager::get_max_retries_per_hour();
	}

	/**
	 * Get all retry entries for a log
	 *
	 * Retrieves all manual retry attempts for a specific log entry.
	 * Returns array with retry details including status.
	 *
	 * @since 1.3.8
	 * @deprecated 2.0.0 Use RetryManager::get_retries_for_log() instead.
	 * @param int $log_id Original log entry ID.
	 * @return array<int, array{id: string, status: string, response_code: string|null, created_at: string}> Array of retry entries with id, status, response_code, and created_at keys.
	 */
	public function get_retries_for_log( int $log_id ): array {
		// Delegate to RetryManager service.
		return $this->retry_manager->get_retries_for_log( $log_id );
	}

	/**
	 * Check if log entry has a successful manual retry
	 *
	 * Determines if a failed request has been successfully retried.
	 * Returns true if any manual retry resulted in success status.
	 *
	 * @since 1.3.8
	 * @deprecated 2.0.0 Use RetryManager::has_successful_retry() instead.
	 * @param int $log_id Original log entry ID.
	 * @return bool True if has successful retry, false otherwise.
	 */
	public function has_successful_retry( int $log_id ): bool {
		// Delegate to RetryManager service.
		return $this->retry_manager->has_successful_retry( $log_id );
	}

	/**
	 * Get ID of successful retry entry
	 *
	 * Returns the ID of the first successful manual retry for a log entry.
	 * Used to create links from original failed entry to successful retry.
	 *
	 * @since 1.3.8
	 * @deprecated 2.0.0 Use RetryManager::get_successful_retry_id() instead.
	 * @param int $log_id Original log entry ID.
	 * @return int|null ID of successful retry or null if none exists.
	 */
	public function get_successful_retry_id( int $log_id ): ?int {
		// Delegate to RetryManager service.
		return $this->retry_manager->get_successful_retry_id( $log_id );
	}

	/**
	 * Count error logs by resolution status
	 *
	 * Returns counts of total errors, resolved (successfully retried), and unresolved.
	 * Uses an optimized query with INNER JOIN to count resolved errors.
	 *
	 * @since 1.3.14
	 * @deprecated 2.0.0 Use RetryManager::count_errors_by_resolution() instead.
	 * @return array{total: int, resolved: int, unresolved: int} Error counts by resolution status.
	 */
	public function count_errors_by_resolution(): array {
		// Delegate to RetryManager service.
		return $this->retry_manager->count_errors_by_resolution();
	}

	/**
	 * Get IDs of error logs that have been successfully retried
	 *
	 * Returns an array of log IDs that are errors with at least one successful retry.
	 * Used for filtering resolved errors in list views.
	 *
	 * @since 1.3.14
	 * @deprecated 2.0.0 Use RetryManager::get_resolved_error_ids() instead.
	 * @return array<int> Array of resolved error log IDs.
	 */
	public function get_resolved_error_ids(): array {
		// Delegate to RetryManager service.
		return $this->retry_manager->get_resolved_error_ids();
	}
}
