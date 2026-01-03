<?php
/**
 * String Helper Utilities
 *
 * Collection of string manipulation utilities for field name conversion
 * and matching across different naming conventions.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Utils
 * @since 1.1.0
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class StringHelper
 *
 * Static utility methods for string operations.
 */
class StringHelper {
	/**
	 * Convert camelCase to kebab-case
	 *
	 * Transforms camelCase field names to kebab-case format for form field matching.
	 * Example: "infoForLife" becomes "info-for-life"
	 *
	 * @param string $input The camelCase string to convert.
	 * @return string The converted kebab-case string.
	 */
	public static function camel_to_kebab( string $input ): string {
		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $input ) );
	}

	/**
	 * Convert kebab-case to camelCase
	 *
	 * Transforms kebab-case field names to camelCase format for form field matching.
	 * Example: "info-for-life" becomes "infoForLife"
	 *
	 * @param string $input The kebab-case string to convert.
	 * @return string The converted camelCase string.
	 */
	public static function kebab_to_camel( string $input ): string {
		return lcfirst( str_replace( '-', '', ucwords( $input, '-' ) ) );
	}

	/**
	 * Convert snake_case to camelCase
	 *
	 * Transforms snake_case field names to camelCase format.
	 * Example: "info_for_life" becomes "infoForLife"
	 *
	 * @param string $input The snake_case string to convert.
	 * @return string The converted camelCase string.
	 */
	public static function snake_to_camel( string $input ): string {
		return lcfirst( str_replace( '_', '', ucwords( $input, '_' ) ) );
	}

	/**
	 * Convert camelCase to snake_case
	 *
	 * Transforms camelCase field names to snake_case format.
	 * Example: "infoForLife" becomes "info_for_life"
	 *
	 * @param string $input The camelCase string to convert.
	 * @return string The converted snake_case string.
	 */
	public static function camel_to_snake( string $input ): string {
		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $input ) );
	}

	/**
	 * Check if two field names match using various naming conventions
	 *
	 * Compares field names considering different naming conventions:
	 * - Direct comparison
	 * - camelCase vs kebab-case
	 * - camelCase vs snake_case
	 *
	 * @param string $field_a First field name.
	 * @param string $field_b Second field name.
	 * @return bool True if the fields match, false otherwise.
	 */
	public static function fields_match( string $field_a, string $field_b ): bool {
		// Direct comparison.
		if ( $field_a === $field_b ) {
			return true;
		}

		// Normalize both to lowercase kebab-case for comparison.
		$normalized_a = self::camel_to_kebab( $field_a );
		$normalized_b = self::camel_to_kebab( $field_b );

		if ( $normalized_a === $normalized_b ) {
			return true;
		}

		// Also try snake_case normalization.
		$snake_a = self::camel_to_snake( $field_a );
		$snake_b = self::camel_to_snake( $field_b );

		if ( $snake_a === $snake_b ) {
			return true;
		}

		// Check additional permutations.
		$possible_matches = array(
			$field_b,
			self::camel_to_kebab( $field_b ),
			self::kebab_to_camel( $field_a ),
			self::snake_to_camel( $field_a ),
		);

		return in_array( $field_b, $possible_matches, true ) || in_array( $field_a, $possible_matches, true );
	}

	/**
	 * Sanitize field name for API use
	 *
	 * Removes special characters and normalizes the field name.
	 *
	 * @param string $field_name The field name to sanitize.
	 * @return string The sanitized field name.
	 */
	public static function sanitize_field_name( string $field_name ): string {
		// Remove any characters that aren't alphanumeric, underscore, or hyphen.
		$sanitized = preg_replace( '/[^a-zA-Z0-9_-]/', '', $field_name );
		return $sanitized ?: $field_name;
	}

	/**
	 * Extract field name from template placeholder
	 *
	 * Extracts the field name from template placeholders like {{field_name}} or {field_name}.
	 *
	 * @param string $placeholder The placeholder string.
	 * @return string The extracted field name.
	 */
	public static function extract_field_from_placeholder( string $placeholder ): string {
		// Match {{field}} or {field} patterns.
		if ( preg_match( '/\{\{?\s*([^}\s]+)\s*\}?\}/', $placeholder, $matches ) ) {
			return trim( $matches[1] );
		}
		return $placeholder;
	}
}
