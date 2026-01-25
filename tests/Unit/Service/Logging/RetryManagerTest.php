<?php
/**
 * Tests for RetryManager Service
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * RetryManager test case.
 *
 * @group unit
 * @group service
 * @group logging
 * @covers \SilverAssist\ContactFormToAPI\Service\Logging\RetryManager
 */
class RetryManagerTest extends TestCase {

	/**
	 * RetryManager instance
	 *
	 * @var RetryManager
	 */
	private RetryManager $retry_manager;

	/**
	 * LogWriter instance for creating test data
	 *
	 * @var LogWriter
	 */
	private LogWriter $log_writer;

	/**
	 * Set up before class - create tables once before any tests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		Activator::create_tables();
	}

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->retry_manager = new RetryManager();
		$this->log_writer    = new LogWriter();

		// Enable logging via the correct global settings option.
		$global_settings = \get_option( 'cf7_api_global_settings', array() );
		if ( ! \is_array( $global_settings ) ) {
			$global_settings = array();
		}
		$global_settings['logging_enabled'] = true;
		\update_option( 'cf7_api_global_settings', $global_settings );
	}

	/**
	 * Test RetryManager instantiation
	 */
	public function testCanInstantiate(): void {
		$this->assertInstanceOf( RetryManager::class, $this->retry_manager );
	}

	/**
	 * Test class constants
	 */
	public function testClassConstants(): void {
		$this->assertSame( 3, RetryManager::MAX_MANUAL_RETRIES );
		$this->assertSame( 10, RetryManager::MAX_RETRIES_PER_HOUR );
	}

	/**
	 * Test count_retries returns 0 for non-existent log
	 */
	public function testCountRetriesReturnsZeroForNonExistent(): void {
		$count = $this->retry_manager->count_retries( 999999 );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test count_retries returns 0 for log without retries
	 */
	public function testCountRetriesReturnsZeroForLogWithoutRetries(): void {
		$log_id = $this->log_writer->start_request(
			form_id: 123,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $log_id, $error );

		$count = $this->retry_manager->count_retries( $log_id );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test count_retries counts retry entries
	 */
	public function testCountRetriesCountsRetryEntries(): void {
		// Create original failed entry.
		$original_id = $this->log_writer->start_request(
			form_id: 456,
			endpoint: 'https://api.example.com/error',
			method: 'POST',
			request_data: array( 'test' => 'data' )
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $original_id, $error );

		// Create 2 retry entries.
		for ( $i = 0; $i < 2; $i++ ) {
			$retry_id = $this->log_writer->start_request(
				form_id: 456,
				endpoint: 'https://api.example.com/error',
				method: 'POST',
				request_data: array( 'test' => 'data' ),
				request_headers: array(),
				retry_of: $original_id
			);

			$this->log_writer->complete_request( $retry_id, $error );
		}

		$count = $this->retry_manager->count_retries( $original_id );
		$this->assertSame( 2, $count );
	}

	/**
	 * Test get_retries_for_log returns empty array for non-existent log
	 */
	public function testGetRetriesForLogReturnsEmptyForNonExistent(): void {
		$retries = $this->retry_manager->get_retries_for_log( 999999 );
		$this->assertSame( array(), $retries );
	}

	/**
	 * Test get_retries_for_log returns retry entries
	 */
	public function testGetRetriesForLogReturnsRetryEntries(): void {
		// Create original failed entry.
		$original_id = $this->log_writer->start_request(
			form_id: 789,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $original_id, $error );

		// Create retry entry.
		$retry_id = $this->log_writer->start_request(
			form_id: 789,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $original_id
		);

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $retry_id, $response );

		$retries = $this->retry_manager->get_retries_for_log( $original_id );

		$this->assertCount( 1, $retries );
		$this->assertSame( (string) $retry_id, $retries[0]['id'] );
		$this->assertSame( 'success', $retries[0]['status'] );
	}

	/**
	 * Test has_successful_retry returns false for non-existent log
	 */
	public function testHasSuccessfulRetryReturnsFalseForNonExistent(): void {
		$result = $this->retry_manager->has_successful_retry( 999999 );
		$this->assertFalse( $result );
	}

	/**
	 * Test has_successful_retry returns false when no successful retry
	 */
	public function testHasSuccessfulRetryReturnsFalseWhenNoSuccess(): void {
		// Create original failed entry.
		$original_id = $this->log_writer->start_request(
			form_id: 111,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $original_id, $error );

		// Create failed retry entry.
		$retry_id = $this->log_writer->start_request(
			form_id: 111,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $original_id
		);
		$this->log_writer->complete_request( $retry_id, $error );

		$result = $this->retry_manager->has_successful_retry( $original_id );
		$this->assertFalse( $result );
	}

	/**
	 * Test has_successful_retry returns true when has successful retry
	 */
	public function testHasSuccessfulRetryReturnsTrueWhenHasSuccess(): void {
		// Create original failed entry.
		$original_id = $this->log_writer->start_request(
			form_id: 222,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $original_id, $error );

		// Create successful retry entry.
		$retry_id = $this->log_writer->start_request(
			form_id: 222,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $original_id
		);

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $retry_id, $response );

		$result = $this->retry_manager->has_successful_retry( $original_id );
		$this->assertTrue( $result );
	}

