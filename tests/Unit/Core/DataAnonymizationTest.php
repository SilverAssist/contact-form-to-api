<?php
/**
 * Data Anonymization Tests
 *
 * Tests for data anonymization at presentation layer vs storage layer.
 * Validates that sensitive data is stored in original form for retry functionality
 * but anonymized when displayed in the UI.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.2.1
 * @version 1.2.1
 * @author  Silver Assist
 */

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test file uses safe table names

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Core;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use SilverAssist\ContactFormToAPI\Config\Settings;
use WP_UnitTestCase;

/**
 * Test cases for data anonymization functionality
 */
class DataAnonymizationTest extends WP_UnitTestCase {

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
	 * Test that original request body data is stored without anonymization
	 *
	 * @return void
	 */
	public function test_request_body_stored_without_anonymization(): void {
		$form_id  = 123;
		$endpoint = 'https://example.com/api/endpoint';
		$method   = 'POST';

		// Test data with sensitive fields
		$data = array(
			'firstName'    => 'Miguel',
			'lastName'     => 'Colmenares',
			'primaryEmail' => 'test@test.com',
			'primaryPhone' => '3191234567',
			'postalCode'   => '25003',
		);

		$headers = array( 'Content-Type' => 'application/json' );

		// Start request logging
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data, $headers );
		$this->assertIsInt( $log_id );

		// Retrieve log from database and decrypt if needed
		$log = $this->logger->get_log( $log_id );
		$this->assertNotNull( $log );
		$log = $this->logger->decrypt_log_fields( $log );

		// Decode request data
		$stored_data = \json_decode( $log['request_data'], true );
		$this->assertIsArray( $stored_data );

		// Verify that sensitive data is NOT redacted in storage
		$this->assertSame( 'test@test.com', $stored_data['primaryEmail'], 'Email should be stored without redaction' );
		$this->assertSame( '3191234567', $stored_data['primaryPhone'], 'Phone should be stored without redaction' );

