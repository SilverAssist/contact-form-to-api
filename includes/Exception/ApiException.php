<?php
/**
 * API Exception
 *
 * Custom exception for API-related errors.
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
 * Class ApiException
 *
 * Thrown when API operations fail.
 *
 * @since 2.0.0
 */
class ApiException extends \Exception {

	/**
	 * Original WP_Error code (string)
	 *
	 * @var string
	 */
	private string $wp_error_code = '';

	/**
	 * Create ApiException from WP_Error
	 *
	 * WordPress error codes are strings (e.g., 'http_request_failed'), not integers.
	 * The original error code is preserved in a property for debugging purposes.
	 *
	 * @since 2.0.0
	 * @param \WP_Error $error WordPress error object.
	 * @return self
	 */
	public static function from_wp_error( \WP_Error $error ): self {
		$exception                = new self( $error->get_error_message() );
		$exception->wp_error_code = (string) $error->get_error_code();
		return $exception;
	}

	/**
	 * Get the original WordPress error code
	 *
	 * @since 2.0.0
	 * @return string WordPress error code.
	 */
	public function get_wp_error_code(): string {
		return $this->wp_error_code;
	}
}
