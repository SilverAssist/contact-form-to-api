<?php
/**
 * Tests for ApiClient cf7_api_after_response hook
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Service\Api
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Api;

use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Service\Api\ApiClient;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * ApiClient hook test case.
 *
 * @group unit
 * @group service
 * @group api
 * @group hooks
 * @covers \SilverAssist\ContactFormToAPI\Service\Api\ApiClient
 */
class ApiClientHookTest extends TestCase {

	/**
	 * ApiClient instance
	 *
	 * @var ApiClient
	 */
	private ApiClient $api_client;

	/**
	 * Track hook calls
	 *
	 * @var array<string, mixed>
	 */
	private array $hook_data = array();

	/**
	 * Original global settings before test
	 *
	 * @var array<string, mixed>|false
	 */
	private $original_settings = false;

	/**
	 * Test form ID
	 *
	 * @var int
	 */
	private int $test_form_id = 0;

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
		$this->api_client = ApiClient::instance();
		$this->hook_data  = array();

		// Store original settings.
		$this->original_settings = \get_option( 'cf7_api_global_settings', false );

		// Enable logging.
		$global_settings                    = \get_option( 'cf7_api_global_settings', array() );
		$global_settings['logging_enabled'] = true;
		\update_option( 'cf7_api_global_settings', $global_settings );

