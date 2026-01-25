<?php
/**
 * Log Statistics Service
 *
 * Handles statistical calculations for API request logs.
 * Provides statistics for API request logs.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Service\Logging
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Service\Logging;

\defined( 'ABSPATH' ) || exit;

/**
 * Class LogStatistics
 *
 * Responsible for calculating statistics from log entries.
 * Provides aggregated data for dashboards and reports.
 *
 * @since 2.0.0
 */
class LogStatistics {

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
	 * Get log statistics for a form
	 *
	 * Returns aggregated statistics about API calls.
	 * Can be filtered by date range to show statistics for specific time periods.
	 *
	 * @since 2.0.0
	 * @param int|null    $form_id    Form ID (0 or null for all forms).
	 * @param string|null $date_start Start date in Y-m-d format (null for no filter).
	 * @param string|null $date_end   End date in Y-m-d format (null for no filter).
	 * @return array<string, int|float> Statistics array.
	 */
	public function get_statistics( ?int $form_id, ?string $date_start = null, ?string $date_end = null ): array {
		global $wpdb;

		// Build base query with placeholders for all dynamic values.
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

		// Base prepare values (always needed).
		$prepare_values = array(
			'success',         // For successful_requests count.
			'error',           // For failed_requests IN clause.
			'client_error',    // For failed_requests IN clause.
			'server_error',    // For failed_requests IN clause.
			$this->table_name, // For subquery FROM.
			'success',         // For subquery status check.
			$this->table_name, // For main FROM.
		);

		// Add form filter if specified.
		if ( null !== $form_id && $form_id > 0 ) {
			$base_query      .= ' AND form_id = %d';
			$prepare_values[] = $form_id;
		}

		// Add date filter if specified.
		if ( null !== $date_start && null !== $date_end ) {
			$base_query      .= ' AND DATE(created_at) BETWEEN %s AND %s';
			$prepare_values[] = $date_start;
			$prepare_values[] = $date_end;
		}

		// Execute query with all values passed to single prepare() call.
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
	 * @since 2.0.0
	 * @param int         $hours  Number of hours to look back.
	 * @param string|null $status Optional status filter ('success', 'error', etc.).
	 * @return int Number of requests.
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
	 * @since 2.0.0
	 * @param int $hours Number of hours to look back.
	 * @return float Success rate as a percentage (0-100).
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

		// Successful requests + errors that were successfully retried.
		$effective_successful = (int) $stats['successful'] + (int) $stats['retried_successfully'];

		return round( ( $effective_successful / (int) $stats['total'] ) * 100, 2 );
	}

	/**
	 * Get average response time for the last N hours
	 *
	 * Calculates the average execution time in milliseconds for requests
	 * in the specified time window.
	 *
	 * @since 2.0.0
	 * @param int $hours Number of hours to look back.
	 * @return float Average response time in milliseconds.
	 */
	public function get_avg_response_time_last_hours( int $hours ): float {
		global $wpdb;

		$avg = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT AVG(execution_time) FROM %i WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR) AND execution_time IS NOT NULL',
				$this->table_name,
				$hours
			)
		);

		if ( ! $avg ) {
			return 0.0;
		}

		// Convert seconds to milliseconds and round to 0 decimal places.
		return round( (float) $avg * 1000, 0 );
	}

	/**
	 * Get recent error logs
	 *
	 * Retrieves the most recent failed API requests for quick diagnostics.
	 * Excludes errors that have been successfully retried.
	 *
	 * @since 2.0.0
	 * @param int      $limit Maximum number of errors to retrieve.
	 * @param int|null $hours Optional time window in hours (null for all time).
	 * @return array<int, array<string, mixed>> Array of error log entries.
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
}
