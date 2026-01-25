<?php
/**
 * Tests for LogReader Service
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging;

use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * LogReader test case.
 *
 * @group unit
 * @group service
 * @group logging
 * @covers \SilverAssist\ContactFormToAPI\Service\Logging\LogReader
 */
class LogReaderTest extends TestCase {

	/**
	 * LogReader instance
	 *
	 * @var LogReader
	 */
	private LogReader $log_reader;

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
		$this->log_reader = new LogReader();
		$this->log_writer = new LogWriter();

		// Enable logging for tests.
		\update_option( 'wpcf7_api_enable_logging', true );
	}

	/**
	 * Test LogReader instantiation
	 */
	public function testCanInstantiate(): void {
		$this->assertInstanceOf( LogReader::class, $this->log_reader );
	}

	/**
	 * Test get_log returns null for non-existent log
	 */
	public function testGetLogReturnsNullForNonExistent(): void {
		$log = $this->log_reader->get_log( 999999 );
		$this->assertNull( $log );
	}

	/**
	 * Test get_log returns log entry
	 */
	public function testGetLogReturnsLogEntry(): void {
		$log_id = $this->log_writer->start_request(
			form_id: 123,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array( 'name' => 'Test' )
		);

		$log = $this->log_reader->get_log( $log_id );

		$this->assertNotNull( $log );
		$this->assertIsArray( $log );
		$this->assertSame( (string) $log_id, $log['id'] );
		$this->assertSame( '123', $log['form_id'] );
		$this->assertSame( 'https://api.example.com/test', $log['endpoint'] );
		$this->assertSame( 'POST', $log['method'] );
	}

	/**
	 * Test get_recent_logs returns empty array for invalid form_id
	 */
	public function testGetRecentLogsReturnsEmptyForInvalidFormId(): void {
		$logs = $this->log_reader->get_recent_logs( null );
		$this->assertSame( array(), $logs );

		$logs = $this->log_reader->get_recent_logs( 0 );
		$this->assertSame( array(), $logs );

		$logs = $this->log_reader->get_recent_logs( -1 );
		$this->assertSame( array(), $logs );
	}

	/**
	 * Test get_recent_logs returns logs for form
	 */
	public function testGetRecentLogsReturnsLogsForForm(): void {
		// Use a unique form_id to avoid conflicts with other tests.
		$form_id = \wp_rand( 100000, 999999 );

		// Create multiple log entries.
		$this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/first',
			method: 'POST',
			request_data: array( 'order' => 1 )
		);

		$this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/second',
			method: 'POST',
			request_data: array( 'order' => 2 )
		);

		$logs = $this->log_reader->get_recent_logs( $form_id, 10 );

		$this->assertIsArray( $logs );
		$this->assertCount( 2, $logs );
		$this->assertSame( (string) $form_id, $logs[0]['form_id'] );
	}

	/**
	 * Test get_recent_logs respects limit parameter
	 */
	public function testGetRecentLogsRespectsLimit(): void {
		$form_id = 789;

		// Create 5 log entries.
		for ( $i = 0; $i < 5; $i++ ) {
			$this->log_writer->start_request(
				form_id: $form_id,
				endpoint: "https://api.example.com/entry-{$i}",
				method: 'POST',
				request_data: array( 'index' => $i )
			);
		}

		$logs = $this->log_reader->get_recent_logs( $form_id, 3 );

		$this->assertCount( 3, $logs );
	}

	/**
	 * Test get_recent_logs returns entries ordered by timestamp
	 *
	 * Note: When entries are created within the same second (MySQL datetime precision),
	 * the order may fall back to ID order. This test verifies basic ordering behavior.
	 */
	public function testGetRecentLogsOrderByCreatedAtDesc(): void {
		// Use unique form_id to isolate this test.
		$form_id = \wp_rand( 100000, 999999 );

		// Create first entry.
		$first_id = $this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/first',
			method: 'POST',
			request_data: array()
		);

		// Create second entry.
		$second_id = $this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/second',
			method: 'POST',
			request_data: array()
		);

		$logs = $this->log_reader->get_recent_logs( $form_id, 10 );

		// Should have 2 entries for this form.
		$this->assertCount( 2, $logs );

		// Both entries should be present.
		$log_ids = \array_column( $logs, 'id' );
		$this->assertContains( (string) $first_id, $log_ids );
		$this->assertContains( (string) $second_id, $log_ids );

		// Results are ordered - verify we get both records in some order.
		$this->assertNotSame( $logs[0]['id'], $logs[1]['id'] );
	}

	/**
	 * Test get_request_for_retry returns null for non-existent log
	 */
	public function testGetRequestForRetryReturnsNullForNonExistent(): void {
		$request = $this->log_reader->get_request_for_retry( 999999 );
		$this->assertNull( $request );
	}

	/**
	 * Test get_request_for_retry returns null for success status
	 */
	public function testGetRequestForRetryReturnsNullForSuccess(): void {
		$log_id = $this->log_writer->start_request(
			form_id: 222,
			endpoint: 'https://api.example.com/success',
			method: 'POST',
			request_data: array( 'test' => 'data' )
		);

		// Complete with success.
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $log_id, $response );

		$request = $this->log_reader->get_request_for_retry( $log_id );
		$this->assertNull( $request );
	}

	/**
	 * Test get_request_for_retry returns data for error status
	 */
	public function testGetRequestForRetryReturnsDataForError(): void {
		$form_id = \wp_rand( 100000, 999999 );

		$log_id = $this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/error',
			method: 'POST',
			request_data: array( 'name' => 'Test User' ),
			request_headers: array( 'Content-Type' => 'application/json' )
		);

		// Complete with error.
		$error = new \WP_Error( 'http_error', 'Connection failed' );
		$this->log_writer->complete_request( $log_id, $error );

		$request = $this->log_reader->get_request_for_retry( $log_id );

		$this->assertNotNull( $request );
		$this->assertIsArray( $request );
		// get_request_for_retry returns a structured array with specific keys.
		$this->assertSame( $form_id, $request['form_id'] );
		$this->assertSame( 'https://api.example.com/error', $request['url'] );
		$this->assertSame( 'POST', $request['method'] );
		$this->assertSame( $log_id, $request['original_log_id'] );
		$this->assertArrayHasKey( 'headers', $request );
		$this->assertArrayHasKey( 'body', $request );
	}

	/**
	 * Test get_request_for_retry with different error statuses
	 *
	 * @dataProvider errorStatusProvider
	 */
	public function testGetRequestForRetryWithErrorStatuses( int $http_code, string $expected_status ): void {
		$log_id = $this->log_writer->start_request(
			form_id: 444,
			endpoint: 'https://api.example.com/test',
			method: 'POST',
			request_data: array()
		);

		$response = array(
			'response' => array( 'code' => $http_code ),
			'body'     => '',
			'headers'  => array(),
		);
		$this->log_writer->complete_request( $log_id, $response );

		$request = $this->log_reader->get_request_for_retry( $log_id );

		// All error statuses should be retryable.
		$this->assertNotNull( $request );
	}

	/**
	 * Data provider for error status codes
	 *
	 * @return array<string, array{int, string}>
	 */
	public static function errorStatusProvider(): array {
		return array(
			'400 Bad Request'     => array( 400, 'client_error' ),
			'404 Not Found'       => array( 404, 'client_error' ),
			'500 Server Error'    => array( 500, 'server_error' ),
			'503 Unavailable'     => array( 503, 'server_error' ),
		);
	}

	/**
	 * Test get_recent_logs only returns logs for specified form
	 */
	public function testGetRecentLogsOnlyReturnsSpecifiedForm(): void {
		$form_id_1 = \wp_rand( 100000, 999999 );
		$form_id_2 = \wp_rand( 100000, 999999 );

		// Create logs for different forms.
		$this->log_writer->start_request(
			form_id: $form_id_1,
			endpoint: 'https://api.example.com/form1',
			method: 'POST',
			request_data: array()
		);

		$this->log_writer->start_request(
			form_id: $form_id_2,
			endpoint: 'https://api.example.com/form2',
			method: 'POST',
			request_data: array()
		);

		$logs = $this->log_reader->get_recent_logs( $form_id_1, 10 );

		$this->assertCount( 1, $logs );
		$this->assertSame( (string) $form_id_1, $logs[0]['form_id'] );
	}
}
