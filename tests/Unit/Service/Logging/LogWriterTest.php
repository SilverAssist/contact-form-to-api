<?php
/**
 * Tests for LogWriter Service
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * LogWriter test case.
 *
 * @group unit
 * @group service
 * @group logging
 * @covers \SilverAssist\ContactFormToAPI\Service\Logging\LogWriter
 */
class LogWriterTest extends TestCase {

	/**
	 * LogWriter instance
	 *
	 * @var LogWriter
	 */
	private LogWriter $log_writer;

	/**
	 * Set up before class - create tables once before any tests.
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		Activator::create_tables();
	}

	/**
	 * Set up test environment
	 */
	public function set_up(): void {
		parent::set_up();
		$this->log_writer = new LogWriter();

		// Enable logging via the correct global settings option.
		$global_settings = \get_option( 'cf7_api_global_settings', array() );
		if ( ! \is_array( $global_settings ) ) {
			$global_settings = array();
		}
		$global_settings['logging_enabled'] = true;
		\update_option( 'cf7_api_global_settings', $global_settings );
	}

	/**
	 * Test LogWriter instantiation
	 */
	public function testCanInstantiate(): void {
		$this->assertInstanceOf( LogWriter::class, $this->log_writer );
	}

	/**
	 * Test start_request with valid data
	 */
	public function testStartRequestWithValidData(): void {
		$log_id = $this->log_writer->start_request(
			form_id: 123,
			endpoint: 'https://api.example.com/webhook',
			method: 'POST',
			request_data: array(
				'name'  => 'Test User',
				'email' => 'test@example.com',
			),
			request_headers: array( 'Content-Type' => 'application/json' )
		);

		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );

