<?php
/**
 * Statistics Partial View
 *
 * Renders statistics summary for the Request Log page.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage View\Admin\Logs\Partials
 * @since 2.0.0
 * @version 2.1.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials;

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
	 * @param array<string, mixed> $stats        Statistics data from LogStatistics service.
	 * @param string               $date_context Date context label (e.g., "(Today)", "(All Time)").
	 * @return void
	 */
	public static function render( array $stats, string $date_context ): void {
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
}
