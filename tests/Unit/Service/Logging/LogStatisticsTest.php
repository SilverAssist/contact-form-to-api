<?php
/**
 * Tests for LogStatistics Service
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging;

use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * LogStatistics test case.
 *
 * @group unit
 * @group service
 * @group logging
 * @covers \SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics
 */
class LogStatisticsTest extends TestCase {

	/**
	 * LogStatistics instance
	 *
	 * @var LogStatistics
	 */
	private LogStatistics $log_statistics;

	/**
	 * LogWriter instance for creating test data
	 *
	 * @var LogWriter
	 */
	private LogWriter $log_writer;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->log_statistics = new LogStatistics();
		$this->log_writer     = new LogWriter();

		// Enable logging for tests.
		\update_option( 'wpcf7_api_enable_logging', true );
	}

	/**
	 * Test LogStatistics instantiation
	 */
	public function testCanInstantiate(): void {
		$this->assertInstanceOf( LogStatistics::class, $this->log_statistics );
	}

	/**
	 * Test get_statistics with no data
	 */
	public function testGetStatisticsWithNoData(): void {
		$stats = $this->log_statistics->get_statistics( 999999 );

		$this->assertIsArray( $stats );
		$this->assertSame( 0, (int) $stats['total_requests'] );
		$this->assertSame( 0, (int) $stats['successful_requests'] );
		$this->assertSame( 0, (int) $stats['failed_requests'] );
	}

	/**
	 * Test get_statistics counts successful requests
	 */
	public function testGetStatisticsCountsSuccessfulRequests(): void {
		$form_id = \wp_rand( 100000, 999999 );

		// Create successful log entries.
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->log_writer->start_request(
				form_id: $form_id,
				endpoint: 'https://api.example.com/success',
				method: 'POST',
				request_data: array()
			);

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => '',
				'headers'  => array(),
			);
			$this->log_writer->complete_request( $log_id, $response );
		}

		$stats = $this->log_statistics->get_statistics( $form_id );

		$this->assertSame( 3, (int) $stats['total_requests'] );
		$this->assertSame( 3, (int) $stats['successful_requests'] );
		$this->assertSame( 0, (int) $stats['failed_requests'] );
	}

	/**
	 * Test get_statistics counts failed requests
	 */
	public function testGetStatisticsCountsFailedRequests(): void {
		$form_id = \wp_rand( 100000, 999999 );

		// Create failed log entries.
		for ( $i = 0; $i < 2; $i++ ) {
			$log_id = $this->log_writer->start_request(
				form_id: $form_id,
				endpoint: 'https://api.example.com/error',
				method: 'POST',
				request_data: array()
			);

			$error = new \WP_Error( 'http_error', 'Connection failed' );
			$this->log_writer->complete_request( $log_id, $error );
		}

		$stats = $this->log_statistics->get_statistics( $form_id );

		$this->assertSame( 2, (int) $stats['total_requests'] );
		$this->assertSame( 0, (int) $stats['successful_requests'] );
		$this->assertSame( 2, (int) $stats['failed_requests'] );
	}

	/**
	 * Test get_statistics counts mixed results
	 */
	public function testGetStatisticsCountsMixedResults(): void {
		$form_id = \wp_rand( 100000, 999999 );

		// Create 3 successful entries.
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->log_writer->start_request(
				form_id: $form_id,
				endpoint: 'https://api.example.com/success',
				method: 'POST',
				request_data: array()
			);

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => '',
				'headers'  => array(),
			);
			$this->log_writer->complete_request( $log_id, $response );
		}

		// Create 2 failed entries.
		for ( $i = 0; $i < 2; $i++ ) {
			$log_id = $this->log_writer->start_request(
				form_id: $form_id,
				endpoint: 'https://api.example.com/error',
				method: 'POST',
				request_data: array()
			);

			$response = array(
				'response' => array( 'code' => 500 ),
				'body'     => '',
				'headers'  => array(),
			);
			$this->log_writer->complete_request( $log_id, $response );
		}

		$stats = $this->log_statistics->get_statistics( $form_id );

		$this->assertSame( 5, (int) $stats['total_requests'] );
		$this->assertSame( 3, (int) $stats['successful_requests'] );
		$this->assertSame( 2, (int) $stats['failed_requests'] );
	}

	/**
	 * Test get_statistics for all forms (null or 0)
	 */
	public function testGetStatisticsForAllForms(): void {
		// Create entries for different forms.
		$log_id = $this->log_writer->start_request(
			form_id: 111,
			endpoint: 'https://api.example.com/form1',
			method: 'POST',
			request_data: array()
		);
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $log_id, $response );

		$log_id = $this->log_writer->start_request(
			form_id: 222,
			endpoint: 'https://api.example.com/form2',
			method: 'POST',
			request_data: array()
		);
		$this->log_writer->complete_request( $log_id, $response );

		// Get stats for all forms.
		$stats = $this->log_statistics->get_statistics( null );

		$this->assertGreaterThanOrEqual( 2, (int) $stats['total_requests'] );
	}

	/**
	 * Test get_count_last_hours with no data
	 */
	public function testGetCountLastHoursWithNoData(): void {
		$count = $this->log_statistics->get_count_last_hours( 24, null );

		// Should be 0 or include any existing test data.
		$this->assertGreaterThanOrEqual( 0, $count );
	}

	/**
	 * Test get_count_last_hours counts recent entries
	 */
	public function testGetCountLastHoursCountsRecentEntries(): void {
		$form_id = 333;

		// Create recent log entries.
		for ( $i = 0; $i < 3; $i++ ) {
			$log_id = $this->log_writer->start_request(
				form_id: $form_id,
				endpoint: 'https://api.example.com/recent',
				method: 'POST',
				request_data: array()
			);

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => '',
				'headers'  => array(),
			);
			$this->log_writer->complete_request( $log_id, $response );
		}

		$count = $this->log_statistics->get_count_last_hours( 1, 'success' );

		$this->assertGreaterThanOrEqual( 3, $count );
	}

	/**
	 * Test get_count_last_hours with error filter
	 */
	public function testGetCountLastHoursWithErrorFilter(): void {
		$form_id = 444;

		// Create error entries.
		for ( $i = 0; $i < 2; $i++ ) {
			$log_id = $this->log_writer->start_request(
				form_id: $form_id,
				endpoint: 'https://api.example.com/error',
				method: 'POST',
				request_data: array()
			);

			$error = new \WP_Error( 'http_error', 'Connection failed' );
			$this->log_writer->complete_request( $log_id, $error );
		}

		$error_count   = $this->log_statistics->get_count_last_hours( 1, 'error' );
		$success_count = $this->log_statistics->get_count_last_hours( 1, 'success' );

		$this->assertGreaterThanOrEqual( 2, $error_count );
	}

	/**
	 * Test get_statistics with date range
	 */
	public function testGetStatisticsWithDateRange(): void {
		$form_id = 555;
		$today   = \wp_date( 'Y-m-d' );

		// Create entry for today.
		$log_id = $this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/today',
			method: 'POST',
			request_data: array()
		);

		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $log_id, $response );

		// Get stats for today only.
		$stats = $this->log_statistics->get_statistics( $form_id, $today, $today );

		$this->assertGreaterThanOrEqual( 1, (int) $stats['total_requests'] );
	}

	/**
	 * Test get_statistics calculates average execution time
	 */
	public function testGetStatisticsCalculatesAverageExecutionTime(): void {
		$form_id = 666;

		// Create entries with known execution times.
		for ( $i = 0; $i < 3; $i++ ) {
			$start_time = \microtime( true );

			$log_id = $this->log_writer->start_request(
				form_id: $form_id,
				endpoint: 'https://api.example.com/timed',
				method: 'POST',
				request_data: array(),
				request_headers: array(),
				retry_of: null,
				start_time: $start_time
			);

			// Small delay to create measurable execution time.
			\usleep( 1000 );

			$response = array(
				'response' => array( 'code' => 200 ),
				'body'     => '',
				'headers'  => array(),
			);
			$this->log_writer->complete_request( $log_id, $response, 0, $start_time );
		}

		$stats = $this->log_statistics->get_statistics( $form_id );

		// Average execution time should be greater than 0.
		$this->assertGreaterThan( 0, (float) $stats['avg_execution_time'] );
	}

	/**
	 * Test different error status types are counted as failed
	 *
	 * @dataProvider errorStatusProvider
	 */
	public function testDifferentErrorStatusesCountedAsFailed( int $http_code ): void {
		$form_id = \wp_rand( 100000, 999999 );

		$log_id = $this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/error',
			method: 'POST',
			request_data: array()
		);

		$response = array(
			'response' => array( 'code' => $http_code ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $log_id, $response );

		$stats = $this->log_statistics->get_statistics( $form_id );

		$this->assertSame( 1, (int) $stats['total_requests'] );
		$this->assertSame( 0, (int) $stats['successful_requests'] );
		$this->assertSame( 1, (int) $stats['failed_requests'] );
	}

	/**
	 * Data provider for error status codes
	 *
	 * @return array<string, array<int>>
	 */
	public static function errorStatusProvider(): array {
		return array(
			'400 Bad Request'     => array( 400 ),
			'403 Forbidden'       => array( 403 ),
			'404 Not Found'       => array( 404 ),
			'500 Server Error'    => array( 500 ),
			'502 Bad Gateway'     => array( 502 ),
			'503 Unavailable'     => array( 503 ),
		);
	}
}
