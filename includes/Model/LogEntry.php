<?php
/**
 * LogEntry Model
 *
 * Domain model representing an API request log entry.
 * Provides type-safe access to log data and validation logic.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Model
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Model;

\defined( 'ABSPATH' ) || exit;

/**
 * LogEntry Model Class
 *
 * Represents an API request log entry with type safety.
 * Provides immutable access to request/response log data.
 *
 * @since 2.0.0
 */
class LogEntry {

	/**
	 * Log entry ID
	 *
	 * @var int|null
	 */
	private ?int $id = null;

	/**
	 * Form ID
	 *
	 * @var int
	 */
	private int $form_id;

	/**
	 * API endpoint URL
	 *
	 * @var string
	 */
	private string $endpoint;

	/**
	 * HTTP method
	 *
	 * @var string
	 */
	private string $method;

	/**
	 * Request status
	 *
	 * @var string
	 */
	private string $status;

	/**
	 * HTTP status code
	 *
	 * @var int|null
	 */
	private ?int $response_code = null;

	/**
	 * Request data
	 *
	 * @var array<string, mixed>
	 */
	private array $request_data;

	/**
	 * Request headers
	 *
	 * @var array<string, mixed>
	 */
	private array $request_headers;

	/**
	 * Response data
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $response_data = null;

	/**
	 * Response headers
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $response_headers = null;

	/**
	 * Error message
	 *
	 * @var string|null
	 */
	private ?string $error_message = null;

	/**
	 * Execution time in seconds
	 *
	 * @var float|null
	 */
	private ?float $execution_time = null;

	/**
	 * Created timestamp
	 *
	 * @var string|null
	 */
	private ?string $created_at = null;

	/**
	 * Retry count
	 *
	 * @var int
	 */
	private int $retry_count = 0;

	/**
	 * Parent log ID (for retries)
	 *
	 * @var int|null
	 */
	private ?int $retry_of = null;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 *
	 * @param int                   $form_id         Form ID.
	 * @param string                $endpoint        API endpoint URL.
	 * @param string                $method          HTTP method.
	 * @param string                $status          Request status.
	 * @param array<string, mixed>  $request_data    Request data.
	 * @param array<string, mixed>  $request_headers Request headers.
	 */
	public function __construct(
		int $form_id,
		string $endpoint,
		string $method,
		string $status,
		array $request_data = array(),
		array $request_headers = array()
	) {
		$this->form_id         = $form_id;
		$this->endpoint        = $endpoint;
		$this->method          = $method;
		$this->status          = $status;
		$this->request_data    = $request_data;
		$this->request_headers = $request_headers;
	}

	/**
	 * Get log ID
	 *
	 * @since 2.0.0
	 *
	 * @return int|null Log ID or null if not persisted.
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Set log ID
	 *
	 * @since 2.0.0
	 *
	 * @param int $id Log ID.
	 * @return void
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Get form ID
	 *
	 * @since 2.0.0
	 *
	 * @return int Form ID.
	 */
	public function get_form_id(): int {
		return $this->form_id;
	}

	/**
	 * Get endpoint URL
	 *
	 * @since 2.0.0
	 *
	 * @return string Endpoint URL.
	 */
	public function get_endpoint(): string {
		return $this->endpoint;
	}

	/**
	 * Get HTTP method
	 *
	 * @since 2.0.0
	 *
	 * @return string HTTP method.
	 */
	public function get_method(): string {
		return $this->method;
	}

	/**
	 * Get status
	 *
	 * @since 2.0.0
	 *
	 * @return string Request status.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Set status
	 *
	 * @since 2.0.0
	 *
	 * @param string $status Request status.
	 * @return void
	 */
	public function set_status( string $status ): void {
		$this->status = $status;
	}

	/**
	 * Get status code
	 *
	 * @since 2.0.0
	 *
	 * @return int|null HTTP status code.
	 */
	public function get_response_code(): ?int {
		return $this->response_code;
	}

	/**
	 * Set status code
	 *
	 * @since 2.0.0
	 *
	 * @param int|null $response_code HTTP status code.
	 * @return void
	 */
	public function set_response_code( ?int $response_code ): void {
		$this->response_code = $response_code;
	}

	/**
	 * Get request data
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Request data.
	 */
	public function get_request_data(): array {
		return $this->request_data;
	}

	/**
	 * Get request headers
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Request headers.
	 */
	public function get_request_headers(): array {
		return $this->request_headers;
	}

	/**
	 * Get response data
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed>|null Response data.
	 */
	public function get_response_data(): ?array {
		return $this->response_data;
	}

	/**
	 * Set response data
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed>|null $response_data Response data.
	 * @return void
	 */
	public function set_response_data( ?array $response_data ): void {
		$this->response_data = $response_data;
	}

	/**
	 * Get response headers
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed>|null Response headers.
	 */
	public function get_response_headers(): ?array {
		return $this->response_headers;
	}

	/**
	 * Set response headers
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed>|null $response_headers Response headers.
	 * @return void
	 */
	public function set_response_headers( ?array $response_headers ): void {
		$this->response_headers = $response_headers;
	}

	/**
	 * Get error message
	 *
	 * @since 2.0.0
	 *
	 * @return string|null Error message.
	 */
	public function get_error_message(): ?string {
		return $this->error_message;
	}

