<?php
/**
 * Migration Service
 *
 * Handles batch migration of unencrypted legacy logs to encrypted format.
 * Provides safe, incremental migration with progress tracking and rollback capability.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Services
 * @since 1.3.4
 * @version 1.3.11
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Services;

use SilverAssist\ContactFormToAPI\Core\EncryptionService;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

\defined( 'ABSPATH' ) || exit;

/**
 * Class MigrationService
 *
 * Service for migrating legacy unencrypted logs to encrypted format.
 *
 * @since 1.3.4
 */
class MigrationService implements LoadableInterface {

	/**
	 * Singleton instance
	 *
	 * @var MigrationService|null
	 */
	private static ?MigrationService $instance = null;

	/**
	 * Maximum number of logs to process in a single migration batch.
	 *
	 * @since 1.3.4
	 */
	private const MAX_BATCH_SIZE = 500;

	/**
	 * Whether the component has been initialized
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Database table name
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
	 * Transient key for migration progress
	 *
	 * @var string
	 */
	private const PROGRESS_TRANSIENT = 'cf7_api_migration_progress';

	/**
	 * Transient expiration time (24 hours)
	 *
	 * @var int
	 */
	private const PROGRESS_EXPIRATION = 86400;

	/**
	 * Get singleton instance
	 *
	 * @return MigrationService
	 */
	public static function instance(): MigrationService {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'cf7_api_logs';
	}

	/**
	 * Initialize the service
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Initialize encryption service if available.
		if ( EncryptionService::is_sodium_available() ) {
			$this->encryption = EncryptionService::instance();
			$this->encryption->init();
		}

		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 20; // Services priority.
	}

	/**
	 * Determine if service should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true; // Always available for admin use.
	}

	/**
	 * Get count of unencrypted logs
	 *
	 * Returns the number of logs with encryption_version = 0.
	 *
	 * @since 1.3.4
	 * @return int Number of unencrypted logs.
	 */
	public function get_unencrypted_count(): int {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE encryption_version = 0', $this->table_name )
		);

