<?php
/**
 * Request Log Table
 *
 * WordPress WP_List_Table implementation for displaying API request logs.
 * Provides filtering, searching, sorting and pagination functionality.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin
 * @since 1.1.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin;

use SilverAssist\ContactFormToAPI\Utils\DateFilterTrait;

\defined( 'ABSPATH' ) || exit;

// Load WP_List_Table if not already loaded.
if ( ! \class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class RequestLogTable
 *
 * Displays API request logs in WordPress admin using native WP_List_Table.
 *
 * @since 1.1.0
 */
class RequestLogTable extends \WP_List_Table {

	use DateFilterTrait;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'cf7-api-log',
				'plural'   => 'cf7-api-logs',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get total items count
	 *
	 * Returns the total number of items after prepare_items() has been called.
	 *
	 * @return int Total number of items.
	 */
	public function get_total_items(): int {
		return (int) $this->get_pagination_arg( 'total_items' );
	}

	/**
	 * Get columns
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'cb'             => '<input type="checkbox" />',
			'form'           => \__( 'Form', 'contact-form-to-api' ),
			'endpoint'       => \__( 'Endpoint', 'contact-form-to-api' ),
			'method'         => \__( 'Method', 'contact-form-to-api' ),
			'status'         => \__( 'Status', 'contact-form-to-api' ),
			'response_code'  => \__( 'Response', 'contact-form-to-api' ),
			'execution_time' => \__( 'Time (s)', 'contact-form-to-api' ),
			'retry_count'    => \__( 'Retries', 'contact-form-to-api' ),
			'created_at'     => \__( 'Date', 'contact-form-to-api' ),
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	public function get_sortable_columns(): array {
		return array(
			'form'           => array( 'form_id', false ),
			'status'         => array( 'status', false ),
			'response_code'  => array( 'response_code', false ),
			'execution_time' => array( 'execution_time', false ),
			'retry_count'    => array( 'retry_count', false ),
			'created_at'     => array( 'created_at', true ), // Default sort.
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		return array(
			'delete' => \__( 'Delete', 'contact-form-to-api' ),
			'retry'  => \__( 'Retry', 'contact-form-to-api' ),
		);
	}

	/**
	 * Get views (status filters)
	 *
	 * @return array<string, string>
	 */
	protected function get_views(): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		$current = isset( $_GET['status'] ) ? \sanitize_text_field( \wp_unslash( $_GET['status'] ) ) : 'all';

