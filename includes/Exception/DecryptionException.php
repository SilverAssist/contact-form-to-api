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
 * @version 2.4.0
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
}