		// Create a test form using the factory.
		$this->test_form_id = $this->factory->post->create(
			array(
				'post_type'   => 'wpcf7_contact_form',
				'post_title'  => 'Test Form Hook',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Tear down test environment
	 */
	public function tear_down(): void {
		// Remove all cf7_api_after_response filters.
		\remove_all_filters( 'cf7_api_after_response' );

		// Remove all pre_http_request filters to restore default HTTP behavior.
		\remove_all_filters( 'pre_http_request' );

		// Restore original global settings.
		if ( false === $this->original_settings ) {
			\delete_option( 'cf7_api_global_settings' );
		} else {
			\update_option( 'cf7_api_global_settings', $this->original_settings );
		}

		// Clean up test logs.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare( 'DELETE FROM %i WHERE form_id = %d', $wpdb->prefix . 'cf7_api_logs', $this->test_form_id )
		);

		// Clean up test form post (factory handles this, but be explicit).
		\wp_delete_post( $this->test_form_id, true );

		parent::tear_down();
	}

	/**
	 * Test hook fires on successful API response
	 */
	public function testHookFiresOnSuccess(): void {
		// Mock successful HTTP response.
		\add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'headers'  => array(
						'Content-Type' => 'application/json',
					),
					'body'     => \wp_json_encode(
						array(
							'success' => true,
							'id'      => 123,
						)
					),
				);
			},
			10,
			0
		);

		// Add hook to capture data.
		\add_filter(
			'cf7_api_after_response',
			function ( $response_data, $context ) {
				$this->hook_data = array(
					'response' => $response_data,
					'context'  => $context,
				);
				return $response_data;
			},
			10,
			2
		);

		// Send request.
		$response = $this->api_client->send(
			array(
				'url'     => 'https://api.example.com/test',
				'method'  => 'POST',
				'body'    => array( 'name' => 'John Doe' ),
				'form_id' => $this->test_form_id,
			)
		);

		// Assert response is not error.
		$this->assertNotInstanceOf( \WP_Error::class, $response );

		// Assert hook was called.
		$this->assertNotEmpty( $this->hook_data, 'Hook should have been called' );

		// Assert response data structure.
		$response_data = $this->hook_data['response'];
		$this->assertArrayHasKey( 'status_code', $response_data );
		$this->assertArrayHasKey( 'headers', $response_data );
		$this->assertArrayHasKey( 'body', $response_data );
		$this->assertArrayHasKey( 'body_parsed', $response_data );
		$this->assertArrayHasKey( 'duration', $response_data );

		// Assert response values.
		$this->assertSame( 200, $response_data['status_code'] );
		$this->assertIsArray( $response_data['headers'] );
		$this->assertIsString( $response_data['body'] );
		$this->assertIsArray( $response_data['body_parsed'] );
		$this->assertSame( 123, $response_data['body_parsed']['id'] );
		$this->assertIsFloat( $response_data['duration'] );

		// Assert context structure.
		$context = $this->hook_data['context'];
		$this->assertArrayHasKey( 'log_id', $context );
		$this->assertArrayHasKey( 'form_id', $context );
		$this->assertArrayHasKey( 'form_title', $context );
		$this->assertArrayHasKey( 'form_data', $context );
		$this->assertArrayHasKey( 'endpoint', $context );
		$this->assertArrayHasKey( 'is_retry', $context );
		$this->assertArrayHasKey( 'attempt', $context );

		// Assert context values.
		$this->assertIsInt( $context['log_id'] );
		$this->assertSame( $this->test_form_id, $context['form_id'] );
		$this->assertSame( 'Test Form Hook', $context['form_title'] );
		$this->assertIsArray( $context['form_data'] );
		$this->assertSame( 'https://api.example.com/test', $context['endpoint'] );
		$this->assertFalse( $context['is_retry'] );
		$this->assertSame( 1, $context['attempt'] );
	}

	/**
	 * Test hook fires on failed API response
	 */
	public function testHookFiresOnClientError(): void {
		// Mock 400 error response.
		\add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array(
						'code'    => 400,
						'message' => 'Bad Request',
					),
					'headers'  => array(),
					'body'     => 'Invalid request',
				);
			},
			10,
			0
		);

		// Add hook to capture data.
		\add_filter(
			'cf7_api_after_response',
			function ( $response_data, $context ) {
				$this->hook_data = array(
					'response' => $response_data,
					'context'  => $context,
				);
				return $response_data;
			},
			10,
			2
		);

		// Send request.
		$response = $this->api_client->send(
			array(
				'url'     => 'https://api.example.com/test',
				'method'  => 'POST',
				'body'    => array( 'invalid' => 'data' ),
				'form_id' => $this->test_form_id,
			)
		);

		// Assert hook was called even on error.
		$this->assertNotEmpty( $this->hook_data, 'Hook should fire on error responses' );

		// Assert error response data.
		$response_data = $this->hook_data['response'];
		$this->assertSame( 400, $response_data['status_code'] );
		$this->assertSame( 'Invalid request', $response_data['body'] );
		$this->assertNull( $response_data['body_parsed'], 'Non-JSON response should have null body_parsed' );
	}

	/**
	 * Test hook does not fire on WP_Error
	 */
	public function testHookDoesNotFireOnWpError(): void {
		// Mock WP_Error response.
		\add_filter(
			'pre_http_request',
			function () {
				return new \WP_Error( 'http_request_failed', 'Connection timeout' );
			},
			10,
			0
		);

		// Add hook to capture data.
		\add_filter(
			'cf7_api_after_response',
			function ( $response_data, $context ) {
				$this->hook_data = array(
					'response' => $response_data,
					'context'  => $context,
				);
				return $response_data;
			},
			10,
			2
		);

		// Send request.
		$response = $this->api_client->send(
			array(
				'url'     => 'https://api.example.com/test',
				'method'  => 'POST',
				'body'    => array( 'name' => 'John Doe' ),
				'form_id' => $this->test_form_id,
			)
		);

		// Assert response is error.
		$this->assertInstanceOf( \WP_Error::class, $response );

		// Assert hook was NOT called for WP_Error.
		$this->assertEmpty( $this->hook_data, 'Hook should not fire on WP_Error' );
	}

	/**
	 * Test body_parsed is null for non-JSON response
	 */
	public function testBodyParsedNullForNonJson(): void {
		// Mock non-JSON response.
		\add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'headers'  => array(
						'Content-Type' => 'text/html',
					),
					'body'     => '<html><body>Success</body></html>',
				);
			},
			10,
			0
		);

		// Add hook to capture data.
		\add_filter(
			'cf7_api_after_response',
			function ( $response_data, $context ) {
				$this->hook_data = array(
					'response' => $response_data,
					'context'  => $context,
				);
				return $response_data;
			},
			10,
			2
		);

		// Send request.
		$this->api_client->send(
			array(
				'url'     => 'https://api.example.com/test',
				'method'  => 'GET',
				'form_id' => $this->test_form_id,
			)
		);

		// Assert body_parsed is null.
		$this->assertNull(
			$this->hook_data['response']['body_parsed'],
			'body_parsed should be null for non-JSON response'
		);
	}

	/**
	 * Test is_retry flag is true for retry requests
	 */
	public function testIsRetryFlagSetCorrectly(): void {
		// Mock successful response.
		\add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'headers'  => array(),
					'body'     => '{}',
				);
			},
			10,
			0
		);

		// Add hook to capture data.
		\add_filter(
			'cf7_api_after_response',
			function ( $response_data, $context ) {
				$this->hook_data = array(
					'response' => $response_data,
					'context'  => $context,
				);
				return $response_data;
			},
			10,
			2
		);

		// Send request with retry_of set.
		$this->api_client->send(
			array(
				'url'      => 'https://api.example.com/test',
				'method'   => 'POST',
				'body'     => array( 'retry' => true ),
				'form_id'  => $this->test_form_id,
				'retry_of' => 123,
			)
		);

		// Assert is_retry is true.
		$this->assertTrue(
			$this->hook_data['context']['is_retry'],
			'is_retry should be true when retry_of is set'
		);
	}

	/**
	 * Test attempt number with retries
	 */
	public function testAttemptNumberWithRetries(): void {
		$attempt_count = 0;

		// Mock 500 error twice, then success.
		\add_filter(
			'pre_http_request',
			function () use ( &$attempt_count ) {
				++$attempt_count;
				if ( $attempt_count < 3 ) {
					return array(
						'response' => array(
							'code'    => 500,
							'message' => 'Server Error',
						),
						'headers'  => array(),
						'body'     => 'Server error',
					);
				}
				return array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'headers'  => array(),
					'body'     => \wp_json_encode( array( 'success' => true ) ),
				);
			},
			10,
			0
		);

		// Add hook to capture data.
		\add_filter(
			'cf7_api_after_response',
			function ( $response_data, $context ) {
				$this->hook_data = array(
					'response' => $response_data,
					'context'  => $context,
				);
				return $response_data;
			},
			10,
			2
		);

		// Send request with retries enabled.
		$this->api_client->send(
			array(
				'url'          => 'https://api.example.com/test',
				'method'       => 'POST',
				'body'         => array( 'test' => true ),
				'form_id'      => $this->test_form_id,
				'retry_config' => array(
					'max_retries' => 2,
					'retry_delay' => 0,
				),
			)
		);

		// Assert attempt number reflects retries.
		$this->assertSame(
			3,
			$this->hook_data['context']['attempt'],
			'attempt should be 3 after 2 retries'
		);
	}
}
