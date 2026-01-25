<?php

/**
 * Integration Tests for Form Submission Logging
 *
 * Tests the complete flow of form submission and logging functionality,
 * including mock API responses and log verification.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.3.0
 * @version 1.3.1
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Integration;

use SilverAssist\ContactFormToAPI\Config\Settings;
use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use SilverAssist\ContactFormToAPI\Service\Api\ApiClient;
use SilverAssist\ContactFormToAPI\Tests\Helpers\CF7TestCase;

/**
 * Test cases for Form Submission Logging Integration
 */
class FormSubmissionLoggingTest extends CF7TestCase {

	/**
	 * Logger instance
	 *
	 * @var RequestLogger|null
	 */
	protected ?RequestLogger $logger;

	/**
	 * Original pre_http_request filter callbacks
	 *
	 * @var array
	 */
	protected array $original_http_filters = array();

	/**
	 * Setup method for logging tests
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Ensure tables exist
		if ( function_exists( '\\dbDelta' ) || class_exists( Activator::class ) ) {
			Activator::create_tables();
		}

		// Initialize logger
		$this->logger = new RequestLogger();

		// Ensure logging is enabled in settings
		$this->enable_logging();
	}

	/**
	 * Teardown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Clean up test logs
		$this->cleanup_test_logs();

		// Restore HTTP filters
		$this->restore_http_filters();

		parent::tearDown();
	}

	/**
	 * Enable logging in settings
	 *
	 * @return void
	 */
	protected function enable_logging(): void {
		if ( class_exists( Settings::class ) ) {
			$settings = Settings::instance();
			$settings->set( 'logging_enabled', true );
		}
	}

	/**
	 * Disable logging in settings
	 *
	 * @return void
	 */
	protected function disable_logging(): void {
		if ( class_exists( Settings::class ) ) {
			$settings = Settings::instance();
			$settings->set( 'logging_enabled', false );
		}
	}

