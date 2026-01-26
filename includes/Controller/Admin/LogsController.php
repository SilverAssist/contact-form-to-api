<?php
/**
 * Request Log Controller
 *
 * WordPress admin interface controller for viewing and managing API request logs.
 * Uses RequestLogView for HTML rendering and RequestLogTable for list display.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Controller\Admin
 * @since 1.1.0
 * @version 2.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Controller\Admin;

use SilverAssist\ContactFormToAPI\Config\Settings;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Infrastructure\ListTable\RequestLogTable;
use SilverAssist\ContactFormToAPI\Service\Api\ApiClient;
use SilverAssist\ContactFormToAPI\Service\Export\ExportService;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;
use SilverAssist\ContactFormToAPI\Service\Notification\EmailAlertService;
use SilverAssist\ContactFormToAPI\Utils\DateFilterTrait;
use SilverAssist\ContactFormToAPI\View\Admin\Logs\RequestLogView;

\defined( 'ABSPATH' ) || exit;

/**
 * Class RequestLogController
 *
 * Admin controller for API request logs.
 * Handles routing, actions, and delegates rendering to RequestLogView.
 *
 * @since 1.1.0
 */
class LogsController implements LoadableInterface {

	use DateFilterTrait;

	/**
	 * Singleton instance
	 *
	 * @var LogsController|null
	 */
	private static ?LogsController $instance = null;

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
	 * @return LogsController
	 */
	public static function instance(): LogsController {
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

		// Handle exports early before any output is sent.
		\add_action( 'admin_init', array( $this, 'maybe_handle_export' ) );

		$this->initialized = true;
	}

	/**
	 * Handle export requests early (before any output)
	 *
	 * This must run on admin_init to send headers before WordPress outputs anything.
	 *
	 * @return void
	 */
	public function maybe_handle_export(): void {
		// Only handle on our page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified in handle_export_action().
		if ( ! isset( $_GET['page'] ) || 'cf7-api-logs' !== $_GET['page'] ) {
			return;
		}

		// Don't allow exports if logging is disabled.
		if ( ! $this->is_logging_enabled() ) {
			return;
		}

		// Check for export actions.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified in handle_export_action().
		if ( ! isset( $_GET['action'] ) || ! \in_array( $_GET['action'], array( 'export_csv', 'export_json' ), true ) ) {
			return;
		}

		$this->handle_export_action();
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
	 * Only registers the menu if logging is enabled in settings.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		// Don't show API Logs menu if logging is disabled.
		if ( ! $this->is_logging_enabled() ) {
			return;
		}

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
	 * Check if logging is enabled
	 *
	 * @return bool
	 */
	private function is_logging_enabled(): bool {
		try {
			$settings = Settings::instance();
			return $settings->is_logging_enabled();
		} catch ( \Exception $e ) {
			// Settings not available, default to enabled.
			unset( $e );
		}
		return true;
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
	 * Retries failed API requests with rate limiting.
	 * Skips entries that have already been successfully retried.
	 *
	 * @param array<int, int> $log_ids Log IDs to retry.
	 * @return void
	 */
	private function handle_retry_action( array $log_ids ): void {
		$api_client    = ApiClient::instance();
		$retry_manager = new RetryManager();

		$success_count = 0;
		$failed_count  = 0;
		$skipped_count = 0;

		// Rate limiting from settings
		$max_retries_per_entry = RetryManager::get_max_manual_retries();
		$max_retries_per_hour  = RetryManager::get_max_retries_per_hour();

		// Check global rate limit (hourly)
		$recent_retries = $this->count_recent_retries( 1 ); // Last 1 hour
		if ( $recent_retries >= $max_retries_per_hour ) {
			$redirect = \add_query_arg(
				array(
					'retry_error' => 'rate_limit',
				),
				\wp_get_referer()
			);
			\wp_safe_redirect( $redirect );
			exit;
		}

		foreach ( $log_ids as $log_id ) {
			// Skip if already successfully retried.
			if ( $retry_manager->has_successful_retry( $log_id ) ) {
				++$skipped_count;
				continue;
			}

			// Check per-entry retry limit.
			$retry_count = $retry_manager->count_retries( $log_id );
			if ( $retry_count >= $max_retries_per_entry ) {
				// Trigger individual failure alert if retries exhausted.
				$this->maybe_trigger_individual_alert( $log_id );
				++$skipped_count;
				continue;
			}

			// Check if we've hit the per-hour limit (including recent retries)
			if ( $recent_retries + $success_count + $failed_count >= $max_retries_per_hour ) {
				++$skipped_count;
				continue;
			}

			// Attempt retry
			$result = $api_client->retry_from_log( $log_id );

			if ( $result['success'] ) {
				++$success_count;
			} else {
				++$failed_count;

				// Check if this was the last allowed retry.
				$updated_retry_count = $retry_manager->count_retries( $log_id );
				if ( $updated_retry_count >= $max_retries_per_entry ) {
					// Trigger individual failure alert after final retry failed.
					$this->maybe_trigger_individual_alert( $log_id );
				}
			}
		}

		// Build redirect with results
		$args = array();
		if ( $success_count > 0 ) {
			$args['retried_success'] = $success_count;
		}
		if ( $failed_count > 0 ) {
			$args['retried_failed'] = $failed_count;
		}
		if ( $skipped_count > 0 ) {
			$args['retried_skipped'] = $skipped_count;
		}

		$redirect = \add_query_arg( $args, \wp_get_referer() );
		\wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Count recent retries for rate limiting
	 *
	 * Counts retries in the last N hours.
	 *
	 * @param int $hours Number of hours to look back.
	 * @return int Number of retries.
	 */
	private function count_recent_retries( int $hours ): int {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i 
				WHERE retry_of IS NOT NULL 
				AND created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)',
				$table_name,
				$hours
			)
		);

		return (int) ( $count ?: 0 );
	}

