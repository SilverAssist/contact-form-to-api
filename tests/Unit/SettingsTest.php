<?php
/**
 * Unit Tests for Settings Class
 *
 * Tests the global settings management functionality including
 * defaults, getters, setters, and integration with WordPress options.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since 1.2.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * Test cases for the Settings class
 */
class SettingsTest extends TestCase {

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Set up before each test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Delete any existing settings.
		\delete_option( 'cf7_api_global_settings' );

		// Get fresh settings instance.
		$this->settings = Settings::instance();
		$this->settings->init();
	}

	/**
	 * Test singleton pattern
	 *
	 * @return void
	 */
	public function testSingletonPattern(): void {
		$instance1 = Settings::instance();
		$instance2 = Settings::instance();

		$this->assertSame( $instance1, $instance2, 'Settings should follow singleton pattern' );
	}

	/**
	 * Test default settings are returned
	 *
	 * @return void
	 */
	public function testGetDefaults(): void {
		$defaults = Settings::get_defaults();

		$this->assertIsArray( $defaults, 'Defaults should be an array' );
		$this->assertArrayHasKey( 'max_manual_retries', $defaults );
		$this->assertArrayHasKey( 'max_retries_per_hour', $defaults );
		$this->assertArrayHasKey( 'sensitive_patterns', $defaults );
		$this->assertArrayHasKey( 'logging_enabled', $defaults );
		$this->assertArrayHasKey( 'log_retention_days', $defaults );
	}

	/**
	 * Test get method returns default values when no settings are saved
	 *
	 * @return void
	 */
	public function testGetReturnsDefaultValues(): void {
		$this->assertSame( 3, $this->settings->get( 'max_manual_retries' ) );
		$this->assertSame( 10, $this->settings->get( 'max_retries_per_hour' ) );
		$this->assertTrue( $this->settings->get( 'logging_enabled' ) );
		$this->assertSame( 30, $this->settings->get( 'log_retention_days' ) );
	}

	/**
	 * Test get method with custom default value
	 *
	 * @return void
	 */
	public function testGetWithCustomDefault(): void {
		$value = $this->settings->get( 'non_existent_key', 'custom_default' );
		$this->assertSame( 'custom_default', $value );
	}

	/**
	 * Test set method updates single setting
	 *
	 * @return void
	 */
	public function testSetUpdatesSingleSetting(): void {
		$result = $this->settings->set( 'max_manual_retries', 5 );

		$this->assertTrue( $result, 'Set should return true on success' );
		$this->assertSame( 5, $this->settings->get( 'max_manual_retries' ) );
	}

	/**
	 * Test update method updates multiple settings
	 *
	 * @return void
	 */
	public function testUpdateUpdatesMultipleSettings(): void {
		$new_settings = array(
			'max_manual_retries'   => 5,
			'max_retries_per_hour' => 20,
			'logging_enabled'      => false,
		);

		$result = $this->settings->update( $new_settings );

		$this->assertTrue( $result, 'Update should return true on success' );
		$this->assertSame( 5, $this->settings->get( 'max_manual_retries' ) );
		$this->assertSame( 20, $this->settings->get( 'max_retries_per_hour' ) );
		$this->assertFalse( $this->settings->get( 'logging_enabled' ) );
	}

	/**
	 * Test get_max_manual_retries helper method
	 *
	 * @return void
	 */
	public function testGetMaxManualRetries(): void {
		$this->assertSame( 3, $this->settings->get_max_manual_retries() );

		$this->settings->set( 'max_manual_retries', 7 );
		$this->assertSame( 7, $this->settings->get_max_manual_retries() );
	}

	/**
	 * Test get_max_retries_per_hour helper method
	 *
	 * @return void
	 */
	public function testGetMaxRetriesPerHour(): void {
		$this->assertSame( 10, $this->settings->get_max_retries_per_hour() );

		$this->settings->set( 'max_retries_per_hour', 25 );
		$this->assertSame( 25, $this->settings->get_max_retries_per_hour() );
	}

	/**
	 * Test get_sensitive_patterns helper method
	 *
	 * @return void
	 */
	public function testGetSensitivePatterns(): void {
		$patterns = $this->settings->get_sensitive_patterns();

		$this->assertIsArray( $patterns, 'Patterns should be an array' );
		$this->assertContains( 'password', $patterns );
		$this->assertContains( 'token', $patterns );
		$this->assertContains( 'secret', $patterns );
	}

	/**
	 * Test is_logging_enabled helper method
	 *
	 * @return void
	 */
	public function testIsLoggingEnabled(): void {
		$this->assertTrue( $this->settings->is_logging_enabled() );

		$this->settings->set( 'logging_enabled', false );
		$this->assertFalse( $this->settings->is_logging_enabled() );
	}

	/**
	 * Test get_log_retention_days helper method
	 *
	 * @return void
	 */
	public function testGetLogRetentionDays(): void {
		$this->assertSame( 30, $this->settings->get_log_retention_days() );

		$this->settings->set( 'log_retention_days', 60 );
		$this->assertSame( 60, $this->settings->get_log_retention_days() );
	}

	/**
	 * Test reset method restores defaults
	 *
	 * @return void
	 */
	public function testResetRestoresDefaults(): void {
		// Change settings.
		$this->settings->set( 'max_manual_retries', 10 );
		$this->settings->set( 'logging_enabled', false );

		// Reset to defaults.
		$result = $this->settings->reset();

		$this->assertTrue( $result, 'Reset should return true on success' );
		$this->assertSame( 3, $this->settings->get( 'max_manual_retries' ) );
		$this->assertTrue( $this->settings->get( 'logging_enabled' ) );
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function testLoadableInterfaceImplementation(): void {
		$this->assertSame( 10, $this->settings->get_priority(), 'Settings priority should be 10 (Core)' );
		$this->assertTrue( $this->settings->should_load(), 'Settings should always load' );
	}

	/**
	 * Test settings persist across instances
	 *
	 * @return void
	 */
	public function testSettingsPersistAcrossInstances(): void {
		$this->settings->set( 'max_manual_retries', 8 );

		// Get new instance and verify persistence.
		\delete_option( 'cf7_api_global_settings' );
		\update_option( 'cf7_api_global_settings', array( 'max_manual_retries' => 8 ) );

		$new_settings = Settings::instance();
		$new_settings->init();

		$this->assertSame( 8, $new_settings->get( 'max_manual_retries' ) );
	}
}
