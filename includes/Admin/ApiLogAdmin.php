<?php
/**
 * API Log Admin Interface
 *
 * Provides WordPress admin interface for viewing and managing API logs.
 * Integrates with database table storage for performance while providing
 * familiar WordPress UI/UX.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\Logger;

\defined( "ABSPATH" ) || exit;

/**
 * Class ApiLogAdmin
 *
 * Admin interface controller for API logs.
 * Hybrid approach: uses database table for storage, WordPress UI for display.
 *
 * @since 1.0.0
 */
class ApiLogAdmin implements LoadableInterface {

	/**
	 * Singleton instance
	 *
	 * @var ApiLogAdmin|null
	 */
	private static ?ApiLogAdmin $instance = null;

	/**
	 * List table instance
	 *
	 * @var ApiLogListTable|null
	 */
	private ?ApiLogListTable $list_table = null;

	/**
	 * Get singleton instance
	 *
	 * @return ApiLogAdmin
	 */
	public static function instance(): ApiLogAdmin {
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
		\add_action( "admin_menu", array( $this, "register_menu" ) );
		\add_action( "admin_enqueue_scripts", array( $this, "enqueue_assets" ) );
		\add_filter( "set-screen-option", array( $this, "set_screen_option" ), 10, 3 );
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
			"wpcf7",
			\__( "API Logs", "contact-form-to-api" ),
			\__( "API Logs", "contact-form-to-api" ),
			"manage_options",
			"cf7-api-logs",
			array( $this, "render_logs_page" )
		);

