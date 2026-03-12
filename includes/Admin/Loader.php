<?php
/**
 * Admin Loader
 *
 * Loads and initializes all Admin components including SettingsPage and RequestLogController.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.1.0
 * @version 2.3.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Controller\Admin\LogsController;
use SilverAssist\ContactFormToAPI\Controller\Admin\SettingsController;
use SilverAssist\ContactFormToAPI\Core\AssetHelper;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Infrastructure\Widget\DashboardWidget;

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
		// Register shared CSS variables for all admin pages.
		\add_action( 'admin_enqueue_scripts', array( $this, 'register_shared_styles' ), 5 );

		// Initialize Settings Hub page (Silver Assist dashboard).
		$settings_page = SettingsPage::instance();
		if ( $settings_page->should_load() ) {
			$settings_page->init();
		}

		// Initialize Settings Controller (Settings Hub submenu).
		$settings_controller = SettingsController::instance();
		if ( $settings_controller->should_load() ) {
			$settings_controller->init();
		}

		// Initialize Logs Controller (under Contact Form 7 menu).
		$logs_controller = LogsController::instance();
		if ( $logs_controller->should_load() ) {
			$logs_controller->init();
		}

		// Initialize Dashboard Widget.
		$dashboard_widget = DashboardWidget::instance();
		if ( $dashboard_widget->should_load() ) {
			$dashboard_widget->init();
		}
	}

	/**
	 * Register shared CSS variables stylesheet
	 *
	 * Registers the variables.css file so all component stylesheets
	 * can declare it as a dependency.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function register_shared_styles(): void {
		\wp_register_style(
			'cf7-api-variables',
			AssetHelper::get_url( 'assets/css/variables.css' ),
			array(),
			CF7_API_VERSION
		);
	}
}
