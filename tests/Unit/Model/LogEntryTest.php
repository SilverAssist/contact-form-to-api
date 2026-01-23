<?php
/**
 * Tests for LogEntry Model
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Model
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use SilverAssist\ContactFormToAPI\Model\LogEntry;

/**
 * LogEntry test case.
 *
 * @group unit
 * @group model
 * @covers \SilverAssist\ContactFormToAPI\Model\LogEntry
 */
class LogEntryTest extends TestCase {

	/**
	 * Test LogEntry construction
	 */
	public function testConstructor(): void {
		$entry = new LogEntry(
			form_id: 123,
			endpoint: 'https://api.example.com/webhook',
			method: 'POST',
			status: 'success',
			request_data: array( 'name' => 'John' ),
			request_headers: array( 'Content-Type' => 'application/json' )
		);

		$this->assertSame( 123, $entry->get_form_id() );
		$this->assertSame( 'https://api.example.com/webhook', $entry->get_endpoint() );
		$this->assertSame( 'POST', $entry->get_method() );
		$this->assertSame( 'success', $entry->get_status() );
		$this->assertSame( array( 'name' => 'John' ), $entry->get_request_data() );
		$this->assertSame( array( 'Content-Type' => 'application/json' ), $entry->get_request_headers() );
	}

	/**
	 * Test is_successful method
	 */
	public function testIsSuccessful(): void {
		$success_entry = new LogEntry(
			form_id: 1,
			endpoint: 'https://api.test',
			method: 'POST',
			status: 'success'
		);

		$error_entry = new LogEntry(
			form_id: 1,
			endpoint: 'https://api.test',
			method: 'POST',
			status: 'error'
		);

		$this->assertTrue( $success_entry->is_successful() );
		$this->assertFalse( $error_entry->is_successful() );
	}

	/**
	 * Test is_error method
	 */
	public function testIsError(): void {
		$error_statuses = array( 'error', 'client_error', 'server_error' );

		foreach ( $error_statuses as $status ) {
			$entry = new LogEntry(
				form_id: 1,
				endpoint: 'https://api.test',
				method: 'POST',
				status: $status
			);

			$this->assertTrue( $entry->is_error(), "Status '{$status}' should be an error" );
		}

		$success_entry = new LogEntry(
			form_id: 1,
			endpoint: 'https://api.test',
			method: 'POST',
			status: 'success'
		);

		$this->assertFalse( $success_entry->is_error() );
	}

	/**
	 * Test is_retry method
	 */
	public function testIsRetry(): void {
		$original_entry = new LogEntry(
			form_id: 1,
			endpoint: 'https://api.test',
			method: 'POST',
			status: 'error'
		);

		$retry_entry = new LogEntry(
			form_id: 1,
			endpoint: 'https://api.test',
			method: 'POST',
			status: 'success'
		);
		$retry_entry->set_parent_log_id( 42 );

		$this->assertFalse( $original_entry->is_retry() );
		$this->assertTrue( $retry_entry->is_retry() );
	}

	/**
	 * Test to_array method
	 */
	public function testToArray(): void {
		$entry = new LogEntry(
			form_id: 123,
			endpoint: 'https://api.example.com',
			method: 'POST',
			status: 'success'
		);

		$entry->set_id( 456 );
		$entry->set_status_code( 200 );
		$entry->set_execution_time( 1.5 );

		$array = $entry->to_array();

		$this->assertSame( 456, $array['id'] );
		$this->assertSame( 123, $array['form_id'] );
		$this->assertSame( 'https://api.example.com', $array['endpoint'] );
		$this->assertSame( 'POST', $array['method'] );
		$this->assertSame( 'success', $array['status'] );
		$this->assertSame( 200, $array['status_code'] );
		$this->assertSame( 1.5, $array['execution_time'] );
	}

	/**
	 * Test from_array factory method
	 */
	public function testFromArray(): void {
		$data = array(
			'id'               => 789,
			'form_id'          => 123,
			'endpoint'         => 'https://api.example.com',
			'method'           => 'POST',
			'status'           => 'success',
			'status_code'      => 200,
			'request_data'     => array( 'name' => 'John' ),
			'request_headers'  => array( 'Content-Type' => 'application/json' ),
			'response_data'    => array( 'id' => 456 ),
			'response_headers' => array( 'X-Response' => 'OK' ),
			'error_message'    => null,
			'execution_time'   => 1.5,
			'created_at'       => '2026-01-23 10:00:00',
			'retry_count'      => 0,
			'parent_log_id'    => null,
		);

		$entry = LogEntry::from_array( $data );

		$this->assertSame( 789, $entry->get_id() );
		$this->assertSame( 123, $entry->get_form_id() );
		$this->assertSame( 'https://api.example.com', $entry->get_endpoint() );
		$this->assertSame( 'POST', $entry->get_method() );
		$this->assertSame( 'success', $entry->get_status() );
		$this->assertSame( 200, $entry->get_status_code() );
		$this->assertSame( 1.5, $entry->get_execution_time() );
		$this->assertSame( '2026-01-23 10:00:00', $entry->get_created_at() );
	}
}
