<?php
/**
 * Dashboard Widget View
 *
 * HTML rendering for the CF7 API Status dashboard widget.
 * Displays statistics cards, recent errors, and action links.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin\Views
 * @since 1.2.0
 * @version 1.2.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin\Views;

\defined( 'ABSPATH' ) || exit;

/**
 * Class DashboardWidgetView
 *
 * Provides static methods for rendering dashboard widget HTML.
 *
 * @since 1.2.0
 */
class DashboardWidgetView {

	/**
	 * Render dashboard widget
	 *
	 * @param array<string, mixed> $stats Statistics array
	 * @return void
	 */
	public static function render( array $stats ): void {
		?>
		<div class="cf7-dashboard-widget">
			<div class="cf7-widget-header">
				<h3><?php \esc_html_e( 'Last 24 Hours', 'contact-form-to-api' ); ?></h3>
			</div>

			<div class="cf7-widget-stats">
				<?php self::render_stat_card( 'requests', $stats['total_24h'], \__( 'Requests', 'contact-form-to-api' ) ); ?>
				<?php self::render_success_rate_card( $stats['success_rate'] ); ?>
				<?php self::render_response_time_card( $stats['avg_response_time'] ); ?>
			</div>

			<?php self::render_recent_errors( $stats['recent_errors'], $stats['errors_24h'] ); ?>

			<div class="cf7-widget-actions">
				<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=cf7-api-logs' ) ); ?>" class="button">
					<?php \esc_html_e( 'View All Logs', 'contact-form-to-api' ); ?>
				</a>
				<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=wpcf7' ) ); ?>" class="button">
					<?php \esc_html_e( 'Settings', 'contact-form-to-api' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a statistics card
	 *
	 * @param string $type  Card type (requests, success, errors)
	 * @param mixed  $value Statistic value
	 * @param string $label Card label
	 * @return void
	 */
	private static function render_stat_card( string $type, $value, string $label ): void {
		$class = 'cf7-stat-card cf7-stat-' . \esc_attr( $type );
		?>
		<div class="<?php echo \esc_attr( $class ); ?>">
			<div class="cf7-stat-value"><?php echo \esc_html( $value ); ?></div>
			<div class="cf7-stat-label"><?php echo \esc_html( $label ); ?></div>
		</div>
		<?php
	}

	/**
	 * Render success rate card
	 *
	 * @param float $success_rate Success rate percentage
	 * @return void
	 */
	private static function render_success_rate_card( float $success_rate ): void {
		// Determine color based on success rate
		$status_class = 'success-high';
		if ( $success_rate < 70 ) {
			$status_class = 'success-low';
		} elseif ( $success_rate < 90 ) {
			$status_class = 'success-medium';
		}

		$class = 'cf7-stat-card cf7-stat-success-rate ' . $status_class;
		?>
		<div class="<?php echo \esc_attr( $class ); ?>">
			<div class="cf7-stat-value"><?php echo \esc_html( \number_format_i18n( $success_rate, 1 ) ); ?>%</div>
			<div class="cf7-stat-label"><?php \esc_html_e( 'Success Rate', 'contact-form-to-api' ); ?></div>
		</div>
		<?php
	}

	/**
	 * Render average response time card
	 *
	 * @param float $avg_time Average response time in milliseconds
	 * @return void
	 */
	private static function render_response_time_card( float $avg_time ): void {
		?>
		<div class="cf7-stat-card cf7-stat-response-time">
			<div class="cf7-stat-value">
				<?php
				/* translators: %d: average response time in milliseconds */
				echo \esc_html( \sprintf( \__( '%d ms', 'contact-form-to-api' ), (int) $avg_time ) );
				?>
			</div>
			<div class="cf7-stat-label"><?php \esc_html_e( 'Avg Response Time', 'contact-form-to-api' ); ?></div>
		</div>
		<?php
	}

	/**
	 * Render recent errors section
	 *
	 * @param array<int, array<string, mixed>> $recent_errors Recent error log entries
	 * @param int                              $error_count   Total error count in 24h
	 * @return void
	 */
	private static function render_recent_errors( array $recent_errors, int $error_count ): void {
		?>
		<div class="cf7-widget-errors">
			<h4>
				<?php
				/* translators: %d: number of errors */
				echo \esc_html( \sprintf( \__( 'Recent Errors (%d)', 'contact-form-to-api' ), $error_count ) );
				?>
			</h4>

			<?php if ( empty( $recent_errors ) ) : ?>
				<p class="cf7-no-errors">
					<?php \esc_html_e( 'No errors in the last 24 hours', 'contact-form-to-api' ); ?>
					<span class="dashicons dashicons-yes-alt"></span>
				</p>
			<?php else : ?>
				<?php if ( $error_count > 0 ) : ?>
					<div class="cf7-error-alert">
						<?php
						/* translators: %d: number of errors */
						echo \esc_html( \sprintf( \__( '%d errors require attention', 'contact-form-to-api' ), $error_count ) );
						?>
					</div>
				<?php endif; ?>

				<ul class="cf7-error-list">
					<?php foreach ( $recent_errors as $error ) : ?>
						<?php self::render_error_item( $error ); ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single error item
	 *
	 * @param array<string, mixed> $error Error log entry
	 * @return void
	 */
	private static function render_error_item( array $error ): void {
		$form_id   = isset( $error['form_id'] ) ? (int) $error['form_id'] : 0;
		$form_name = self::get_form_name( $form_id );
		$error_msg = isset( $error['error_message'] ) ? $error['error_message'] : \__( 'Unknown error', 'contact-form-to-api' );
		$time_ago  = self::time_ago( $error['created_at'] ?? '' );
		$log_url   = \admin_url( 'admin.php?page=cf7-api-logs&action=view&log_id=' . ( $error['id'] ?? 0 ) );
		?>
		<li class="cf7-error-item">
			<strong><?php echo \esc_html( $form_name ); ?>:</strong>
			<?php echo \esc_html( self::truncate( $error_msg, 60 ) ); ?>
			<span class="cf7-error-time"><?php echo \esc_html( $time_ago ); ?></span>
			<a href="<?php echo \esc_url( $log_url ); ?>" class="cf7-error-link">
				<?php \esc_html_e( 'View', 'contact-form-to-api' ); ?>
			</a>
		</li>
		<?php
	}

	/**
	 * Get form name by ID
	 *
	 * @param int $form_id Form ID
	 * @return string Form title or "Unknown Form"
	 */
	private static function get_form_name( int $form_id ): string {
		if ( $form_id <= 0 ) {
			return \__( 'Unknown Form', 'contact-form-to-api' );
		}

		$form = \get_post( $form_id );
		if ( ! $form instanceof \WP_Post ) {
			return \__( 'Unknown Form', 'contact-form-to-api' );
		}

		return $form->post_title;
	}

	/**
	 * Get human-readable time ago
	 *
	 * @param string $datetime MySQL datetime string
	 * @return string Human-readable time ago
	 */
	private static function time_ago( string $datetime ): string {
		if ( empty( $datetime ) ) {
			return '';
		}

		$timestamp = \strtotime( $datetime );
		if ( ! $timestamp ) {
			return '';
		}

		return \human_time_diff( $timestamp, \current_time( 'timestamp' ) ) . ' ' . \__( 'ago', 'contact-form-to-api' );
	}

	/**
	 * Truncate string to specified length
	 *
	 * @param string $text   Text to truncate
	 * @param int    $length Maximum length
	 * @return string Truncated text
	 */
	private static function truncate( string $text, int $length = 60 ): string {
		if ( \strlen( $text ) <= $length ) {
			return $text;
		}

		return \substr( $text, 0, $length ) . '...';
	}
}
