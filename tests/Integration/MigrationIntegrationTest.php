<?php
/**
 * Migration Integration Tests
 *
 * Integration tests for the MigrationService AJAX endpoints and full workflow.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.3.4
 * @version 1.3.4
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Integration;

use SilverAssist\ContactFormToAPI\Admin\GlobalSettingsController;
use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Services\MigrationService;
use WP_UnitTestCase;

/**
 * Test cases for migration integration
 */
class MigrationIntegrationTest extends WP_UnitTestCase {

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	private int $admin_user_id;

	/**
	 * Set up before class - runs ONCE before any tests
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

		// Create admin user for capability tests.
		$this->admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		// Enable encryption for tests.
		\update_option( 'cf7_api_global_settings', array( 'encryption_enabled' => true ) );
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
	 * Test full migration workflow
	 *
	 * @return void
	 */
	public function test_full_migration_workflow(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert test logs.
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

		// Initialize service.
		$service = MigrationService::instance();
		$service->init();

		// Start migration.
		$service->start_migration();
		$this->assertTrue( $service->is_migration_running() );

		// Process all logs.
		$result = $service->migrate_batch( 5, false );

		$this->assertSame( 5, $result['processed'] );
		$this->assertSame( 5, $result['success'] );
		$this->assertSame( 0, $result['failed'] );
		$this->assertSame( 0, $result['remaining'] );

		// Complete migration.
		$service->complete_migration();
		$this->assertFalse( $service->is_migration_running() );

		// Verify all logs encrypted.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$unencrypted_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE encryption_version = 0" );
		$this->assertSame( 0, (int) $unencrypted_count );
	}

	/**
	 * Test migration preserves data integrity
	 *
	 * @return void
	 */
	public function test_migration_preserves_data_integrity(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert test log with specific data.
		$test_data = '{"name":"John Doe","email":"john@example.com"}';
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

		// Migrate.
		$service = MigrationService::instance();
		$service->init();
		$service->migrate_batch( 10, false );

		// Get log and decrypt.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		// Verify encryption version updated.
		$this->assertSame( 1, (int) $log['encryption_version'] );

		// Decrypt and verify data integrity.
		$encryption = \SilverAssist\ContactFormToAPI\Core\EncryptionService::instance();
		$encryption->init();

		$decrypted_data = $encryption->decrypt( $log['request_data'] );
		$this->assertSame( $test_data, $decrypted_data );
	}

	/**
	 * Test AJAX start migration requires nonce
	 *
	 * @return void
	 */
	public function test_ajax_start_migration_requires_nonce(): void {
		// Set current user to admin.
		\wp_set_current_user( $this->admin_user_id );

		// Initialize controller.
		$controller = GlobalSettingsController::instance();
		$controller->init();

		// Simulate AJAX request without nonce.
		$_POST['dry_run'] = '0';

		// Capture JSON output.
		\ob_start();
		try {
			$controller->handle_start_migration();
		} catch ( \WPDieException $e ) {
			// Expected exception from wp_send_json_error - intentionally suppressed.
			unset( $e );
		}
		$output = \ob_get_clean();

		// Decode JSON response.
		$response = \json_decode( $output, true );

		// Verify security check failed.
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Security check failed', $response['data']['message'] );
	}

	/**
	 * Test AJAX start migration requires capability
	 *
	 * @return void
	 */
	public function test_ajax_start_migration_requires_capability(): void {
		// Create subscriber user (no manage_options capability).
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		\wp_set_current_user( $subscriber_id );

		// Initialize controller.
		$controller = GlobalSettingsController::instance();
		$controller->init();

		// Simulate AJAX request.
		$_POST['nonce']   = \wp_create_nonce( 'cf7_api_migration' );
		$_POST['dry_run'] = '0';

		// Capture JSON output.
		\ob_start();
		try {
			$controller->handle_start_migration();
		} catch ( \WPDieException $e ) {
			// Expected exception from wp_send_json_error - intentionally suppressed.
			unset( $e );
		}
		$output = \ob_get_clean();

		// Decode JSON response.
		$response = \json_decode( $output, true );

		// Verify permission denied.
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Permission denied', $response['data']['message'] );
	}

	/**
	 * Test migration with large dataset
	 *
	 * @return void
	 */
	public function test_migration_with_large_dataset(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert 250 test logs.
		for ( $i = 0; $i < 250; $i++ ) {
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

		// Initialize service.
		$service = MigrationService::instance();
		$service->init();

		// Process in multiple batches.
		$total_processed = 0;
		$batch_count     = 0;
		$max_batches     = 10; // Safety limit.

		while ( $service->get_unencrypted_count() > 0 && $batch_count < $max_batches ) {
			$result           = $service->migrate_batch( 50, false );
			$total_processed += $result['processed'];
			++$batch_count;
		}

		$this->assertSame( 250, $total_processed );
		$this->assertSame( 0, $service->get_unencrypted_count() );
	}
}
