<?php
/**
 * Request Log View
 *
 * Handles HTML rendering for the Request Log admin pages.
 * Separates view logic from controller logic.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin\Views
 * @since 1.1.0
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin\Views;

use SilverAssist\ContactFormToAPI\Admin\RequestLogTable;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;

\defined( 'ABSPATH' ) || exit;

/**
 * Class RequestLogView
 *
 * Renders HTML for request log pages.
 *
 * @since 1.1.0
 */
class RequestLogView {

	/**
	 * Render the main logs page
	 *
	 * @param RequestLogTable $list_table The list table instance.
	 * @return void
	 */
	public static function render_page( RequestLogTable $list_table ): void {
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php \esc_html_e( 'API Logs', 'contact-form-to-api' ); ?></h1>
			<?php self::render_export_buttons( $list_table->get_total_items() ); ?>

			<?php self::render_statistics(); ?>

			<?php self::render_date_filter(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo \esc_attr( $_REQUEST['page'] ?? '' ); ?>" />
				<?php
				$list_table->search_box( \__( 'Search logs', 'contact-form-to-api' ), 'cf7-api-log' );
				$list_table->display();
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
	public static function render_statistics(): void {
		$logger = new RequestLogger();

		// Get form_id from query if filtering by form.
		$form_id = isset( $_GET['form_id'] ) ? \absint( $_GET['form_id'] ) : 0;
		$stats   = $logger->get_statistics( $form_id );

		if ( empty( $stats['total_requests'] ) ) {
			return;
		}

		$success_rate = $stats['total_requests'] > 0
			? \round( ( $stats['successful_requests'] / $stats['total_requests'] ) * 100, 1 )
			: 0;

		?>
		<div class="cf7-api-stats-summary">
			<div class="stats-grid">
				<div class="stat-box">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats['total_requests'] ) ); ?></span>
					<span class="stat-label"><?php \esc_html_e( 'Total Requests', 'contact-form-to-api' ); ?></span>
				</div>
				<div class="stat-box success">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats['successful_requests'] ) ); ?></span>
					<span class="stat-label"><?php \esc_html_e( 'Successful', 'contact-form-to-api' ); ?></span>
					<span class="stat-percentage"><?php echo \esc_html( $success_rate ); ?>%</span>
				</div>
				<div class="stat-box error">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats['failed_requests'] ) ); ?></span>
					<span class="stat-label"><?php \esc_html_e( 'Failed', 'contact-form-to-api' ); ?></span>
				</div>
				<div class="stat-box">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( (float) $stats['avg_execution_time'], 3 ) ); ?>s</span>
					<span class="stat-label"><?php \esc_html_e( 'Avg Response Time', 'contact-form-to-api' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render log detail view
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	public static function render_detail( array $log ): void {
		?>
		<div class="wrap">
			<h1><?php \esc_html_e( 'API Log Detail', 'contact-form-to-api' ); ?></h1>

			<p>
				<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=cf7-api-logs' ) ); ?>" class="button">
					← <?php \esc_html_e( 'Back to Logs', 'contact-form-to-api' ); ?>
				</a>
			</p>

			<div class="cf7-api-log-detail">
				<?php self::render_request_section( $log ); ?>
				<?php self::render_request_headers( $log ); ?>
				<?php self::render_request_data( $log ); ?>
				<?php self::render_response_section( $log ); ?>
				<?php self::render_response_headers( $log ); ?>
				<?php self::render_response_data( $log ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render request information section
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	private static function render_request_section( array $log ): void {
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Request Information', 'contact-form-to-api' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php \esc_html_e( 'Endpoint', 'contact-form-to-api' ); ?></th>
					<td><code><?php echo \esc_html( $log['endpoint'] ); ?></code></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Method', 'contact-form-to-api' ); ?></th>
					<td><span class="method-badge method-<?php echo \esc_attr( \strtolower( $log['method'] ) ); ?>"><?php echo \esc_html( $log['method'] ); ?></span></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Status', 'contact-form-to-api' ); ?></th>
					<td><span class="cf7-api-status cf7-api-status-<?php echo \esc_attr( $log['status'] ); ?>"><?php echo \esc_html( \ucfirst( \str_replace( '_', ' ', $log['status'] ) ) ); ?></span></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Date', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( \mysql2date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), $log['created_at'] ) ); ?></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Execution Time', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( \number_format( (float) $log['execution_time'], 3 ) ); ?>s</td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Retry Count', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( $log['retry_count'] ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render request headers section
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	private static function render_request_headers( array $log ): void {
		if ( empty( $log['request_headers'] ) ) {
			return;
		}
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Request Headers', 'contact-form-to-api' ); ?></h2>
			<pre class="log-content"><?php echo \esc_html( self::format_json( $log['request_headers'] ) ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Render request data section
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	private static function render_request_data( array $log ): void {
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Request Data', 'contact-form-to-api' ); ?></h2>
			<pre class="log-content"><?php echo \esc_html( self::format_json( $log['request_data'] ?? '' ) ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Render response information section
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	private static function render_response_section( array $log ): void {
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Response Information', 'contact-form-to-api' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php \esc_html_e( 'Response Code', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( $log['response_code'] ?? '-' ); ?></td>
				</tr>
				<?php if ( ! empty( $log['error_message'] ) ) : ?>
				<tr>
					<th><?php \esc_html_e( 'Error Message', 'contact-form-to-api' ); ?></th>
					<td class="error-message"><?php echo \esc_html( $log['error_message'] ); ?></td>
				</tr>
				<?php endif; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render response headers section
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	private static function render_response_headers( array $log ): void {
		if ( empty( $log['response_headers'] ) ) {
			return;
		}
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Response Headers', 'contact-form-to-api' ); ?></h2>
			<pre class="log-content"><?php echo \esc_html( self::format_json( $log['response_headers'] ) ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Render response data section
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	private static function render_response_data( array $log ): void {
		if ( empty( $log['response_data'] ) ) {
			return;
		}
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Response Data', 'contact-form-to-api' ); ?></h2>
			<pre class="log-content"><?php echo \esc_html( self::format_json( $log['response_data'] ) ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Render admin notices
	 *
	 * @return void
	 */
	public static function render_notices(): void {
		if ( isset( $_GET['deleted'] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of deleted logs */
							\_n(
								'%d log entry deleted.',
								'%d log entries deleted.',
								\absint( $_GET['deleted'] ),
								'contact-form-to-api'
							),
							\absint( $_GET['deleted'] )
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET['retried'] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of retried logs */
							\_n(
								'%d log entry queued for retry.',
								'%d log entries queued for retry.',
								\absint( $_GET['retried'] ),
								'contact-form-to-api'
							),
							\absint( $_GET['retried'] )
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Format JSON for display
	 *
	 * @param string $json JSON string.
	 * @return string Formatted JSON.
	 */
	private static function format_json( string $json ): string {
		$decoded = \json_decode( $json, true );
		if ( \json_last_error() === JSON_ERROR_NONE && \is_array( $decoded ) ) {
			$encoded = \wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			return $encoded !== false ? $encoded : $json;
		}
		return $json;
	}

	/**
	 * Render date filter UI
	 *
	 * @return void
	 */
	public static function render_date_filter(): void {
		$current_filter = isset( $_GET['date_filter'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_filter'] ) ) : '';
		$date_start     = isset( $_GET['date_start'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_start'] ) ) : '';
		$date_end       = isset( $_GET['date_end'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_end'] ) ) : '';
		$page           = isset( $_GET['page'] ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 'cf7-api-logs';

		$is_custom = 'custom' === $current_filter;
		?>
		<div class="cf7-api-date-filter">
			<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" id="cf7-date-filter-form">
				<input type="hidden" name="page" value="<?php echo \esc_attr( $page ); ?>" />
				
				<div class="date-filter-controls">
					<label for="date_filter" class="date-filter-label">
						<?php \esc_html_e( 'Date Filter', 'contact-form-to-api' ); ?>:
					</label>
					
					<select name="date_filter" id="date_filter" class="date-filter-select">
						<option value="" <?php \selected( $current_filter, '' ); ?>>
							<?php \esc_html_e( 'All Time', 'contact-form-to-api' ); ?>
						</option>
						<option value="today" <?php \selected( $current_filter, 'today' ); ?>>
							<?php \esc_html_e( 'Today', 'contact-form-to-api' ); ?>
						</option>
						<option value="yesterday" <?php \selected( $current_filter, 'yesterday' ); ?>>
							<?php \esc_html_e( 'Yesterday', 'contact-form-to-api' ); ?>
						</option>
						<option value="7days" <?php \selected( $current_filter, '7days' ); ?>>
							<?php \esc_html_e( 'Last 7 Days', 'contact-form-to-api' ); ?>
						</option>
						<option value="30days" <?php \selected( $current_filter, '30days' ); ?>>
							<?php \esc_html_e( 'Last 30 Days', 'contact-form-to-api' ); ?>
						</option>
						<option value="month" <?php \selected( $current_filter, 'month' ); ?>>
							<?php \esc_html_e( 'This Month', 'contact-form-to-api' ); ?>
						</option>
						<option value="custom" <?php \selected( $current_filter, 'custom' ); ?>>
							<?php \esc_html_e( 'Custom Range', 'contact-form-to-api' ); ?>
						</option>
					</select>

					<div class="custom-date-range" id="custom-date-range" <?php echo $is_custom ? '' : 'style="display:none;"'; ?>>
						<label for="date_start">
							<?php \esc_html_e( 'From', 'contact-form-to-api' ); ?>:
						</label>
						<input type="date" name="date_start" id="date_start" value="<?php echo \esc_attr( $date_start ); ?>" class="date-input" />
						
						<label for="date_end">
							<?php \esc_html_e( 'To', 'contact-form-to-api' ); ?>:
						</label>
						<input type="date" name="date_end" id="date_end" value="<?php echo \esc_attr( $date_end ); ?>" class="date-input" />
					</div>

					<button type="submit" class="button button-primary">
						<?php \esc_html_e( 'Apply Filter', 'contact-form-to-api' ); ?>
					</button>

					<?php if ( ! empty( $current_filter ) ) : ?>
						<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . $page ) ); ?>" class="button">
							<?php \esc_html_e( 'Clear Filter', 'contact-form-to-api' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $current_filter ) ) : ?>
					<div class="active-filter-badge">
						<span class="dashicons dashicons-filter"></span>
						<?php
						$filter_labels = array(
							'today'     => \__( 'Today', 'contact-form-to-api' ),
							'yesterday' => \__( 'Yesterday', 'contact-form-to-api' ),
							'7days'     => \__( 'Last 7 Days', 'contact-form-to-api' ),
							'30days'    => \__( 'Last 30 Days', 'contact-form-to-api' ),
							'month'     => \__( 'This Month', 'contact-form-to-api' ),
							'custom'    => \__( 'Custom Range', 'contact-form-to-api' ),
						);

						$label = $filter_labels[ $current_filter ] ?? $current_filter;
						
						if ( 'custom' === $current_filter && ! empty( $date_start ) ) {
							/* translators: %1$s: start date, %2$s: end date */
							$label = \sprintf(
								\__( 'Custom Range: %1$s to %2$s', 'contact-form-to-api' ),
								$date_start,
								! empty( $date_end ) ? $date_end : \__( 'now', 'contact-form-to-api' )
							);
						}

						echo \esc_html( $label );
						?>
					</div>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render export buttons
	 *
	 * @param int $total_items Total number of items available for export.
	 * @return void
	 */
	private static function render_export_buttons( int $total_items ): void {
		$has_logs = $total_items > 0;

		// Build export URLs with nonce and current filters.
		$page      = isset( $_GET['page'] ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 'cf7-api-logs';
		$base_args = array(
			'page'     => $page,
			'_wpnonce' => \wp_create_nonce( 'cf7_api_export_logs' ),
		);

		// Preserve current filters.
		if ( isset( $_GET['status'] ) && 'all' !== $_GET['status'] ) {
			$base_args['status'] = \sanitize_text_field( \wp_unslash( $_GET['status'] ) );
		}

		if ( isset( $_GET['form_id'] ) && ! empty( $_GET['form_id'] ) ) {
			$base_args['form_id'] = \absint( $_GET['form_id'] );
		}

		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$base_args['s'] = \sanitize_text_field( \wp_unslash( $_GET['s'] ) );
		}

		// Preserve date filter parameters.
		if ( isset( $_GET['date_filter'] ) && ! empty( $_GET['date_filter'] ) ) {
			$base_args['date_filter'] = \sanitize_text_field( \wp_unslash( $_GET['date_filter'] ) );

			if ( 'custom' === $base_args['date_filter'] ) {
				if ( isset( $_GET['date_start'] ) && ! empty( $_GET['date_start'] ) ) {
					$base_args['date_start'] = \sanitize_text_field( \wp_unslash( $_GET['date_start'] ) );
				}
				if ( isset( $_GET['date_end'] ) && ! empty( $_GET['date_end'] ) ) {
					$base_args['date_end'] = \sanitize_text_field( \wp_unslash( $_GET['date_end'] ) );
				}
			}
		}

		// CSV export URL.
		$csv_args = \array_merge( $base_args, array( 'action' => 'export_csv' ) );
		$csv_url  = \add_query_arg( $csv_args, \admin_url( 'admin.php' ) );

		// JSON export URL.
		$json_args = \array_merge( $base_args, array( 'action' => 'export_json' ) );
		$json_url  = \add_query_arg( $json_args, \admin_url( 'admin.php' ) );

		$disabled_class = $has_logs ? '' : ' disabled';
		$disabled_attr  = $has_logs ? '' : ' aria-disabled="true" tabindex="-1"';
		?>
		<div class="cf7-api-export-buttons">
			<div class="button-group">
				<a href="<?php echo $has_logs ? \esc_url( $csv_url ) : '#'; ?>" class="button<?php echo \esc_attr( $disabled_class ); ?>"<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string. ?>>
					<span class="dashicons dashicons-download"></span>
					<?php \esc_html_e( 'Export as CSV', 'contact-form-to-api' ); ?>
				</a>
				<a href="<?php echo $has_logs ? \esc_url( $json_url ) : '#'; ?>" class="button<?php echo \esc_attr( $disabled_class ); ?>"<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string. ?>>
					<span class="dashicons dashicons-download"></span>
					<?php \esc_html_e( 'Export as JSON', 'contact-form-to-api' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
