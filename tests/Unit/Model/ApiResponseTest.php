<?php
/**
 * Tests for ApiResponse Model
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Model
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Model;

use SilverAssist\ContactFormToAPI\Model\ApiResponse;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * ApiResponse test case.
 *
 * @group unit
 * @group model
 * @covers \SilverAssist\ContactFormToAPI\Model\ApiResponse
 */
class ApiResponseTest extends TestCase {

	/**
	 * Test ApiResponse construction with all parameters
	 */
	public function testConstructor(): void {
		$response = new ApiResponse(
			status_code: 200,
			body: array( 'id' => 123, 'status' => 'created' ),
			headers: array( 'Content-Type' => 'application/json' ),
			is_success: true,
			execution_time: 1.25,
			error_message: null
		);

		$this->assertSame( 200, $response->get_status_code() );
		$this->assertSame( array( 'id' => 123, 'status' => 'created' ), $response->get_body() );
		$this->assertSame( array( 'Content-Type' => 'application/json' ), $response->get_headers() );
		$this->assertTrue( $response->is_success() );
		$this->assertSame( 1.25, $response->get_execution_time() );
		$this->assertNull( $response->get_error_message() );
	}

	/**
	 * Test ApiResponse construction with default values
	 */
	public function testConstructorWithDefaults(): void {
		$response = new ApiResponse(
			status_code: 201,
			body: 'OK'
		);

		$this->assertSame( 201, $response->get_status_code() );
		$this->assertSame( 'OK', $response->get_body() );
		$this->assertSame( array(), $response->get_headers() );
		$this->assertTrue( $response->is_success() );
		$this->assertSame( 0.0, $response->get_execution_time() );
		$this->assertNull( $response->get_error_message() );
	}

	/**
	 * Test ApiResponse with error
	 */
	public function testConstructorWithError(): void {
		$response = new ApiResponse(
			status_code: 500,
			body: array( 'error' => 'Internal Server Error' ),
			headers: array( 'Content-Type' => 'application/json' ),
			is_success: false,
			execution_time: 0.5,
			error_message: 'Server returned error status'
		);

		$this->assertSame( 500, $response->get_status_code() );
		$this->assertFalse( $response->is_success() );
		$this->assertSame( 'Server returned error status', $response->get_error_message() );
	}

	/**
	 * Test is_success method with various status codes
	 */
	public function testIsSuccessWithDifferentStatusCodes(): void {
		// Successful response (2xx).
		$success_response = new ApiResponse(
			status_code: 200,
			body: null,
			is_success: true
		);
		$this->assertTrue( $success_response->is_success() );

		// Created response (201).
		$created_response = new ApiResponse(
			status_code: 201,
			body: null,
			is_success: true
		);
		$this->assertTrue( $created_response->is_success() );

		// Client error (4xx).
		$client_error_response = new ApiResponse(
			status_code: 400,
			body: null,
			is_success: false
		);
		$this->assertFalse( $client_error_response->is_success() );

		// Server error (5xx).
		$server_error_response = new ApiResponse(
			status_code: 503,
			body: null,
			is_success: false
		);
		$this->assertFalse( $server_error_response->is_success() );
	}

	/**
	 * Test get_body with different types
	 */
	public function testGetBodyWithDifferentTypes(): void {
		// Array body.
		$array_response = new ApiResponse(
			status_code: 200,
			body: array( 'key' => 'value' )
		);
		$this->assertIsArray( $array_response->get_body() );
		$this->assertSame( array( 'key' => 'value' ), $array_response->get_body() );

		// String body.
		$string_response = new ApiResponse(
			status_code: 200,
			body: 'Simple string response'
		);
		$this->assertIsString( $string_response->get_body() );
		$this->assertSame( 'Simple string response', $string_response->get_body() );

		// Null body.
		$null_response = new ApiResponse(
			status_code: 204,
			body: null
		);
		$this->assertNull( $null_response->get_body() );

		// Boolean body.
		$bool_response = new ApiResponse(
			status_code: 200,
			body: true
		);
		$this->assertTrue( $bool_response->get_body() );

		// Nested array body.
		$nested_response = new ApiResponse(
			status_code: 200,
			body: array(
				'data' => array(
					'users' => array(
						array( 'id' => 1, 'name' => 'John' ),
						array( 'id' => 2, 'name' => 'Jane' ),
					),
				),
			)
		);
		$this->assertSame( 'John', $nested_response->get_body()['data']['users'][0]['name'] );
	}

