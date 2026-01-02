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
 * @version 1.1.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

\defined( "ABSPATH" ) || exit;

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
	 * Sensitive field patterns to anonymize
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
		"ssn",
		"social_security",
		"credit_card",
		"card_number",
	);

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
	 * @param int    $form_id        Contact Form 7 form ID
	 * @param string $endpoint       API endpoint URL
	 * @param string $method         HTTP method (GET, POST, etc.)
	 * @param mixed  $request_data   Request body data
	 * @param array  $request_headers Request headers
	 * @return int|false Log entry ID or false on failure
	 */
	public function start_request( int $form_id, string $endpoint, string $method, $request_data, array $request_headers = array() ) {
		global $wpdb;

		$this->start_time = \microtime( true );

		// Anonymize sensitive data
		$anonymized_data    = $this->anonymize_data( $request_data );
		$anonymized_headers = $this->anonymize_headers( $request_headers );

		// Prepare data for storage
		$prepared_data    = \is_string( $anonymized_data ) ? $anonymized_data : \wp_json_encode( $anonymized_data );
		$prepared_headers = \wp_json_encode( $anonymized_headers );

		$result = $wpdb->insert(
			$this->table_name,
			array(
				"form_id"         => $form_id,
				"endpoint"        => $endpoint,
				"method"          => $method,
				"status"          => "pending",
				"request_data"    => $prepared_data,
				"request_headers" => $prepared_headers,
				"retry_count"     => 0,
				"created_at"      => \current_time( "mysql" ),
			),
			array( "%d", "%s", "%s", "%s", "%s", "%s", "%d", "%s" )
		);

		if ( $result ) {
			$this->log_id = $wpdb->insert_id;
			return $this->log_id;
		}

		return false;
	}

	/**
	 * Complete logging with response data
	 *
	 * Updates log entry with response information and execution time.
	 *
	 * @since 1.1.0
	 * @param array|\WP_Error $response      API response or error
	 * @param int|null        $retry_count   Number of retry attempts
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
				"status"         => "error",
				"error_message"  => $response->get_error_message(),
				"execution_time" => $execution_time,
				"retry_count"    => $retry_count,
			);
			$format = array( "%s", "%s", "%f", "%d" );
		} else {
			// Handle successful responses
			$response_code = \wp_remote_retrieve_response_code( $response );
			$response_body = \wp_remote_retrieve_body( $response );
			$response_headers = \wp_remote_retrieve_headers( $response );

			// Anonymize response data
			$anonymized_body    = $this->anonymize_data( $response_body );
			$anonymized_headers = $this->anonymize_headers( $response_headers );

			// Determine status based on response code
			$status = $this->determine_status( $response_code );

			$update_data = array(
				"status"           => $status,
				"response_code"    => $response_code,
				"response_data"    => $anonymized_body,
				"response_headers" => \wp_json_encode( $anonymized_headers ),
				"execution_time"   => $execution_time,
				"retry_count"      => $retry_count,
			);
			$format = array( "%s", "%d", "%s", "%s", "%f", "%d" );
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( "id" => $this->log_id ),
			$format,
			array( "%d" )
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
			array( "retry_count" => $retry_count ),
			array( "id" => $this->log_id ),
			array( "%d" ),
			array( "%d" )
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
				$key_lower = \strtolower( $key );

				// Check if key matches sensitive pattern
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
	 * @param array|\WpOrg\Requests\Utility\CaseInsensitiveDictionary $headers Headers to anonymize
	 * @return array Anonymized headers
	 */
	private function anonymize_headers( $headers ): array {
		if ( ! \is_array( $headers ) && ! $headers instanceof \ArrayAccess ) {
			return array();
		}

		$anonymized = array();
		foreach ( $headers as $key => $value ) {
			$key_lower = \strtolower( $key );

			// Check if header is sensitive
			$is_sensitive = false;
			foreach ( $this->sensitive_patterns as $pattern ) {
				if ( \strpos( $key_lower, $pattern ) !== false ) {
					$is_sensitive = true;
					break;
				}
			}

			$anonymized[ $key ] = $is_sensitive ? "***REDACTED***" : $value;
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
			return "success";
		} elseif ( $code >= 400 && $code < 500 ) {
			return "client_error";
		} elseif ( $code >= 500 ) {
			return "server_error";
		}

		return "unknown";
	}

	/**
	 * Get recent logs for a form
	 *
	 * Retrieves recent API call logs for debugging.
	 *
	 * @since 1.1.0
	 * @param int|null $form_id Form ID to get logs for (null for new forms)
	 * @param int      $limit   Maximum number of logs to retrieve
	 * @return array Array of log entries
	 */
	public function get_recent_logs( ?int $form_id, int $limit = 10 ): array {
		if ( null === $form_id || $form_id <= 0 ) {
			return array();
		}

		global $wpdb;

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
				"DELETE FROM {$this->table_name} 
				WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		return $result ?: 0;
	}

	/**
	 * Get log statistics for a form
	 *
	 * Returns aggregated statistics about API calls.
	 *
	 * @since 1.1.0
	 * @param int|null $form_id Form ID (0 or null for all forms)
	 * @return array Statistics array
	 */
	public function get_statistics( ?int $form_id ): array {
		global $wpdb;

		if ( null !== $form_id && $form_id > 0 ) {
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
		} else {
			// Get statistics for all forms
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
		}

		return $stats ?: array(
			"total_requests"      => 0,
			"successful_requests" => 0,
			"failed_requests"     => 0,
			"avg_execution_time"  => 0,
			"max_retries"         => 0,
		);
	}
}
