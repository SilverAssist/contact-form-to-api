<?php
/**
 * Date Filter Partial View
 *
 * Renders date and status filters for the Request Log page.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage View\Admin\Logs\Partials
 * @since 2.0.0
 * @version 2.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials;

\defined( 'ABSPATH' ) || exit;

/**
 * Class DateFilterPartial
 *
 * Handles rendering of date and status filter controls.
 *
 * @since 2.0.0
 */
class DateFilterPartial {

	/**
	 * Render filter controls
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering.
		$current_date_filter = isset( $_GET['date_filter'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_filter'] ) ) : '';
		$current_status      = isset( $_GET['status'] ) ? \sanitize_text_field( \wp_unslash( $_GET['status'] ) ) : '';
		$date_start          = isset( $_GET['date_start'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_start'] ) ) : '';
		$date_end            = isset( $_GET['date_end'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_end'] ) ) : '';
		$page                = isset( $_GET['page'] ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 'cf7-api-logs';
		$form_id             = isset( $_GET['form_id'] ) ? \absint( $_GET['form_id'] ) : 0;
		$search              = isset( $_GET['s'] ) ? \sanitize_text_field( \wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:enable

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

				<?php self::render_active_filters( $current_date_filter, $current_status, $date_start, $date_end, $page, $form_id, $search ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render active filters display
	 *
	 * @since 2.0.0
	 * @param string $current_date_filter Current date filter.
	 * @param string $current_status      Current status filter.
	 * @param string $date_start          Start date.
	 * @param string $date_end            End date.
	 * @param string $page                Page slug.
	 * @param int    $form_id             Form ID.
	 * @param string $search              Search term.
	 * @return void
	 */
	private static function render_active_filters( string $current_date_filter, string $current_status, string $date_start, string $date_end, string $page, int $form_id, string $search ): void {
		if ( empty( $current_date_filter ) && empty( $current_status ) ) {
			return;
		}
		?>
		<div class="filtered-by">
			<span><?php \esc_html_e( 'Filtered by:', 'contact-form-to-api' ); ?></span>
			<div class="tags">
				<?php
				// Build base URL for removing individual filters.
				$base_args = array( 'page' => $page );
				if ( $form_id > 0 ) {
					$base_args['form_id'] = $form_id;
				}
				if ( ! empty( $search ) ) {
					$base_args['s'] = $search;
				}

				// Status filter tag.
				if ( ! empty( $current_status ) ) :
					$status_labels = array(
						'success' => \__( 'Success', 'contact-form-to-api' ),
						'error'   => \__( 'Error', 'contact-form-to-api' ),
					);
					$status_label = $status_labels[ $current_status ] ?? $current_status;
					
					// URL to remove only status filter (keep date filter).
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
				// Date filter tag.
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
						$date_label = \sprintf(
							/* translators: %1$s: start date, %2$s: end date */
							\__( '%1$s to %2$s', 'contact-form-to-api' ),
							$date_start,
							! empty( $date_end ) ? $date_end : \__( 'now', 'contact-form-to-api' )
						);
					}

					// URL to remove only date filter (keep status filter).
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
		<?php
	}
}