	/**
	 * Test get_successful_retry_id returns null for non-existent
	 */
	public function testGetSuccessfulRetryIdReturnsNullForNonExistent(): void {
		$retry_id = $this->retry_manager->get_successful_retry_id( 999999 );
		$this->assertNull( $retry_id );
	}

	/**
	 * Test get_successful_retry_id returns null when no successful retry
	 */
	public function testGetSuccessfulRetryIdReturnsNullWhenNoSuccess(): void {
		// Create original failed entry.
		$original_id = $this->log_writer->start_request(
			form_id: 333,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $original_id, $error );

		$retry_id = $this->retry_manager->get_successful_retry_id( $original_id );
		$this->assertNull( $retry_id );
	}

	/**
	 * Test get_successful_retry_id returns ID when has successful retry
	 */
	public function testGetSuccessfulRetryIdReturnsIdWhenHasSuccess(): void {
		// Create original failed entry.
		$original_id = $this->log_writer->start_request(
			form_id: 444,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $original_id, $error );

		// Create successful retry entry.
		$expected_retry_id = $this->log_writer->start_request(
			form_id: 444,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $original_id
		);

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $expected_retry_id, $response );

		$retry_id = $this->retry_manager->get_successful_retry_id( $original_id );
		$this->assertSame( $expected_retry_id, $retry_id );
	}

	/**
	 * Test count_errors_by_resolution
	 */
	public function testCountErrorsByResolution(): void {
		// Create error entries that will be resolved.
		$resolved_error_id = $this->log_writer->start_request(
			form_id: 555,
			endpoint: 'https://api.example.com/resolved',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $resolved_error_id, $error );

		// Create successful retry for the resolved error.
		$retry_id = $this->log_writer->start_request(
			form_id: 555,
			endpoint: 'https://api.example.com/resolved',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $resolved_error_id
		);

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $retry_id, $response );

		// Create unresolved error entry.
		$unresolved_error_id = $this->log_writer->start_request(
			form_id: 666,
			endpoint: 'https://api.example.com/unresolved',
			method: 'POST',
			request_data: array()
		);
		$this->log_writer->complete_request( $unresolved_error_id, $error );

		$counts = $this->retry_manager->count_errors_by_resolution();

		$this->assertIsArray( $counts );
		$this->assertArrayHasKey( 'total', $counts );
		$this->assertArrayHasKey( 'resolved', $counts );
		$this->assertArrayHasKey( 'unresolved', $counts );
		$this->assertGreaterThanOrEqual( 2, $counts['total'] );
		$this->assertGreaterThanOrEqual( 1, $counts['resolved'] );
		$this->assertGreaterThanOrEqual( 1, $counts['unresolved'] );
	}

	/**
	 * Test get_resolved_error_ids returns resolved error IDs
	 */
	public function testGetResolvedErrorIdsReturnsResolvedIds(): void {
		// Create error entry that will be resolved.
		$resolved_error_id = $this->log_writer->start_request(
			form_id: 777,
			endpoint: 'https://api.example.com/resolved',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $resolved_error_id, $error );

		// Create successful retry.
		$retry_id = $this->log_writer->start_request(
			form_id: 777,
			endpoint: 'https://api.example.com/resolved',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $resolved_error_id
		);

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $retry_id, $response );

		$resolved_ids = $this->retry_manager->get_resolved_error_ids();

		$this->assertIsArray( $resolved_ids );
		$this->assertContains( $resolved_error_id, $resolved_ids );
	}

	/**
	 * Test get_max_manual_retries static method
	 */
	public function testGetMaxManualRetries(): void {
		$max = RetryManager::get_max_manual_retries();
		$this->assertIsInt( $max );
		$this->assertGreaterThan( 0, $max );
	}

	/**
	 * Test get_max_retries_per_hour static method
	 */
	public function testGetMaxRetriesPerHour(): void {
		$max = RetryManager::get_max_retries_per_hour();
		$this->assertIsInt( $max );
		$this->assertGreaterThan( 0, $max );
	}

	/**
	 * Test retry entries are ordered by created_at ASC
	 */
	public function testGetRetriesForLogOrderedByCreatedAt(): void {
		// Create original failed entry.
		$original_id = $this->log_writer->start_request(
			form_id: 888,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $original_id, $error );

		// Create first retry.
		$first_retry_id = $this->log_writer->start_request(
			form_id: 888,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $original_id
		);
		$this->log_writer->complete_request( $first_retry_id, $error );

		// Delay to ensure different timestamps (created_at has second precision).
		\sleep( 1 );

		// Create second retry.
		$second_retry_id = $this->log_writer->start_request(
			form_id: 888,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array(),
			request_headers: array(),
			retry_of: $original_id
		);
		$this->log_writer->complete_request( $second_retry_id, $error );

		$retries = $this->retry_manager->get_retries_for_log( $original_id );

		$this->assertCount( 2, $retries );
		// First retry should be first in list (ASC order).
		$this->assertSame( (string) $first_retry_id, $retries[0]['id'] );
		$this->assertSame( (string) $second_retry_id, $retries[1]['id'] );
	}
}
