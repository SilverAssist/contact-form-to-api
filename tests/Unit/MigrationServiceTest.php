<?php
/**
 * Migration Service Tests
 *
 * Tests for the MigrationService class functionality including batch processing,
 * encryption of legacy logs, and progress tracking.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.3.4
 * @version 1.3.4
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Service\Migration\MigrationService;
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;
use WP_UnitTestCase;

/**
 * Test cases for MigrationService class
 */
class MigrationServiceTest extends WP_UnitTestCase {

	/**
	 * Migration service instance
	 *
	 * @var MigrationService
	 */
	private MigrationService $service;

	/**
	 * Encryption service instance
	 *
	 * @var EncryptionService
	 */
	private EncryptionService $encryption;

	/**
	 * Set up before class - runs ONCE before any tests
	 * CRITICAL: Use this for CREATE TABLE to avoid MySQL implicit COMMIT
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// Create tables BEFORE inserting any test data.
		Activator::create_tables();
	}

	/**
	 * Set up before each test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable encryption for tests.
		\update_option( 'cf7_api_global_settings', array( 'encryption_enabled' => true ) );

		$this->service    = MigrationService::instance();
		$this->encryption = EncryptionService::instance();

		$this->service->init();
		$this->encryption->init();
	}

	/**
	 * Tear down after each test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;

		// Clean up test logs.
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		// Clean up settings.
		\delete_option( 'cf7_api_global_settings' );

		// Clean up transients.
		\delete_transient( 'cf7_api_migration_progress' );

		parent::tearDown();
	}

	/**
	 * Test migration service can be instantiated
	 *
	 * @return void
	 */
	public function test_migration_service_can_be_instantiated(): void {
		$this->assertInstanceOf( MigrationService::class, $this->service );
	}

