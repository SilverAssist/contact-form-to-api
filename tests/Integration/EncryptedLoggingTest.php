<?php
/**
 * Encrypted Logging Integration Tests
 *
 * Tests the complete flow of encryption/decryption in the logging system.
 * Verifies that data is encrypted on storage and decrypted on retrieval.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.3.0
 * @version 1.3.0
 * @author  Silver Assist
 */

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test file uses safe table names.

namespace SilverAssist\ContactFormToAPI\Tests\Integration;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Core\EncryptionService;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use SilverAssist\ContactFormToAPI\Services\ExportService;
use WP_UnitTestCase;

/**
 * Test cases for encrypted logging integration
 */
class EncryptedLoggingTest extends WP_UnitTestCase {

	/**
	 * Request logger instance
	 *
	 * @var RequestLogger
	 */
	private RequestLogger $logger;

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
	 * @param \WP_UnitTest_Factory $factory Test factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
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

		// Enable encryption.
		\update_option( 'cf7_api_global_settings', array( 'encryption_enabled' => true ) );

		$this->encryption = EncryptionService::instance();
		$this->encryption->init();

		$this->logger = new RequestLogger();
	}

	/**
	 * Tear down after each test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Clean up test logs.
		$wpdb->query( "DELETE FROM {$table_name}" );

		// Clean up settings.
		\delete_option( 'cf7_api_global_settings' );

		parent::tearDown();
	}

	/**
	 * Test log is stored encrypted in database
	 *
	 * @return void
	 */
	public function test_log_stored_encrypted(): void {
		$form_id  = 123;
		$endpoint = 'https://api.example.com/test';
		$method   = 'POST';
		$data     = array(
			'email' => 'test@example.com',
			'phone' => '555-1234',
		);

		// Start request.
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data );

		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );

		// Read raw data directly from database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$raw_log    = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ),
			ARRAY_A
		);

		// Verify encryption_version is set.
		$this->assertEquals( 1, $raw_log['encryption_version'], 'Encryption version should be 1' );

		// Verify request_data is NOT readable JSON (it's encrypted).
		$decoded = \json_decode( $raw_log['request_data'], true );
		$this->assertNull( $decoded, 'Encrypted data should not be valid JSON' );

		// Verify sensitive data is not visible in raw ciphertext.
		$this->assertStringNotContainsString( 'test@example.com', $raw_log['request_data'], 'Email should not be visible in encrypted data' );
		$this->assertStringNotContainsString( '555-1234', $raw_log['request_data'], 'Phone should not be visible in encrypted data' );
	}

	/**
	 * Test log can be decrypted correctly
	 *
	 * @return void
	 */
	public function test_log_decrypts_correctly(): void {
		$form_id  = 123;
		$endpoint = 'https://api.example.com/test';
		$method   = 'POST';
		$data     = array(
			'name'  => 'John Doe',
			'email' => 'john@example.com',
		);

		// Start request.
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data );

		// Get log and decrypt it.
		$log = $this->logger->get_log( $log_id );
		$log = $this->logger->decrypt_log_fields( $log );

		// Verify decryption worked.
		$this->assertIsArray( $log );
		$this->assertEquals( $log_id, $log['id'] );

		// Verify request_data is now valid JSON.
		$decoded = \json_decode( $log['request_data'], true );
		$this->assertIsArray( $decoded, 'Decrypted data should be valid JSON' );

		// Verify data matches original.
		$this->assertEquals( 'John Doe', $decoded['name'] );
		$this->assertEquals( 'john@example.com', $decoded['email'] );
	}

	/**
	 * Test retry uses decrypted data
	 *
	 * @return void
	 */
	public function test_retry_uses_decrypted_data(): void {
		$form_id  = 123;
		$endpoint = 'https://api.example.com/test';
		$method   = 'POST';
		$data     = array(
			'email' => 'retry@example.com',
		);

		// Create a failed log entry.
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data );

		// Complete with error.
		$this->logger->complete_request( new \WP_Error( 'test_error', 'Test error message' ), 0 );

		// Get request for retry.
		$retry_data = $this->logger->get_request_for_retry( $log_id );

		// Verify retry data contains decrypted information.
		$this->assertIsArray( $retry_data );
		$this->assertEquals( $endpoint, $retry_data['url'] );
		$this->assertEquals( $method, $retry_data['method'] );

		// Verify body contains original data (decrypted).
		$this->assertIsArray( $retry_data['body'] );
		$this->assertEquals( 'retry@example.com', $retry_data['body']['email'] );
	}

	/**
	 * Test response data is encrypted
	 *
	 * @return void
	 */
	public function test_response_data_encrypted(): void {
		$form_id  = 123;
		$endpoint = 'https://api.example.com/test';
		$method   = 'POST';
		$data     = array( 'test' => 'data' );

		// Start request.
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data );

		// Mock response with sensitive data.
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => \wp_json_encode(
				array(
					'user_id' => 12345,
					'token'   => 'secret_token_12345',
				)
			),
			'headers'  => array(),
		);

		// Complete request.
		$this->logger->complete_request( $response, 0 );

		// Read raw data from database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$raw_log    = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ),
			ARRAY_A
		);

		// Verify response_data is encrypted.
		$this->assertStringNotContainsString( 'secret_token_12345', $raw_log['response_data'], 'Token should not be visible in encrypted response' );

		// Decrypt and verify.
		$log     = $this->logger->decrypt_log_fields( $raw_log );
		$decoded = \json_decode( $log['response_data'], true );

		$this->assertEquals( 'secret_token_12345', $decoded['token'], 'Decrypted response should contain token' );
	}

	/**
	 * Test export decrypts data before sanitizing
	 *
	 * @return void
	 */
	public function test_export_decrypts_before_sanitizing(): void {
		$form_id  = 123;
		$endpoint = 'https://api.example.com/test';
		$method   = 'POST';
		$data     = array(
			'email'    => 'export@example.com',
			'password' => 'secret123',
		);

		// Create log entry.
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data );

		// Complete successfully.
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '{"success": true}',
			'headers'  => array(),
		);
		$this->logger->complete_request( $response, 0 );

		// Get log from database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$logs       = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ),
			ARRAY_A
		);

		// Export to JSON.
		$export_service = ExportService::instance();
		$json           = $export_service->export_json( $logs );

		// Verify JSON is valid.
		$exported = \json_decode( $json, true );
		$this->assertIsArray( $exported );
		$this->assertCount( 1, $exported );

		// Verify password was sanitized (redacted).
		$request_data = \json_decode( $exported[0]['request_data'], true );
		$this->assertEquals( '***REDACTED***', $request_data['password'], 'Password should be redacted in export' );

		// Verify email is still present.
		$this->assertEquals( 'export@example.com', $request_data['email'], 'Email should be present in export' );
	}

	/**
	 * Test legacy plaintext logs still work
	 *
	 * @return void
	 */
	public function test_legacy_plaintext_logs_work(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert a legacy plaintext log directly.
		$plaintext_data = \wp_json_encode(
			array(
				'email' => 'legacy@example.com',
			)
		);

		$wpdb->insert(
			$table_name,
			array(
				'form_id'            => 123,
				'endpoint'           => 'https://api.example.com/legacy',
				'method'             => 'POST',
				'status'             => 'success',
				'request_data'       => $plaintext_data,
				'request_headers'    => '{}',
				'encryption_version' => 0, // No encryption.
				'created_at'         => \current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$log_id = $wpdb->insert_id;

		// Try to get and decrypt.
		$log = $this->logger->get_log( $log_id );
		$log = $this->logger->decrypt_log_fields( $log );

		// Verify plaintext is returned as-is.
		$decoded = \json_decode( $log['request_data'], true );
		$this->assertIsArray( $decoded );
		$this->assertEquals( 'legacy@example.com', $decoded['email'] );
	}

	/**
	 * Test encryption performance for list page
	 *
	 * Verify decrypting 25 logs meets performance requirements (< 50ms).
	 *
	 * @return void
	 */
	public function test_list_page_decryption_performance(): void {
		// Create 25 encrypted logs.
		for ( $i = 0; $i < 25; $i++ ) {
			$this->logger->start_request(
				123,
				'https://api.example.com/test',
				'POST',
				array( 'test' => "data_{$i}" )
			);
		}

		// Get all logs.
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$logs       = $wpdb->get_results( "SELECT * FROM {$table_name} LIMIT 25", ARRAY_A );

		$this->assertCount( 25, $logs );

		// Measure decryption time.
		$start = \microtime( true );

		foreach ( $logs as $log ) {
			$this->logger->decrypt_log_fields( $log );
		}

		$elapsed = ( \microtime( true ) - $start ) * 1000; // Convert to milliseconds.

		// Should complete in less than 50ms.
		$this->assertLessThan( 50, $elapsed, "Decrypting 25 logs took {$elapsed}ms (expected < 50ms)" );
	}
}
