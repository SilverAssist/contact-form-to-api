<?php
/**
 * Log Writer Service
 *
 * Handles creation and updating of API request logs.
 * Extracted from RequestLogger as part of Phase 2 refactoring.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Service\Logging
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Service\Logging;

use SilverAssist\ContactFormToAPI\Config\Settings;
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;
use SilverAssist\ContactFormToAPI\Service\Security\SensitiveDataPatterns;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

\defined( 'ABSPATH' ) || exit;

/**
 * Class LogWriter
 *
 * Responsible for creating and updating log entries in the database.
 * Handles encryption, anonymization, and status management.
 *
 * @since 2.0.0
 */
class LogWriter {

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
	 * Start logging an API request
	 *
	 * Creates initial log entry before sending request.
	 * Stores original request body data for retry functionality.
	 * Only redacts authorization headers for security.
	 *
	 * @since 2.0.0
	 * @param int                    $form_id         Contact Form 7 form ID.
	 * @param string                 $endpoint        API endpoint URL.
	 * @param string                 $method          HTTP method (GET, POST, etc.).
	 * @param mixed                  $request_data    Request body data.
	 * @param array<string, string>  $request_headers Request headers.
	 * @param int|null               $retry_of        Original log ID if this is a retry.
	 * @param float|null             $start_time      Request start time (microtime). If not provided, current time is used.
	 * @return int|false Log entry ID or false on failure.
	 */
	public function start_request( int $form_id, string $endpoint, string $method, $request_data, array $request_headers = array(), ?int $retry_of = null, ?float $start_time = null ) {
		// Check if logging is enabled via settings.
		if ( ! $this->is_logging_enabled() ) {
			return false;
		}

		global $wpdb;

		// Use provided start time or current time.
		if ( null === $start_time ) {
			$start_time = \microtime( true );
		}

		// Store original request body data (needed for retry functionality).
		// Only redact authorization headers for security.
		$anonymized_headers = $this->anonymize_headers( $request_headers );

		// Prepare data for storage.
		$prepared_data    = \is_string( $request_data ) ? $request_data : \wp_json_encode( $request_data );
		$prepared_headers = \wp_json_encode( $anonymized_headers );

		// Encrypt sensitive data if encryption is available.
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

		// Add retry_of if this is a retry attempt.
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
			return $wpdb->insert_id ?: false;
		}

		return false;
	}

	/**
	 * Complete logging with response data
	 *
	 * Updates log entry with response information and execution time.
	 *
	 * @since 2.0.0
	 * @param int                               $log_id      Log entry ID.
	 * @param array<string, mixed>|\WP_Error $response    API response or error.
	 * @param int|null                          $retry_count Number of retry attempts.
	 * @param float|null                        $start_time  Request start time for calculating execution time. If not provided, execution time will be 0.
	 * @return bool True on success, false on failure.
	 */
	public function complete_request( int $log_id, $response, ?int $retry_count = 0, ?float $start_time = null ): bool {
		global $wpdb;

		$execution_time = $start_time ? \microtime( true ) - $start_time : 0;

		// Handle WP_Error responses.
		if ( \is_wp_error( $response ) ) {
			$update_data = array(
				'status'         => 'error',
				'error_message'  => $response->get_error_message(),
				'execution_time' => $execution_time,
				'retry_count'    => $retry_count,
			);
			$format      = array( '%s', '%s', '%f', '%d' );
		} else {
			// Handle successful responses.
			$response_code    = \wp_remote_retrieve_response_code( $response );
			$response_body    = \wp_remote_retrieve_body( $response );
			$response_headers = \wp_remote_retrieve_headers( $response );

			// Store original response body data.
			// Only redact authorization headers for security.
			$anonymized_headers = $this->anonymize_headers( $response_headers );

			// Determine status based on response code.
			$status = $this->determine_status( $response_code );

			// Encode response data if it's an array.
			$encoded_body    = \is_array( $response_body ) ? \wp_json_encode( $response_body ) : $response_body;
			$encoded_headers = \wp_json_encode( $anonymized_headers );

			// Encrypt sensitive response data if encryption is available.
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
			array( 'id' => $log_id ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Update retry count for a log entry
	 *
	 * @since 2.0.0
	 * @param int $log_id      Log entry ID.
	 * @param int $retry_count Current retry attempt number.
	 * @return bool True on success, false on failure.
	 */
	public function update_retry_count( int $log_id, int $retry_count ): bool {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'retry_count' => $retry_count ),
			array( 'id' => $log_id ),
			array( '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete old logs
	 *
	 * Removes logs older than specified days.
	 *
	 * @since 2.0.0
	 * @param int $days Number of days to keep logs.
	 * @return int Number of deleted rows.
	 */
	public function delete_old_logs( int $days = 30 ): int {
		global $wpdb;

		$result = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)',
				$this->table_name,
				$days
			)
		);

		return $result ?: 0;
	}

	/**
	 * Anonymize request/response headers
	 *
	 * Removes or masks sensitive headers.
	 *
	 * @since 2.0.0
	 * @param array<string, string>|\WpOrg\Requests\Utility\CaseInsensitiveDictionary $headers Headers to anonymize.
	 * @return array<string, string> Anonymized headers.
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
	 * @since 2.0.0
	 * @param int $code HTTP response code.
	 * @return string Status string.
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
	 * Check if logging is enabled via settings
	 *
	 * @since 2.0.0
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
}
