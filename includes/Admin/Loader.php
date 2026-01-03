<?php
/**
 * Admin Loader
 *
 * Loads and initializes all Admin components including SettingsPage and RequestLogController.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.1.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Class Loader
 *
 * Manages loading of Admin components.
 */
class Loader implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var Loader|null
	 */
	private static ?Loader $instance = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return Loader
	 */
	public static function instance(): Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Initialize Admin components
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		$this->init_components();
		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 30; // Admin components - after Services (20).
	}

	/**
	 * Determine if Admin should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return \is_admin();
	}

	/**
	 * Initialize Admin components
	 *
	 * @return void
	 */
	private function init_components(): void {
		// Initialize Settings Hub page (Silver Assist dashboard).
		if ( \class_exists( SettingsPage::class ) ) {
			$settings_page = SettingsPage::instance();
			if ( $settings_page->should_load() ) {
				$settings_page->init();
			}
		}

		// Initialize Global Settings Controller (Settings Hub submenu).
		if ( \class_exists( GlobalSettingsController::class ) ) {
			$global_settings = GlobalSettingsController::instance();
			if ( $global_settings->should_load() ) {
				$global_settings->init();
			}
		}

		// Initialize Request Log Controller (under Contact Form 7 menu).
		if ( \class_exists( RequestLogController::class ) ) {
			$request_log_controller = RequestLogController::instance();
			if ( $request_log_controller->should_load() ) {
				$request_log_controller->init();
			}
		}

		// Initialize Dashboard Widget.
		if ( \class_exists( DashboardWidget::class ) ) {
			$dashboard_widget = DashboardWidget::instance();
			if ( $dashboard_widget->should_load() ) {
				$dashboard_widget->init();
			}
		}
	}
}
