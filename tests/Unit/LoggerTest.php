<?php
/**
 * Logger Tests
 *
 * Tests for the Logger class functionality including request logging,
 * response logging, retry tracking, and data anonymization.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.0.0
 * @version 1.0.0
 * @author  Silver Assist
 */

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test file uses safe table names

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use WP_UnitTestCase;

/**
 * Test cases for RequestLogger class
 */
class LoggerTest extends WP_UnitTestCase {

	/**
	 * Logger instance
	 *
	 * @var RequestLogger
	 */
	private $logger;

	/**
	 * Set up before class - runs ONCE before any tests
	 * CRITICAL: Use this for CREATE TABLE to avoid MySQL implicit COMMIT
	 *
	 * @param WP_UnitTest_Factory $factory Test factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		// Create tables BEFORE inserting any test data
		Activator::create_tables();
	}

	/**
	 * Set up before each test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
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

		// Clean up test logs
		$wpdb->query( "DELETE FROM {$table_name}" );

		parent::tearDown();
	}

	/**
	 * Test logger instantiation
	 *
	 * @return void
	 */
	public function test_logger_can_be_instantiated(): void {
		$this->assertInstanceOf( RequestLogger::class, $this->logger );
	}

	/**
	 * Test starting a request log
	 *
	 * @return void
	 */
	public function test_start_request_creates_log_entry(): void {
		$form_id  = 123;
		$endpoint = 'https://example.com/api/endpoint';
		$method   = 'POST';
		$data     = array(
			'name'  => 'John Doe',
			'email' => 'john@example.com',
		);
		$headers  = array( 'Content-Type' => 'application/json' );

		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data, $headers );

		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );

		// Verify log was created in database
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$log        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertNotNull( $log );
		$this->assertEquals( $form_id, $log['form_id'] );
		$this->assertEquals( $endpoint, $log['endpoint'] );
		$this->assertEquals( $method, $log['method'] );
		$this->assertEquals( 'pending', $log['status'] );
	}

	/**
	 * Test completing a request with successful response
	 *
	 * @return void
	 */
	public function test_complete_request_with_success(): void {
		$form_id = 123;
		$log_id  = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test data' );

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode(
				array(
					'success' => true,
					'id'      => 456,
				)
			),
			'headers'  => array( 'content-type' => 'application/json' ),
		);

		$result = $this->logger->complete_request( $response );

		$this->assertTrue( $result );

		// Verify log was updated
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$log        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertEquals( 'success', $log['status'] );
		$this->assertEquals( 200, $log['response_code'] );
		$this->assertNotEmpty( $log['response_data'] );
		$this->assertGreaterThan( 0, (float) $log['execution_time'] );
	}

	/**
	 * Test completing a request with error response
	 *
	 * @return void
	 */
	public function test_complete_request_with_error(): void {
		$form_id = 123;
		$log_id  = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test data' );

		$error = new \WP_Error( 'http_request_failed', 'Connection timeout' );

		$result = $this->logger->complete_request( $error );

		$this->assertTrue( $result );

		// Verify log was updated
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$log        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertEquals( 'error', $log['status'] );
		$this->assertEquals( 'Connection timeout', $log['error_message'] );
		$this->assertGreaterThan( 0, (float) $log['execution_time'] );
	}

	/**
	 * Test logging retry attempts
	 *
	 * @return void
	 */
	public function test_log_retry_updates_count(): void {
		$form_id = 123;
		$log_id  = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test data' );

		$result = $this->logger->log_retry( 1 );
		$this->assertTrue( $result );

		$result = $this->logger->log_retry( 2 );
		$this->assertTrue( $result );

		// Verify retry count was updated
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$log        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertEquals( 2, $log['retry_count'] );
	}

	/**
	 * Test data anonymization for sensitive fields
	 *
	 * @return void
	 */
	public function test_sensitive_data_is_anonymized(): void {
		$form_id = 123;
		$data    = array(
			'name'     => 'John Doe',
			'email'    => 'john@example.com',
			'password' => 'secret123',
			'api_key'  => 'abc123xyz',
		);

		$log_id = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', $data );

		// Verify sensitive data was anonymized
		global $wpdb;
		$table_name   = $wpdb->prefix . 'cf7_api_logs';
		$log          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );
		$request_data = json_decode( $log['request_data'], true );

		$this->assertEquals( 'John Doe', $request_data['name'] );
		$this->assertEquals( 'john@example.com', $request_data['email'] );
		$this->assertEquals( '***REDACTED***', $request_data['password'] );
		$this->assertEquals( '***REDACTED***', $request_data['api_key'] );
	}

	/**
	 * Test getting recent logs
	 *
	 * @return void
	 */
	public function test_get_recent_logs_returns_logs(): void {
		$form_id = 123;

		// Create multiple logs
		for ( $i = 0; $i < 5; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/{$i}", 'POST', "test data {$i}" );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger to start fresh
			$this->logger = new RequestLogger();
		}

		// Get recent logs
		$logs = $this->logger->get_recent_logs( $form_id, 3 );

		$this->assertCount( 3, $logs );
		$this->assertEquals( $form_id, $logs[0]['form_id'] );
	}

	/**
	 * Test getting statistics
	 *
	 * @return void
	 */
	public function test_get_statistics_returns_correct_data(): void {
		$form_id = 123;

		// Create successful requests
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/{$i}", 'POST', 'test data' );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create failed request
		$log_id = $this->logger->start_request( $form_id, 'https://example.com/api/fail', 'POST', 'test data' );
		$error  = new \WP_Error( 'http_request_failed', 'Connection failed' );
		$this->logger->complete_request( $error );

		// Get statistics
		$stats = $this->logger->get_statistics( $form_id );

		$this->assertEquals( 4, $stats['total_requests'] );
		$this->assertEquals( 3, $stats['successful_requests'] );
		$this->assertEquals( 1, $stats['failed_requests'] );
		$this->assertGreaterThan( 0, (float) $stats['avg_execution_time'] );
	}

	/**
	 * Test cleaning old logs
	 *
	 * @return void
	 */
	public function test_clean_old_logs_removes_old_entries(): void {
		$form_id = 123;

		// Create a log entry
		$log_id = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test data' );

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'success' => true ) ),
		);
		$this->logger->complete_request( $response );

		// Manually update created_at to be old
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$wpdb->update(
			$table_name,
			array( 'created_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-31 days' ) ) ),
			array( 'id' => $log_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Clean logs older than 30 days
		$deleted = $this->logger->clean_old_logs( 30 );

		$this->assertGreaterThan( 0, $deleted );

		// Verify log was deleted
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );
		$this->assertNull( $log );
	}

	/**
	 * Test response code status determination
	 *
	 * @return void
	 */
	public function test_status_is_determined_correctly_from_response_code(): void {
		$form_id = 123;

		// Test successful status (2xx)
		$log_id = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test' );
		$this->logger->complete_request(
			array(
				'response' => array( 'code' => 201 ),
				'body'     => '',
			)
		);

		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$log        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );
		$this->assertEquals( 'success', $log['status'] );

		// Test client error (4xx)
		$this->logger = new RequestLogger();
		$log_id       = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test' );
		$this->logger->complete_request(
			array(
				'response' => array( 'code' => 404 ),
				'body'     => '',
			)
		);
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );
		$this->assertEquals( 'client_error', $log['status'] );

		// Test server error (5xx)
		$this->logger = new RequestLogger();
		$log_id       = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test' );
		$this->logger->complete_request(
			array(
				'response' => array( 'code' => 500 ),
				'body'     => '',
			)
		);
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );
		$this->assertEquals( 'server_error', $log['status'] );
	}

	/**
	 * Test header anonymization
	 *
	 * @return void
	 */
	public function test_sensitive_headers_are_anonymized(): void {
		$form_id = 123;
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer secret_token_123',
			'X-API-Key'     => 'my_api_key_456',
		);

		$log_id = $this->logger->start_request( $form_id, 'https://example.com/api', 'POST', 'test', $headers );

		// Verify sensitive headers were anonymized
		global $wpdb;
		$table_name      = $wpdb->prefix . 'cf7_api_logs';
		$log             = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ), ARRAY_A );
		$request_headers = json_decode( $log['request_headers'], true );

		$this->assertEquals( 'application/json', $request_headers['Content-Type'] );
		$this->assertEquals( '***REDACTED***', $request_headers['Authorization'] );
		$this->assertEquals( '***REDACTED***', $request_headers['X-API-Key'] );
	}
}
