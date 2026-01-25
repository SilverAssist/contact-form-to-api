<?php
/**
 * Tests for Statistics Model
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Model
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Model;

use SilverAssist\ContactFormToAPI\Model\Statistics;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * Statistics test case.
 *
 * @group unit
 * @group model
 * @covers \SilverAssist\ContactFormToAPI\Model\Statistics
 */
class StatisticsTest extends TestCase {

	/**
	 * Test Statistics construction with all parameters
	 */
	public function testConstructor(): void {
		$recent_logs = array(
			array(
				'id'     => 1,
				'status' => 'success',
			),
			array(
				'id'     => 2,
				'status' => 'error',
			),
		);

		$stats = new Statistics(
			total: 100,
			success: 80,
			error: 10,
			client_error: 5,
			server_error: 5,
			pending: 0,
			avg_execution_time: 0.5,
			recent_logs: $recent_logs
		);

		$this->assertSame( 100, $stats->get_total() );
		$this->assertSame( 80, $stats->get_success() );
		$this->assertSame( 10, $stats->get_error() );
		$this->assertSame( 5, $stats->get_client_error() );
		$this->assertSame( 5, $stats->get_server_error() );
		$this->assertSame( 0, $stats->get_pending() );
		$this->assertSame( 0.5, $stats->get_avg_execution_time() );
		$this->assertSame( $recent_logs, $stats->get_recent_logs() );
	}

	/**
	 * Test Statistics construction with default values
	 */
	public function testConstructorWithDefaults(): void {
		$stats = new Statistics();

		$this->assertSame( 0, $stats->get_total() );
		$this->assertSame( 0, $stats->get_success() );
		$this->assertSame( 0, $stats->get_error() );
		$this->assertSame( 0, $stats->get_client_error() );
		$this->assertSame( 0, $stats->get_server_error() );
		$this->assertSame( 0, $stats->get_pending() );
		$this->assertSame( 0.0, $stats->get_avg_execution_time() );
		$this->assertSame( array(), $stats->get_recent_logs() );
	}

	/**
	 * Test get_success_rate calculation
	 */
	public function testGetSuccessRate(): void {
		// 80 out of 100 = 80%.
		$stats = new Statistics(
			total: 100,
			success: 80
		);
		$this->assertSame( 80.0, $stats->get_success_rate() );

		// 50 out of 200 = 25%.
		$stats = new Statistics(
			total: 200,
			success: 50
		);
		$this->assertSame( 25.0, $stats->get_success_rate() );

		// 100% success rate.
		$stats = new Statistics(
			total: 50,
			success: 50
		);
		$this->assertSame( 100.0, $stats->get_success_rate() );
	}

	/**
	 * Test get_success_rate with zero total
	 */
	public function testGetSuccessRateWithZeroTotal(): void {
		$stats = new Statistics(
			total: 0,
			success: 0
		);

		$this->assertSame( 0.0, $stats->get_success_rate() );
	}

	/**
	 * Test get_error_rate calculation
	 */
	public function testGetErrorRate(): void {
		// 20 errors out of 100 = 20%.
		$stats = new Statistics(
			total: 100,
			success: 80,
			error: 10,
			client_error: 5,
			server_error: 5
		);
		$this->assertSame( 20.0, $stats->get_error_rate() );

		// Mixed error types.
		$stats = new Statistics(
			total: 200,
			success: 150,
			error: 20,
			client_error: 15,
			server_error: 15
		);
		// (20 + 15 + 15) / 200 * 100 = 25%.
		$this->assertSame( 25.0, $stats->get_error_rate() );
	}

	/**
	 * Test get_error_rate with zero total
	 */
	public function testGetErrorRateWithZeroTotal(): void {
		$stats = new Statistics(
			total: 0
		);

		$this->assertSame( 0.0, $stats->get_error_rate() );
	}

	/**
	 * Test to_array method
	 */
	public function testToArray(): void {
		$recent_logs = array(
			array(
				'id'     => 1,
				'status' => 'success',
			),
		);

		$stats = new Statistics(
			total: 100,
			success: 80,
			error: 10,
			client_error: 5,
			server_error: 5,
			pending: 2,
			avg_execution_time: 0.75,
			recent_logs: $recent_logs
		);

		$array = $stats->to_array();

		$this->assertSame( 100, $array['total'] );
		$this->assertSame( 80, $array['success'] );
		$this->assertSame( 10, $array['error'] );
		$this->assertSame( 5, $array['client_error'] );
		$this->assertSame( 5, $array['server_error'] );
		$this->assertSame( 2, $array['pending'] );
		$this->assertSame( 0.75, $array['avg_execution_time'] );
		$this->assertSame( $recent_logs, $array['recent_logs'] );
		$this->assertSame( 80.0, $array['success_rate'] );
		$this->assertSame( 20.0, $array['error_rate'] );
	}

