<?php
/**
 * Retry Manager Service
 *
 * Handles retry logic and tracking for failed API requests.
 * Extracted from RequestLogger as part of Phase 2 refactoring.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Service\Logging
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Service\Logging;

use SilverAssist\ContactFormToAPI\Core\Settings;

\defined( 'ABSPATH' ) || exit;

/**
 * Class RetryManager
 *
 * Responsible for managing retry logic, tracking retry attempts,
 * and determining error resolution status.
 *
 * @since 2.0.0
 */
class RetryManager {

	/**
	 * Maximum number of manual retries allowed per log entry
	 *
	 * @since 2.0.0
	 * @var int
	 */
	public const MAX_MANUAL_RETRIES = 3;

	/**
	 * Maximum number of retries allowed per hour (global rate limit)
	 *
	 * @since 2.0.0
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
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = "{$wpdb->prefix}cf7_api_logs";
	}

	/**
	 * Count retries for a specific log entry
	 *
	 * Counts how many times a specific log entry has been retried.
	 *
	 * @since 2.0.0
	 * @param int $log_id Original log entry ID.
	 * @return int Number of retry attempts.
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
	 * Get all retry entries for a log
	 *
	 * Retrieves all manual retry attempts for a specific log entry.
	 * Returns array with retry details including status.
	 *
	 * @since 2.0.0
	 * @param int $log_id Original log entry ID.
	 * @return array<int, array{id: int, status: string, response_code: int|null, created_at: string}> Array of retry entries with id, status, response_code, and created_at keys.
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
	 * @since 2.0.0
	 * @param int $log_id Original log entry ID.
	 * @return bool True if has successful retry, false otherwise.
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
	 * @since 2.0.0
	 * @param int $log_id Original log entry ID.
	 * @return int|null ID of successful retry or null if none exists.
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
	 * Uses an optimized query with INNER JOIN to count resolved errors.
	 *
	 * @since 2.0.0
	 * @return array{total: int, resolved: int, unresolved: int} Error counts by resolution status.
	 */
	public function count_errors_by_resolution(): array {
		global $wpdb;

		// Count total errors (not including retry entries themselves).
		$total_errors = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE status IN (\'error\', \'client_error\', \'server_error\') AND retry_of IS NULL',
				$this->table_name
			)
		);

		// Count resolved errors (errors that have a successful retry).
		$resolved_errors = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT e.id) FROM %i e
				INNER JOIN %i r ON r.retry_of = e.id AND r.status = \'success\'
				WHERE e.status IN (\'error\', \'client_error\', \'server_error\') AND e.retry_of IS NULL',
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
	 * @since 2.0.0
	 * @return array<int> Array of resolved error log IDs.
	 */
	public function get_resolved_error_ids(): array {
		global $wpdb;

		$results = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT e.id FROM %i e
				INNER JOIN %i r ON r.retry_of = e.id AND r.status = \'success\'
				WHERE e.status IN (\'error\', \'client_error\', \'server_error\') AND e.retry_of IS NULL',
				$this->table_name,
				$this->table_name
			)
		);

		return \array_map( 'intval', $results ?: array() );
	}

	/**
	 * Get maximum manual retries from settings
	 *
	 * @since 2.0.0
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
	 * Get maximum retries per hour from settings
	 *
	 * @since 2.0.0
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
}
