<?php
/**
 * Integration Tests for WordPress Integration
 *
 * Tests the integration with WordPress core functionality,
 * including hooks, filters, and database operations.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.0.0
 * @version 2.0.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Integration;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use WP_Error;

/**
 * Test cases for WordPress Integration
 */
class WordPressIntegrationTest extends TestCase {

	/**
	 * Test WordPress environment setup
	 *
	 * @return void
	 */
	public function test_wordpress_environment_setup(): void {
		$this->assertTrue(
			defined( 'CF7_API_TESTING' ),
			'Testing constant should be defined'
		);

		$this->assertTrue(
			CF7_API_TESTING,
			'Should be running in testing mode'
		);

		$this->assertTrue(
			defined( 'ABSPATH' ),
			'WordPress ABSPATH should be defined'
		);
	}

	/**
	 * Test WordPress hooks are available
	 *
	 * @return void
	 */
	public function test_wordpress_hooks_available(): void {
		$this->assertTrue( function_exists( 'add_action' ), 'add_action should be available' );
		$this->assertTrue( function_exists( 'add_filter' ), 'add_filter should be available' );
		$this->assertTrue( function_exists( 'do_action' ), 'do_action should be available' );
		$this->assertTrue( function_exists( 'apply_filters' ), 'apply_filters should be available' );
	}

	/**
	 * Test hook registration and execution
	 *
	 * @return void
	 */
	public function test_hook_registration_and_execution(): void {
		$callback_executed = false;

		add_action(
			'cf7_api_test_action',
			function () use ( &$callback_executed ) {
				$callback_executed = true;
			}
		);

		do_action( 'cf7_api_test_action' );

		$this->assertTrue( $callback_executed, 'Action callback should have been executed' );
	}

	/**
	 * Test filter registration and execution
	 *
	 * @return void
	 */
	public function test_filter_registration_and_execution(): void {
		add_filter(
			'cf7_api_test_filter',
			function ( $value ) {
				return $value . '_filtered';
			}
		);

		$result = apply_filters( 'cf7_api_test_filter', 'original' );

		$this->assertSame( 'original_filtered', $result, 'Filter should modify the value' );
	}

	/**
	 * Test WordPress i18n functions available
	 *
	 * @return void
	 */
	public function test_wordpress_i18n_functions(): void {
		$this->assertTrue( function_exists( '__' ), 'Translation function __ should be available' );
		$this->assertTrue( function_exists( 'esc_html__' ), 'esc_html__ should be available' );
		$this->assertTrue( function_exists( '_e' ), '_e should be available' );

		// Test translation returns string
		$translated = __( 'Test String', 'contact-form-to-api' );
		$this->assertIsString( $translated );
	}

	/**
	 * Test WordPress option operations
	 *
	 * @return void
	 */
	public function test_wordpress_option_operations(): void {
		$option_name = 'cf7_api_test_option';
		$test_value  = array( 'api_url' => 'https://example.com/api' );

		// Save option
		$save_result = update_option( $option_name, $test_value );
		$this->assertTrue( $save_result, 'Should be able to save option' );

		// Retrieve option
		$retrieved = get_option( $option_name );
		$this->assertSame( $test_value, $retrieved, 'Retrieved value should match saved value' );

		// Delete option
		$delete_result = delete_option( $option_name );
		$this->assertTrue( $delete_result, 'Should be able to delete option' );

		// Verify deleted
		$after_delete = get_option( $option_name, 'default' );
		$this->assertSame( 'default', $after_delete, 'Option should be deleted' );
	}

	/**
	 * Test WordPress nonce security
	 *
	 * @return void
	 */
	public function test_wordpress_nonce_security(): void {
		$action = 'cf7_api_test_action';

		// Create nonce
		$nonce = wp_create_nonce( $action );
		$this->assertIsString( $nonce, 'Nonce should be a string' );
		$this->assertNotEmpty( $nonce, 'Nonce should not be empty' );

		// Verify nonce - returns 1 or 2 on success, false on failure
		$is_valid = wp_verify_nonce( $nonce, $action );
		$this->assertNotFalse( $is_valid, 'Nonce should be valid' );
		$this->assertContains( $is_valid, array( 1, 2 ), 'Valid nonce returns 1 or 2' );

		// Invalid nonce
		$invalid = wp_verify_nonce( 'invalid_nonce', $action );
		$this->assertFalse( $invalid, 'Invalid nonce should fail verification' );
	}

