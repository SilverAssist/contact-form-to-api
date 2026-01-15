<?php
/**
 * Manual Retry Traceability Integration Tests
 *
 * Tests the new retry traceability features added in version 1.3.8.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Tests\Integration
 * @since 1.3.8
 */

namespace SilverAssist\ContactFormToAPI\Tests\Integration;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use WP_UnitTestCase;

/**
 * Class RetryTraceabilityTest
 *
 * Integration tests for manual retry traceability functionality.
 *
 * @since 1.3.8
 */
class RetryTraceabilityTest extends WP_UnitTestCase {

	/**
	 * Set up before class - runs ONCE before any tests
	 * CRITICAL: Use this for CREATE TABLE to avoid MySQL implicit COMMIT
	 */
	public static function wpSetUpBeforeClass(): void {
		parent::wpSetUpBeforeClass();

		// Create tables BEFORE inserting any test data
		Activator::create_tables();
	}

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Clean logs table before each test
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_name ) );
	}

	/**
	 * Test get_successful_retry_id() method
	 *
	 * @return void
	 */
	public function test_get_successful_retry_id_returns_correct_id(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create original failed request
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 100,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_count'  => 3,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		// Create first retry (also failed)
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 101,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_of'     => 100,
				'retry_count'  => 0,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		// Create second retry (success!)
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 102,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'retry_of'      => 100,
				'retry_count'   => 0,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		$logger = new RequestLogger();
		$result = $logger->get_successful_retry_id( 100 );

		$this->assertSame( 102, $result, 'Should return ID of successful retry' );
	}

	/**
	 * Test get_successful_retry_id() returns null when no successful retry exists
	 *
	 * @return void
	 */
	public function test_get_successful_retry_id_returns_null_when_no_success(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create original failed request
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 200,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_count'  => 3,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		// Create retry that also failed
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 201,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_of'     => 200,
				'retry_count'  => 0,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		$logger = new RequestLogger();
		$result = $logger->get_successful_retry_id( 200 );

		$this->assertNull( $result, 'Should return null when no successful retry exists' );
	}

	/**
	 * Test has_successful_retry() method
	 *
	 * @return void
	 */
	public function test_has_successful_retry_returns_true_when_successful(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create original failed request
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 300,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_count'  => 3,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		// Create successful retry
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 301,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'retry_of'      => 300,
				'retry_count'   => 0,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		$logger = new RequestLogger();
		$result = $logger->has_successful_retry( 300 );

		$this->assertTrue( $result, 'Should return true when successful retry exists' );
	}

	/**
	 * Test has_successful_retry() returns false when no successful retry
	 *
	 * @return void
	 */
	public function test_has_successful_retry_returns_false_when_not_successful(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create original failed request with no retries
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 400,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_count'  => 3,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$logger = new RequestLogger();
		$result = $logger->has_successful_retry( 400 );

		$this->assertFalse( $result, 'Should return false when no retry exists' );
	}

	/**
	 * Test get_retries_for_log() method
	 *
	 * @return void
	 */
	public function test_get_retries_for_log_returns_all_retries(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create original failed request
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 500,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_count'  => 3,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		// Create first retry (failed)
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 501,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_of'     => 500,
				'retry_count'  => 0,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		// Create second retry (success)
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 502,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'retry_of'      => 500,
				'retry_count'   => 0,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		$logger  = new RequestLogger();
		$retries = $logger->get_retries_for_log( 500 );

		$this->assertIsArray( $retries, 'Should return an array' );
		$this->assertCount( 2, $retries, 'Should return 2 retry entries' );
		$this->assertSame( '501', $retries[0]['id'], 'First retry should have ID 501' );
		$this->assertSame( 'error', $retries[0]['status'], 'First retry should have error status' );
		$this->assertSame( '502', $retries[1]['id'], 'Second retry should have ID 502' );
		$this->assertSame( 'success', $retries[1]['status'], 'Second retry should have success status' );
	}

	/**
	 * Test get_retries_for_log() returns empty array when no retries
	 *
	 * @return void
	 */
	public function test_get_retries_for_log_returns_empty_array_when_no_retries(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create original failed request with no retries
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 600,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_count'  => 3,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$logger  = new RequestLogger();
		$retries = $logger->get_retries_for_log( 600 );

		$this->assertIsArray( $retries, 'Should return an array' );
		$this->assertEmpty( $retries, 'Should return empty array when no retries exist' );
	}
}
