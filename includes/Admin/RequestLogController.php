<?php
/**
 * Request Log Controller
 *
 * WordPress admin interface controller for viewing and managing API request logs.
 * Uses RequestLogView for HTML rendering and RequestLogTable for list display.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.1.0
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Admin\Views\RequestLogView;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Class RequestLogController
 *
 * Admin controller for API request logs.
 * Handles routing, actions, and delegates rendering to RequestLogView.
 *
 * @since 1.1.0
 */
class RequestLogController implements LoadableInterface {

	/**
	 * Singleton instance
	 *
	 * @var RequestLogController|null
	 */
	private static ?RequestLogController $instance = null;

	/**
	 * List table instance
	 *
	 * @var RequestLogTable|null
	 */
	private ?RequestLogTable $list_table = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return RequestLogController
	 */
	public static function instance(): RequestLogController {
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
	 * Initialize admin interface
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		\add_action( 'admin_menu', array( $this, 'register_menu' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		\add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

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
		return \is_admin();
	}

	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$hook = \add_submenu_page(
			'wpcf7',
			\__( 'API Logs', 'contact-form-to-api' ),
			\__( 'API Logs', 'contact-form-to-api' ),
			'manage_options',
			'cf7-api-logs',
			array( $this, 'handle_page_request' )
		);

		\add_action( "load-{$hook}", array( $this, 'screen_options' ) );
		\add_action( "load-{$hook}", array( $this, 'process_bulk_actions' ) );
	}

	/**
	 * Add screen options
	 *
	 * @return void
	 */
	public function screen_options(): void {
		$option = 'per_page';
		$args   = array(
			'label'   => \__( 'Logs per page', 'contact-form-to-api' ),
			'default' => 20,
			'option'  => 'cf7_api_logs_per_page',
		);

		\add_screen_option( $option, $args );

		// Initialize list table.
		$this->list_table = new RequestLogTable();
	}

	/**
	 * Set screen option
	 *
	 * @param mixed  $status Status.
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return mixed
	 */
	public function set_screen_option( $status, string $option, $value ) {
		if ( 'cf7_api_logs_per_page' === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Process bulk actions
	 *
	 * @return void
	 */
	public function process_bulk_actions(): void {
		if ( ! $this->list_table ) {
			return;
		}

		$action = $this->list_table->current_action();

		if ( ! $action ) {
			return;
		}

		// Skip non-bulk actions (like 'view' for single log detail).
		$bulk_actions = array( 'delete', 'retry' );
		if ( ! \in_array( $action, $bulk_actions, true ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->list_table->_args['plural'] ) ) {
			\wp_die( \esc_html__( 'Security check failed', 'contact-form-to-api' ) );
		}

		$log_ids = isset( $_REQUEST['log'] ) ? \array_map( 'absint', (array) $_REQUEST['log'] ) : array();

		if ( empty( $log_ids ) ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				$this->handle_delete_action( $log_ids );
				break;

			case 'retry':
				$this->handle_retry_action( $log_ids );
				break;
		}
	}

	/**
	 * Handle delete action
	 *
	 * @param array<int, int> $log_ids Log IDs to delete.
	 * @return void
	 */
	private function handle_delete_action( array $log_ids ): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		foreach ( $log_ids as $log_id ) {
			$wpdb->delete(
				$table_name,
				array( 'id' => $log_id ),
				array( '%d' )
			);
		}

		$redirect = \add_query_arg(
			array( 'deleted' => \count( $log_ids ) ),
			\wp_get_referer()
		);
		\wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle retry action
	 *
	 * @param array<int, int> $log_ids Log IDs to retry.
	 * @return void
	 */
	private function handle_retry_action( array $log_ids ): void {
		// TODO: Implement retry logic for failed requests.
		$redirect = \add_query_arg(
			array( 'retried' => \count( $log_ids ) ),
			\wp_get_referer()
		);
		\wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle page request - routes to appropriate view
	 *
	 * @return void
	 */
	public function handle_page_request(): void {
		if ( ! $this->list_table ) {
			$this->list_table = new RequestLogTable();
		}

		// Check for single log view.
		if ( isset( $_GET['action'] ) && 'view' === $_GET['action'] && isset( $_GET['log_id'] ) ) {
			$this->show_log_detail( \absint( $_GET['log_id'] ) );
			return;
		}

		// Show list view.
		$this->show_logs_list();
	}

	/**
	 * Show logs list page
	 *
	 * @return void
	 */
	private function show_logs_list(): void {
		// Show admin notices.
		RequestLogView::render_notices();

		// Prepare items.
		if ( $this->list_table ) {
			$this->list_table->prepare_items();
		}

		// Render page.
		RequestLogView::render_page( $this->list_table );
	}

	/**
	 * Show log detail page
	 *
	 * @param int $log_id Log ID.
	 * @return void
	 */
	private function show_log_detail( int $log_id ): void {
		$log = $this->get_log_by_id( $log_id );

		if ( ! $log ) {
			\wp_die( \esc_html__( 'Log entry not found.', 'contact-form-to-api' ) );
		}

		RequestLogView::render_detail( $log );
	}

	/**
	 * Get log entry by ID
	 *
	 * @param int $log_id Log ID.
	 * @return array<string, mixed>|null Log data or null if not found.
	 */
	private function get_log_by_id( int $log_id ): ?array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		$log = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ),
			ARRAY_A
		);

		return $log ?: null;
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'contact_page_cf7-api-logs' !== $hook ) {
			return;
		}

		\wp_enqueue_style(
			'cf7-request-log-admin',
			CF7_API_URL . 'assets/css/request-log.css',
			array(),
			CF7_API_VERSION
		);

		\wp_enqueue_script(
			'cf7-api-log-admin',
			CF7_API_URL . 'assets/js/api-log-admin.js',
			array( 'jquery' ),
			CF7_API_VERSION,
			true
		);
	}
}
