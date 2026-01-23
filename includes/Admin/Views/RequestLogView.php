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
 * @version 1.3.13
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin\Views;

use SilverAssist\ContactFormToAPI\Admin\RequestLogTable;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use SilverAssist\ContactFormToAPI\Utils\DateFilterTrait;

\defined( 'ABSPATH' ) || exit;

/**
 * Class RequestLogView
 *
 * Renders HTML for request log pages.
 *
 * @since 1.1.0
 */
class RequestLogView {

	use DateFilterTrait;

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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering
		$form_id = isset( $_GET['form_id'] ) ? \absint( $_GET['form_id'] ) : 0;

		// Get date filter parameters using centralized helper
		$date_params = self::get_date_range_from_filter();
		$date_filter = $date_params['filter'];
		$date_start  = $date_params['start'];
		$date_end    = $date_params['end'];

		$stats = $logger->get_statistics( $form_id, $date_start, $date_end );

		if ( empty( $stats['total_requests'] ) ) {
			return;
		}

		$success_rate = $stats['total_requests'] > 0
			? \round( ( $stats['successful_requests'] / $stats['total_requests'] ) * 100, 1 )
			: 0;

		// Determine date context label
		$date_context = self::get_date_context_label( $date_filter, $date_start, $date_end );

		?>
		<div class="cf7-api-stats-summary">
			<div class="stats-grid">
				<div class="stat-box">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats['total_requests'] ) ); ?></span>
					<span class="stat-label">
						<?php
						echo \esc_html(
							\sprintf(
								/* translators: %s: date context (e.g., "(Today)", "(All Time)") */
								\__( 'Total Requests %s', 'contact-form-to-api' ),
								$date_context
							)
						);
						?>
					</span>
				</div>
				<div class="stat-box stat-success">
					<span class="stat-number"><?php echo \esc_html( \number_format_i18n( $stats['successful_requests'] ) ); ?></span>
					<span class="stat-label"><?php \esc_html_e( 'Successful', 'contact-form-to-api' ); ?></span>
					<span class="stat-percentage"><?php echo \esc_html( $success_rate ); ?>%</span>
				</div>
				<div class="stat-box stat-error">
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
	 * Get date context label for statistics
	 *
	 * @param string      $date_filter Date filter type
	 * @param string|null $date_start  Start date
	 * @param string|null $date_end    End date
	 * @return string Date context label (e.g., "(Today)", "(All Time)")
	 */
	private static function get_date_context_label( string $date_filter, ?string $date_start, ?string $date_end ): string {
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
	 * Uses DateFilterTrait validation for custom date ranges.
	 *
	 * @return array{filter: string, start: string|null, end: string|null} Date range parameters
	 */
	private static function get_date_range_from_filter(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering
		$date_filter = isset( $_GET['date_filter'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_filter'] ) ) : '';
		// phpcs:enable

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
			'custom' => self::get_custom_date_range(),
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
	 * Validates dates using DateFilterTrait::is_valid_date_format().
	 *
	 * @return array{filter: string, start: string|null, end: string|null} Custom date range
	 */
	private static function get_custom_date_range(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering
		$date_start = isset( $_GET['date_start'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_start'] ) ) : '';
		$date_end   = isset( $_GET['date_end'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_end'] ) ) : '';
		// phpcs:enable

		// Validate date formats using trait method (instance needed for trait)
		$instance = new self();

		$valid_start = ! empty( $date_start ) && $instance->is_valid_date_format( $date_start );
		$valid_end   = empty( $date_end ) || $instance->is_valid_date_format( $date_end );

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
	 * Render log detail view
	 *
	 * @param array<string, mixed> $log Log entry data.
	 * @return void
	 */
	public static function render_detail( array $log ): void {
		$logger = new RequestLogger();
		?>
		<div class="wrap">
			<h1><?php \esc_html_e( 'API Log Detail', 'contact-form-to-api' ); ?></h1>

			<p>
				<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=cf7-api-logs' ) ); ?>" class="button">
					← <?php \esc_html_e( 'Back to Logs', 'contact-form-to-api' ); ?>
				</a>
				<?php self::render_retry_button( $log, $logger ); ?>
			</p>

			<?php self::render_retry_information( $log, $logger ); ?>

			<div class="cf7-api-log-detail">
				<?php self::render_request_section( $log, $logger ); ?>
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
	 * @param array<string, mixed> $log    Log entry data.
	 * @param RequestLogger        $logger Logger instance.
	 * @return void
	 */
	private static function render_request_section( array $log, RequestLogger $logger ): void {
		// Get manual retry count from provided logger instance
		$manual_retry_count = $logger->count_retries( (int) $log['id'] );

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
					<td>
						<?php echo \esc_html( $log['retry_count'] ); ?>
						<small class="description"><?php \esc_html_e( '(automatic retries)', 'contact-form-to-api' ); ?></small>
					</td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Manual Retry Count', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( $manual_retry_count ); ?></td>
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
		$data = $log['request_data'] ?? '';
		
		// Anonymize sensitive data at render time
		$anonymized_data = RequestLogger::anonymize_data( $data );
		
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Request Data', 'contact-form-to-api' ); ?></h2>
			<pre class="log-content"><?php echo \esc_html( self::format_json( \is_string( $anonymized_data ) ? $anonymized_data : \wp_json_encode( $anonymized_data ) ) ); ?></pre>
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
		
		$data = $log['response_data'];
		
		// Anonymize sensitive data at render time
		$anonymized_data = RequestLogger::anonymize_data( $data );
		
		?>
		<div class="log-section">
			<h2><?php \esc_html_e( 'Response Data', 'contact-form-to-api' ); ?></h2>
			<pre class="log-content"><?php echo \esc_html( self::format_json( \is_string( $anonymized_data ) ? $anonymized_data : \wp_json_encode( $anonymized_data ) ) ); ?></pre>
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

		if ( isset( $_GET['retried_success'] ) ) {
			$count = \absint( $_GET['retried_success'] );
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of successfully retried requests */
							\_n(
								'%d request retried successfully.',
								'%d requests retried successfully.',
								$count,
								'contact-form-to-api'
							),
							$count
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET['retried_failed'] ) ) {
			$count = \absint( $_GET['retried_failed'] );
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of failed retry attempts */
							\_n(
								'%d retry attempt failed.',
								'%d retry attempts failed.',
								$count,
								'contact-form-to-api'
							),
							$count
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET['retried_skipped'] ) ) {
			$count = \absint( $_GET['retried_skipped'] );
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of skipped retry attempts */
							\_n(
								'%d request skipped (maximum retries exceeded).',
								'%d requests skipped (maximum retries exceeded).',
								$count,
								'contact-form-to-api'
							),
							$count
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET['retry_error'] ) && 'rate_limit' === $_GET['retry_error'] ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php \esc_html_e( 'Rate limit exceeded. Please wait before retrying more requests.', 'contact-form-to-api' ); ?>
				</p>
			</div>
			<?php
		}

		// Legacy notice for backward compatibility
		if ( isset( $_GET['retried'] ) && ! isset( $_GET['retried_success'] ) ) {
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
	 * Render filters UI (date and status)
	 *
	 * @return void
	 */
	public static function render_filters(): void {
		$current_date_filter = isset( $_GET['date_filter'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_filter'] ) ) : '';
		$current_status      = isset( $_GET['status'] ) ? \sanitize_text_field( \wp_unslash( $_GET['status'] ) ) : '';
		$date_start          = isset( $_GET['date_start'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_start'] ) ) : '';
		$date_end            = isset( $_GET['date_end'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_end'] ) ) : '';
		$page                = isset( $_GET['page'] ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 'cf7-api-logs';
		$form_id             = isset( $_GET['form_id'] ) ? \absint( $_GET['form_id'] ) : 0;
		$search              = isset( $_GET['s'] ) ? \sanitize_text_field( \wp_unslash( $_GET['s'] ) ) : '';

		$is_custom = 'custom' === $current_date_filter;
		?>
		<div class="cf7-api-filters">
			<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" id="cf7-date-filter-form">
				<input type="hidden" name="page" value="<?php echo \esc_attr( $page ); ?>" />
				<?php if ( $form_id > 0 ) : ?>
					<input type="hidden" name="form_id" value="<?php echo \esc_attr( $form_id ); ?>" />
				<?php endif; ?>
				<?php if ( ! empty( $search ) ) : ?>
					<input type="hidden" name="s" value="<?php echo \esc_attr( $search ); ?>" />
				<?php endif; ?>
				
				<div class="filter-controls">
					<!-- Status Filter -->
					<div class="filter-group">
						<label for="status_filter" class="filter-label">
							<?php \esc_html_e( 'Status', 'contact-form-to-api' ); ?>:
						</label>
						
						<select name="status" id="status_filter" class="filter-select">
							<option value="" <?php \selected( $current_status, '' ); ?>>
								<?php \esc_html_e( 'All', 'contact-form-to-api' ); ?>
							</option>
							<option value="success" <?php \selected( $current_status, 'success' ); ?>>
								<?php \esc_html_e( 'Success', 'contact-form-to-api' ); ?>
							</option>
							<option value="error" <?php \selected( $current_status, 'error' ); ?>>
								<?php \esc_html_e( 'Error', 'contact-form-to-api' ); ?>
							</option>
						</select>
					</div>

					<!-- Date Filter -->
					<div class="filter-group">
						<label for="date_filter" class="filter-label">
							<?php \esc_html_e( 'Date', 'contact-form-to-api' ); ?>:
						</label>
						
						<select name="date_filter" id="date_filter" class="filter-select">
							<option value="" <?php \selected( $current_date_filter, '' ); ?>>
								<?php \esc_html_e( 'All Time', 'contact-form-to-api' ); ?>
							</option>
							<option value="today" <?php \selected( $current_date_filter, 'today' ); ?>>
								<?php \esc_html_e( 'Today', 'contact-form-to-api' ); ?>
							</option>
							<option value="yesterday" <?php \selected( $current_date_filter, 'yesterday' ); ?>>
								<?php \esc_html_e( 'Yesterday', 'contact-form-to-api' ); ?>
							</option>
							<option value="7days" <?php \selected( $current_date_filter, '7days' ); ?>>
								<?php \esc_html_e( 'Last 7 Days', 'contact-form-to-api' ); ?>
							</option>
							<option value="30days" <?php \selected( $current_date_filter, '30days' ); ?>>
								<?php \esc_html_e( 'Last 30 Days', 'contact-form-to-api' ); ?>
							</option>
							<option value="month" <?php \selected( $current_date_filter, 'month' ); ?>>
								<?php \esc_html_e( 'This Month', 'contact-form-to-api' ); ?>
							</option>
							<option value="custom" <?php \selected( $current_date_filter, 'custom' ); ?>>
								<?php \esc_html_e( 'Custom Range', 'contact-form-to-api' ); ?>
							</option>
						</select>
					</div>

					<div class="custom-date-range<?php echo $is_custom ? '' : ' cf7-api-hidden'; ?>" id="custom-date-range">
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
						<?php \esc_html_e( 'Apply Filters', 'contact-form-to-api' ); ?>
					</button>
				</div>

				<?php if ( ! empty( $current_date_filter ) || ! empty( $current_status ) ) : ?>
					<div class="filtered-by">
						<span><?php \esc_html_e( 'Filtered by:', 'contact-form-to-api' ); ?></span>
						<div class="tags">
							<?php
							// Build base URL for removing individual filters
							$base_args = array( 'page' => $page );
							if ( $form_id > 0 ) {
								$base_args['form_id'] = $form_id;
							}
							if ( ! empty( $search ) ) {
								$base_args['s'] = $search;
							}

							// Status filter tag
							if ( ! empty( $current_status ) ) :
								$status_labels = array(
									'success' => \__( 'Success', 'contact-form-to-api' ),
									'error'   => \__( 'Error', 'contact-form-to-api' ),
								);
								$status_label = $status_labels[ $current_status ] ?? $current_status;
								
								// URL to remove only status filter (keep date filter)
								$remove_status_args = $base_args;
								if ( ! empty( $current_date_filter ) ) {
									$remove_status_args['date_filter'] = $current_date_filter;
									if ( 'custom' === $current_date_filter ) {
										$remove_status_args['date_start'] = $date_start;
										if ( ! empty( $date_end ) ) {
											$remove_status_args['date_end'] = $date_end;
										}
									}
								}
								$remove_status_url = \add_query_arg( $remove_status_args, \admin_url( 'admin.php' ) );
								?>
								<span class="tag">
									<?php echo \esc_html( $status_label ); ?>
									<a href="<?php echo \esc_url( $remove_status_url ); ?>" class="remove-tag" aria-label="<?php \esc_attr_e( 'Remove status filter', 'contact-form-to-api' ); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</a>
								</span>
							<?php endif; ?>

							<?php
							// Date filter tag
							if ( ! empty( $current_date_filter ) ) :
								$date_labels = array(
									'today'     => \__( 'Today', 'contact-form-to-api' ),
									'yesterday' => \__( 'Yesterday', 'contact-form-to-api' ),
									'7days'     => \__( 'Last 7 Days', 'contact-form-to-api' ),
									'30days'    => \__( 'Last 30 Days', 'contact-form-to-api' ),
									'month'     => \__( 'This Month', 'contact-form-to-api' ),
									'custom'    => \__( 'Custom Range', 'contact-form-to-api' ),
								);

								$date_label = $date_labels[ $current_date_filter ] ?? $current_date_filter;
								
								if ( 'custom' === $current_date_filter && ! empty( $date_start ) ) {
									/* translators: %1$s: start date, %2$s: end date */
									$date_label = \sprintf(
										\__( '%1$s to %2$s', 'contact-form-to-api' ),
										$date_start,
										! empty( $date_end ) ? $date_end : \__( 'now', 'contact-form-to-api' )
									);
								}

								// URL to remove only date filter (keep status filter)
								$remove_date_args = $base_args;
								if ( ! empty( $current_status ) ) {
									$remove_date_args['status'] = $current_status;
								}
								$remove_date_url = \add_query_arg( $remove_date_args, \admin_url( 'admin.php' ) );
								?>
								<span class="tag">
									<?php echo \esc_html( $date_label ); ?>
									<a href="<?php echo \esc_url( $remove_date_url ); ?>" class="remove-tag" aria-label="<?php \esc_attr_e( 'Remove date filter', 'contact-form-to-api' ); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</a>
								</span>
							<?php endif; ?>
						</div>
						<a href="<?php echo \esc_url( \add_query_arg( $base_args, \admin_url( 'admin.php' ) ) ); ?>" class="button-link clear-filters">
							<?php \esc_html_e( 'Clear all', 'contact-form-to-api' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render date filter UI
	 *
	 * @deprecated 1.4.0 Use render_filters() instead
	 * @return void
	 */
	public static function render_date_filter(): void {
		self::render_filters();
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
		if ( isset( $_GET['status'] ) && ! empty( $_GET['status'] ) ) {
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

	/**
	 * Render retry button for failed requests
	 *
	 * Shows retry button only for failed requests that haven't exceeded retry limit
	 * or haven't been successfully retried yet.
	 *
	 * @param array<string, mixed> $log    Log entry data.
	 * @param RequestLogger        $logger Logger instance.
	 * @return void
	 */
	private static function render_retry_button( array $log, RequestLogger $logger ): void {
		$retryable_statuses = array( 'error', 'client_error', 'server_error' );
		if ( ! \in_array( $log['status'], $retryable_statuses, true ) ) {
			return;
		}

		$retry_count         = $logger->count_retries( (int) $log['id'] );
		$max_retries         = RequestLogger::get_max_manual_retries();
		$has_successful_retry = $logger->has_successful_retry( (int) $log['id'] );

		// Disable if already successfully retried
		if ( $has_successful_retry ) {
			?>
			<span class="button disabled" aria-disabled="true" title="<?php \esc_attr_e( 'Already successfully retried', 'contact-form-to-api' ); ?>">
				<?php \esc_html_e( 'Retry Request', 'contact-form-to-api' ); ?>
			</span>
			<?php
			return;
		}

		// Disable if maximum retries exceeded
		if ( $retry_count >= $max_retries ) {
			?>
			<span class="button disabled" aria-disabled="true" title="<?php \esc_attr_e( 'Maximum retries exceeded', 'contact-form-to-api' ); ?>">
				<?php \esc_html_e( 'Retry Request', 'contact-form-to-api' ); ?>
			</span>
			<?php
			return;
		}

		$retry_url = \wp_nonce_url(
			\add_query_arg(
				array(
					'page'   => 'cf7-api-logs',
					'action' => 'retry',
					'log'    => $log['id'],
				),
				\admin_url( 'admin.php' )
			),
			'bulk-cf7-api-logs'
		);
		?>
		<a href="<?php echo \esc_url( $retry_url ); ?>" class="button button-primary cf7-api-retry-button" 
		   onclick="return confirm('<?php echo \esc_js( \__( 'Are you sure you want to retry this request?', 'contact-form-to-api' ) ); ?>');">
			<?php \esc_html_e( 'Retry Request', 'contact-form-to-api' ); ?>
		</a>
		<?php
	}

	/**
	 * Render retry information section
	 *
	 * Shows if this is a retry and links to original/retried entries.
	 *
	 * @param array<string, mixed> $log    Log entry data.
	 * @param RequestLogger        $logger Logger instance.
	 * @return void
	 */
	private static function render_retry_information( array $log, RequestLogger $logger ): void {
		$retry_of = isset( $log['retry_of'] ) ? (int) $log['retry_of'] : 0;

		// Show if this is a retry of another request
		if ( $retry_of > 0 ) {
			$original_url = \add_query_arg(
				array(
					'page'   => 'cf7-api-logs',
					'action' => 'view',
					'log_id' => $retry_of,
				),
				\admin_url( 'admin.php' )
			);
			?>
			<div class="notice notice-info inline">
				<p>
					<?php
					echo \wp_kses_post(
						\sprintf(
							/* translators: %1$d: original log ID, %2$s: link to original log */
							\__( 'This is a retry of log entry <a href="%2$s">#%1$d</a>.', 'contact-form-to-api' ),
							$retry_of,
							\esc_url( $original_url )
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		// Show retry attempts for this request
		$retry_count = $logger->count_retries( (int) $log['id'] );
		if ( $retry_count > 0 ) {
			// Check if there's a successful retry
			$successful_retry_id = $logger->get_successful_retry_id( (int) $log['id'] );
			$notice_class        = $successful_retry_id ? 'notice-success' : 'notice-info';
			?>
			<div class="notice <?php echo \esc_attr( $notice_class ); ?> inline">
				<p>
					<?php
					echo \esc_html(
						\sprintf(
							/* translators: %d: number of retry attempts */
							\_n(
								'This request has been retried %d time.',
								'This request has been retried %d times.',
								$retry_count,
								'contact-form-to-api'
							),
							$retry_count
						)
					);

					// Add link to successful retry if exists
					if ( $successful_retry_id ) {
						$retry_url = \add_query_arg(
							array(
								'page'   => 'cf7-api-logs',
								'action' => 'view',
								'log_id' => $successful_retry_id,
							),
							\admin_url( 'admin.php' )
						);
						echo ' ';
						echo \wp_kses_post(
							\sprintf(
								/* translators: %1$s: link to successful retry log, %2$d: successful retry log ID */
								\__( '→ <a href="%1$s">View successful retry (#%2$d)</a>', 'contact-form-to-api' ),
								\esc_url( $retry_url ),
								$successful_retry_id
							)
						);
					}
					?>
				</p>
			</div>
			<?php
		}
	}
}
