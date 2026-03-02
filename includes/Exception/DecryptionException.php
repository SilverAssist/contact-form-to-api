<?php
/**
 * Decryption Exception
 *
 * Custom exception thrown when decryption operations fail.
 * Used to differentiate decryption failures from other exceptions.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Exception
 * @since 1.3.0
 * @version 2.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Exception;

use Exception;

\defined( 'ABSPATH' ) || exit;

/**
 * Class DecryptionException
 *
 * Exception thrown when data decryption fails.
 *
 * @since 1.3.0
 */
class DecryptionException extends Exception {

	/**
	 * Constructor
	 *
	 * @since 1.3.0
	 * @param string          $message  Exception message.
	 * @param int             $code     Exception code.
	 * @param \Throwable|null $previous Previous exception.
	 */
	public function __construct( string $message = '', int $code = 0, ?\Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
