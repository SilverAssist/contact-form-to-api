<?php
/**
 * Export Buttons Partial View
 *
 * Renders export action buttons for the Request Log page.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage View\Admin\Logs\Partials
 * @since 2.0.0
 * @version 2.3.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials;

\defined( 'ABSPATH' ) || exit;

/**
 * Class ExportButtonsPartial
 *
 * Handles rendering of export buttons (CSV and JSON).
 *
 * @since 2.0.0
 */
class ExportButtonsPartial {

	/**
	 * Render export buttons
	 *
	 * @since 2.0.0
	 * @param int $total_items Total number of items available for export.
	 * @return void
	 */
	public static function render( int $total_items ): void {
		$has_logs = $total_items > 0;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only operation for building URLs.
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
		// phpcs:enable

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
