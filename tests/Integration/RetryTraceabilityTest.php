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
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

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

	/**
	 * Test count_errors_by_resolution() returns correct counts
	 *
	 * @return void
	 * @since 1.3.14
	 */
	public function test_count_errors_by_resolution_returns_correct_counts(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create unresolved error (no retry)
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 700,
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

		// Create another unresolved error (retry also failed)
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 701,
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

		// Create failed retry for 701 (still unresolved)
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 702,
				'form_id'      => 1,
				'endpoint'     => 'https://api.example.com/test',
				'method'       => 'POST',
				'status'       => 'error',
				'request_data' => '{}',
				'retry_of'     => 701,
				'retry_count'  => 0,
				'created_at'   => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		// Create resolved error (has successful retry)
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 703,
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

		// Create successful retry for 703 (resolved)
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 704,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'retry_of'      => 703,
				'retry_count'   => 0,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		// Create a success log (should not be counted as error)
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 705,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$logger = new RequestLogger();
		$result = $logger->count_errors_by_resolution();

		$this->assertIsArray( $result, 'Should return an array' );
		$this->assertArrayHasKey( 'total', $result, 'Should have total key' );
		$this->assertArrayHasKey( 'resolved', $result, 'Should have resolved key' );
		$this->assertArrayHasKey( 'unresolved', $result, 'Should have unresolved key' );

		// Total errors (originals only): 700, 701, 703 = 3 (702 is a retry of 701 and not counted)
		$this->assertSame( 3, $result['total'], 'Should count 3 total errors' );
		// Resolved: only 703 has successful retry
		$this->assertSame( 1, $result['resolved'], 'Should count 1 resolved error' );
		// Unresolved: 700, 701 = 2
		$this->assertSame( 2, $result['unresolved'], 'Should count 2 unresolved errors' );
	}

	/**
	 * Test count_errors_by_resolution() returns zeros when no errors
	 *
	 * @return void
	 * @since 1.3.14
	 */
	public function test_count_errors_by_resolution_returns_zeros_when_no_errors(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create only success logs
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 800,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$logger = new RequestLogger();
		$result = $logger->count_errors_by_resolution();

		$this->assertSame( 0, $result['total'], 'Should count 0 total errors' );
		$this->assertSame( 0, $result['resolved'], 'Should count 0 resolved errors' );
		$this->assertSame( 0, $result['unresolved'], 'Should count 0 unresolved errors' );
	}

	/**
	 * Test get_resolved_error_ids() returns correct IDs
	 *
	 * @return void
	 * @since 1.3.14
	 */
	public function test_get_resolved_error_ids_returns_correct_ids(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create unresolved error
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 900,
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

		// Create resolved error #1
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 901,
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

		// Successful retry for 901
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 902,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'retry_of'      => 901,
				'retry_count'   => 0,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		// Create resolved error #2
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 903,
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

		// Successful retry for 903
		$wpdb->insert(
			$table_name,
			array(
				'id'            => 904,
				'form_id'       => 1,
				'endpoint'      => 'https://api.example.com/test',
				'method'        => 'POST',
				'status'        => 'success',
				'request_data'  => '{}',
				'response_code' => 200,
				'retry_of'      => 903,
				'retry_count'   => 0,
				'created_at'    => \current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		$logger = new RequestLogger();
		$result = $logger->get_resolved_error_ids();

		$this->assertIsArray( $result, 'Should return an array' );
		$this->assertCount( 2, $result, 'Should return 2 resolved error IDs' );
		$this->assertContains( 901, $result, 'Should contain error ID 901' );
		$this->assertContains( 903, $result, 'Should contain error ID 903' );
		$this->assertNotContains( 900, $result, 'Should not contain unresolved error ID 900' );
	}

	/**
	 * Test get_resolved_error_ids() returns empty array when no resolved errors
	 *
	 * @return void
	 * @since 1.3.14
	 */
	public function test_get_resolved_error_ids_returns_empty_when_no_resolved(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Create only unresolved errors
		$wpdb->insert(
			$table_name,
			array(
				'id'           => 1000,
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
		$result = $logger->get_resolved_error_ids();

		$this->assertIsArray( $result, 'Should return an array' );
		$this->assertEmpty( $result, 'Should return empty array when no resolved errors' );
	}
}