	/**
	 * Test from_query factory method with new format
	 */
	public function testFromQueryWithNewFormat(): void {
		$query_result = array(
			'total'              => 150,
			'success'            => 120,
			'error'              => 10,
			'client_error'       => 15,
			'server_error'       => 5,
			'pending'            => 3,
			'avg_execution_time' => 0.8,
		);

		$recent_logs = array(
			array(
				'id'     => 1,
				'status' => 'success',
			),
			array(
				'id'     => 2,
				'status' => 'error',
			),
		);

		$stats = Statistics::from_query( $query_result, $recent_logs );

		$this->assertSame( 150, $stats->get_total() );
		$this->assertSame( 120, $stats->get_success() );
		$this->assertSame( 10, $stats->get_error() );
		$this->assertSame( 15, $stats->get_client_error() );
		$this->assertSame( 5, $stats->get_server_error() );
		$this->assertSame( 3, $stats->get_pending() );
		$this->assertSame( 0.8, $stats->get_avg_execution_time() );
		$this->assertSame( $recent_logs, $stats->get_recent_logs() );
	}

	/**
	 * Test from_query factory method with old format
	 */
	public function testFromQueryWithOldFormat(): void {
		$query_result = array(
			'total_requests'      => 100,
			'successful_requests' => 80,
			'failed_requests'     => 20,
		);

		$stats = Statistics::from_query( $query_result );

		$this->assertSame( 100, $stats->get_total() );
		$this->assertSame( 80, $stats->get_success() );
		$this->assertSame( 20, $stats->get_error() );
		$this->assertSame( 0, $stats->get_client_error() );
		$this->assertSame( 0, $stats->get_server_error() );
	}

	/**
	 * Test from_query with empty data
	 */
	public function testFromQueryWithEmptyData(): void {
		$stats = Statistics::from_query( array() );

		$this->assertSame( 0, $stats->get_total() );
		$this->assertSame( 0, $stats->get_success() );
		$this->assertSame( 0, $stats->get_error() );
		$this->assertSame( 0, $stats->get_client_error() );
		$this->assertSame( 0, $stats->get_server_error() );
		$this->assertSame( 0, $stats->get_pending() );
		$this->assertSame( 0.0, $stats->get_avg_execution_time() );
		$this->assertSame( array(), $stats->get_recent_logs() );
	}

	/**
	 * Test getters individually
	 */
	public function testIndividualGetters(): void {
		$stats = new Statistics(
			total: 500,
			success: 400,
			error: 50,
			client_error: 30,
			server_error: 20,
			pending: 10,
			avg_execution_time: 1.5
		);

		$this->assertSame( 500, $stats->get_total() );
		$this->assertSame( 400, $stats->get_success() );
		$this->assertSame( 50, $stats->get_error() );
		$this->assertSame( 30, $stats->get_client_error() );
		$this->assertSame( 20, $stats->get_server_error() );
		$this->assertSame( 10, $stats->get_pending() );
		$this->assertSame( 1.5, $stats->get_avg_execution_time() );
	}

	/**
	 * Test success and error rates add up correctly
	 */
	public function testRatesConsistency(): void {
		$stats = new Statistics(
			total: 100,
			success: 70,
			error: 10,
			client_error: 10,
			server_error: 10
		);

		// Success rate + Error rate should equal 100% when all requests are either success or error.
		$success_rate = $stats->get_success_rate();
		$error_rate   = $stats->get_error_rate();

		$this->assertSame( 70.0, $success_rate );
		$this->assertSame( 30.0, $error_rate );
		$this->assertSame( 100.0, $success_rate + $error_rate );
	}

	/**
	 * Test with pending requests (rates won't add to 100%)
	 */
	public function testRatesWithPending(): void {
		$stats = new Statistics(
			total: 100,
			success: 60,
			error: 10,
			client_error: 5,
			server_error: 5,
			pending: 20
		);

		$success_rate = $stats->get_success_rate();
		$error_rate   = $stats->get_error_rate();

		// 60% success, 20% error, 20% pending.
		$this->assertSame( 60.0, $success_rate );
		$this->assertSame( 20.0, $error_rate );
		// With pending, they don't add to 100%.
		$this->assertSame( 80.0, $success_rate + $error_rate );
	}

	/**
	 * Test recent_logs getter
	 */
	public function testRecentLogs(): void {
		$recent_logs = array(
			array(
				'id'        => 1,
				'status'    => 'success',
				'endpoint'  => 'https://api.test',
				'timestamp' => '2024-01-15 10:00:00',
			),
			array(
				'id'        => 2,
				'status'    => 'error',
				'endpoint'  => 'https://api.test',
				'timestamp' => '2024-01-15 09:55:00',
			),
			array(
				'id'        => 3,
				'status'    => 'success',
				'endpoint'  => 'https://api.test',
				'timestamp' => '2024-01-15 09:50:00',
			),
		);

		$stats = new Statistics(
			total: 3,
			success: 2,
			error: 1,
			recent_logs: $recent_logs
		);

		$this->assertCount( 3, $stats->get_recent_logs() );
		$this->assertSame( 'success', $stats->get_recent_logs()[0]['status'] );
		$this->assertSame( 'error', $stats->get_recent_logs()[1]['status'] );
	}
}
