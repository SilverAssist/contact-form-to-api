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
		global $wpdb;
		$form_id    = 123;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create errors with explicit timestamps to ensure ordering
		for ( $i = 1; $i <= 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/error{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', "Error message {$i}" );
			$this->logger->complete_request( $error );

			// Update timestamp directly for predictable ordering (older to newer)
			$timestamp = gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( 4 - $i ) . ' minutes' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				array( 'created_at' => $timestamp ),
				array( 'id' => $log_id ),
				array( '%s' ),
				array( '%d' )
			);

			// Reset logger
			$this->logger = new RequestLogger();
		}

		$recent_errors = $this->logger->get_recent_errors( 5 );

		// Most recent error should be first (Error message 3)
		$this->assertEquals( 'Error message 3', $recent_errors[0]['error_message'] );
		$this->assertEquals( 'Error message 2', $recent_errors[1]['error_message'] );
		$this->assertEquals( 'Error message 1', $recent_errors[2]['error_message'] );
	}

	/**
	 * Test get_recent_errors excludes errors with successful retries
	 *
	 * @return void
	 */
	public function test_get_recent_errors_excludes_errors_with_successful_retries(): void {
		$form_id = 123;

		// Create 2 errors without retries
		for ( $i = 0; $i < 2; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/error{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', "Unretried error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 2 errors WITH successful retries
		for ( $i = 2; $i < 4; $i++ ) {
			// Create original error
			$original_log_id = $this->logger->start_request( $form_id, "https://example.com/api/error{$i}", 'POST', 'test data' );
			$error           = new \WP_Error( 'http_request_failed', "Retried error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();

			// Create successful retry
			$retry_log_id = $this->logger->start_request( $form_id, "https://example.com/api/error{$i}", 'POST', 'test data', array(), $original_log_id );
			$response     = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Get recent errors - should only return the 2 errors WITHOUT successful retries
		$recent_errors = $this->logger->get_recent_errors( 10 );

		$this->assertCount( 2, $recent_errors );

		// Verify returned errors are the unretried ones
		$error_messages = array_column( $recent_errors, 'error_message' );
		$this->assertContains( 'Unretried error 0', $error_messages );
		$this->assertContains( 'Unretried error 1', $error_messages );
		$this->assertNotContains( 'Retried error 2', $error_messages );
		$this->assertNotContains( 'Retried error 3', $error_messages );
	}

	/**
	 * Test get_recent_errors includes errors with only failed retries
	 *
	 * @return void
	 */
	public function test_get_recent_errors_includes_errors_with_only_failed_retries(): void {
		$form_id = 123;

		// Create error with failed retry (should still be included)
		$original_log_id = $this->logger->start_request( $form_id, 'https://example.com/api/error', 'POST', 'test data' );
		$error           = new \WP_Error( 'http_request_failed', 'Original error' );
		$this->logger->complete_request( $error );

		// Reset logger
		$this->logger = new RequestLogger();

		// Create failed retry
		$retry_log_id = $this->logger->start_request( $form_id, 'https://example.com/api/error', 'POST', 'test data', array(), $original_log_id );
		$error2       = new \WP_Error( 'http_request_failed', 'Retry also failed' );
		$this->logger->complete_request( $error2 );

		// Reset logger
		$this->logger = new RequestLogger();

		// Get recent errors - should return the original error since retry was not successful
		$recent_errors = $this->logger->get_recent_errors( 10 );

		$this->assertCount( 1, $recent_errors );
		$this->assertEquals( 'Original error', $recent_errors[0]['error_message'] );
	}

	/**
	 * Test get_count_last_hours excludes retried errors from error count
	 *
	 * @return void
	 */
	public function test_get_count_last_hours_excludes_retried_errors_from_error_count(): void {
		$form_id = 123;

		// Create 3 successful requests
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/success{$i}", 'POST', 'test data' );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 2 errors WITHOUT retries
		for ( $i = 0; $i < 2; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/unretried{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', "Unretried error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 2 errors WITH successful retries
		for ( $i = 0; $i < 2; $i++ ) {
			// Create original error
			$original_log_id = $this->logger->start_request( $form_id, "https://example.com/api/retried{$i}", 'POST', 'test data' );
			$error           = new \WP_Error( 'http_request_failed', "Retried error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();

			// Create successful retry
			$retry_log_id = $this->logger->start_request( $form_id, "https://example.com/api/retried{$i}", 'POST', 'test data', array(), $original_log_id );
			$response     = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Total: 3 success + 2 unretried errors + 2 retried errors + 2 successful retries = 9 total
		$total = $this->logger->get_count_last_hours( 24 );
		$this->assertEquals( 9, $total );

		// Success count: 3 original + 2 successful retries = 5
		$success = $this->logger->get_count_last_hours( 24, 'success' );
		$this->assertEquals( 5, $success );

		// Error count: Should only count the 2 unretried errors (not the 2 with successful retries)
		$errors = $this->logger->get_count_last_hours( 24, 'error' );
		$this->assertEquals( 2, $errors );
	}

	/**
	 * Test get_success_rate_last_hours accounts for retried errors as successes
	 *
	 * @return void
	 */
	public function test_get_success_rate_last_hours_accounts_for_retried_errors(): void {
		$form_id = 123;

		// Create 5 successful requests
		for ( $i = 0; $i < 5; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/success{$i}", 'POST', 'test data' );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 2 errors WITHOUT retries (permanent failures)
		for ( $i = 0; $i < 2; $i++ ) {
			$log_id = $this->logger->start_request( $form_id, "https://example.com/api/unretried{$i}", 'POST', 'test data' );
			$error  = new \WP_Error( 'http_request_failed', "Unretried error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Create 3 errors WITH successful retries (eventual successes)
		for ( $i = 0; $i < 3; $i++ ) {
			// Create original error
			$original_log_id = $this->logger->start_request( $form_id, "https://example.com/api/retried{$i}", 'POST', 'test data' );
			$error           = new \WP_Error( 'http_request_failed', "Retried error {$i}" );
			$this->logger->complete_request( $error );

			// Reset logger
			$this->logger = new RequestLogger();

			// Create successful retry
			$retry_log_id = $this->logger->start_request( $form_id, "https://example.com/api/retried{$i}", 'POST', 'test data', array(), $original_log_id );
			$response     = array(
				'response' => array( 'code' => 200 ),
				'body'     => \json_encode( array( 'success' => true ) ),
			);
			$this->logger->complete_request( $response );

			// Reset logger
			$this->logger = new RequestLogger();
		}

		// Total requests: 5 success + 2 unretried errors + 3 retried errors + 3 successful retries = 13
		// Effective successes: 5 original success + 3 successful retries + 3 retried errors = 11
		// (retried errors count as successes since they eventually succeeded)
		// Success rate: 11/13 = 84.62%
		$success_rate = $this->logger->get_success_rate_last_hours( 24 );
		$this->assertEquals( 84.62, $success_rate );
	}
}
