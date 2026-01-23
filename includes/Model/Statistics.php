<?php
/**
 * Statistics Model
 *
 * Domain model representing log statistics.
 * Provides type-safe access to aggregated log data.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Model
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Model;

\defined( 'ABSPATH' ) || exit;

/**
 * Statistics Model Class
 *
 * Represents aggregated log statistics with type safety.
 * Part of Phase 1 foundation for 2.0.0 architecture refactoring.
 *
 * @since 2.0.0
 */
class Statistics {

	/**
	 * Total count
	 *
	 * @var int
	 */
	private int $total;

	/**
	 * Success count
	 *
	 * @var int
	 */
	private int $success;

	/**
	 * Error count
	 *
	 * @var int
	 */
	private int $error;

	/**
	 * Client error count (4xx)
	 *
	 * @var int
	 */
	private int $client_error;

	/**
	 * Server error count (5xx)
	 *
	 * @var int
	 */
	private int $server_error;

	/**
	 * Pending count
	 *
	 * @var int
	 */
	private int $pending;

	/**
	 * Average execution time in seconds
	 *
	 * @var float
	 */
	private float $avg_execution_time;

	/**
	 * Recent logs
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $recent_logs;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 *
	 * @param int                               $total               Total count.
	 * @param int                               $success             Success count.
	 * @param int                               $error               Error count.
	 * @param int                               $client_error        Client error count.
	 * @param int                               $server_error        Server error count.
	 * @param int                               $pending             Pending count.
	 * @param float                             $avg_execution_time  Average execution time.
	 * @param array<int, array<string, mixed>>  $recent_logs         Recent logs.
	 */
	public function __construct(
		int $total = 0,
		int $success = 0,
		int $error = 0,
		int $client_error = 0,
		int $server_error = 0,
		int $pending = 0,
		float $avg_execution_time = 0.0,
		array $recent_logs = array()
	) {
		$this->total              = $total;
		$this->success            = $success;
		$this->error              = $error;
		$this->client_error       = $client_error;
		$this->server_error       = $server_error;
		$this->pending            = $pending;
		$this->avg_execution_time = $avg_execution_time;
		$this->recent_logs        = $recent_logs;
	}

	/**
	 * Get total count
	 *
	 * @since 2.0.0
	 *
	 * @return int Total count.
	 */
	public function get_total(): int {
		return $this->total;
	}

	/**
	 * Get success count
	 *
	 * @since 2.0.0
	 *
	 * @return int Success count.
	 */
	public function get_success(): int {
		return $this->success;
	}

	/**
	 * Get error count
	 *
	 * @since 2.0.0
	 *
	 * @return int Error count.
	 */
	public function get_error(): int {
		return $this->error;
	}

	/**
	 * Get client error count
	 *
	 * @since 2.0.0
	 *
	 * @return int Client error count.
	 */
	public function get_client_error(): int {
		return $this->client_error;
	}

	/**
	 * Get server error count
	 *
	 * @since 2.0.0
	 *
	 * @return int Server error count.
	 */
	public function get_server_error(): int {
		return $this->server_error;
	}

	/**
	 * Get pending count
	 *
	 * @since 2.0.0
	 *
	 * @return int Pending count.
	 */
	public function get_pending(): int {
		return $this->pending;
	}

	/**
	 * Get average execution time
	 *
	 * @since 2.0.0
	 *
	 * @return float Average execution time in seconds.
	 */
	public function get_avg_execution_time(): float {
		return $this->avg_execution_time;
	}

	/**
	 * Get recent logs
	 *
	 * @since 2.0.0
	 *
	 * @return array<int, array<string, mixed>> Recent logs.
	 */
	public function get_recent_logs(): array {
		return $this->recent_logs;
	}

	/**
	 * Calculate success rate
	 *
	 * @since 2.0.0
	 *
	 * @return float Success rate (0-100).
	 */
	public function get_success_rate(): float {
		if ( 0 === $this->total ) {
			return 0.0;
		}
		return ( $this->success / $this->total ) * 100;
	}

	/**
	 * Calculate error rate
	 *
	 * @since 2.0.0
	 *
	 * @return float Error rate (0-100).
	 */
	public function get_error_rate(): float {
		if ( 0 === $this->total ) {
			return 0.0;
		}
		$total_errors = $this->error + $this->client_error + $this->server_error;
		return ( $total_errors / $this->total ) * 100;
	}

	/**
	 * Convert to array representation
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Array representation.
	 */
	public function to_array(): array {
		return array(
			'total'              => $this->total,
			'success'            => $this->success,
			'error'              => $this->error,
			'client_error'       => $this->client_error,
			'server_error'       => $this->server_error,
			'pending'            => $this->pending,
			'avg_execution_time' => $this->avg_execution_time,
			'recent_logs'        => $this->recent_logs,
			'success_rate'       => $this->get_success_rate(),
			'error_rate'         => $this->get_error_rate(),
		);
	}

	/**
	 * Create Statistics from database query result
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed>              $stats       Statistics from query.
	 * @param array<int, array<string, mixed>>  $recent_logs Recent logs.
	 * @return Statistics Statistics instance.
	 */
	public static function from_query( array $stats, array $recent_logs = array() ): Statistics {
		return new self(
			(int) ( $stats['total'] ?? 0 ),
			(int) ( $stats['success'] ?? 0 ),
			(int) ( $stats['error'] ?? 0 ),
			(int) ( $stats['client_error'] ?? 0 ),
			(int) ( $stats['server_error'] ?? 0 ),
			(int) ( $stats['pending'] ?? 0 ),
			(float) ( $stats['avg_execution_time'] ?? 0.0 ),
			$recent_logs
		);
	}
}
