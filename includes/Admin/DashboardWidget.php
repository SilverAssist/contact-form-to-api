<?php
/**
 * Dashboard Widget
 *
 * WordPress dashboard widget displaying API request statistics.
 * Provides at-a-glance visibility into API health and recent errors.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.1.3
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Admin\Views\DashboardWidgetView;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;

\defined( 'ABSPATH' ) || exit;

/**
 * Class DashboardWidget
 *
 * Registers and manages the CF7 API Status dashboard widget.
 *
 * @since 1.1.3
 */
class DashboardWidget implements LoadableInterface {

	/**
	 * Singleton instance
	 *
	 * @var DashboardWidget|null
	 */
	private static ?DashboardWidget $instance = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return DashboardWidget
	 */
	public static function instance(): DashboardWidget {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		// Empty - initialization happens in init().
	}

	/**
	 * Initialize dashboard widget
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		\add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 30; // Admin components.
	}

	/**
	 * Determine if should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		// Only load in admin and for users with manage_options capability
		return \is_admin() && \current_user_can( 'manage_options' );
	}

	/**
	 * Register dashboard widget
	 *
	 * @return void
	 */
	public function register_widget(): void {
		\wp_add_dashboard_widget(
			'cf7_api_dashboard_widget',
			\__( 'CF7 API Status', CF7_API_TEXT_DOMAIN ),
			array( $this, 'render' ),
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render dashboard widget
	 *
	 * @return void
	 */
	public function render(): void {
		$stats = $this->get_statistics();
		DashboardWidgetView::render( $stats );
	}

	/**
	 * Get statistics for dashboard
	 *
	 * @return array<string, mixed> Statistics array
	 */
	private function get_statistics(): array {
		$logger = new RequestLogger();

		return array(
			'total_24h'          => $logger->get_count_last_hours( 24 ),
			'success_24h'        => $logger->get_count_last_hours( 24, 'success' ),
			'errors_24h'         => $logger->get_count_last_hours( 24, 'error' ),
			'success_rate'       => $logger->get_success_rate_last_hours( 24 ),
			'avg_response_time'  => $logger->get_avg_response_time_last_hours( 24 ),
			'recent_errors'      => $logger->get_recent_errors( 5 ),
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		// Only enqueue on dashboard page
		if ( 'index.php' !== $hook ) {
			return;
		}

		\wp_enqueue_style(
			'cf7-dashboard-widget',
			CF7_API_URL . 'assets/css/dashboard-widget.css',
			array(),
			CF7_API_VERSION
		);
	}
}