	/**
	 * Test get_unencrypted_count returns correct count
	 *
	 * @return void
	 */
	public function test_get_unencrypted_count_returns_correct_count(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert test logs with encryption_version = 0.
		for ( $i = 0; $i < 5; $i++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->insert(
				$table_name,
				array(
					'form_id'            => 1,
					'endpoint'           => 'https://example.com/api',
					'method'             => 'POST',
					'status'             => 'success',
					'request_data'       => '{"test": "data"}',
					'encryption_version' => 0,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d' )
			);
		}

		$count = $this->service->get_unencrypted_count();
		$this->assertSame( 5, $count );
	}

	/**
	 * Test get_unencrypted_count returns zero when all encrypted
	 *
	 * @return void
	 */
	public function test_get_unencrypted_count_returns_zero_when_all_encrypted(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert test logs with encryption_version = 1.
		for ( $i = 0; $i < 3; $i++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->insert(
				$table_name,
				array(
					'form_id'            => 1,
					'endpoint'           => 'https://example.com/api',
					'method'             => 'POST',
					'status'             => 'success',
					'request_data'       => 'encrypted_data',
					'encryption_version' => 1,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d' )
			);
		}

		$count = $this->service->get_unencrypted_count();
		$this->assertSame( 0, $count );
	}

	/**
	 * Test migrate_batch encrypts logs
	 *
	 * @return void
	 */
	public function test_migrate_batch_encrypts_logs(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert unencrypted test log.
		$test_data = '{"name": "John", "email": "john@example.com"}';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => $test_data,
				'encryption_version' => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		$log_id = $wpdb->insert_id;

		// Migrate batch.
		$result = $this->service->migrate_batch( 10, false );

		$this->assertSame( 1, $result['processed'] );
		$this->assertSame( 1, $result['success'] );
		$this->assertSame( 0, $result['failed'] );

		// Verify log is encrypted.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertSame( 1, (int) $log['encryption_version'] );
		$this->assertNotSame( $test_data, $log['request_data'] );
	}

	/**
	 * Test migrate_batch updates encryption_version
	 *
	 * @return void
	 */
	public function test_migrate_batch_updates_encryption_version(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert unencrypted test log.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => '{"test": "data"}',
				'encryption_version' => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		$log_id = $wpdb->insert_id;

		// Migrate batch.
		$this->service->migrate_batch( 10, false );

		// Verify encryption_version is updated.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT encryption_version FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertSame( 1, (int) $log['encryption_version'] );
	}

	/**
	 * Test migrate_batch skips already encrypted logs
	 *
	 * @return void
	 */
	public function test_migrate_batch_skips_already_encrypted(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert already encrypted test log.
		$encrypted_data = 'already_encrypted_data';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => $encrypted_data,
				'encryption_version' => 1,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		$log_id = $wpdb->insert_id;

		// Migrate batch (should skip encrypted log).
		$result = $this->service->migrate_batch( 10, false );

		$this->assertSame( 0, $result['processed'] );

		// Verify log data unchanged.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertSame( $encrypted_data, $log['request_data'] );
		$this->assertSame( 1, (int) $log['encryption_version'] );
	}

	/**
	 * Test migrate_batch handles null fields
	 *
	 * @return void
	 */
	public function test_migrate_batch_handles_null_fields(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert test log with NULL fields.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => '{"test": "data"}',
				'request_headers'    => null,
				'response_data'      => null,
				'response_headers'   => null,
				'encryption_version' => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		// Migrate batch (should handle NULL fields).
		$result = $this->service->migrate_batch( 10, false );

		$this->assertSame( 1, $result['processed'] );
		$this->assertSame( 1, $result['success'] );
	}

	/**
	 * Test migrate_batch respects batch size
	 *
	 * @return void
	 */
	public function test_migrate_batch_respects_batch_size(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert 10 unencrypted test logs.
		for ( $i = 0; $i < 10; $i++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->insert(
				$table_name,
				array(
					'form_id'            => 1,
					'endpoint'           => 'https://example.com/api',
					'method'             => 'POST',
					'status'             => 'success',
					'request_data'       => '{"test": "data"}',
					'encryption_version' => 0,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d' )
			);
		}

		// Migrate with batch size 5.
		$result = $this->service->migrate_batch( 5, false );

		$this->assertSame( 5, $result['processed'] );
		$this->assertSame( 5, $result['remaining'] );
	}

	/**
	 * Test migrate_batch enforces MAX_BATCH_SIZE limit
	 *
	 * @return void
	 */
	public function test_migrate_batch_enforces_max_batch_size_limit(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert 600 unencrypted test logs (more than MAX_BATCH_SIZE).
		for ( $i = 0; $i < 600; $i++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->insert(
				$table_name,
				array(
					'form_id'            => 1,
					'endpoint'           => 'https://example.com/api',
					'method'             => 'POST',
					'status'             => 'success',
					'request_data'       => '{"test": "data"}',
					'encryption_version' => 0,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d' )
			);
		}

		// Try to migrate with batch size 1000 (above MAX_BATCH_SIZE).
		$result = $this->service->migrate_batch( 1000, false );

		// Should only process 500 (MAX_BATCH_SIZE).
		$this->assertSame( 500, $result['processed'] );
		$this->assertSame( 500, $result['success'] );
		$this->assertSame( 100, $result['remaining'] ); // 600 - 500 = 100.
	}

	/**
	 * Test dry_run does not modify data
	 *
	 * @return void
	 */
	public function test_dry_run_does_not_modify_data(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert unencrypted test log.
		$test_data = '{"test": "data"}';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => $test_data,
				'encryption_version' => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		$log_id = $wpdb->insert_id;

		// Dry run.
		$result = $this->service->migrate_batch( 10, true );

		$this->assertSame( 1, $result['processed'] );
		$this->assertSame( 1, $result['success'] );

		// Verify log data unchanged.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertSame( $test_data, $log['request_data'] );
		$this->assertSame( 0, (int) $log['encryption_version'] );
	}

	/**
	 * Test get_progress returns correct statistics
	 *
	 * @return void
	 */
	public function test_get_progress_returns_correct_statistics(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert mixed logs (encrypted and unencrypted).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => '{"test": "data"}',
				'encryption_version' => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => 'encrypted_data',
				'encryption_version' => 1,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		$progress = $this->service->get_progress();

		$this->assertSame( 2, $progress['total'] );
		$this->assertSame( 1, $progress['encrypted'] );
		$this->assertSame( 1, $progress['unencrypted'] );
		$this->assertSame( 50.0, $progress['percentage'] );
	}

	/**
	 * Test migration handles empty database
	 *
	 * @return void
	 */
	public function test_migration_handles_empty_database(): void {
		$result = $this->service->migrate_batch( 10, false );

		$this->assertSame( 0, $result['processed'] );
		$this->assertSame( 0, $result['success'] );
		$this->assertSame( 0, $result['remaining'] );
	}

	/**
	 * Test migration is idempotent
	 *
	 * @return void
	 */
	public function test_migration_is_idempotent(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert unencrypted test log.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 1,
				'endpoint'           => 'https://example.com/api',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => '{"test": "data"}',
				'encryption_version' => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		$log_id = $wpdb->insert_id;

		// First migration.
		$result1 = $this->service->migrate_batch( 10, false );

		// Second migration (should process nothing).
		$result2 = $this->service->migrate_batch( 10, false );

		$this->assertSame( 1, $result1['processed'] );
		$this->assertSame( 0, $result2['processed'] );

		// Verify log is still encrypted and unchanged.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT encryption_version FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertSame( 1, (int) $log['encryption_version'] );
	}

	/**
	 * Test is_migration_running returns correct status
	 *
	 * @return void
	 */
	public function test_is_migration_running_returns_correct_status(): void {
		$this->assertFalse( $this->service->is_migration_running() );

		// Start migration.
		$this->service->start_migration();
		$this->assertTrue( $this->service->is_migration_running() );

		// Cancel migration.
		$this->service->cancel_migration();
		$this->assertFalse( $this->service->is_migration_running() );
	}
}