	/**
	 * Set error message
	 *
	 * @since 2.0.0
	 *
	 * @param string|null $error_message Error message.
	 * @return void
	 */
	public function set_error_message( ?string $error_message ): void {
		$this->error_message = $error_message;
	}

	/**
	 * Get execution time
	 *
	 * @since 2.0.0
	 *
	 * @return float|null Execution time in seconds.
	 */
	public function get_execution_time(): ?float {
		return $this->execution_time;
	}

	/**
	 * Set execution time
	 *
	 * @since 2.0.0
	 *
	 * @param float|null $execution_time Execution time in seconds.
	 * @return void
	 */
	public function set_execution_time( ?float $execution_time ): void {
		$this->execution_time = $execution_time;
	}

	/**
	 * Get created timestamp
	 *
	 * @since 2.0.0
	 *
	 * @return string|null Created timestamp.
	 */
	public function get_created_at(): ?string {
		return $this->created_at;
	}

	/**
	 * Set created timestamp
	 *
	 * @since 2.0.0
	 *
	 * @param string|null $created_at Created timestamp.
	 * @return void
	 */
	public function set_created_at( ?string $created_at ): void {
		$this->created_at = $created_at;
	}

	/**
	 * Get retry count
	 *
	 * @since 2.0.0
	 *
	 * @return int Retry count.
	 */
	public function get_retry_count(): int {
		return $this->retry_count;
	}

	/**
	 * Set retry count
	 *
	 * @since 2.0.0
	 *
	 * @param int $retry_count Retry count.
	 * @return void
	 */
	public function set_retry_count( int $retry_count ): void {
		$this->retry_count = $retry_count;
	}

	/**
	 * Get parent log ID
	 *
	 * @since 2.0.0
	 *
	 * @return int|null Parent log ID.
	 */
	public function get_retry_of(): ?int {
		return $this->retry_of;
	}

	/**
	 * Set parent log ID
	 *
	 * @since 2.0.0
	 *
	 * @param int|null $retry_of Parent log ID.
	 * @return void
	 */
	public function set_retry_of( ?int $retry_of ): void {
		$this->retry_of = $retry_of;
	}

	/**
	 * Check if this is a retry
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if this is a retry.
	 */
	public function is_retry(): bool {
		return null !== $this->retry_of;
	}

	/**
	 * Check if request was successful
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if successful.
	 */
	public function is_successful(): bool {
		return 'success' === $this->status;
	}

	/**
	 * Check if request resulted in an error
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if error occurred.
	 */
	public function is_error(): bool {
		return \in_array( $this->status, array( 'error', 'client_error', 'server_error' ), true );
	}

	/**
	 * Convert to array representation
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Array representation.
	 */
	public function to_array(): array {
		return array(
			'id'               => $this->id,
			'form_id'          => $this->form_id,
			'endpoint'         => $this->endpoint,
			'method'           => $this->method,
			'status'           => $this->status,
			'response_code'    => $this->response_code,
			'request_data'     => $this->request_data,
			'request_headers'  => $this->request_headers,
			'response_data'    => $this->response_data,
			'response_headers' => $this->response_headers,
			'error_message'    => $this->error_message,
			'execution_time'   => $this->execution_time,
			'created_at'       => $this->created_at,
			'retry_count'      => $this->retry_count,
			'retry_of'         => $this->retry_of,
		);
	}

	/**
	 * Create LogEntry from array data
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $data Log data from database.
	 * @return LogEntry LogEntry instance.
	 */
	public static function from_array( array $data ): LogEntry {
		// Helper to decode JSON strings or return arrays.
		$decode_json = function ( $value ) {
			if ( \is_string( $value ) && ! empty( $value ) ) {
				$decoded = \json_decode( $value, true );
				return \is_array( $decoded ) ? $decoded : array();
			}
			return \is_array( $value ) ? $value : array();
		};

		$entry = new self(
			(int) $data['form_id'],
			(string) $data['endpoint'],
			(string) $data['method'],
			(string) $data['status'],
			isset( $data['request_data'] ) ? $decode_json( $data['request_data'] ) : array(),
			isset( $data['request_headers'] ) ? $decode_json( $data['request_headers'] ) : array()
		);

		if ( isset( $data['id'] ) ) {
			$entry->set_id( (int) $data['id'] );
		}

		if ( isset( $data['response_code'] ) ) {
			$entry->set_response_code( (int) $data['response_code'] );
		}

		if ( isset( $data['response_data'] ) ) {
			$entry->set_response_data( $decode_json( $data['response_data'] ) );
		}

		if ( isset( $data['response_headers'] ) ) {
			$entry->set_response_headers( $decode_json( $data['response_headers'] ) );
		}

		if ( isset( $data['error_message'] ) ) {
			$entry->set_error_message( (string) $data['error_message'] );
		}

		if ( isset( $data['execution_time'] ) ) {
			$entry->set_execution_time( (float) $data['execution_time'] );
		}

		if ( isset( $data['created_at'] ) ) {
			$entry->set_created_at( (string) $data['created_at'] );
		}

		if ( isset( $data['retry_count'] ) ) {
			$entry->set_retry_count( (int) $data['retry_count'] );
		}

		if ( isset( $data['retry_of'] ) ) {
			$entry->set_retry_of( (int) $data['retry_of'] );
		}

		return $entry;
	}
}
