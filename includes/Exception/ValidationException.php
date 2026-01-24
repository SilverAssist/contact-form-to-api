<?php
/**
 * Validation Exception
 *
 * Custom exception for validation errors.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Exception
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Exception;

\defined( 'ABSPATH' ) || exit;

/**
 * Class ValidationException
 *
 * Thrown when data validation fails.
 *
 * @since 2.0.0
 */
class ValidationException extends \Exception {

	/**
	 * Validation errors
	 *
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Set validation errors
	 *
	 * @since 2.0.0
	 * @param array<string, string> $errors Validation errors.
	 * @return void
	 */
	public function set_errors( array $errors ): void {
		$this->errors = $errors;
	}

	/**
	 * Get validation errors
	 *
	 * @since 2.0.0
	 * @return array<string, string> Validation errors.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Create exception with multiple errors
	 *
	 * @since 2.0.0
	 * @param array<string, string> $errors Validation errors.
	 * @return self
	 */
	public static function with_errors( array $errors ): self {
		$exception = new self( 'Validation failed' );
		$exception->set_errors( $errors );
		return $exception;
	}
}
