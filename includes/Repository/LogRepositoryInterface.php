<?php
/**
 * LogRepositoryInterface
 *
 * Interface for log data access operations.
 * Defines contract for log persistence layer.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Repository
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Repository;

use SilverAssist\ContactFormToAPI\Model\LogEntry;
use SilverAssist\ContactFormToAPI\Model\Statistics;

\defined( 'ABSPATH' ) || exit;

/**
 * LogRepositoryInterface
 *
 * Defines data access operations for log entries.
 * Part of Phase 1 foundation for 2.0.0 architecture refactoring.
 *
 * @since 2.0.0
 */
interface LogRepositoryInterface {

	/**
	 * Save a log entry
	 *
	 * @since 2.0.0
	 *
	 * @param LogEntry $entry Log entry to save.
	 * @return int|false Log ID on success, false on failure.
	 */
	public function save( LogEntry $entry ): int|false;

	/**
	 * Find log entry by ID
	 *
	 * @since 2.0.0
	 *
	 * @param int $id Log ID.
	 * @return LogEntry|null Log entry or null if not found.
	 */
	public function find_by_id( int $id ): ?LogEntry;

	/**
	 * Find log entries with filters
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return array<int, LogEntry> Array of log entries.
	 */
	public function find_all( array $filters = array() ): array;

	/**
	 * Delete log entries by IDs
	 *
	 * @since 2.0.0
	 *
	 * @param array<int, int> $ids Log IDs to delete.
	 * @return int Number of deleted entries.
	 */
	public function delete( array $ids ): int;

	/**
	 * Get log statistics
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return Statistics Statistics object.
	 */
	public function get_statistics( array $filters = array() ): Statistics;

	/**
	 * Count log entries
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return int Count of matching entries.
	 */
	public function count( array $filters = array() ): int;
}