	/**
	 * Handle page request - routes to appropriate view
	 *
	 * Note: Export actions are handled earlier via admin_init hook
	 * to prevent "headers already sent" errors.
	 *
	 * @return void
	 */
	public function handle_page_request(): void {
		// Prevent access if logging is disabled.
		if ( ! $this->is_logging_enabled() ) {
			\wp_die(
				\esc_html__( 'API logging is disabled. Enable it in the plugin settings to view logs.', 'contact-form-to-api' ),
				\esc_html__( 'Logging Disabled', 'contact-form-to-api' ),
				array( 'back_link' => true )
			);
		}

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

		// Get forms with logs for the filter dropdown.
		$log_reader      = new LogReader();
		$forms_with_logs = $log_reader->get_forms_with_logs();

		// Get statistics for display.
		$stats_data   = $this->get_statistics_data();
		$stats        = $stats_data['stats'];
		$date_context = $stats_data['date_context'];

		// Render page.
		RequestLogView::render_page( $this->list_table, $forms_with_logs, $stats, $date_context );
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

		$retry_manager = new RetryManager();
		RequestLogView::render_detail( $log, $retry_manager );
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
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table_name, $log_id ),
			ARRAY_A
		);

		if ( ! $log ) {
			return null;
		}

		// Decrypt log fields if encryption is enabled.
		$log_reader = new LogReader();
		$log        = $log_reader->decrypt_log_fields( $log );

		return $log;
	}

	/**
	 * Get statistics data for the logs page
	 *
	 * Fetches statistics from LogStatistics service based on current filters.
	 *
	 * @since 2.1.0
	 * @return array{stats: array<string, mixed>, date_context: string} Statistics and date context.
	 */
	private function get_statistics_data(): array {
		$stats_service = new LogStatistics();

		// Get form_id from query if filtering by form.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering.
		$form_id = isset( $_GET['form_id'] ) ? \absint( $_GET['form_id'] ) : 0;

		// Get date filter parameters.
		$date_params = $this->get_date_range_from_filter();
		$date_filter = $date_params['filter'];
		$date_start  = $date_params['start'];
		$date_end    = $date_params['end'];

		$stats = $stats_service->get_statistics( $form_id, $date_start, $date_end );

		// Determine date context label.
		$date_context = $this->get_date_context_label( $date_filter, $date_start, $date_end );

		return array(
			'stats'        => $stats,
			'date_context' => $date_context,
		);
	}

	/**
	 * Get date context label for statistics
	 *
	 * @since 2.1.0
	 * @param string      $date_filter Date filter type.
	 * @param string|null $date_start  Start date.
	 * @param string|null $date_end    End date.
	 * @return string Date context label (e.g., "(Today)", "(All Time)").
	 */
	private function get_date_context_label( string $date_filter, ?string $date_start, ?string $date_end ): string {
		if ( empty( $date_filter ) ) {
			return '(' . \__( 'All Time', 'contact-form-to-api' ) . ')';
		}

		$labels = array(
			'today'     => '(' . \__( 'Today', 'contact-form-to-api' ) . ')',
			'yesterday' => '(' . \__( 'Yesterday', 'contact-form-to-api' ) . ')',
			'7days'     => '(' . \__( 'Last 7 Days', 'contact-form-to-api' ) . ')',
			'30days'    => '(' . \__( 'Last 30 Days', 'contact-form-to-api' ) . ')',
			'month'     => '(' . \__( 'This Month', 'contact-form-to-api' ) . ')',
		);

		if ( isset( $labels[ $date_filter ] ) ) {
			return $labels[ $date_filter ];
		}

		if ( 'custom' === $date_filter && $date_start ) {
			return '(' . \esc_html( $date_start ) . ' - ' . \esc_html( $date_end ?: \__( 'now', 'contact-form-to-api' ) ) . ')';
		}

		return '(' . \__( 'All Time', 'contact-form-to-api' ) . ')';
	}

	/**
	 * Get date range from filter parameter
	 *
	 * Converts date filter type to start/end date strings.
	 *
	 * @since 2.1.0
	 * @return array{filter: string, start: string|null, end: string|null} Date range parameters.
	 */
	private function get_date_range_from_filter(): array {
		$params      = $this->get_date_filter_params();
		$date_filter = $params['filter'];

		if ( empty( $date_filter ) ) {
			return array(
				'filter' => '',
				'start'  => null,
				'end'    => null,
			);
		}

		$current_date = \current_time( 'Y-m-d' );

		return match ( $date_filter ) {
			'today' => array(
				'filter' => 'today',
				'start'  => $current_date,
				'end'    => $current_date,
			),
			'yesterday' => array(
				'filter' => 'yesterday',
				'start'  => \gmdate( 'Y-m-d', \strtotime( '-1 day', \strtotime( $current_date ) ) ),
				'end'    => \gmdate( 'Y-m-d', \strtotime( '-1 day', \strtotime( $current_date ) ) ),
			),
			'7days' => array(
				'filter' => '7days',
				'start'  => \gmdate( 'Y-m-d', \strtotime( '-7 days', \strtotime( $current_date ) ) ),
				'end'    => $current_date,
			),
			'30days' => array(
				'filter' => '30days',
				'start'  => \gmdate( 'Y-m-d', \strtotime( '-30 days', \strtotime( $current_date ) ) ),
				'end'    => $current_date,
			),
			'month' => array(
				'filter' => 'month',
				'start'  => \gmdate( 'Y-m-01', \strtotime( $current_date ) ),
				'end'    => $current_date,
			),
			'custom' => $this->get_custom_date_range( $params['start'], $params['end'] ),
			default => array(
				'filter' => '',
				'start'  => null,
				'end'    => null,
			),
		};
	}

	/**
	 * Get custom date range from request parameters
	 *
	 * @since 2.1.0
	 * @param string $date_start Start date.
	 * @param string $date_end   End date.
	 * @return array{filter: string, start: string|null, end: string|null} Custom date range.
	 */
	private function get_custom_date_range( string $date_start, string $date_end ): array {
		$valid_start = ! empty( $date_start ) && $this->is_valid_date_format( $date_start );
		$valid_end   = empty( $date_end ) || $this->is_valid_date_format( $date_end );

		if ( ! $valid_start ) {
			return array(
				'filter' => 'custom',
				'start'  => null,
				'end'    => null,
			);
		}

		return array(
			'filter' => 'custom',
			'start'  => $date_start,
			'end'    => $valid_end ? ( $date_end ?: null ) : null,
		);
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

		// Localize script with translated strings.
		\wp_localize_script(
			'cf7-api-log-admin',
			'cf7ApiAdmin',
			array(
				'confirmDelete'      => \__( 'Are you sure you want to delete this log entry?', 'contact-form-to-api' ),
				'selectItems'        => \__( 'Please select at least one item.', 'contact-form-to-api' ),
				'confirmBulkDelete'  => \__( 'Are you sure you want to delete the selected log entries?', 'contact-form-to-api' ),
				'confirmRetry'       => \__( 'Are you sure you want to retry this request?', 'contact-form-to-api' ),
				'confirmBulkRetry'   => \__( 'Are you sure you want to retry the selected requests?', 'contact-form-to-api' ),
				'dateStartBeforeEnd' => \__( 'Start date must be before or equal to end date.', 'contact-form-to-api' ),
				'dateEndAfterStart'  => \__( 'End date must be after or equal to start date.', 'contact-form-to-api' ),
			)
		);
	}

	/**
	 * Handle export action
	 *
	 * Routes to appropriate export handler based on action.
	 *
	 * @return void
	 */
	private function handle_export_action(): void {
		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) ), 'cf7_api_export_logs' ) ) {
			\wp_die( \esc_html__( 'Security check failed', 'contact-form-to-api' ) );
		}

		// Verify capability.
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'contact-form-to-api' ) );
		}

		$action = \sanitize_text_field( \wp_unslash( $_GET['action'] ) );

		switch ( $action ) {
			case 'export_csv':
				$this->handle_export_csv();
				break;

			case 'export_json':
				$this->handle_export_json();
				break;

			default:
				\wp_die( \esc_html__( 'Invalid export action.', 'contact-form-to-api' ) );
		}
	}

	/**
	 * Handle CSV export
	 *
	 * Exports logs to CSV format with current filters applied.
	 *
	 * @return void
	 */
	private function handle_export_csv(): void {
		$logs = $this->get_filtered_logs();

		$export_service = ExportService::instance();
		$csv_content    = $export_service->export_csv( $logs );
		$filename       = $export_service->get_export_filename( 'csv' );

		// Set headers for download.
		\header( 'Content-Type: text/csv; charset=utf-8' );
		\header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		\header( 'Pragma: no-cache' );
		\header( 'Expires: 0' );

		echo $csv_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV content is escaped via fputcsv() for CSV format.
		exit;
	}

	/**
	 * Handle JSON export
	 *
	 * Exports logs to JSON format with current filters applied.
	 *
	 * @return void
	 */
	private function handle_export_json(): void {
		$logs = $this->get_filtered_logs();

		$export_service = ExportService::instance();
		$json_content   = $export_service->export_json( $logs );
		$filename       = $export_service->get_export_filename( 'json' );

		// Set headers for download.
		\header( 'Content-Type: application/json; charset=utf-8' );
		\header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		\header( 'Pragma: no-cache' );
		\header( 'Expires: 0' );

		echo $json_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON content is escaped via wp_json_encode() for JSON format.
		exit;
	}

	/**
	 * Maximum number of logs to export
	 *
	 * Prevents memory exhaustion on large datasets.
	 */
	private const EXPORT_LIMIT = 10000;

	/**
	 * Get filtered logs for export
	 *
	 * Retrieves logs matching current filters with a reasonable limit to prevent memory issues.
	 * Maximum of 10,000 records can be exported at once.
	 *
	 * @return array<int, array<string, mixed>> Array of log entries.
	 */
	private function get_filtered_logs(): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Build WHERE clause with same logic as RequestLogTable.
		$where        = '1=1';
		$where_values = array();

		// Filter by status.
		if ( isset( $_GET['status'] ) && 'all' !== $_GET['status'] ) {
			$status = \sanitize_text_field( \wp_unslash( $_GET['status'] ) );
			if ( 'error' === $status ) {
				$where .= " AND status IN ('error', 'client_error', 'server_error')";
			} else {
				$where         .= ' AND status = %s';
				$where_values[] = $status;
			}
		}

		// Filter by form ID.
		if ( isset( $_GET['form_id'] ) && ! empty( $_GET['form_id'] ) ) {
			$where         .= ' AND form_id = %d';
			$where_values[] = \absint( $_GET['form_id'] );
		}

		// Search functionality.
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$search         = '%' . $wpdb->esc_like( \sanitize_text_field( \wp_unslash( $_GET['s'] ) ) ) . '%';
			$where         .= ' AND (endpoint LIKE %s OR error_message LIKE %s)';
			$where_values[] = $search;
			$where_values[] = $search;
		}

		// Apply date filter using shared trait method.
		$params      = $this->get_date_filter_params();
		$date_filter = $this->build_date_filter_clause( $params['filter'], $params['start'], $params['end'] );
		if ( ! empty( $date_filter['clause'] ) ) {
			$where         .= ' ' . $date_filter['clause'];
			$where_values   = \array_merge( $where_values, $date_filter['values'] );
		}

		// Prepare WHERE clause.
		if ( ! empty( $where_values ) ) {
			$where = $wpdb->prepare( $where, ...$where_values );
		}

		// Get logs matching filters with limit to prevent memory exhaustion.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- WHERE clause is prepared above if needed.
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE ' . $where . ' ORDER BY created_at DESC LIMIT %d',
				$table_name,
				self::EXPORT_LIMIT
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return $logs ?: array();
	}

	/**
	 * Maybe trigger individual failure alert
	 *
	 * Triggers an individual failure alert for a log entry if the feature is enabled.
	 * Gets the form_id from the log and passes it to the EmailAlertService.
	 *
	 * @since 2.1.0
	 * @param int $log_id Log entry ID.
	 * @return void
	 */
	private function maybe_trigger_individual_alert( int $log_id ): void {
		// Get log to retrieve form_id.
		$log_reader = new LogReader();
		$log        = $log_reader->get_log( $log_id );

		if ( null === $log || empty( $log['form_id'] ) ) {
			return;
		}

		$form_id = (int) $log['form_id'];

		// Trigger individual alert.
		$alert_service = EmailAlertService::instance();
		$alert_service->maybe_send_individual_alert( $log_id, $form_id );
	}
}