	/**
	 * Mock HTTP request to return a successful response
	 *
	 * @param int    $status_code Response status code.
	 * @param array  $body        Response body.
	 * @param string $url_pattern Optional URL pattern to match.
	 * @return void
	 */
	protected function mock_http_response( int $status_code = 200, array $body = array(), string $url_pattern = '' ): void {
		$response = array(
			'response' => array(
				'code'    => $status_code,
				'message' => $status_code === 200 ? 'OK' : 'Error',
			),
			'body'     => \wp_json_encode( $body ),
			'headers'  => array(
				'content-type' => 'application/json',
			),
		);

		\add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $response, $url_pattern ) {
				if ( empty( $url_pattern ) || strpos( $url, $url_pattern ) !== false ) {
					return $response;
				}
				return $preempt;
			},
			10,
			3
		);
	}

	/**
	 * Mock HTTP request to simulate an error
	 *
	 * @param string $error_message Error message.
	 * @param string $error_code    Error code.
	 * @return void
	 */
	protected function mock_http_error( string $error_message = 'Connection failed', string $error_code = 'http_request_failed' ): void {
		\add_filter(
			'pre_http_request',
			function () use ( $error_message, $error_code ) {
				return new \WP_Error( $error_code, $error_message );
			},
			10,
			3
		);
	}

	/**
	 * Restore HTTP filters
	 *
	 * @return void
	 */
	protected function restore_http_filters(): void {
		\remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Cleanup test logs from database
	 *
	 * @return void
	 */
	protected function cleanup_test_logs(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Delete logs from test forms (form_id starting with 999)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE form_id >= 99900', $table_name ) );
	}

	/**
	 * Get logs for a specific form
	 *
	 * @param int $form_id Form ID.
	 * @return array
	 */
	protected function get_logs_for_form( int $form_id ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE form_id = %d ORDER BY id DESC',
				$table_name,
				$form_id
			),
			ARRAY_A
		);
	}

	/**
	 * Test that logs are created when API request is successful
	 *
	 * @return void
	 */
	public function test_logs_created_on_successful_api_request(): void {
		// Skip if WordPress functions not available
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		// Mock successful API response
		$this->mock_http_response(
			200,
			array(
				'success' => true,
				'message' => 'Data received',
			)
		);

		// Test form ID
		$form_id = 99901;

		// Start a log entry
		$log_id = $this->logger->start_request(
			$form_id,
			'https://api.example.com/webhook',
			'POST',
			array(
				'name'  => 'Test User',
				'email' => 'test@example.com',
			),
			array( 'Content-Type' => 'application/json' )
		);

		$this->assertNotFalse( $log_id, 'Log entry should be created' );
		$this->assertIsInt( $log_id, 'Log ID should be an integer' );

		// Simulate response
		$response = array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => '{"success":true}',
			'headers'  => array( 'content-type' => 'application/json' ),
		);

		// Complete the log entry
		$completed = $this->logger->complete_request( $response, 0 );

		$this->assertTrue( $completed, 'Log entry should be completed successfully' );

		// Verify log was saved
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'At least one log should exist' );
		$this->assertEquals( 'success', $logs[0]['status'], 'Log status should be success' );
		$this->assertEquals( 200, $logs[0]['response_code'], 'Response code should be 200' );
	}

	/**
	 * Test that logs are created when API request fails
	 *
	 * @return void
	 */
	public function test_logs_created_on_failed_api_request(): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$form_id = 99902;

		// Start a log entry
		$log_id = $this->logger->start_request(
			$form_id,
			'https://api.example.com/webhook',
			'POST',
			array( 'name' => 'Test User' ),
			array( 'Content-Type' => 'application/json' )
		);

		$this->assertNotFalse( $log_id, 'Log entry should be created' );

		// Simulate error response
		$error = new \WP_Error( 'http_request_failed', 'Connection timeout' );

		// Complete the log entry with error
		$completed = $this->logger->complete_request( $error, 0 );

		$this->assertTrue( $completed, 'Log entry should be completed' );

		// Verify log was saved with error
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'At least one log should exist' );
		$this->assertEquals( 'error', $logs[0]['status'], 'Log status should be error' );
		$this->assertStringContainsString( 'Connection timeout', $logs[0]['error_message'], 'Error message should be saved' );
	}

	/**
	 * Test that logs record execution time
	 *
	 * @return void
	 */
	public function test_logs_record_execution_time(): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$form_id = 99903;

		// Start a log entry
		$log_id = $this->logger->start_request(
			$form_id,
			'https://api.example.com/webhook',
			'POST',
			array( 'data' => 'test' ),
			array()
		);

		// Simulate some processing time
		usleep( 50000 ); // 50ms

		// Complete the log
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '{}',
			'headers'  => array(),
		);
		$this->logger->complete_request( $response, 0 );

		// Verify execution time was recorded
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'Log should exist' );
		$this->assertGreaterThan( 0, (float) $logs[0]['execution_time'], 'Execution time should be recorded' );
	}

	/**
	 * Test that logs are NOT created when logging is disabled
	 *
	 * @return void
	 */
	public function test_logs_not_created_when_logging_disabled(): void {
		if ( ! function_exists( '\\update_option' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		// Disable logging
		$this->disable_logging();

		// Need to create new logger instance to pick up settings change
		$logger = new RequestLogger();

		$form_id = 99904;

		// Try to start a log entry
		$log_id = $logger->start_request(
			$form_id,
			'https://api.example.com/webhook',
			'POST',
			array( 'data' => 'test' ),
			array()
		);

		$this->assertFalse( $log_id, 'Log entry should NOT be created when logging is disabled' );

		// Verify no log was saved
		$logs = $this->get_logs_for_form( $form_id );
		$this->assertEmpty( $logs, 'No logs should exist when logging is disabled' );

		// Re-enable logging for other tests
		$this->enable_logging();
	}

	/**
	 * Test logs record retry count
	 *
	 * @return void
	 */
	public function test_logs_record_retry_count(): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$form_id = 99905;

		// Start a log entry
		$log_id = $this->logger->start_request(
			$form_id,
			'https://api.example.com/webhook',
			'POST',
			array( 'data' => 'test' ),
			array()
		);

		// Complete with retry count
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '{}',
			'headers'  => array(),
		);
		$this->logger->complete_request( $response, 2 );

		// Verify retry count was recorded
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'Log should exist' );
		$this->assertEquals( 2, (int) $logs[0]['retry_count'], 'Retry count should be 2' );
	}

	/**
	 * Test full ApiClient flow with mocked HTTP
	 *
	 * @return void
	 */
	public function test_api_client_creates_logs_on_send(): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		// Mock successful HTTP response
		$this->mock_http_response(
			200,
			array( 'status' => 'received' )
		);

		$form_id = 99906;

		// Use ApiClient to send request
		$client = ApiClient::instance();
		$client->init();

		$response = $client->send(
			array(
				'url'          => 'https://api.example.com/test',
				'method'       => 'POST',
				'body'         => array(
					'name'  => 'Test',
					'email' => 'test@example.com',
				),
				'headers'      => array( 'X-Custom-Header' => 'value' ),
				'content_type' => 'json',
				'form_id'      => $form_id,
				'retry_config' => array(
					'max_retries' => 0,
				),
			)
		);

		// Response should not be an error
		$this->assertNotInstanceOf( \WP_Error::class, $response, 'Response should not be an error' );

		// Verify log was created
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'Log should be created by ApiClient' );
		$this->assertEquals( 'success', $logs[0]['status'], 'Log status should be success' );
		$this->assertEquals( 'POST', $logs[0]['method'], 'Method should be POST' );
		$this->assertStringContainsString( 'api.example.com', $logs[0]['endpoint'], 'Endpoint should be recorded' );
	}

	/**
	 * Test that error responses create logs with error status
	 *
	 * @return void
	 */
	public function test_api_client_logs_error_responses(): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		// Mock HTTP error
		$this->mock_http_error( 'Connection refused', 'http_request_failed' );

		$form_id = 99907;

		$client = ApiClient::instance();
		$client->init();

		$response = $client->send(
			array(
				'url'          => 'https://api.example.com/test',
				'method'       => 'POST',
				'body'         => array( 'data' => 'test' ),
				'content_type' => 'json',
				'form_id'      => $form_id,
				'retry_config' => array(
					'max_retries' => 0,
				),
			)
		);

		// Response should be a WP_Error
		$this->assertInstanceOf( \WP_Error::class, $response, 'Response should be a WP_Error' );

		// Verify log was created with error
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'Error log should be created' );
		$this->assertEquals( 'error', $logs[0]['status'], 'Log status should be error' );
	}

	/**
	 * Test different HTTP status codes are logged correctly
	 *
	 * @dataProvider http_status_code_provider
	 * @param int    $status_code     HTTP status code.
	 * @param string $expected_status Expected log status.
	 * @return void
	 */
	public function test_http_status_codes_logged_correctly( int $status_code, string $expected_status ): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$this->restore_http_filters();
		$this->mock_http_response( $status_code, array( 'code' => $status_code ) );

		// Use unique form_id for each status code
		$form_id = 99910 + $status_code;

		$client = ApiClient::instance();
		$client->init();

		$client->send(
			array(
				'url'          => 'https://api.example.com/test',
				'method'       => 'POST',
				'body'         => array(),
				'content_type' => 'json',
				'form_id'      => $form_id,
				'retry_config' => array( 'max_retries' => 0 ),
			)
		);

		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, "Log should exist for status {$status_code}" );
		$this->assertEquals( $expected_status, $logs[0]['status'], "Status for {$status_code} should be {$expected_status}" );
		$this->assertEquals( $status_code, (int) $logs[0]['response_code'], "Response code should be {$status_code}" );
	}

	/**
	 * Data provider for HTTP status codes
	 *
	 * @return array
	 */
	public static function http_status_code_provider(): array {
		return array(
			'200 OK'                    => array( 200, 'success' ),
			'201 Created'               => array( 201, 'success' ),
			'204 No Content'            => array( 204, 'success' ),
			'400 Bad Request'           => array( 400, 'client_error' ),
			'401 Unauthorized'          => array( 401, 'client_error' ),
			'403 Forbidden'             => array( 403, 'client_error' ),
			'404 Not Found'             => array( 404, 'client_error' ),
			'500 Internal Server Error' => array( 500, 'server_error' ),
			'502 Bad Gateway'           => array( 502, 'server_error' ),
			'503 Service Unavailable'   => array( 503, 'server_error' ),
		);
	}

	/**
	 * Test that request data is stored correctly
	 *
	 * @return void
	 */
	public function test_request_data_stored_correctly(): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$form_id      = 99908;
		$request_data = array(
			'name'    => 'John Doe',
			'email'   => 'john@example.com',
			'message' => 'Test message content',
		);
		$headers      = array(
			'Content-Type'    => 'application/json',
			'X-Custom-Header' => 'custom-value',
		);

		// Start log
		$this->logger->start_request(
			$form_id,
			'https://api.example.com/submit',
			'POST',
			$request_data,
			$headers
		);

		// Complete log
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '{"received":true}',
			'headers'  => array( 'content-type' => 'application/json' ),
		);
		$this->logger->complete_request( $response, 0 );

		// Get and verify log
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'Log should exist' );

		// Request data should be stored (may be encrypted)
		$this->assertNotEmpty( $logs[0]['request_data'], 'Request data should be stored' );

		// Endpoint should match
		$this->assertEquals( 'https://api.example.com/submit', $logs[0]['endpoint'], 'Endpoint should match' );

		// Method should match
		$this->assertEquals( 'POST', $logs[0]['method'], 'Method should match' );
	}

	/**
	 * Test that response data is stored correctly
	 *
	 * @return void
	 */
	public function test_response_data_stored_correctly(): void {
		if ( ! function_exists( '\\add_filter' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$form_id = 99909;

		// Start log
		$this->logger->start_request(
			$form_id,
			'https://api.example.com/submit',
			'POST',
			array( 'test' => 'data' ),
			array()
		);

		// Complete with specific response
		$response = array(
			'response' => array(
				'code'    => 201,
				'message' => 'Created',
			),
			'body'     => '{"id":12345,"status":"created"}',
			'headers'  => array(
				'content-type' => 'application/json',
				'x-request-id' => 'abc123',
			),
		);
		$this->logger->complete_request( $response, 0 );

		// Get and verify log
		$logs = $this->get_logs_for_form( $form_id );

		$this->assertNotEmpty( $logs, 'Log should exist' );
		$this->assertEquals( 201, (int) $logs[0]['response_code'], 'Response code should be 201' );
		$this->assertNotEmpty( $logs[0]['response_data'], 'Response data should be stored' );
	}
}
