<?php
/**
 * ApiResponse Model
 *
 * Domain model representing an API response.
 * Provides type-safe access to response data.
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
 * ApiResponse Model Class
 *
 * Represents an API response with type safety.
 * Part of Phase 1 foundation for 2.0.0 architecture refactoring.
 *
 * @since 2.0.0
 */
class ApiResponse {

	/**
	 * HTTP status code
	 *
	 * @var int
	 */
	private int $status_code;

	/**
	 * Response body
	 *
	 * @var mixed
	 */
	private $body;

	/**
	 * Response headers
	 *
	 * @var array<string, mixed>
	 */
	private array $headers;

	/**
	 * Error message if request failed
	 *
	 * @var string|null
	 */
	private ?string $error_message = null;

	/**
	 * Whether the request was successful
	 *
	 * @var bool
	 */
	private bool $is_success;

	/**
	 * Execution time in seconds
	 *
	 * @var float
	 */
	private float $execution_time;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 *
	 * @param int                  $status_code    HTTP status code.
	 * @param mixed                $body           Response body.
	 * @param array<string, mixed> $headers        Response headers.
	 * @param bool                 $is_success     Whether successful.
	 * @param float                $execution_time Execution time in seconds.
	 * @param string|null          $error_message  Error message if failed.
	 */
	public function __construct(
		int $status_code,
		$body,
		array $headers = array(),
		bool $is_success = true,
		float $execution_time = 0.0,
		?string $error_message = null
	) {
		$this->status_code    = $status_code;
		$this->body           = $body;
		$this->headers        = $headers;
		$this->is_success     = $is_success;
		$this->execution_time = $execution_time;
		$this->error_message  = $error_message;
	}

	/**
	 * Get status code
	 *
	 * @since 2.0.0
	 *
	 * @return int HTTP status code.
	 */
	public function get_status_code(): int {
		return $this->status_code;
	}

	/**
	 * Get response body
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Response body.
	 */
	public function get_body() {
		return $this->body;
	}

	/**
	 * Get response headers
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Response headers.
	 */
	public function get_headers(): array {
		return $this->headers;
	}

	/**
	 * Check if request was successful
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if successful.
	 */
	public function is_success(): bool {
		return $this->is_success;
	}

	/**
	 * Get execution time
	 *
	 * @since 2.0.0
	 *
	 * @return float Execution time in seconds.
	 */
	public function get_execution_time(): float {
		return $this->execution_time;
	}

	/**
	 * Get error message
	 *
	 * @since 2.0.0
	 *
	 * @return string|null Error message or null if successful.
	 */
	public function get_error_message(): ?string {
		return $this->error_message;
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
			'status_code'    => $this->status_code,
			'body'           => $this->body,
			'headers'        => $this->headers,
			'is_success'     => $this->is_success,
			'execution_time' => $this->execution_time,
			'error_message'  => $this->error_message,
		);
	}
}