	/**
	 * Test WordPress error handling
	 *
	 * @return void
	 */
	public function test_wordpress_error_handling(): void {
		// Normal data is not an error
		$this->assertFalse( is_wp_error( array( 'success' => true ) ) );
		$this->assertFalse( is_wp_error( 'string' ) );
		$this->assertFalse( is_wp_error( 123 ) );

		// WP_Error is an error
		$error = new WP_Error( 'test_error', 'Test error message' );
		$this->assertTrue( is_wp_error( $error ) );
		$this->assertSame( 'test_error', $error->get_error_code() );
		$this->assertSame( 'Test error message', $error->get_error_message() );
	}

	/**
	 * Test WordPress sanitization functions
	 *
	 * @return void
	 */
	public function test_wordpress_sanitization(): void {
		// sanitize_text_field removes HTML and extra whitespace
		$dirty     = "  Test <script>alert('xss')</script>  ";
		$sanitized = sanitize_text_field( $dirty );
		$this->assertStringNotContainsString( '<script>', $sanitized );
		$this->assertStringNotContainsString( '</script>', $sanitized );

		// sanitize_email
		$this->assertSame( 'test@example.com', sanitize_email( 'test@example.com' ) );
		$this->assertSame( '', sanitize_email( 'not-an-email' ) );

		// sanitize_url
		$this->assertSame( 'https://example.com/', sanitize_url( 'https://example.com/' ) );
	}

	/**
	 * Test WordPress escaping functions
	 *
	 * @return void
	 */
	public function test_wordpress_escaping(): void {
		// esc_html escapes HTML entities
		$html    = "<script>alert('xss')</script>";
		$escaped = esc_html( $html );
		$this->assertStringNotContainsString( '<script>', $escaped );
		$this->assertStringContainsString( '&lt;script&gt;', $escaped );

		// esc_attr for attribute values
		$attr = 'value" onclick="alert(1)';
		$this->assertStringNotContainsString( '"', esc_attr( $attr ) );

		// esc_url for URLs
		$this->assertSame( 'https://example.com/', esc_url( 'https://example.com/' ) );
	}

	/**
	 * Test WordPress cron functions
	 *
	 * @return void
	 */
	public function test_wordpress_cron_functions(): void {
		$hook = 'cf7_api_test_cron';

		// Clear any existing scheduled events
		wp_clear_scheduled_hook( $hook );

		// Verify not scheduled
		$this->assertFalse( wp_next_scheduled( $hook ) );

		// Schedule event
		$timestamp = time() + 3600;
		$result    = wp_schedule_event( $timestamp, 'hourly', $hook );

		// wp_schedule_event returns true on success in WP 5.7+
		if ( false !== $result ) {
			$this->assertTrue( $result );
			$next = wp_next_scheduled( $hook );
			$this->assertIsInt( $next );
		}

		// Cleanup
		wp_clear_scheduled_hook( $hook );
	}

	/**
	 * Test WordPress plugin constants
	 *
	 * @return void
	 */
	public function test_plugin_constants(): void {
		$this->assertTrue( defined( 'CF7_API_FILE' ), 'CF7_API_FILE should be defined' );
		$this->assertTrue( defined( 'CF7_API_VERSION' ), 'CF7_API_VERSION should be defined' );

		$this->assertFileExists( CF7_API_FILE, 'Plugin file should exist' );
	}

	/**
	 * Test WordPress multisite detection
	 *
	 * @return void
	 */
	public function test_wordpress_multisite_detection(): void {
		$is_multisite = is_multisite();
		$this->assertIsBool( $is_multisite );

		$blog_id = get_current_blog_id();
		$this->assertIsInt( $blog_id );
		$this->assertGreaterThan( 0, $blog_id );
	}

	/**
	 * Test WordPress user capabilities
	 *
	 * @return void
	 */
	public function test_wordpress_user_capabilities(): void {
		// Create admin user for testing
		$admin_id = static::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->assertTrue( current_user_can( 'manage_options' ) );
		$this->assertTrue( current_user_can( 'edit_posts' ) );

		// Create subscriber
		$subscriber_id = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$this->assertFalse( current_user_can( 'manage_options' ) );
		$this->assertFalse( current_user_can( 'edit_posts' ) );
	}

	/**
	 * Test WordPress database operations via $wpdb
	 *
	 * @return void
	 */
	public function test_wordpress_database_operations(): void {
		global $wpdb;

		$this->assertInstanceOf( 'wpdb', $wpdb );
		$this->assertNotEmpty( $wpdb->prefix );

		// Test simple query
		$result = $wpdb->get_var( 'SELECT 1' );
		$this->assertEquals( '1', $result );
	}
}
