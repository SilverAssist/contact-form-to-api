<?php

/**
 * Base Test Case for Contact Form 7 to API Plugin
 *
 * Provides common functionality and utilities for all test cases in the plugin.
 * Extends WP_UnitTestCase with plugin-specific helpers.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.0.0
 * @version 2.0.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Helpers;

/**
 * Base test case class for Contact Form 7 to API plugin tests
 *
 * Extends WP_UnitTestCase to leverage WordPress testing infrastructure:
 * - Database transactions with automatic rollback after each test
 * - Factory methods for creating test data (posts, users, terms, etc.)
 * - WordPress-specific assertions and helpers
 * - Proper WordPress environment initialization
 */
abstract class TestCase extends \WP_UnitTestCase {

	/**
	 * Plugin instance for testing
	 *
	 * @var object|null
	 */
	protected $plugin;

	/**
	 * Test data directory path
	 *
	 * @var string
	 */
	protected $test_data_dir;

	/**
	 * Temporary files created during tests
	 *
	 * @var array<string>
	 */
	protected array $temp_files = array();

	/**
	 * Setup method called before each test
	 *
	 * Uses WordPress snake_case convention (set_up instead of setUp).
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->test_data_dir = dirname( __DIR__ ) . '/data';
		$this->temp_files    = array();

		// Initialize plugin for testing
		$this->initializePlugin();
	}

	/**
	 * Teardown method called after each test
	 *
	 * Uses WordPress snake_case convention (tear_down instead of tearDown).
	 *
	 * @return void
	 */
	public function tear_down(): void {
		// Clean up any test data
		$this->cleanupTestData();
		$this->cleanupTempFiles();

		parent::tear_down();
	}

	/**
	 * Initialize the plugin for testing
	 *
	 * @return void
	 */
	protected function initializePlugin(): void {
		// Only initialize plugin if the class exists and has instance method
		if ( class_exists( '\\SilverAssist\\ContactFormToAPI\\Core\\Plugin' ) ) {
			$plugin_class = '\\SilverAssist\\ContactFormToAPI\\Core\\Plugin';
			if ( method_exists( $plugin_class, 'instance' ) ) {
				$this->plugin = $plugin_class::instance();
			} else {
				// Try to instantiate normally if no singleton
				try {
					$this->plugin = new $plugin_class();
				} catch ( \Exception $e ) {
					// Plugin initialization failed, continue without plugin instance
					$this->plugin = null;
				}
			}
		}
	}

	/**
	 * Clean up test data after each test
	 *
	 * @return void
	 */
	protected function cleanupTestData(): void {
		// Override in child classes if needed
	}

	/**
	 * Load test data from JSON file
	 *
	 * @param string $filename The filename without .json extension
	 * @return array|null The decoded JSON data or null on failure
	 */
	protected function loadTestData( string $filename ): ?array {
		$file_path = $this->test_data_dir . '/' . $filename . '.json';

		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Test helper, not production code
		$json_content = file_get_contents( $file_path );
		$data         = json_decode( $json_content, true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Create a mock HTTP response
	 *
	 * @param array $response_data The response data
	 * @param int   $status_code   HTTP status code
	 * @return array Mock response array
	 */
	protected function createMockHttpResponse( array $response_data = array(), int $status_code = 200 ): array {
		return array(
			'response' => array(
				'code'    => $status_code,
				'message' => $this->getHttpStatusMessage( $status_code ),
			),
			'body'     => json_encode( $response_data ),
			'headers'  => array(
				'content-type' => 'application/json',
			),
		);
	}

	/**
	 * Get HTTP status message for status code
	 *
	 * @param int $status_code HTTP status code
	 * @return string Status message
	 */
	private function getHttpStatusMessage( int $status_code ): string {
		$messages = array(
			200 => 'OK',
			201 => 'Created',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			404 => 'Not Found',
			500 => 'Internal Server Error',
		);

		return $messages[ $status_code ] ?? 'Unknown Status';
	}

	/**
	 * Assert that a string contains valid JSON
	 *
	 * @param string $json_string The string to test
	 * @param string $message     Optional failure message
	 * @return void
	 */
	protected function assertJsonString( string $json_string, string $message = '' ): void {
		$decoded = \json_decode( $json_string );
		$this->assertNotNull( $decoded, '' !== $message ? $message : 'String is not valid JSON: ' . $json_string );
	}

	/**
	 * Assert that an array has the expected structure
	 *
	 * @param array $expected_keys Expected array keys
	 * @param array $actual_array  Actual array to test
	 * @param string $message      Optional failure message
	 * @return void
	 */
	protected function assertArrayStructure( array $expected_keys, array $actual_array, string $message = '' ): void {
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey(
				$key,
				$actual_array,
				'' !== $message ? $message : 'Array is missing expected key: ' . $key
			);
		}
	}

	/**
	 * Create a temporary file for testing
	 *
	 * @param string $content File content
	 * @param string $extension File extension (without dot)
	 * @return string Path to the created file
	 */
	protected function createTempFile( string $content, string $extension = 'txt' ): string {
		$temp_file = tempnam( sys_get_temp_dir(), 'cf7api_test_' ) . '.' . $extension;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test helper, not production code
		file_put_contents( $temp_file, $content );

		// Store for cleanup
		if ( ! isset( $this->temp_files ) ) {
			$this->temp_files = array();
		}
		$this->temp_files[] = $temp_file;

		return $temp_file;
	}

	/**
	 * Clean up temporary files
	 *
	 * @return void
	 */
	protected function cleanupTempFiles(): void {
		if ( \is_array( $this->temp_files ) ) {
			foreach ( $this->temp_files as $file ) {
				if ( \file_exists( $file ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test helper, not production code
					\unlink( $file );
				}
			}
			$this->temp_files = array();
		}
	}
}