		// Verify non-sensitive data is also stored correctly
		$this->assertSame( 'Miguel', $stored_data['firstName'] );
		$this->assertSame( 'Colmenares', $stored_data['lastName'] );
		$this->assertSame( '25003', $stored_data['postalCode'] );
	}

	/**
	 * Test that authorization headers are still redacted at storage
	 *
	 * @return void
	 */
	public function test_authorization_headers_redacted_at_storage(): void {
		$form_id  = 123;
		$endpoint = 'https://example.com/api/endpoint';
		$method   = 'POST';
		$data     = array( 'name' => 'Test' );

		// Headers with sensitive authorization data
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer secret-token-12345',
			'X-API-Key'     => 'super-secret-api-key',
		);

		// Start request logging
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data, $headers );
		$this->assertIsInt( $log_id );

		// Retrieve log from database and decrypt if needed
		$log = $this->logger->get_log( $log_id );
		$this->assertNotNull( $log );
		$log = $this->logger->decrypt_log_fields( $log );

		// Decode request headers
		$stored_headers = \json_decode( $log['request_headers'], true );
		$this->assertIsArray( $stored_headers );

		// Verify authorization header is redacted
		$this->assertSame( '***REDACTED***', $stored_headers['Authorization'], 'Authorization header should be redacted at storage' );
		$this->assertSame( '***REDACTED***', $stored_headers['X-API-Key'], 'API key header should be redacted at storage' );

		// Verify non-sensitive headers are preserved
		$this->assertSame( 'application/json', $stored_headers['Content-Type'] );
	}

	/**
	 * Test that anonymize_data method correctly redacts sensitive fields
	 *
	 * @return void
	 */
	public function test_anonymize_data_redacts_sensitive_fields(): void {
		$data = array(
			'firstName'    => 'Miguel',
			'lastName'     => 'Colmenares',
			'primaryEmail' => 'test@test.com',
			'primaryPhone' => '3191234567',
			'password'     => 'secret123',
			'api_key'      => 'abc-def-ghi',
		);

		// Anonymize the data using static method
		$anonymized = RequestLogger::anonymize_data( $data );

		// Verify sensitive fields are redacted (only password and api_key are in default patterns)
		$this->assertSame( '***REDACTED***', $anonymized['password'] );
		$this->assertSame( '***REDACTED***', $anonymized['api_key'] );

		// Verify email and phone are NOT redacted (not in default patterns)
		$this->assertSame( 'test@test.com', $anonymized['primaryEmail'] );
		$this->assertSame( '3191234567', $anonymized['primaryPhone'] );

		// Verify non-sensitive fields are preserved
		$this->assertSame( 'Miguel', $anonymized['firstName'] );
		$this->assertSame( 'Colmenares', $anonymized['lastName'] );
	}

	/**
	 * Test that anonymize_data handles nested arrays
	 *
	 * @return void
	 */
	public function test_anonymize_data_handles_nested_arrays(): void {
		$data = array(
			'user' => array(
				'name'     => 'John',
				'email'    => 'john@example.com',
				'password' => 'secret123',
			),
			'meta' => array(
				'phone'  => '1234567890',
				'source' => 'web',
			),
		);

		// Anonymize the data using static method
		$anonymized = RequestLogger::anonymize_data( $data );

		// Verify nested sensitive fields are redacted (only password is in default patterns)
		$this->assertSame( '***REDACTED***', $anonymized['user']['password'] );

		// Verify email and phone are NOT redacted (not in default patterns)
		$this->assertSame( 'john@example.com', $anonymized['user']['email'] );
		$this->assertSame( '1234567890', $anonymized['meta']['phone'] );

		// Verify non-sensitive fields are preserved
		$this->assertSame( 'John', $anonymized['user']['name'] );
		$this->assertSame( 'web', $anonymized['meta']['source'] );
	}

	/**
	 * Test that retry functionality gets original data
	 *
	 * @return void
	 */
	public function test_retry_gets_original_data(): void {
		$form_id  = 123;
		$endpoint = 'https://example.com/api/endpoint';
		$method   = 'POST';

		// Test data with sensitive fields
		$data = array(
			'firstName'    => 'Miguel',
			'primaryEmail' => 'test@test.com',
			'primaryPhone' => '3191234567',
		);

		$headers = array( 'Content-Type' => 'application/json' );

		// Start request logging
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data, $headers );
		$this->assertIsInt( $log_id );

		// Complete with error status to make it retryable
		$error_response = new \WP_Error( 'timeout', 'Request timeout' );
		$this->logger->complete_request( $error_response, 0 );

		// Get request data for retry
		$retry_data = $this->logger->get_request_for_retry( $log_id );
		$this->assertIsArray( $retry_data );

		// Verify that retry data contains original values
		$body = $retry_data['body'];
		$this->assertIsArray( $body );

		$this->assertSame( 'test@test.com', $body['primaryEmail'], 'Retry should use original email' );
		$this->assertSame( '3191234567', $body['primaryPhone'], 'Retry should use original phone' );
		$this->assertSame( 'Miguel', $body['firstName'] );
	}

	/**
	 * Test that original response body data is stored without anonymization
	 *
	 * @return void
	 */
	public function test_response_body_stored_without_anonymization(): void {
		$form_id  = 123;
		$endpoint = 'https://example.com/api/endpoint';
		$method   = 'POST';
		$data     = array( 'name' => 'Test' );
		$headers  = array( 'Content-Type' => 'application/json' );

		// Start request logging
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data, $headers );
		$this->assertIsInt( $log_id );

		// Mock response with sensitive data
		$response_body = \wp_json_encode(
			array(
				'success'       => true,
				'user_email'    => 'response@example.com',
				'contact_phone' => '9876543210',
				'message'       => 'Success',
			)
		);

		$mock_response = array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => $response_body,
			'headers'  => array( 'content-type' => 'application/json' ),
		);

		// Complete request with response
		$this->logger->complete_request( $mock_response, 0 );

		// Retrieve log from database and decrypt if needed
		$log = $this->logger->get_log( $log_id );
		$this->assertNotNull( $log );
		$log = $this->logger->decrypt_log_fields( $log );

		// Decode response data
		$stored_response = \json_decode( $log['response_data'], true );
		$this->assertIsArray( $stored_response );

		// Verify that sensitive data is NOT redacted in storage
		$this->assertSame( 'response@example.com', $stored_response['user_email'], 'Response email should be stored without redaction' );
		$this->assertSame( '9876543210', $stored_response['contact_phone'], 'Response phone should be stored without redaction' );

		// Verify non-sensitive data is also stored correctly
		$this->assertSame( true, $stored_response['success'] );
		$this->assertSame( 'Success', $stored_response['message'] );
	}

	/**
	 * Test that response authorization headers are still redacted at storage
	 *
	 * @return void
	 */
	public function test_response_authorization_headers_redacted_at_storage(): void {
		$form_id  = 123;
		$endpoint = 'https://example.com/api/endpoint';
		$method   = 'POST';
		$data     = array( 'name' => 'Test' );
		$headers  = array( 'Content-Type' => 'application/json' );

		// Start request logging
		$log_id = $this->logger->start_request( $form_id, $endpoint, $method, $data, $headers );
		$this->assertIsInt( $log_id );

		// Mock response with authorization headers
		$mock_response = array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => '{"success":true}',
			'headers'  => array(
				'content-type'  => 'application/json',
				'x-auth-token'  => 'response-token-12345',
				'authorization' => 'Bearer response-bearer',
			),
		);

		// Complete request with response
		$this->logger->complete_request( $mock_response, 0 );

		// Retrieve log from database and decrypt if needed
		$log = $this->logger->get_log( $log_id );
		$this->assertNotNull( $log );
		$log = $this->logger->decrypt_log_fields( $log );

		// Decode response headers
		$stored_headers = \json_decode( $log['response_headers'], true );
		$this->assertIsArray( $stored_headers );

		// Verify authorization headers are redacted
		$this->assertSame( '***REDACTED***', $stored_headers['x-auth-token'], 'Auth token header should be redacted at storage' );
		$this->assertSame( '***REDACTED***', $stored_headers['authorization'], 'Authorization header should be redacted at storage' );

		// Verify non-sensitive headers are preserved
		$this->assertSame( 'application/json', $stored_headers['content-type'] );
	}

	/**
	 * Test backward compatibility with existing anonymized logs
	 *
	 * @return void
	 */
	public function test_backward_compatibility_with_anonymized_logs(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Insert a log entry with already-anonymized data (simulating old behavior)
		$wpdb->insert(
			$table_name,
			array(
				'form_id'         => 123,
				'endpoint'        => 'https://example.com/api',
				'method'          => 'POST',
				'status'          => 'error',
				'request_data'    => \wp_json_encode(
					array(
						'name'  => 'Test',
						'email' => '***REDACTED***',
						'phone' => '***REDACTED***',
					)
				),
				'request_headers' => \wp_json_encode( array( 'Content-Type' => 'application/json' ) ),
				'retry_count'     => 0,
				'created_at'      => \current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$log_id = $wpdb->insert_id;

		// Try to get retry data
		$retry_data = $this->logger->get_request_for_retry( $log_id );
		$this->assertIsArray( $retry_data );

		// Verify that anonymized data is still returned (backward compatible)
		$body = $retry_data['body'];
		$this->assertIsArray( $body );
		$this->assertSame( '***REDACTED***', $body['email'] );
		$this->assertSame( '***REDACTED***', $body['phone'] );
	}
}