		// Verify the log entry was created.
		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$log_id
			),
			ARRAY_A
		);

		$this->assertNotNull( $log );
		$this->assertSame( '123', $log['form_id'] );
		$this->assertSame( 'https://api.example.com/webhook', $log['endpoint'] );
		$this->assertSame( 'POST', $log['method'] );
		$this->assertSame( 'pending', $log['status'] );
	}

	/**
	 * Test start_request creates log entry successfully
	 *
	 * Note: The logging enabled check uses Settings::instance()->is_logging_enabled()
	 * which defaults to true when settings are not available in test environment.
	 * This test verifies the actual log creation functionality.
	 */
	public function testStartRequestCreatesLogEntry(): void {
		$log_id = $this->log_writer->start_request(
			form_id: 123,
			endpoint: 'https://api.example.com/webhook',
			method: 'POST',
			request_data: array( 'test' => 'data' )
		);

		// Log should be created since logging defaults to enabled.
		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );
	}

	/**
	 * Test start_request with retry_of parameter
	 */
	public function testStartRequestWithRetryOf(): void {
		\update_option( 'wpcf7_api_enable_logging', true );

		// Create initial log entry.
		$original_log_id = $this->log_writer->start_request(
			form_id: 456,
			endpoint: 'https://api.example.com/submit',
			method: 'POST',
			request_data: array( 'field' => 'value' )
		);

		// Create retry entry.
		$retry_log_id = $this->log_writer->start_request(
			form_id: 456,
			endpoint: 'https://api.example.com/submit',
			method: 'POST',
			request_data: array( 'field' => 'value' ),
			request_headers: array(),
			retry_of: $original_log_id
		);

		$this->assertIsInt( $retry_log_id );
		$this->assertNotSame( $original_log_id, $retry_log_id );

		// Verify retry_of was set.
		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT retry_of FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$retry_log_id
			),
			ARRAY_A
		);

		$this->assertSame( (string) $original_log_id, $log['retry_of'] );
	}

	/**
	 * Test complete_request with successful response
	 */
	public function testCompleteRequestWithSuccess(): void {
		\update_option( 'wpcf7_api_enable_logging', true );

		$start_time = \microtime( true );

		$log_id = $this->log_writer->start_request(
			form_id: 789,
			endpoint: 'https://api.example.com/success',
			method: 'POST',
			request_data: array( 'data' => 'test' )
		);

		// Simulate successful response.
		$response = array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => \wp_json_encode( array( 'success' => true ) ),
			'headers'  => array( 'Content-Type' => 'application/json' ),
		);

		$result = $this->log_writer->complete_request(
			log_id: $log_id,
			response: $response,
			retry_count: 0,
			start_time: $start_time
		);

		$this->assertTrue( $result );

		// Verify log was updated.
		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT status, response_code FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$log_id
			),
			ARRAY_A
		);

		$this->assertSame( 'success', $log['status'] );
		$this->assertSame( '200', $log['response_code'] );
	}

	/**
	 * Test complete_request with WP_Error
	 */
	public function testCompleteRequestWithWPError(): void {
		\update_option( 'wpcf7_api_enable_logging', true );

		$log_id = $this->log_writer->start_request(
			form_id: 111,
			endpoint: 'https://api.example.com/error',
			method: 'POST',
			request_data: array( 'data' => 'test' )
		);

		$error = new \WP_Error( 'http_request_failed', 'Connection timed out' );

		$result = $this->log_writer->complete_request(
			log_id: $log_id,
			response: $error,
			retry_count: 0
		);

		$this->assertTrue( $result );

		// Verify log was updated with error.
		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT status, error_message FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$log_id
			),
			ARRAY_A
		);

		$this->assertSame( 'error', $log['status'] );
		$this->assertSame( 'Connection timed out', $log['error_message'] );
	}

	/**
	 * Test complete_request with client error (4xx)
	 */
	public function testCompleteRequestWithClientError(): void {
		\update_option( 'wpcf7_api_enable_logging', true );

		$log_id = $this->log_writer->start_request(
			form_id: 222,
			endpoint: 'https://api.example.com/bad-request',
			method: 'POST',
			request_data: array( 'data' => 'test' )
		);

		$response = array(
			'response' => array(
				'code'    => 400,
				'message' => 'Bad Request',
			),
			'body'     => \wp_json_encode( array( 'error' => 'Invalid data' ) ),
			'headers'  => array(),
		);

		$result = $this->log_writer->complete_request(
			log_id: $log_id,
			response: $response
		);

		$this->assertTrue( $result );

		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT status, response_code FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$log_id
			),
			ARRAY_A
		);

		$this->assertSame( 'client_error', $log['status'] );
		$this->assertSame( '400', $log['response_code'] );
	}

	/**
	 * Test complete_request with server error (5xx)
	 */
	public function testCompleteRequestWithServerError(): void {
		\update_option( 'wpcf7_api_enable_logging', true );

		$log_id = $this->log_writer->start_request(
			form_id: 333,
			endpoint: 'https://api.example.com/server-error',
			method: 'POST',
			request_data: array( 'data' => 'test' )
		);

		$response = array(
			'response' => array(
				'code'    => 500,
				'message' => 'Internal Server Error',
			),
			'body'     => '',
			'headers'  => array(),
		);

		$result = $this->log_writer->complete_request(
			log_id: $log_id,
			response: $response
		);

		$this->assertTrue( $result );

		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT status, response_code FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$log_id
			),
			ARRAY_A
		);

		$this->assertSame( 'server_error', $log['status'] );
		$this->assertSame( '500', $log['response_code'] );
	}

	/**
	 * Test header anonymization (verifies authorization headers are redacted)
	 *
	 * Note: Headers may be encrypted in the database, so we test the anonymization
	 * logic by checking that sensitive data is not stored in plain text.
	 */
	public function testHeaderAnonymization(): void {
		$log_id = $this->log_writer->start_request(
			form_id: \wp_rand( 100000, 999999 ),
			endpoint: 'https://api.example.com/auth',
			method: 'POST',
			request_data: array( 'test' => 'data' ),
			request_headers: array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer secret-token-12345',
				'X-Custom'      => 'custom-value',
			)
		);

		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );

		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT request_headers FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$log_id
			),
			ARRAY_A
		);

		// Headers should be stored (either encrypted or as JSON).
		$this->assertNotNull( $log['request_headers'] );
		$this->assertNotEmpty( $log['request_headers'] );

		// The raw secret token should never appear in stored data.
		$this->assertStringNotContainsString( 'secret-token-12345', $log['request_headers'] );
	}

	/**
	 * Test different HTTP methods
	 *
	 * @dataProvider httpMethodProvider
	 */
	public function testDifferentHttpMethods( string $method ): void {
		\update_option( 'wpcf7_api_enable_logging', true );

		$log_id = $this->log_writer->start_request(
			form_id: 555,
			endpoint: 'https://api.example.com/resource',
			method: $method,
			request_data: array( 'key' => 'value' )
		);

		$this->assertIsInt( $log_id );

		global $wpdb;
		$log = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT method FROM %i WHERE id = %d',
				$wpdb->prefix . 'cf7_api_logs',
				$log_id
			),
			ARRAY_A
		);

		$this->assertSame( $method, $log['method'] );
	}

	/**
	 * Data provider for HTTP methods
	 *
	 * @return array<string, array<string>>
	 */
	public static function httpMethodProvider(): array {
		return array(
			'GET method'    => array( 'GET' ),
			'POST method'   => array( 'POST' ),
			'PUT method'    => array( 'PUT' ),
			'PATCH method'  => array( 'PATCH' ),
			'DELETE method' => array( 'DELETE' ),
		);
	}
}
