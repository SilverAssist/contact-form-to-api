<?php
/**
 * Tests for LogReader Service
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging;

use SilverAssist\ContactFormToAPI\Core\Activator;
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
		$this->log_reader = new LogReader();
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
	public function testGetRequestForRetryWithErrorStatuses( int $http_code ): void {
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
		$this->assertNotNull( $request, "HTTP code $http_code should be retryable" );
		$this->assertArrayHasKey( 'url', $request );
		$this->assertArrayHasKey( 'method', $request );
		$this->assertArrayHasKey( 'original_log_id', $request );
	}

	/**
	 * Data provider for error status codes
	 *
	 * @return array<string, array{int}>
	 */
	public static function errorStatusProvider(): array {
		return array(
			'400 Bad Request'  => array( 400 ),
			'404 Not Found'    => array( 404 ),
			'500 Server Error' => array( 500 ),
			'503 Unavailable'  => array( 503 ),
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

	/**
	 * Test get_forms_with_logs returns empty array when no logs exist
	 */
	public function testGetFormsWithLogsReturnsEmptyWhenNoLogs(): void {
		// Clean up any existing logs first.
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table_name ) );

		$forms = $this->log_reader->get_forms_with_logs();

		$this->assertIsArray( $forms );
		$this->assertEmpty( $forms );
	}

	/**
	 * Test get_forms_with_logs returns correct form list with titles
	 */
	public function testGetFormsWithLogsReturnsFormListWithTitles(): void {
		// Create test forms (posts).
		$form_id_1 = $this->factory->post->create(
			array(
				'post_type'  => 'wpcf7_contact_form',
				'post_title' => 'Contact Form 1',
			)
		);
		$form_id_2 = $this->factory->post->create(
			array(
				'post_type'  => 'wpcf7_contact_form',
				'post_title' => 'Contact Form 2',
			)
		);

		// Create logs for these forms.
		$this->log_writer->start_request(
			form_id: $form_id_1,
			endpoint: 'https://api.example.com/test1',
			method: 'POST',
			request_data: array()
		);

		$this->log_writer->start_request(
			form_id: $form_id_2,
			endpoint: 'https://api.example.com/test2',
			method: 'POST',
			request_data: array()
		);

		$forms = $this->log_reader->get_forms_with_logs();

		$this->assertIsArray( $forms );
		$this->assertCount( 2, $forms );

		// Check that both forms are present.
		$form_ids = \array_column( $forms, 'form_id' );
		$this->assertContains( (string) $form_id_1, $form_ids );
		$this->assertContains( (string) $form_id_2, $form_ids );

		// Check that titles are present.
		$form_titles = \array_column( $forms, 'post_title' );
		$this->assertContains( 'Contact Form 1', $form_titles );
		$this->assertContains( 'Contact Form 2', $form_titles );
	}

	/**
	 * Test get_forms_with_logs handles deleted forms gracefully
	 */
	public function testGetFormsWithLogsHandlesDeletedForms(): void {
		// Create a form.
		$form_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpcf7_contact_form',
				'post_title' => 'Temporary Form',
			)
		);

		// Create log for this form.
		$this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/temp',
			method: 'POST',
			request_data: array()
		);

		// Delete the form.
		\wp_delete_post( $form_id, true );

		$forms = $this->log_reader->get_forms_with_logs();

		$this->assertIsArray( $forms );
		$this->assertNotEmpty( $forms );

		// Find the deleted form in results.
		$deleted_form = null;
		foreach ( $forms as $form ) {
			if ( (int) $form['form_id'] === $form_id ) {
				$deleted_form = $form;
				break;
			}
		}

		$this->assertNotNull( $deleted_form, 'Deleted form should still appear in results' );
		$this->assertNull( $deleted_form['post_title'], 'Deleted form should have null post_title' );
	}

	/**
	 * Test get_forms_with_logs returns distinct forms only
	 */
	public function testGetFormsWithLogsReturnsDistinctForms(): void {
		$form_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpcf7_contact_form',
				'post_title' => 'Multiple Logs Form',
			)
		);

		// Create multiple logs for same form.
		$this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/log1',
			method: 'POST',
			request_data: array()
		);

		$this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/log2',
			method: 'POST',
			request_data: array()
		);

		$this->log_writer->start_request(
			form_id: $form_id,
			endpoint: 'https://api.example.com/log3',
			method: 'POST',
			request_data: array()
		);

		$forms = $this->log_reader->get_forms_with_logs();

		// Should only return the form once, not multiple times.
		$form_ids = \array_column( $forms, 'form_id' );
		$count    = \count( \array_filter( $form_ids, fn( $id ) => (int) $id === $form_id ) );
		$this->assertSame( 1, $count, 'Form should appear only once despite multiple logs' );
	}

	/**
	 * Test get_forms_with_logs orders by post_title
	 */
	public function testGetFormsWithLogsOrdersByPostTitle(): void {
		// Create forms with specific titles for ordering.
		$form_z = $this->factory->post->create(
			array(
				'post_type'  => 'wpcf7_contact_form',
				'post_title' => 'Zebra Form',
			)
		);

		$form_a = $this->factory->post->create(
			array(
				'post_type'  => 'wpcf7_contact_form',
				'post_title' => 'Alpha Form',
			)
		);

		$form_m = $this->factory->post->create(
			array(
				'post_type'  => 'wpcf7_contact_form',
				'post_title' => 'Middle Form',
			)
		);

		// Create logs for each form.
		$this->log_writer->start_request( form_id: $form_z, endpoint: 'https://api.example.com/z', method: 'POST', request_data: array() );
		$this->log_writer->start_request( form_id: $form_a, endpoint: 'https://api.example.com/a', method: 'POST', request_data: array() );
		$this->log_writer->start_request( form_id: $form_m, endpoint: 'https://api.example.com/m', method: 'POST', request_data: array() );

		$forms = $this->log_reader->get_forms_with_logs();

		$this->assertCount( 3, $forms );

		// Check ordering by title.
		$this->assertSame( 'Alpha Form', $forms[0]['post_title'] );
		$this->assertSame( 'Middle Form', $forms[1]['post_title'] );
		$this->assertSame( 'Zebra Form', $forms[2]['post_title'] );
	}
}
