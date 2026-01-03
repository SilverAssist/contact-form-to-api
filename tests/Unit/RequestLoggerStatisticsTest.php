<?php
/**
 * RequestLogger Statistics Tests
 *
 * Tests for the new dashboard statistics methods in RequestLogger class.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.2.0
 * @version 1.2.0
 * @author  Silver Assist
 */

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test file uses safe table names

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use WP_UnitTestCase;

/**
 * Test cases for RequestLogger dashboard statistics methods
 */
class RequestLoggerStatisticsTest extends WP_UnitTestCase {

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
	 * @param \WP_UnitTest_Factory $factory Test factory instance.
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
	 * Test get_count_last_hours returns correct count
	 *
	 * @return void
	 */
	public function test_get_count_last_hours_returns_correct_count(): void {
		$form_id = 123;

		// Create 3 successful requests
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/{$i}", 'POST', 'test data' );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 1 failed request
		$log_id = $this->logger->start_request( $form_id, 'https://example.com/api/fail', 'POST', 'test data' );
		$error  = new \WP_Error( 'http_request_failed', 'Connection failed' );
		$this->logger->complete_request( $error );

		$this->logger = new RequestLogger();

		// Test total count
		$total = $this->logger->get_count_last_hours( 24 );
		$this->assertEquals( 4, $total );

		// Test success count
		$success = $this->logger->get_count_last_hours( 24, 'success' );
		$this->assertEquals( 3, $success );

		// Test error count
		$errors = $this->logger->get_count_last_hours( 24, 'error' );
		$this->assertEquals( 1, $errors );
	}

	/**
	 * Test get_count_last_hours returns zero when no logs exist
	 *
	 * @return void
	 */
	public function test_get_count_last_hours_returns_zero_with_no_logs(): void {
		$count = $this->logger->get_count_last_hours( 24 );
		$this->assertEquals( 0, $count );
	}

	/**
	 * Test get_success_rate_last_hours returns correct percentage
	 *
	 * @return void
	 */
	public function test_get_success_rate_last_hours_returns_correct_percentage(): void {
		$form_id = 123;

		// Create 7 successful requests
		for ( $i = 0; $i < 7; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/{$i}", 'POST', 'test data' );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 3 failed requests
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/fail{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', 'Connection failed' );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// 7 success out of 10 = 70%
		$success_rate = $this->logger->get_success_rate_last_hours( 24 );
		$this->assertEquals( 70.0, $success_rate );
	}

	/**
	 * Test get_success_rate_last_hours returns zero when no logs exist
	 *
	 * @return void
	 */
	public function test_get_success_rate_last_hours_returns_zero_with_no_logs(): void {
		$success_rate = $this->logger->get_success_rate_last_hours( 24 );
		$this->assertEquals( 0.0, $success_rate );
	}

	/**
	 * Test get_avg_response_time_last_hours returns correct average in milliseconds
	 *
	 * @return void
	 */
	public function test_get_avg_response_time_last_hours_returns_correct_average(): void {
		$form_id = 123;

		// Create requests with specific execution times
		// We need to manually set execution times since they're calculated automatically
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		$log_id   = $this->logger->start_request( $form_id, 'https://example.com/api/1', 'POST', 'test data' );
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => \json_encode( array( 'success' => true ) ),
		);
		$this->logger->complete_request( $response );
		// Update execution time to 0.1 seconds (100ms)
		$wpdb->update( $table_name, array( 'execution_time' => 0.1 ), array( 'id' => $log_id ), array( '%f' ), array( '%d' ) );

		$this->logger = new RequestLogger();
		$log_id       = $this->logger->start_request( $form_id, 'https://example.com/api/2', 'POST', 'test data' );
		$this->logger->complete_request( $response );
		// Update execution time to 0.3 seconds (300ms)
		$wpdb->update( $table_name, array( 'execution_time' => 0.3 ), array( 'id' => $log_id ), array( '%f' ), array( '%d' ) );

		$this->logger = new RequestLogger();

		// Average: (0.1 + 0.3) / 2 = 0.2 seconds = 200ms
		$avg_time = $this->logger->get_avg_response_time_last_hours( 24 );
		$this->assertEquals( 200.0, $avg_time );
	}

	/**
	 * Test get_avg_response_time_last_hours returns zero when no logs exist
	 *
	 * @return void
	 */
	public function test_get_avg_response_time_last_hours_returns_zero_with_no_logs(): void {
		$avg_time = $this->logger->get_avg_response_time_last_hours( 24 );
		$this->assertEquals( 0.0, $avg_time );
	}

	/**
	 * Test get_recent_errors returns only error logs
	 *
	 * @return void
	 */
	public function test_get_recent_errors_returns_only_errors(): void {
		$form_id = 123;

		// Create 2 successful requests
		for ( $i = 0; $i < 2; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/success{$i}", 'POST', 'test data' );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 3 failed requests
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/error{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', "Error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		$recent_errors = $this->logger->get_recent_errors( 5 );

		// Should only return the 3 errors, not the successful requests
		$this->assertCount( 3, $recent_errors );

		// Verify all are error status
		foreach ( $recent_errors as $error ) {
			$this->assertContains( $error['status'], array( 'error', 'client_error', 'server_error' ) );
		}
	}

	/**
	 * Test get_recent_errors respects limit parameter
	 *
	 * @return void
	 */
	public function test_get_recent_errors_respects_limit(): void {
		$form_id = 123;

		// Create 10 failed requests
		for ( $i = 0; $i < 10; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/error{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', "Error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Request only 3 errors
		$recent_errors = $this->logger->get_recent_errors( 3 );
		$this->assertCount( 3, $recent_errors );
	}

	/**
	 * Test get_recent_errors returns empty array when no errors exist
	 *
	 * @return void
	 */
	public function test_get_recent_errors_returns_empty_array_with_no_errors(): void {
		$form_id = 123;

		// Create only successful requests
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/{$i}", 'POST', 'test data' );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		$recent_errors = $this->logger->get_recent_errors( 5 );
		$this->assertIsArray( $recent_errors );
		$this->assertEmpty( $recent_errors );
	}

	/**
	 * Test get_recent_errors returns most recent errors first
	 *
	 * @return void
	 */
	public function test_get_recent_errors_returns_most_recent_first(): void {
		$form_id = 123;

		// Create errors with different messages
		for ( $i = 1; $i <= 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/error{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', "Error message {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();

			// Small delay to ensure different timestamps
			\sleep( 1 );
		}

		$recent_errors = $this->logger->get_recent_errors( 5 );

		// Most recent error should be first (Error message 3)
		$this->assertEquals( 'Error message 3', $recent_errors[0]['error_message'] );
		$this->assertEquals( 'Error message 2', $recent_errors[1]['error_message'] );
		$this->assertEquals( 'Error message 1', $recent_errors[2]['error_message'] );
	}
}
