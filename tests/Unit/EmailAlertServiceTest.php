<?php
/**
 * Unit Tests for EmailAlertService Class
 *
 * Tests the email alert service functionality including
 * alert triggering, cooldown logic, and email sending.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since 1.2.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Services\EmailAlertService;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * Test cases for the EmailAlertService class
 */
class EmailAlertServiceTest extends TestCase {

	/**
	 * EmailAlertService instance
	 *
	 * @var EmailAlertService
	 */
	private EmailAlertService $service;

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

		// Get fresh instances.
		$this->service  = EmailAlertService::instance();
		$this->settings = Settings::instance();

		// Reset settings to defaults.
		$this->settings->reset();
		$this->settings->init();
	}

	/**
	 * Test singleton pattern
	 *
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = EmailAlertService::instance();
		$instance2 = EmailAlertService::instance();

		$this->assertSame( $instance1, $instance2, 'EmailAlertService should follow singleton pattern' );
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function test_loadable_interface_implementation(): void {
		$this->assertSame( 20, $this->service->get_priority(), 'EmailAlertService priority should be 20 (Services)' );
		$this->assertTrue( $this->service->should_load(), 'EmailAlertService should always load' );
	}

	/**
	 * Test check_and_alert does not run when alerts disabled
	 *
	 * @return void
	 */
	public function test_check_and_alert_does_not_run_when_disabled(): void {
		// Disable alerts.
		$this->settings->set( 'alerts_enabled', false );

		// Mock wp_mail to verify it's not called.
		$mail_called = false;
		\add_filter(
			'pre_wp_mail',
			function () use ( &$mail_called ) {
				$mail_called = true;
				return true;
			}
		);

		// Run check.
		$this->service->check_and_alert();

		// Verify wp_mail was not called.
		$this->assertFalse( $mail_called, 'Email should not be sent when alerts are disabled' );
	}

	/**
	 * Test send_test_email with invalid email
	 *
	 * @return void
	 */
	public function test_send_test_email_with_invalid_email(): void {
		$result = $this->service->send_test_email( 'invalid-email' );
		$this->assertFalse( $result, 'send_test_email should return false for invalid email' );
	}

	/**
	 * Test send_test_email with valid email
	 *
	 * @return void
	 */
	public function test_send_test_email_with_valid_email(): void {
		// Mock wp_mail to return success.
		\add_filter(
			'pre_wp_mail',
			function () {
				return true;
			}
		);

		$result = $this->service->send_test_email( 'test@example.com' );
		$this->assertTrue( $result, 'send_test_email should return true for valid email' );
	}

	/**
	 * Test alert settings defaults
	 *
	 * @return void
	 */
	public function test_alert_settings_defaults(): void {
		$this->assertFalse( $this->settings->is_alerts_enabled(), 'Alerts should be disabled by default' );
		$this->assertSame( 10, $this->settings->get_alert_error_threshold(), 'Default error threshold should be 10' );
		$this->assertSame( 20, $this->settings->get_alert_rate_threshold(), 'Default rate threshold should be 20' );
		$this->assertSame( 'hourly', $this->settings->get_alert_check_interval(), 'Default check interval should be hourly' );
		$this->assertSame( 4, $this->settings->get_alert_cooldown_hours(), 'Default cooldown should be 4 hours' );
	}

	/**
	 * Test alert settings can be updated
	 *
	 * @return void
	 */
	public function test_alert_settings_can_be_updated(): void {
		// Update alert settings.
		$this->settings->set( 'alerts_enabled', true );
		$this->settings->set( 'alert_recipients', 'admin@test.com, user@test.com' );
		$this->settings->set( 'alert_error_threshold', 15 );
		$this->settings->set( 'alert_rate_threshold', 30 );
		$this->settings->set( 'alert_check_interval', 'twicehourly' );
		$this->settings->set( 'alert_cooldown_hours', 8 );

		// Verify updates.
		$this->assertTrue( $this->settings->is_alerts_enabled() );
		$this->assertSame( 'admin@test.com, user@test.com', $this->settings->get_alert_recipients() );
		$this->assertSame( 15, $this->settings->get_alert_error_threshold() );
		$this->assertSame( 30, $this->settings->get_alert_rate_threshold() );
		$this->assertSame( 'twicehourly', $this->settings->get_alert_check_interval() );
		$this->assertSame( 8, $this->settings->get_alert_cooldown_hours() );
	}

	/**
	 * Test last alert sent timestamp can be updated
	 *
	 * @return void
	 */
	public function test_last_alert_sent_can_be_updated(): void {
		$timestamp = \time();
		$this->settings->update_alert_last_sent( $timestamp );

		$this->assertSame( $timestamp, $this->settings->get_alert_last_sent() );
	}

	/**
	 * Test alert recipients getter
	 *
	 * @return void
	 */
	public function test_alert_recipients_defaults_to_admin_email(): void {
		$admin_email = \get_option( 'admin_email' );
		$recipients  = $this->settings->get_alert_recipients();

		$this->assertSame( $admin_email, $recipients, 'Recipients should default to admin email' );
	}
}