	/**
	 * Test to_array method
	 */
	public function testToArray(): void {
		$response = new ApiResponse(
			status_code: 200,
			body: array( 'result' => 'success' ),
			headers: array( 'X-Request-Id' => 'abc123' ),
			is_success: true,
			execution_time: 0.75,
			error_message: null
		);

		$array = $response->to_array();

		$this->assertSame( 200, $array['status_code'] );
		$this->assertSame( array( 'result' => 'success' ), $array['body'] );
		$this->assertSame( array( 'X-Request-Id' => 'abc123' ), $array['headers'] );
		$this->assertTrue( $array['is_success'] );
		$this->assertSame( 0.75, $array['execution_time'] );
		$this->assertNull( $array['error_message'] );
	}

	/**
	 * Test to_array method with error response
	 */
	public function testToArrayWithError(): void {
		$response = new ApiResponse(
			status_code: 404,
			body: array( 'error' => 'Not Found' ),
			headers: array(),
			is_success: false,
			execution_time: 0.1,
			error_message: 'Resource not found'
		);

		$array = $response->to_array();

		$this->assertSame( 404, $array['status_code'] );
		$this->assertFalse( $array['is_success'] );
		$this->assertSame( 'Resource not found', $array['error_message'] );
	}

	/**
	 * Test execution time
	 */
	public function testExecutionTime(): void {
		$response = new ApiResponse(
			status_code: 200,
			body: null,
			execution_time: 2.5
		);

		$this->assertSame( 2.5, $response->get_execution_time() );
	}

	/**
	 * Test response headers
	 */
	public function testHeaders(): void {
		$headers = array(
			'Content-Type'     => 'application/json',
			'X-RateLimit-Remaining' => '99',
			'Cache-Control'    => 'no-cache',
		);

		$response = new ApiResponse(
			status_code: 200,
			body: null,
			headers: $headers
		);

		$this->assertSame( $headers, $response->get_headers() );
		$this->assertArrayHasKey( 'Content-Type', $response->get_headers() );
		$this->assertSame( '99', $response->get_headers()['X-RateLimit-Remaining'] );
	}

	/**
	 * Test common HTTP status codes
	 *
	 * @dataProvider httpStatusCodeProvider
	 */
	public function testHttpStatusCodes( int $status_code, bool $expected_success ): void {
		$response = new ApiResponse(
			status_code: $status_code,
			body: null,
			is_success: $expected_success
		);

		$this->assertSame( $status_code, $response->get_status_code() );
		$this->assertSame( $expected_success, $response->is_success() );
	}

	/**
	 * Data provider for HTTP status codes
	 *
	 * @return array<string, array{int, bool}>
	 */
	public static function httpStatusCodeProvider(): array {
		return array(
			'200 OK'              => array( 200, true ),
			'201 Created'         => array( 201, true ),
			'204 No Content'      => array( 204, true ),
			'400 Bad Request'     => array( 400, false ),
			'401 Unauthorized'    => array( 401, false ),
			'403 Forbidden'       => array( 403, false ),
			'404 Not Found'       => array( 404, false ),
			'422 Unprocessable'   => array( 422, false ),
			'429 Too Many'        => array( 429, false ),
			'500 Server Error'    => array( 500, false ),
			'502 Bad Gateway'     => array( 502, false ),
			'503 Unavailable'     => array( 503, false ),
		);
	}
}