		// Get counts for each status.
		$counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status",
			ARRAY_A
		);

		$status_counts = array(
			'all'          => 0,
			'success'      => 0,
			'error'        => 0,
			'timeout'      => 0,
			'client_error' => 0,
			'server_error' => 0,
			'pending'      => 0,
		);

		foreach ( $counts as $row ) {
			$status_counts[ $row['status'] ] = (int) $row['count'];
			$status_counts['all']           += (int) $row['count'];
		}

		$views = array();

		$views['all'] = \sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			\remove_query_arg( 'status' ),
			'all' === $current ? 'current' : '',
			\__( 'All', 'contact-form-to-api' ),
			$status_counts['all']
		);

		$views['success'] = \sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			\add_query_arg( 'status', 'success' ),
			'success' === $current ? 'current' : '',
			\__( 'Success', 'contact-form-to-api' ),
			$status_counts['success']
		);

		$views['error'] = \sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			\add_query_arg( 'status', 'error' ),
			'error' === $current ? 'current' : '',
			\__( 'Errors', 'contact-form-to-api' ),
			$status_counts['error'] + $status_counts['client_error'] + $status_counts['server_error']
		);

		return $views;
	}

	/**
	 * Prepare items for display
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Handle bulk actions.
		$this->process_bulk_action();

		// Get pagination parameters.
		$per_page = $this->get_items_per_page( 'cf7_api_logs_per_page', 20 );
		$paged    = $this->get_pagenum();

		// Get data.
		$data = $this->get_logs_data( $per_page, $paged );

		$this->items = $data['items'];

		// Set pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $data['total'],
				'per_page'    => $per_page,
				'total_pages' => \ceil( $data['total'] / $per_page ),
			)
		);
	}

	/**
	 * Get logs data from database
	 *
	 * @param int $per_page Items per page.
	 * @param int $paged    Current page.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	private function get_logs_data( int $per_page, int $paged ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Build WHERE clause.
		$where        = '1=1';
		$where_values = array();

		// Filter by status.
		if ( isset( $_GET['status'] ) && 'all' !== $_GET['status'] ) {
			$status = \sanitize_text_field( \wp_unslash( $_GET['status'] ) );
			if ( 'error' === $status ) {
				// Include all error types.
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

		// Apply date filter.
		$date_filter = $this->get_date_filter_clause();
		if ( ! empty( $date_filter['clause'] ) ) {
			$where         .= ' ' . $date_filter['clause'];
			$where_values   = \array_merge( $where_values, $date_filter['values'] );
		}

		// Prepare WHERE clause.
		if ( ! empty( $where_values ) ) {
			$where = $wpdb->prepare( $where, ...$where_values );
		}

		// Get sorting parameters.
		$orderby = isset( $_GET['orderby'] ) ? \sanitize_text_field( \wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order   = isset( $_GET['order'] ) ? \sanitize_text_field( \wp_unslash( $_GET['order'] ) ) : 'DESC';

		// Validate orderby.
		$valid_orderby = array( 'form_id', 'status', 'response_code', 'execution_time', 'retry_count', 'created_at' );
		if ( ! \in_array( $orderby, $valid_orderby, true ) ) {
			$orderby = 'created_at';
		}

		// Validate order.
		$order = \strtoupper( $order );
		if ( ! \in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		// Get total count.
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE {$where}" );

		// Get paginated results.
		$offset = ( $paged - 1 ) * $per_page;
		$items  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		return array(
			'items' => $items,
			'total' => (int) $total,
		);
	}

	/**
	 * Column default
	 *
	 * @param array<string, mixed> $item        Item data.
	 * @param string               $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'execution_time':
				return \number_format( (float) $item[ $column_name ], 3 );

			case 'retry_count':
			case 'response_code':
				return \esc_html( $item[ $column_name ] ?? '-' );

			case 'created_at':
				return \esc_html( \mysql2date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), $item[ $column_name ] ) );

			default:
				return \esc_html( $item[ $column_name ] ?? '' );
		}
	}

	/**
	 * Column checkbox
	 *
	 * @param array<string, mixed> $item Item data.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return \sprintf(
			'<input type="checkbox" name="log[]" value="%d" />',
			$item['id']
		);
	}

	/**
	 * Column form
	 *
	 * @param array<string, mixed> $item Item data.
	 * @return string
	 */
	public function column_form( $item ): string {
		$form_id = (int) $item['form_id'];

		// Try to get form title.
		$form       = \get_post( $form_id );
		/* translators: %d: Form ID number */
		$form_title = ( $form instanceof \WP_Post ) ? $form->post_title : \sprintf( \__( 'Form #%d', 'contact-form-to-api' ), $form_id );

		// Add filter link.
		$filter_url = \add_query_arg( 'form_id', $form_id );

		return \sprintf(
			'<a href="%s">%s</a>',
			\esc_url( $filter_url ),
			\esc_html( $form_title )
		);
	}

	/**
	 * Column endpoint
	 *
	 * @param array<string, mixed> $item Item data.
	 * @return string
	 */
	public function column_endpoint( $item ): string {
		$actions = array(
			'view' => \sprintf(
				'<a href="%s">%s</a>',
				\esc_url(
					\add_query_arg(
						array(
							'action' => 'view',
							'log_id' => $item['id'],
						)
					)
				),
				\__( 'View Details', 'contact-form-to-api' )
			),
		);

		if ( 'error' === $item['status'] || 'client_error' === $item['status'] || 'server_error' === $item['status'] ) {
			$actions['retry'] = \sprintf(
				'<a href="%s">%s</a>',
				\esc_url(
					\wp_nonce_url(
						\add_query_arg(
							array(
								'action' => 'retry',
								'log'    => $item['id'],
							)
						),
						'bulk-cf7-api-logs'
					)
				),
				\__( 'Retry', 'contact-form-to-api' )
			);
		}

		$actions['delete'] = \sprintf(
			'<a href="%s" class="submitdelete">%s</a>',
			\esc_url(
				\wp_nonce_url(
					\add_query_arg(
						array(
							'action' => 'delete',
							'log'    => $item['id'],
						)
					),
					'bulk-cf7-api-logs'
				)
			),
			\__( 'Delete', 'contact-form-to-api' )
		);

		$endpoint = $item['endpoint'];
		if ( \strlen( $endpoint ) > 60 ) {
			$endpoint = \substr( $endpoint, 0, 57 ) . '...';
		}

		return \sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			\esc_url(
				\add_query_arg(
					array(
						'action' => 'view',
						'log_id' => $item['id'],
					)
				)
			),
			\esc_html( $endpoint ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Column method
	 *
	 * @param array<string, mixed> $item Item data.
	 * @return string
	 */
	public function column_method( $item ): string {
		return \sprintf(
			'<span class="method-badge method-%s">%s</span>',
			\esc_attr( \strtolower( $item['method'] ) ),
			\esc_html( $item['method'] )
		);
	}

	/**
	 * Column status
	 *
	 * @param array<string, mixed> $item Item data.
	 * @return string
	 */
	public function column_status( $item ): string {
		$status = $item['status'];
		$label  = \ucfirst( \str_replace( '_', ' ', $status ) );

		return \sprintf(
			'<span class="cf7-api-status cf7-api-status-%s">%s</span>',
			\esc_attr( $status ),
			\esc_html( $label )
		);
	}

	/**
	 * Get date filter clause for SQL query
	 *
	 * Builds WHERE clause for date filtering based on GET parameters.
	 * Supports preset filters and custom date ranges.
	 *
	 * @return array{clause: string, values: array<int, string>} SQL clause and prepared statement values.
	 */
	private function get_date_filter_clause(): array {
		$params = $this->get_date_filter_params();
		return $this->build_date_filter_clause( $params['filter'], $params['start'], $params['end'] );
	}

	/**
	 * Validate date format (wrapper for trait method)
	 *
	 * Checks if date string is in Y-m-d format.
	 * Kept for backward compatibility with tests.
	 *
	 * @param string $date Date string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_date_format( string $date ): bool {
		return $this->is_valid_date_format( $date );
	}

	/**
	 * Message when no items found
	 *
	 * @return void
	 */
	public function no_items(): void {
		\esc_html_e( 'No API logs found.', 'contact-form-to-api' );
	}
}
