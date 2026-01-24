<?php
/**
 * Statistics Partial View
 *
 * Renders statistics summary for the Request Log page.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage View\Admin\Logs\Partials
 * @since 2.0.0
 * @version 2.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials;

use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;

\defined( 'ABSPATH' ) || exit;

/**
 * Class StatisticsPartial
 *
 * Handles rendering of statistics summary.
 *
 * @since 2.0.0
 */
class StatisticsPartial {

	/**
	 * Render statistics summary
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function render(): void {
		$stats_service = new LogStatistics();

		// Get form_id from query if filtering by form.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering
		$form_id = isset( $_GET['form_id'] ) ? \absint( $_GET['form_id'] ) : 0;

		// Get date filter parameters using centralized helper.
		$date_params = self::get_date_range_from_filter();
		$date_filter = $date_params['filter'];
		$date_start  = $date_params['start'];
		$date_end    = $date_params['end'];

		$stats = $stats_service->get_statistics( $form_id, $date_start, $date_end );

		if ( empty( $stats['total_requests'] ) ) {
			return;
		}

		$success_rate = $stats['total_requests'] > 0
			? \round( ( $stats['successful_requests'] / $stats['total_requests'] ) * 100, 1 )
			: 0;

		// Determine date context label.
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
	 * @since 2.0.0
	 * @param string      $date_filter Date filter type.
	 * @param string|null $date_start  Start date.
	 * @param string|null $date_end    End date.
	 * @return string Date context label (e.g., "(Today)", "(All Time)").
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
	 * @since 2.0.0
	 * @return array{filter: string, start: string|null, end: string|null} Date range parameters.
	 */
	private static function get_date_range_from_filter(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering.
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
	 * Get custom date range from query parameters
	 *
	 * @since 2.0.0
	 * @return array{filter: string, start: string|null, end: string|null} Date range.
	 */
	private static function get_custom_date_range(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering.
		$date_start = isset( $_GET['date_start'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_start'] ) ) : '';
		$date_end   = isset( $_GET['date_end'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_end'] ) ) : '';
		// phpcs:enable

		// Validate date format (Y-m-d).
		$date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
		if ( ! \preg_match( $date_pattern, $date_start ) ) {
			$date_start = '';
		}
		if ( ! empty( $date_end ) && ! \preg_match( $date_pattern, $date_end ) ) {
			$date_end = '';
		}

		return array(
			'filter' => 'custom',
			'start'  => $date_start ?: null,
			'end'    => $date_end ?: null,
		);
	}
}
