<?php
/**
 * Date Filter Trait
 *
 * Provides reusable date filtering logic for SQL queries.
 * Used by RequestLogTable and RequestLogController to avoid code duplication.
 *
 * @package SilverAssist\ContactFormToAPI\Utils
 * @since   1.2.0
 * @version 1.2.1
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Utils;

/**
 * Trait DateFilterTrait
 *
 * Provides date filtering functionality for log queries.
 */
trait DateFilterTrait {

	/**
	 * Get date filter SQL clause based on current request parameters.
	 *
	 * @param string $filter Current filter type (today, yesterday, 7days, 30days, month, custom).
	 * @param string $start  Start date for custom range (Y-m-d format).
	 * @param string $end    End date for custom range (Y-m-d format).
	 * @return array{clause: string, values: array<int, string>} SQL clause and prepared statement values.
	 */
	protected function build_date_filter_clause( string $filter, string $start = '', string $end = '' ): array {
		if ( empty( $filter ) ) {
			return array(
				'clause' => '',
				'values' => array(),
			);
		}

		return match ( $filter ) {
			'today' => array(
				'clause' => 'AND DATE(created_at) = CURDATE()',
				'values' => array(),
			),
			'yesterday' => array(
				'clause' => 'AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)',
				'values' => array(),
			),
			'7days' => array(
				'clause' => 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
				'values' => array(),
			),
			'30days' => array(
				'clause' => 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
				'values' => array(),
			),
			'month' => array(
				'clause' => 'AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())',
				'values' => array(),
			),
			'custom' => $this->build_custom_date_range_clause( $start, $end ),
			default => array(
				'clause' => '',
				'values' => array(),
			),
		};
	}

	/**
	 * Build custom date range clause
	 *
	 * @param string $start Start date (Y-m-d format).
	 * @param string $end   End date (Y-m-d format).
	 * @return array{clause: string, values: array<int, string>} SQL clause and prepared statement values.
	 */
	protected function build_custom_date_range_clause( string $start, string $end ): array {
		if ( ! $this->is_valid_date_format( $start ) ) {
			return array(
				'clause' => '',
				'values' => array(),
			);
		}

		if ( ! empty( $end ) ) {
			if ( ! $this->is_valid_date_format( $end ) ) {
				return array(
					'clause' => '',
					'values' => array(),
				);
			}

			return array(
				'clause' => 'AND DATE(created_at) BETWEEN %s AND %s',
				'values' => array( $start, $end ),
			);
		}

		return array(
			'clause' => 'AND DATE(created_at) >= %s',
			'values' => array( $start ),
		);
	}

	/**
	 * Validate date format (Y-m-d)
	 *
	 * @param string $date Date string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	protected function is_valid_date_format( string $date ): bool {
		if ( empty( $date ) ) {
			return false;
		}

		$d = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}

	/**
	 * Get sanitized date filter parameters from request
	 *
	 * @return array{filter: string, start: string, end: string} Sanitized filter parameters.
	 */
	protected function get_date_filter_params(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only operation for filtering
		$filter = isset( $_GET['date_filter'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_filter'] ) ) : '';
		$start  = isset( $_GET['date_start'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_start'] ) ) : '';
		$end    = isset( $_GET['date_end'] ) ? \sanitize_text_field( \wp_unslash( $_GET['date_end'] ) ) : '';
		// phpcs:enable

		return array(
			'filter' => $filter,
			'start'  => $start,
			'end'    => $end,
		);
	}
}
