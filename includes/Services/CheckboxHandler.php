<?php
/**
 * Checkbox Handler Service
 *
 * Handles checkbox value detection and conversion for Contact Form 7 submissions.
 * Converts various checkbox formats to standardized yes/no values for API consumption.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Services
 * @since 1.1.0
 * @version 1.2.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Services;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Utils\StringHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class CheckboxHandler
 *
 * Processes checkbox values from CF7 forms and converts them to API-friendly formats.
 */
class CheckboxHandler implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var CheckboxHandler|null
	 */
	private static ?CheckboxHandler $instance = null;

	/**
	 * Whether the component has been initialized
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Known checkbox values that indicate a field is a checkbox
	 *
	 * @var array<string>
	 */
	public const CHECKBOX_VALUES = array( '1', '0', 'true', 'false', 'yes', 'no', 'on', 'off', '' );

	/**
	 * Values that represent "checked" state
	 *
	 * @var array<string>
	 */
	public const CHECKED_VALUES = array( '1', 'true', 'yes', 'on' );

	/**
	 * Output values for yes/no conversion
	 *
	 * @var array<string>
	 */
	public const OUTPUT_VALUES = array( '1', '0' );

	/**
	 * Get singleton instance
	 *
	 * @return CheckboxHandler
	 */
	public static function instance(): CheckboxHandler {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {}

	/**
	 * Initialize the service
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 20; // Services priority.
	}

	/**
	 * Determine if service should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true;
	}

	/**
	 * Handle checkbox value conversion
	 *
	 * Auto-detects checkbox fields based on their values and converts them to "1"/"0" format.
	 *
	 * @param mixed $value          The field value to process.
	 * @param mixed $api_field_name The API field name.
	 * @return mixed The processed value ("1"/"0" for checkboxes, original value otherwise).
	 */
	public function process_value( $value, $api_field_name ) {
		if ( is_string( $api_field_name ) && $this->is_checkbox_value( $value ) ) {
			return $this->convert_value( $value );
		}

		return $value;
	}

	/**
	 * Handle checkbox values in JSON/XML template mode
	 *
	 * @param array<string, mixed> $record         The record data containing fields.
	 * @param array<string, mixed> $submitted_data The submitted form data from Contact Form 7.
	 * @param array<string, mixed> $field_map      The field mapping configuration.
	 * @param string               $type           The template type (json, xml, etc.).
	 * @param string               $template       The original template string with placeholders.
	 * @return array<string, mixed> The modified record with processed checkbox values.
	 */
	public function process_template_record( array $record, array $submitted_data, array $field_map, string $type, string $template ): array {
		if ( 'json' !== $type && 'xml' !== $type ) {
			return $record;
		}

		// Try to decode and process the template as JSON.
		$decoded_template = json_decode( $template, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded_template ) ) {
			$record['fields'] = $template;
			return $record;
		}

		// Process checkbox fields.
		foreach ( $field_map as $form_field_name => $field_mapping ) {
			// Only process checkbox fields (arrays with exactly one element).
			if ( ! is_array( $field_mapping ) || 1 !== count( $field_mapping ) ) {
				continue;
			}

			$is_checked     = $this->is_field_checked( $submitted_data, $form_field_name );
			$checkbox_value = $is_checked ? self::OUTPUT_VALUES[0] : self::OUTPUT_VALUES[1];

			// Update the template value.
			foreach ( $decoded_template as $template_key => $template_value ) {
				if (
					( $template_value === '' || $template_value === null ) &&
					StringHelper::fields_match( $template_key, $form_field_name )
				) {
					$decoded_template[ $template_key ] = $checkbox_value;
					break;
				}
			}
		}

		$record['fields'] = wp_json_encode( $decoded_template );
		return $record;
	}

	/**
	 * Final processing for checkbox values in JSON strings
	 *
	 * @param array<string, mixed>      $record         The record data.
	 * @param array<string, mixed>|null $submitted_data Optional submitted data for reference.
	 * @return array<string, mixed> The modified record.
	 */
	public function process_final( array $record, ?array $submitted_data = null ): array {
		if ( ! isset( $record['fields'] ) || ! is_string( $record['fields'] ) ) {
			return $record;
		}

		$json_data = $record['fields'];
		$decoded   = json_decode( $json_data, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
			return $record;
		}

		// Get submitted data if not provided.
		if ( null === $submitted_data ) {
			$submission     = \WPCF7_Submission::get_instance();
			$submitted_data = $submission ? $submission->get_posted_data() : array();
		}

		$modified = false;

		foreach ( $decoded as $field_name => $field_value ) {
			if ( $field_value !== '' && $field_value !== null ) {
				continue;
			}

			// Try to find matching submitted field.
			$kebab_field_name = StringHelper::camel_to_kebab( $field_name );

			if ( isset( $submitted_data[ $kebab_field_name ] ) && is_array( $submitted_data[ $kebab_field_name ] ) ) {
				$original_value = $submitted_data[ $kebab_field_name ][0] ?? null;

				if ( $this->is_checkbox_value( $original_value ) ) {
					$decoded[ $field_name ] = $this->convert_value( $original_value );
					$modified               = true;
				}
			}
		}

		if ( $modified ) {
			$record['fields'] = wp_json_encode( $decoded );
		}

		return $record;
	}

	/**
	 * Check if a value looks like a checkbox value
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value appears to be from a checkbox.
	 */
	public function is_checkbox_value( $value ): bool {
		return in_array( $value, self::CHECKBOX_VALUES, true );
	}

	/**
	 * Determine if a checkbox value represents "checked" state
	 *
	 * @param mixed $value The checkbox value to evaluate.
	 * @return bool True if the value represents a checked checkbox.
	 */
	public function is_checked( $value ): bool {
		return in_array( $value, self::CHECKED_VALUES, true );
	}

	/**
	 * Convert checkbox value to standardized format
	 *
	 * @param mixed $value The checkbox value to convert.
	 * @return string "1" or "0".
	 */
	public function convert_value( $value ): string {
		return $this->is_checked( $value ) ? self::OUTPUT_VALUES[0] : self::OUTPUT_VALUES[1];
	}

	/**
	 * Check if a form field was checked in submitted data
	 *
	 * @param array<string, mixed> $submitted_data The submitted form data.
	 * @param string               $field_name     The field name to check.
	 * @return bool True if the checkbox was checked.
	 */
	private function is_field_checked( array $submitted_data, string $field_name ): bool {
		if ( ! isset( $submitted_data[ $field_name ] ) ) {
			return false;
		}

		if ( ! is_array( $submitted_data[ $field_name ] ) ) {
			return (bool) $submitted_data[ $field_name ];
		}

		if ( empty( $submitted_data[ $field_name ] ) ) {
			return false;
		}

		$value = $submitted_data[ $field_name ][0] ?? null;

		return ! empty( $value ) &&
			$value !== 'false' &&
			$value !== false &&
			$value !== '0';
	}
}
