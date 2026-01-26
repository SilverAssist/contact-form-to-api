<?php
/**
 * Log Reader Service
 *
 * Handles reading and querying of API request logs.
 * Handles reading API request logs from the database.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Service\Logging
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Service\Logging;

use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

\defined( 'ABSPATH' ) || exit;

/**
 * Class LogReader
 *
 * Responsible for reading log entries from the database.
 * Handles decryption and data retrieval.
 *
 * @since 2.0.0
 */
class LogReader {

	/**
	 * Table name for logs
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Encryption service instance
	 *
	 * @var EncryptionService|null
	 */
	private ?EncryptionService $encryption = null;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
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
	 * Get log entry by ID
	 *
	 * Retrieves a single log entry for detailed inspection.
	 *
	 * @since 2.0.0
	 * @param int $log_id Log entry ID.
	 * @return array<string, mixed>|null Log entry data or null if not found.
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
	 * Get recent logs for a form
	 *
	 * Retrieves recent API call logs for debugging.
	 *
	 * @since 2.0.0
	 * @param int|null $form_id Form ID to get logs for (null for new forms).
	 * @param int      $limit   Maximum number of logs to retrieve.
	 * @return array<int, array<string, mixed>> Array of log entries.
	 */
	public function get_recent_logs( ?int $form_id, int $limit = 10 ): array {
		if ( null === $form_id || $form_id <= 0 ) {
			return array();
		}

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE form_id = %d ORDER BY created_at DESC LIMIT %d',
				$this->table_name,
				$form_id,
				$limit
			),
			ARRAY_A
		);

		return $results ?: array();
	}

	/**
	 * Get request data for retry
	 *
	 * Retrieves complete request data needed to replay a failed API request.
	 * Returns null if log entry doesn't exist or is not retryable.
	 *
	 * @since 2.0.0
	 * @param int $log_id Log entry ID to retry.
	 * @return array<string, mixed>|null Request data or null if not retryable.
	 */
	public function get_request_for_retry( int $log_id ): ?array {
		$log = $this->get_log( $log_id );

		if ( ! $log ) {
			return null;
		}

		// Only retry failed requests (pending excluded as they haven't completed yet).
		$retryable_statuses = array( 'error', 'client_error', 'server_error' );
		if ( ! \in_array( $log['status'], $retryable_statuses, true ) ) {
			return null;
		}

		// Decrypt data if encrypted.
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

		// Decode JSON data.
		$request_headers = \json_decode( $request_headers_raw, true );
		$request_data    = \json_decode( $request_data_raw, true );

		// If request_data is not valid JSON, use the raw string.
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
	 * Decrypt log fields for display
	 *
	 * Decrypts encrypted log fields for viewing in admin or exports.
	 *
	 * @since 2.0.0
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
	 * Get forms that have log entries
	 *
	 * Retrieves a list of forms that have at least one log entry.
	 * Includes form ID and title, with graceful handling for deleted forms.
	 *
	 * @since 2.1.0
	 * @return array<int, array{form_id: string, post_title: string|null}> Array of forms with logs.
	 */
	public function get_forms_with_logs(): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT l.form_id, p.post_title
				FROM %i l
				LEFT JOIN %i p ON l.form_id = p.ID
				WHERE l.form_id IS NOT NULL
				ORDER BY p.post_title ASC, l.form_id ASC',
				$this->table_name,
				$wpdb->posts
			),
			ARRAY_A
		);

		return $results ?: array();
	}
}
