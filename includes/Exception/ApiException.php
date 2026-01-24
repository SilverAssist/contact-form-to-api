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
	 * Create ApiException from WP_Error
	 *
	 * @since 2.0.0
	 * @param \WP_Error $error WordPress error object.
	 * @return self
	 */
	public static function from_wp_error( \WP_Error $error ): self {
		return new self( $error->get_error_message(), $error->get_error_code() ? (int) $error->get_error_code() : 0 );
	}
}