		return (int) ( $count ?: 0 );
	}

	/**
	 * Migrate a batch of logs
	 *
	 * Encrypts a batch of unencrypted logs and updates encryption_version.
	 *
	 * @since 1.3.4
	 * @param int  $batch_size Number of logs to process (default: 100).
	 * @param bool $dry_run    Preview only, no changes (default: false).
	 * @return array{processed: int, success: int, failed: int, remaining: int, errors: array<string>} Migration results.
	 */
	public function migrate_batch( int $batch_size = 100, bool $dry_run = false ): array {
		global $wpdb;

		// Validate batch size.
		$batch_size = \max( 1, \min( $batch_size, self::MAX_BATCH_SIZE ) );

		// Check if encryption is available.
		if ( ! $this->encryption ) {
			return array(
				'processed' => 0,
				'success'   => 0,
				'failed'    => 0,
				'remaining' => $this->get_unencrypted_count(),
				'errors'    => array( \__( 'Encryption service not available', 'contact-form-to-api' ) ),
			);
		}

		// Check if encryption is enabled.
		$settings = Settings::instance();
		if ( ! $settings->is_encryption_enabled() ) {
			return array(
				'processed' => 0,
				'success'   => 0,
				'failed'    => 0,
				'remaining' => $this->get_unencrypted_count(),
				'errors'    => array( \__( 'Encryption is disabled in settings', 'contact-form-to-api' ) ),
			);
		}

		// Get batch of unencrypted logs.
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, request_data, request_headers, response_data, response_headers 
				FROM %i 
				WHERE encryption_version = 0 
				ORDER BY id ASC 
				LIMIT %d',
				$this->table_name,
				$batch_size
			),
			ARRAY_A
		);

		if ( empty( $logs ) ) {
			return array(
				'processed' => 0,
				'success'   => 0,
				'failed'    => 0,
				'remaining' => 0,
				'errors'    => array(),
			);
		}

		$processed = 0;
		$success   = 0;
		$failed    = 0;
		$errors    = array();

		foreach ( $logs as $log ) {
			$processed++;

			try {
				// Encrypt fields (handle NULL values).
				$encrypted_data = $this->encrypt_log_fields( $log );

				// In dry-run mode, just count successes without updating.
				if ( $dry_run ) {
					$success++;
					continue;
				}

				// Update log with encrypted data.
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$updated = $wpdb->update(
					$this->table_name,
					array(
						'request_data'       => $encrypted_data['request_data'],
						'request_headers'    => $encrypted_data['request_headers'],
						'response_data'      => $encrypted_data['response_data'],
						'response_headers'   => $encrypted_data['response_headers'],
						'encryption_version' => $this->encryption->get_version(),
					),
					array(
						'id'                 => $log['id'],
						'encryption_version' => 0, // Only update if still unencrypted.
					),
					array( '%s', '%s', '%s', '%s', '%d' ),
					array( '%d', '%d' )
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				if ( false !== $updated ) {
					$success++;
				} else {
					$failed++;
					$errors[] = \sprintf(
						/* translators: %d: log ID */
						\__( 'Failed to update log ID %d', 'contact-form-to-api' ),
						$log['id']
					);
				}
			} catch ( \Exception $e ) {
				$failed++;
				$error_message = \sprintf(
					/* translators: %1$d: log ID, %2$s: error message */
					\__( 'Error encrypting log ID %1$d: %2$s', 'contact-form-to-api' ),
					$log['id'],
					$e->getMessage()
				);
				$errors[] = $error_message;

				// Log to debug logger.
				DebugLogger::instance()->error( $error_message );
			}
		}

		// Get remaining count.
		$remaining = $this->get_unencrypted_count();

		return array(
			'processed' => $processed,
			'success'   => $success,
			'failed'    => $failed,
			'remaining' => $remaining,
			'errors'    => $errors,
		);
	}

	/**
	 * Get migration progress
	 *
	 * Returns current migration progress including statistics.
	 *
	 * @since 1.3.4
	 * @return array{total: int, encrypted: int, unencrypted: int, percentage: float, is_running: bool} Progress data.
	 */
	public function get_progress(): array {
		global $wpdb;

		$total_logs     = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $this->table_name )
		);
		$encrypted_logs = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE encryption_version > 0', $this->table_name )
		);

		$unencrypted_logs = $total_logs - $encrypted_logs;
		$percentage       = $total_logs > 0 ? ( $encrypted_logs / $total_logs ) * 100 : 100;

		return array(
			'total'       => $total_logs,
			'encrypted'   => $encrypted_logs,
			'unencrypted' => $unencrypted_logs,
			'percentage'  => \round( $percentage, 2 ),
			'is_running'  => $this->is_migration_running(),
		);
	}

	/**
	 * Check if migration is in progress
	 *
	 * Checks transient to determine if migration is currently running.
	 *
	 * @since 1.3.4
	 * @return bool True if migration is running.
	 */
	public function is_migration_running(): bool {
		$progress = \get_transient( self::PROGRESS_TRANSIENT );
		return false !== $progress;
	}

	/**
	 * Start migration
	 *
	 * Initializes migration by setting transient.
	 *
	 * @since 1.3.4
	 * @return bool True on success.
	 */
	public function start_migration(): bool {
		$progress = array(
			'started_at' => \current_time( 'mysql' ),
			'status'     => 'running',
		);

		return \set_transient( self::PROGRESS_TRANSIENT, $progress, self::PROGRESS_EXPIRATION );
	}

	/**
	 * Cancel migration
	 *
	 * Cancels ongoing migration by deleting transient.
	 *
	 * @since 1.3.4
	 * @return bool True on success.
	 */
	public function cancel_migration(): bool {
		return \delete_transient( self::PROGRESS_TRANSIENT );
	}

	/**
	 * Complete migration
	 *
	 * Marks migration as complete and cleans up transient.
	 *
	 * @since 1.3.4
	 * @return bool True on success.
	 */
	public function complete_migration(): bool {
		return \delete_transient( self::PROGRESS_TRANSIENT );
	}

	/**
	 * Encrypt log fields
	 *
	 * Helper method to encrypt sensitive log fields, handling NULL values.
	 *
	 * @since 1.3.4
	 * @param array<string, mixed> $log Log entry data.
	 * @return array<string, mixed> Log entry with encrypted fields.
	 */
	private function encrypt_log_fields( array $log ): array {
		// Encryption service must be available at this point.
		if ( null === $this->encryption ) {
			return $log;
		}

		$fields_to_encrypt = array( 'request_data', 'request_headers', 'response_data', 'response_headers' );
		$encrypted_data    = array();

		foreach ( $fields_to_encrypt as $field ) {
			$value                    = $log[ $field ] ?? null;
			$encrypted_data[ $field ] = null !== $value ? $this->encryption->encrypt( $value ) : $value;
		}

		return $encrypted_data;
	}
}
