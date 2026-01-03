<?php
/**
 * Sensitive Data Patterns
 *
 * Defines patterns for sensitive data fields that should be redacted
 * in logs and exports for security and privacy compliance.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 1.2.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

\defined( 'ABSPATH' ) || exit;

/**
 * Class SensitiveDataPatterns
 *
 * Centralized definition of sensitive data field patterns.
 * Used by RequestLogger and ExportService for consistent data redaction.
 *
 * @since 1.2.0
 */
final class SensitiveDataPatterns {

	/**
	 * Password-related patterns
	 */
	public const PASSWORD = 'password';
	public const PASSWD   = 'passwd';

	/**
	 * Secret and token patterns
	 */
	public const SECRET = 'secret';
	public const TOKEN  = 'token';
	public const BEARER = 'bearer';

	/**
	 * API key patterns
	 */
	public const API_KEY        = 'api_key';
	public const API_KEY_HYPHEN = 'api-key';
	public const APIKEY         = 'apikey';

	/**
	 * Authentication patterns
	 */
	public const AUTH          = 'auth';
	public const AUTHORIZATION = 'authorization';

	/**
	 * Personal identification patterns
	 */
	public const SSN             = 'ssn';
	public const SOCIAL_SECURITY = 'social_security';

	/**
	 * Payment card patterns
	 */
	public const CREDIT_CARD = 'credit_card';
	public const CARD_NUMBER = 'card_number';

	/**
	 * Get all sensitive patterns as array
	 *
	 * Returns all defined sensitive patterns for use in data redaction.
	 * Includes both default patterns and custom patterns from settings.
	 *
	 * @return array<string> Array of sensitive field patterns.
	 */
	public static function get_all(): array {
		$default_patterns = array(
			self::PASSWORD,
			self::PASSWD,
			self::SECRET,
			self::API_KEY,
			self::API_KEY_HYPHEN,
			self::APIKEY,
			self::TOKEN,
			self::AUTH,
			self::AUTHORIZATION,
			self::BEARER,
			self::SSN,
			self::SOCIAL_SECURITY,
			self::CREDIT_CARD,
			self::CARD_NUMBER,
		);

		// Merge with custom patterns from settings if available.
		if ( \class_exists( Settings::class ) ) {
			try {
				$settings         = Settings::instance();
				$custom_patterns  = $settings->get_sensitive_patterns();
				$default_patterns = \array_merge( $default_patterns, $custom_patterns );
			} catch ( \Exception $e ) {
				// Settings not available, use defaults only.
				unset( $e );
			}
		}

		// Remove duplicates and return.
		return \array_unique( $default_patterns );
	}

	/**
	 * Check if a field name matches any sensitive pattern
	 *
	 * @param string $field_name Field name to check.
	 * @return bool True if field matches a sensitive pattern.
	 */
	public static function is_sensitive( string $field_name ): bool {
		$field_lower = \strtolower( $field_name );

		foreach ( self::get_all() as $pattern ) {
			if ( \strpos( $field_lower, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Private constructor to prevent instantiation
	 */
	private function __construct() {
		// This class should not be instantiated.
	}
}