		\add_action( "load-{$hook}", array( $this, "screen_options" ) );
		\add_action( "load-{$hook}", array( $this, "process_bulk_actions" ) );
	}

	/**
	 * Add screen options
	 *
	 * @return void
	 */
	public function screen_options(): void {
		$option = "per_page";
		$args   = array(
			"label"   => \__( "Logs per page", "contact-form-to-api" ),
			"default" => 20,
			"option"  => "cf7_api_logs_per_page",
		);

		\add_screen_option( $option, $args );

		// Initialize list table.
		$this->list_table = new ApiLogListTable();
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
		if ( "cf7_api_logs_per_page" === $option ) {
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

		// Verify nonce.
		if ( ! isset( $_REQUEST["_wpnonce"] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST["_wpnonce"] ) ), "bulk-" . $this->list_table->_args["plural"] ) ) {
			\wp_die( \esc_html__( "Security check failed", "contact-form-to-api" ) );
		}

		$log_ids = isset( $_REQUEST["log"] ) ? \array_map( "absint", (array) $_REQUEST["log"] ) : array();

		if ( empty( $log_ids ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . "cf7_api_logs";

		switch ( $action ) {
			case "delete":
				foreach ( $log_ids as $log_id ) {
					$wpdb->delete(
						$table_name,
						array( "id" => $log_id ),
						array( "%d" )
					);
				}

				$redirect = \add_query_arg(
					array(
						"deleted" => \count( $log_ids ),
					),
					\wp_get_referer()
				);
				\wp_safe_redirect( $redirect );
				exit;

			case "retry":
				// TODO: Implement retry logic for failed requests.
				$redirect = \add_query_arg(
					array(
						"retried" => \count( $log_ids ),
					),
					\wp_get_referer()
				);
				\wp_safe_redirect( $redirect );
				exit;
		}
	}

	/**
	 * Render logs page
	 *
	 * @return void
	 */
	public function render_logs_page(): void {
		if ( ! $this->list_table ) {
			$this->list_table = new ApiLogListTable();
		}

		// Check for single log view.
		if ( isset( $_GET["action"] ) && "view" === $_GET["action"] && isset( $_GET["log_id"] ) ) {
			$this->render_log_detail( \absint( $_GET["log_id"] ) );
			return;
		}

		// Show admin notices.
		$this->show_admin_notices();

		// Prepare items.
		$this->list_table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php \esc_html_e( "API Logs", "contact-form-to-api" ); ?></h1>

			<?php $this->render_statistics(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo \esc_attr( $_REQUEST["page"] ?? "" ); ?>" />
				<?php
				$this->list_table->search_box( \__( "Search logs", "contact-form-to-api" ), "cf7-api-log" );
				$this->list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render statistics summary
	 *
	 * @return void
	 */
	private function render_statistics(): void {
		$logger = new Logger();
		
		// Get form_id from query if filtering by form.
		$form_id = isset( $_GET["form_id"] ) ? \absint( $_GET["form_id"] ) : 0;
		$stats   = $logger->get_statistics( $form_id );

		if ( empty( $stats["total_requests"] ) ) {
			return;
		}

		$success_rate = $stats["total_requests"] > 0
			? \round( ( $stats["successful_requests"] / $stats["total_requests"] ) * 100, 1 )
			: 0;

		?>
		<div class="cf7-api-stats-summary">
			<div class="stats-grid">
				<div class="stat-box">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats["total_requests"] ) ); ?></span>
					<span class="stat-label"><?php \esc_html_e( "Total Requests", "contact-form-to-api" ); ?></span>
				</div>
				<div class="stat-box success">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats["successful_requests"] ) ); ?></span>
					<span class="stat-label"><?php \esc_html_e( "Successful", "contact-form-to-api" ); ?></span>
					<span class="stat-percentage"><?php echo \esc_html( $success_rate ); ?>%</span>
				</div>
				<div class="stat-box error">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats["failed_requests"] ) ); ?></span>
					<span class="stat-label"><?php \esc_html_e( "Failed", "contact-form-to-api" ); ?></span>
				</div>
				<div class="stat-box">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( (float) $stats["avg_execution_time"], 3 ) ); ?>s</span>
					<span class="stat-label"><?php \esc_html_e( "Avg Response Time", "contact-form-to-api" ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render log detail view
	 *
	 * @param int $log_id Log ID.
	 * @return void
	 */
	private function render_log_detail( int $log_id ): void {
		global $wpdb;
		$table_name = $wpdb->prefix . "cf7_api_logs";

		$log = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ),
			ARRAY_A
		);

		if ( ! $log ) {
			\wp_die( \esc_html__( "Log entry not found.", "contact-form-to-api" ) );
		}

		?>
		<div class="wrap">
			<h1><?php \esc_html_e( "API Log Detail", "contact-form-to-api" ); ?></h1>
			
			<p>
				<a href="<?php echo \esc_url( \admin_url( "admin.php?page=cf7-api-logs" ) ); ?>" class="button">
					← <?php \esc_html_e( "Back to Logs", "contact-form-to-api" ); ?>
				</a>
			</p>

			<div class="cf7-api-log-detail">
				<div class="log-section">
					<h2><?php \esc_html_e( "Request Information", "contact-form-to-api" ); ?></h2>
					<table class="form-table">
						<tr>
							<th><?php \esc_html_e( "Endpoint", "contact-form-to-api" ); ?></th>
							<td><code><?php echo \esc_html( $log["endpoint"] ); ?></code></td>
						</tr>
						<tr>
							<th><?php \esc_html_e( "Method", "contact-form-to-api" ); ?></th>
							<td><span class="method-badge method-<?php echo \esc_attr( \strtolower( $log["method"] ) ); ?>"><?php echo \esc_html( $log["method"] ); ?></span></td>
						</tr>
						<tr>
							<th><?php \esc_html_e( "Status", "contact-form-to-api" ); ?></th>
							<td><span class="cf7-api-status cf7-api-status-<?php echo \esc_attr( $log["status"] ); ?>"><?php echo \esc_html( \ucfirst( \str_replace( "_", " ", $log["status"] ) ) ); ?></span></td>
						</tr>
						<tr>
							<th><?php \esc_html_e( "Date", "contact-form-to-api" ); ?></th>
							<td><?php echo \esc_html( \mysql2date( \get_option( "date_format" ) . " " . \get_option( "time_format" ), $log["created_at"] ) ); ?></td>
						</tr>
						<tr>
							<th><?php \esc_html_e( "Execution Time", "contact-form-to-api" ); ?></th>
							<td><?php echo \esc_html( \number_format( (float) $log["execution_time"], 3 ) ); ?>s</td>
						</tr>
						<tr>
							<th><?php \esc_html_e( "Retry Count", "contact-form-to-api" ); ?></th>
							<td><?php echo \esc_html( $log["retry_count"] ); ?></td>
						</tr>
					</table>
				</div>

				<?php if ( ! empty( $log["request_headers"] ) ) : ?>
				<div class="log-section">
					<h2><?php \esc_html_e( "Request Headers", "contact-form-to-api" ); ?></h2>
					<pre class="log-content"><?php echo \esc_html( $this->format_json( $log["request_headers"] ) ); ?></pre>
				</div>
				<?php endif; ?>

				<div class="log-section">
					<h2><?php \esc_html_e( "Request Data", "contact-form-to-api" ); ?></h2>
					<pre class="log-content"><?php echo \esc_html( $this->format_json( $log["request_data"] ) ); ?></pre>
				</div>

				<div class="log-section">
					<h2><?php \esc_html_e( "Response Information", "contact-form-to-api" ); ?></h2>
					<table class="form-table">
						<tr>
							<th><?php \esc_html_e( "Response Code", "contact-form-to-api" ); ?></th>
							<td><?php echo \esc_html( $log["response_code"] ?? "-" ); ?></td>
						</tr>
						<?php if ( ! empty( $log["error_message"] ) ) : ?>
						<tr>
							<th><?php \esc_html_e( "Error Message", "contact-form-to-api" ); ?></th>
							<td class="error-message"><?php echo \esc_html( $log["error_message"] ); ?></td>
						</tr>
						<?php endif; ?>
					</table>
				</div>

				<?php if ( ! empty( $log["response_headers"] ) ) : ?>
				<div class="log-section">
					<h2><?php \esc_html_e( "Response Headers", "contact-form-to-api" ); ?></h2>
					<pre class="log-content"><?php echo \esc_html( $this->format_json( $log["response_headers"] ) ); ?></pre>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $log["response_data"] ) ) : ?>
				<div class="log-section">
					<h2><?php \esc_html_e( "Response Data", "contact-form-to-api" ); ?></h2>
					<pre class="log-content"><?php echo \esc_html( $this->format_json( $log["response_data"] ) ); ?></pre>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Format JSON for display
	 *
	 * @param string $json JSON string.
	 * @return string Formatted JSON.
	 */
	private function format_json( string $json ): string {
		$decoded = \json_decode( $json, true );
		if ( \json_last_error() === JSON_ERROR_NONE && \is_array( $decoded ) ) {
			return \wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		}
		return $json;
	}

	/**
	 * Show admin notices
	 *
	 * @return void
	 */
	private function show_admin_notices(): void {
		if ( isset( $_GET["deleted"] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of deleted logs */
							\_n(
								"%d log entry deleted.",
								"%d log entries deleted.",
								\absint( $_GET["deleted"] ),
								"contact-form-to-api"
							),
							\absint( $_GET["deleted"] )
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET["retried"] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of retried logs */
							\_n(
								"%d log entry queued for retry.",
								"%d log entries queued for retry.",
								\absint( $_GET["retried"] ),
								"contact-form-to-api"
							),
							\absint( $_GET["retried"] )
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( "contact_page_cf7-api-logs" !== $hook ) {
			return;
		}

		\wp_enqueue_style(
			"cf7-api-log-admin",
			CF7_API_URL . "assets/css/api-log-admin.css",
			array(),
			CF7_API_VERSION
		);

		\wp_enqueue_script(
			"cf7-api-log-admin",
			CF7_API_URL . "assets/js/api-log-admin.js",
			array( "jquery" ),
			CF7_API_VERSION,
			true
		);
	}
}
