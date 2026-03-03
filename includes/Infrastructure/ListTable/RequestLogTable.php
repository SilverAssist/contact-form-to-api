<?php
/**
 * Request Log Table
 *
 * WordPress WP_List_Table implementation for displaying API request logs.
 * Provides filtering, searching, sorting and pagination functionality.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Infrastructure\ListTable
 * @since 1.1.0
 * @version 2.3.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Infrastructure\ListTable;

use SilverAssist\ContactFormToAPI\Service\Security\SensitiveDataPatterns;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;
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
	 * Log reader instance for decryption
	 *
	 * @var LogReader
	 */
	private LogReader $log_reader;

	/**
	 * Retry manager instance for retry checks
	 *
	 * @var RetryManager
	 */
	private RetryManager $retry_manager;

	/**
	 * Cached resolved error IDs to avoid N+1 queries
	 *
	 * @var array<int>|null
	 */
	private ?array $resolved_error_ids = null;

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

		// Initialize logging service instances once for reuse.
		$this->log_reader    = new LogReader();
		$this->retry_manager = new RetryManager();
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
			'from'           => \__( 'From', 'contact-form-to-api' ),
			'form'           => \__( 'Channel', 'contact-form-to-api' ),
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
			$wpdb->prepare(
				'SELECT status, COUNT(*) as count FROM %i GROUP BY status',
				$table_name
			),
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

		// Get error resolution counts for the unresolved filter.
		$error_resolution = $this->retry_manager->count_errors_by_resolution();

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
			\__( 'All Errors', 'contact-form-to-api' ),
			$status_counts['error'] + $status_counts['client_error'] + $status_counts['server_error']
		);

		// Add unresolved errors filter (errors without successful retry).
		$views['unresolved'] = \sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			\add_query_arg( 'status', 'unresolved' ),
			'unresolved' === $current ? 'current' : '',
			\__( 'Unresolved', 'contact-form-to-api' ),
			$error_resolution['unresolved']
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
	 * When search is active, filtering by name/lastname is done in PHP to respect
	 * anonymization rules (fields marked as sensitive are not searched).
	 *
	 * @param int $per_page Items per page.
	 * @param int $paged    Current page.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	private function get_logs_data( int $per_page, int $paged ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_api_logs';

		// Build WHERE conditions and values for single prepare() call.
		$conditions        = array( '1=1' );
		$values            = array( $table_name ); // First value is table name for %i.
		$filter_unresolved = false;

		// Filter by status.
		if ( isset( $_GET['status'] ) && ! empty( $_GET['status'] ) && 'all' !== $_GET['status'] ) {
			$status = \sanitize_text_field( \wp_unslash( $_GET['status'] ) );
			if ( 'error' === $status ) {
				// Include all error types (hardcoded values, no placeholder needed).
				$conditions[] = "status IN ('error', 'client_error', 'server_error')";
			} elseif ( 'unresolved' === $status ) {
				// Filter for unresolved errors (errors without successful retry).
				$conditions[]      = "status IN ('error', 'client_error', 'server_error')";
				$filter_unresolved = true;
			} else {
				$conditions[] = 'status = %s';
				$values[]     = $status;
			}
		}

		// Filter by form ID.
		if ( isset( $_GET['form_id'] ) && ! empty( $_GET['form_id'] ) ) {
			$conditions[] = 'form_id = %d';
			$values[]     = \absint( $_GET['form_id'] );
		}

		// Search term - when active, all filtering is done in PHP to support
		// endpoint/error_message/sender_name search with OR logic.
		$search_term = '';
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$search_term = \sanitize_text_field( \wp_unslash( $_GET['s'] ) );
		}

		// Apply date filter.
		$date_filter = $this->get_date_filter_clause();
		if ( ! empty( $date_filter['clause'] ) ) {
			// Remove leading AND from clause since we join with AND later.
			$clause = \ltrim( $date_filter['clause'], ' AND' );
			if ( ! empty( $clause ) ) {
				$conditions[] = $clause;
				$values       = \array_merge( $values, $date_filter['values'] );
			}
		}

		// Get sorting parameters with whitelist validation.
		$orderby       = isset( $_GET['orderby'] ) ? \sanitize_text_field( \wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order         = isset( $_GET['order'] ) ? \sanitize_text_field( \wp_unslash( $_GET['order'] ) ) : 'DESC';
		$valid_orderby = array( 'form_id', 'status', 'response_code', 'execution_time', 'retry_count', 'created_at' );
		$orderby       = \in_array( $orderby, $valid_orderby, true ) ? $orderby : 'created_at';
		$order         = \in_array( \strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? \strtoupper( $order ) : 'DESC';

		// For unresolved filter, exclude errors that have successful retries.
		if ( $filter_unresolved ) {
			$resolved_ids = $this->retry_manager->get_resolved_error_ids();
			if ( ! empty( $resolved_ids ) ) {
				$id_placeholders = \implode( ', ', \array_fill( 0, \count( $resolved_ids ), '%d' ) );
				$conditions[]    = 'id NOT IN (' . $id_placeholders . ')';
				$values          = \array_merge( $values, $resolved_ids );
			}
		}

		// Build WHERE clause from conditions.
		$where_clause = \implode( ' AND ', $conditions );

		// If search is active, use PHP filtering for full OR logic.
		if ( ! empty( $search_term ) ) {
			return $this->get_logs_data_with_search( $table_name, $where_clause, $values, $orderby, $order, $search_term, $per_page, $paged );
		}

		// Get total count with single prepare() call.
		// $where_clause contains only placeholders, all values go through prepare().
		$count_query = "SELECT COUNT(*) FROM %i WHERE {$where_clause}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total       = $wpdb->get_var( $wpdb->prepare( $count_query, ...$values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Get paginated results with single prepare() call.
		// $orderby and $order are validated against whitelists above.
		$offset       = ( $paged - 1 ) * $per_page;
		$query_values = \array_merge( $values, array( $per_page, $offset ) );
		// $where_clause contains only placeholders, $orderby/$order are whitelist-validated.
		$query = "SELECT * FROM %i WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $query, ...$query_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items' => $items,
			'total' => (int) $total,
		);
	}

	/**
	 * Get logs data with PHP-based search filtering
	 *
	 * This method handles search by endpoint, error_message, name, and lastname
	 * using OR logic. Name/lastname search respects anonymization rules - fields
	 * marked as sensitive via SensitiveDataPatterns are not searched.
	 *
	 * @since 1.3.13
	 * @param string              $table_name   Table name.
	 * @param string              $where_clause WHERE clause with placeholders.
	 * @param array<int, mixed>   $values       Values for placeholders.
	 * @param string              $orderby      Order by column (whitelist-validated).
	 * @param string              $order        Sort order (ASC/DESC, whitelist-validated).
	 * @param string              $search_term  Search term.
	 * @param int                 $per_page     Items per page.
	 * @param int                 $paged        Current page.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	private function get_logs_data_with_search(
		string $table_name,
		string $where_clause,
		array $values,
		string $orderby,
		string $order,
		string $search_term,
		int $per_page,
		int $paged
	): array {
		global $wpdb;

		// Select only needed columns to reduce memory usage.
		// We need request_data for sender extraction, encryption_version for decryption.
		$columns = 'id, form_id, endpoint, method, status, error_message, request_data, '
			. 'encryption_version, response_code, execution_time, retry_count, retry_of, created_at';

		// Fetch records matching base filters (status, form, date), limit for memory safety.
		// Search filtering is done in PHP to support OR logic with sender name.
		// $columns is hardcoded, $where_clause contains only placeholders, $orderby/$order are whitelist-validated.
		$max_records  = 5000;
		$query_values = \array_merge( $values, array( $max_records ) );
		$query        = "SELECT {$columns} FROM %i WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all_items    = $wpdb->get_results( $wpdb->prepare( $query, ...$query_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $all_items ) ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		// PHP filtering for search: matches endpoint OR error_message OR sender name.
		// This respects anonymization rules for sender data.
		$filtered_items = array();
		$search_lower   = \strtolower( $search_term );

		foreach ( $all_items as $item ) {
			$endpoint      = isset( $item['endpoint'] ) ? \strtolower( (string) $item['endpoint'] ) : '';
			$error_message = isset( $item['error_message'] ) ? \strtolower( (string) $item['error_message'] ) : '';

			// Match if search term found in endpoint, error_message, or sender name.
			if (
				false !== \strpos( $endpoint, $search_lower ) ||
				false !== \strpos( $error_message, $search_lower ) ||
				$this->item_matches_sender_search( $item, $search_lower )
			) {
				$filtered_items[] = $item;
			}
		}

		$total = \count( $filtered_items );

		// Apply pagination to filtered results.
		$offset = ( $paged - 1 ) * $per_page;
		$items  = \array_slice( $filtered_items, $offset, $per_page );

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Check if an item matches the search term in sender info
	 *
	 * Searches in sender name/lastname only (endpoint/error_message handled by SQL).
	 * Respects anonymization - fields marked as sensitive via SensitiveDataPatterns
	 * are not included in search.
	 *
	 * @since 1.3.13
	 * @param array<string, mixed> $item         Log item.
	 * @param string               $search_lower Lowercase search term.
	 * @return bool True if item matches search in sender info.
	 */
	private function item_matches_sender_search( array $item, string $search_lower ): bool {
		// Check sender info (name/lastname) - this respects anonymization rules.
		$sender_info = $this->extract_sender_info( $item );

		if ( ! empty( $sender_info['display_name'] ) && \strpos( \strtolower( $sender_info['display_name'] ), $search_lower ) !== false ) {
			return true;
		}

		return false;
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
		$form = \get_post( $form_id );
		/* translators: %d: form ID */
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
	 * Column from (sender information)
	 *
	 * Extracts and displays sender info from request data.
	 * Shows name, lastname and masked email.
	 *
	 * @since 1.3.12
	 * @param array<string, mixed> $item Item data.
	 * @return string
	 */
	public function column_from( $item ): string {
		$from_parts = $this->extract_sender_info( $item );

		if ( empty( $from_parts['display_name'] ) && empty( $from_parts['email'] ) ) {
			return '<span class="from-unknown">' . \esc_html__( 'Unknown', 'contact-form-to-api' ) . '</span>';
		}

		$output = '';

		if ( ! empty( $from_parts['display_name'] ) ) {
			$output .= '<span class="from-name">' . \esc_html( $from_parts['display_name'] ) . '</span>';
		}

		if ( ! empty( $from_parts['email'] ) ) {
			$masked_email = $this->mask_email( $from_parts['email'] );
			$output      .= ' <span class="from-email">&lt;' . \esc_html( $masked_email ) . '&gt;</span>';
		}

		return $output;
	}

	/**
	 * Extract sender information from request data
	 *
	 * Attempts to find name, lastname and email from various common field names.
	 * Uses static cache to avoid redundant decryption operations.
	 * Respects user-configured sensitive data patterns - fields marked as sensitive
	 * will not be extracted to maintain consistency with the anonymization settings.
	 *
	 * @since 1.3.12
	 * @param array<string, mixed> $item Item data.
	 * @return array{display_name: string, email: string} Extracted sender info.
	 */
	private function extract_sender_info( array $item ): array {
		static $sender_data_cache = array();

		$result = array(
			'display_name' => '',
			'email'        => '',
		);

		// Build a cache key per log item (prefer the log ID when available).
		if ( isset( $item['id'] ) ) {
			$cache_key = 'id_' . (string) $item['id'];
		} else {
			$request_data_raw   = $item['request_data'] ?? '';
			$encryption_version = $item['encryption_version'] ?? '';
			$cache_key          = \md5( (string) $request_data_raw . '|' . (string) $encryption_version );
		}

		// Reuse already decrypted and decoded data when available.
		if ( isset( $sender_data_cache[ $cache_key ] ) ) {
			$data = $sender_data_cache[ $cache_key ];
		} else {
			// Get and decrypt request data if needed.
			$request_data = $item['request_data'] ?? '';

			if ( empty( $request_data ) ) {
				return $result;
			}

			// Decrypt if encrypted.
			if ( isset( $item['encryption_version'] ) && $item['encryption_version'] > 0 ) {
				$decrypted_item = $this->log_reader->decrypt_log_fields( $item );
				$request_data   = $decrypted_item['request_data'] ?? '';
			}

			// Parse JSON data.
			$data = \is_string( $request_data ) ? \json_decode( $request_data, true ) : $request_data;

			// Cache the parsed data (even if not an array, to avoid repeating work).
			$sender_data_cache[ $cache_key ] = $data;
		}

		if ( ! \is_array( $data ) ) {
			return $result;
		}

		// Common field names for name (case-insensitive search).
		$name_fields     = array( 'name', 'first_name', 'firstname', 'your-name', 'nombre', 'prenom' );
		$lastname_fields = array( 'lastname', 'last_name', 'surname', 'your-lastname', 'apellido', 'nom' );
		$email_fields    = array( 'email', 'your-email', 'youremail', 'mail', 'e-mail', 'correo', 'courriel', 'primaryEmail' );

		$name     = '';
		$lastname = '';
		$email    = '';

		// Search for fields (case-insensitive).
		$data_lower = \array_change_key_case( $data, CASE_LOWER );

		foreach ( $name_fields as $field ) {
			if ( isset( $data_lower[ $field ] ) && ! empty( $data_lower[ $field ] ) ) {
				// Check if field is marked as sensitive by user configuration.
				if ( SensitiveDataPatterns::is_sensitive( $field ) ) {
					continue;
				}
				if ( \is_array( $data_lower[ $field ] ) ) {
					$name = isset( $data_lower[ $field ][0] ) ? $data_lower[ $field ][0] : '';
				} else {
					$name = $data_lower[ $field ];
				}
				break;
			}
		}

		foreach ( $lastname_fields as $field ) {
			if ( isset( $data_lower[ $field ] ) && ! empty( $data_lower[ $field ] ) ) {
				// Check if field is marked as sensitive by user configuration.
				if ( SensitiveDataPatterns::is_sensitive( $field ) ) {
					continue;
				}
				if ( \is_array( $data_lower[ $field ] ) ) {
					$lastname = isset( $data_lower[ $field ][0] ) ? $data_lower[ $field ][0] : '';
				} else {
					$lastname = $data_lower[ $field ];
				}
				break;
			}
		}

		foreach ( $email_fields as $field ) {
			if ( isset( $data_lower[ $field ] ) && ! empty( $data_lower[ $field ] ) ) {
				// Check if field is marked as sensitive by user configuration.
				if ( SensitiveDataPatterns::is_sensitive( $field ) ) {
					continue;
				}
				if ( \is_array( $data_lower[ $field ] ) ) {
					$email = isset( $data_lower[ $field ][0] ) ? $data_lower[ $field ][0] : '';
				} else {
					$email = $data_lower[ $field ];
				}
				break;
			}
		}

		// Build display name.
		$display_name = \trim( $name . ' ' . $lastname );

		return array(
			'display_name' => $display_name,
			'email'        => $email,
		);
	}

	/**
	 * Mask email address for privacy
	 *
	 * Shows first 2 chars, masks middle, shows domain.
	 * Handles edge cases for very short email local parts.
	 * Examples:
	 * - john@example.com -> jo***@example.com
	 * - ab@example.com -> a***@example.com
	 * - a@example.com -> ***@example.com
	 *
	 * @since 1.3.12
	 * @param string $email Email address to mask.
	 * @return string Masked email.
	 */
	private function mask_email( string $email ): string {
		if ( empty( $email ) || ! \str_contains( $email, '@' ) ) {
			return $email;
		}

		$parts  = \explode( '@', $email );
		$local  = $parts[0];
		$domain = $parts[1] ?? '';

		$local_length = \strlen( $local );

		if ( 1 === $local_length ) {
			// For 1-character local part, do not reveal the character at all.
			$masked_local = '***';
		} elseif ( 2 === $local_length ) {
			// For 2-character local part, reveal only the first character.
			$masked_local = \substr( $local, 0, 1 ) . '***';
		} else {
			// Show first 2 characters of local part, mask the rest.
			$masked_local = \substr( $local, 0, 2 ) . '***';
		}

		return $masked_local . '@' . $domain;
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

		// Only show retry action for failed requests that haven't been successfully retried
		if ( 'error' === $item['status'] || 'client_error' === $item['status'] || 'server_error' === $item['status'] ) {
			// Check if already successfully retried using cached logger instance
			if ( ! $this->retry_manager->has_successful_retry( (int) $item['id'] ) ) {
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

		$endpoint      = $item['endpoint'];
		$endpoint_full = $endpoint;

		return \sprintf(
			'<span class="endpoint-cell" title="%s"><strong><a href="%s">%s</a></strong></span>%s',
			\esc_attr( $endpoint_full ),
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
	 * Displays status badge with visual indicator for resolved errors.
	 * Errors that have been successfully retried show a "Resolved" badge.
	 *
	 * @since 1.1.0
	 * @since 1.3.14 Added resolved indicator for errors with successful retry.
	 * @param array<string, mixed> $item Item data.
	 * @return string
	 */
	public function column_status( $item ): string {
		$status = $item['status'];
		$label  = \ucfirst( \str_replace( '_', ' ', $status ) );
		$output = \sprintf(
			'<span class="cf7-api-status cf7-api-status-%s">%s</span>',
			\esc_attr( $status ),
			\esc_html( $label )
		);

		// Check if this is an error that has been successfully retried.
		// Use cached resolved IDs to avoid N+1 queries.
		$is_error = \in_array( $status, array( 'error', 'client_error', 'server_error' ), true );
		if ( $is_error ) {
			// Lazy load resolved IDs once per request.
			if ( null === $this->resolved_error_ids ) {
				$this->resolved_error_ids = $this->retry_manager->get_resolved_error_ids();
			}
			if ( \in_array( (int) $item['id'], $this->resolved_error_ids, true ) ) {
				$output .= \sprintf(
					' <span class="cf7-api-status cf7-api-status-resolved" title="%s">%s</span>',
					\esc_attr__( 'This error was resolved via manual retry', 'contact-form-to-api' ),
					\esc_html__( 'Resolved', 'contact-form-to-api' )
				);
			}
		}

		return $output;
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
