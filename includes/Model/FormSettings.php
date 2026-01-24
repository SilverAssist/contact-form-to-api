<?php
/**
 * FormSettings Model
 *
 * Domain model representing Contact Form 7 API integration settings.
 * Provides type-safe access to form configuration.
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
 * FormSettings Model Class
 *
 * Represents CF7 form API integration configuration.
 * Part of Phase 1 foundation for 2.0.0 architecture refactoring.
 *
 * @since 2.0.0
 */
class FormSettings {

	/**
	 * Form ID
	 *
	 * @var int
	 */
	private int $form_id;

	/**
	 * API integration enabled
	 *
	 * @var bool
	 */
	private bool $enabled;

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
	 * Input type (params, json, xml)
	 *
	 * @var string
	 */
	private string $input_type;

	/**
	 * Field mappings
	 *
	 * @var array<string, mixed>
	 */
	private array $field_mappings;

	/**
	 * Authentication configuration
	 *
	 * @var array<string, mixed>
	 */
	private array $auth_config;

	/**
	 * Custom headers
	 *
	 * @var array<string, mixed>
	 */
	private array $custom_headers;

	/**
	 * Retry configuration
	 *
	 * @var array<string, mixed>
	 */
	private array $retry_config;

	/**
	 * Debug mode enabled
	 *
	 * @var bool
	 */
	private bool $debug_mode;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 *
	 * @param int                  $form_id        Form ID.
	 * @param bool                 $enabled        Integration enabled.
	 * @param string               $endpoint       API endpoint URL.
	 * @param string               $method         HTTP method.
	 * @param string               $input_type     Input type.
	 * @param array<string, mixed> $field_mappings Field mappings.
	 * @param array<string, mixed> $auth_config    Authentication config.
	 * @param array<string, mixed> $custom_headers Custom headers.
	 * @param array<string, mixed> $retry_config   Retry configuration.
	 * @param bool                 $debug_mode     Debug mode.
	 */
	public function __construct(
		int $form_id,
		bool $enabled = false,
		string $endpoint = '',
		string $method = 'POST',
		string $input_type = 'params',
		array $field_mappings = array(),
		array $auth_config = array(),
		array $custom_headers = array(),
		array $retry_config = array(),
		bool $debug_mode = false
	) {
		$this->form_id        = $form_id;
		$this->enabled        = $enabled;
		$this->endpoint       = $endpoint;
		$this->method         = $method;
		$this->input_type     = $input_type;
		$this->field_mappings = $field_mappings;
		$this->auth_config    = $auth_config;
		$this->custom_headers = $custom_headers;
		$this->retry_config   = $retry_config;
		$this->debug_mode     = $debug_mode;
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
	 * Check if integration is enabled
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if enabled.
	 */
	public function is_enabled(): bool {
		return $this->enabled;
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
	 * Get input type
	 *
	 * @since 2.0.0
	 *
	 * @return string Input type.
	 */
	public function get_input_type(): string {
		return $this->input_type;
	}

	/**
	 * Get field mappings
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Field mappings.
	 */
	public function get_field_mappings(): array {
		return $this->field_mappings;
	}

	/**
	 * Get authentication configuration
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Auth config.
	 */
	public function get_auth_config(): array {
		return $this->auth_config;
	}

	/**
	 * Get custom headers
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Custom headers.
	 */
	public function get_custom_headers(): array {
		return $this->custom_headers;
	}

	/**
	 * Get retry configuration
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed> Retry config.
	 */
	public function get_retry_config(): array {
		return $this->retry_config;
	}

	/**
	 * Check if debug mode is enabled
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if debug mode enabled.
	 */
	public function is_debug_mode(): bool {
		return $this->debug_mode;
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
			'form_id'        => $this->form_id,
			'enabled'        => $this->enabled,
			'endpoint'       => $this->endpoint,
			'method'         => $this->method,
			'input_type'     => $this->input_type,
			'field_mappings' => $this->field_mappings,
			'auth_config'    => $this->auth_config,
			'custom_headers' => $this->custom_headers,
			'retry_config'   => $this->retry_config,
			'debug_mode'     => $this->debug_mode,
		);
	}

	/**
	 * Create FormSettings from post meta array
	 *
	 * @since 2.0.0
	 *
	 * @param int                  $form_id Form ID.
	 * @param array<string, mixed> $meta    Post meta array.
	 * @return FormSettings FormSettings instance.
	 */
	public static function from_meta( int $form_id, array $meta ): FormSettings {
		// Support both direct meta keys and the wpcf7_api_data structure.
		$api_data = array();
		if ( isset( $meta['_wpcf7_api_data'] ) && \is_array( $meta['_wpcf7_api_data'] ) ) {
			$api_data = $meta['_wpcf7_api_data'];
		} elseif ( isset( $meta['wpcf7_api_data'] ) && \is_array( $meta['wpcf7_api_data'] ) ) {
			$api_data = $meta['wpcf7_api_data'];
		}

		$api_data_map = array();
		if ( isset( $meta['_wpcf7_api_data_map'] ) && \is_array( $meta['_wpcf7_api_data_map'] ) ) {
			$api_data_map = $meta['_wpcf7_api_data_map'];
		} elseif ( isset( $meta['wpcf7_api_data_map'] ) && \is_array( $meta['wpcf7_api_data_map'] ) ) {
			$api_data_map = $meta['wpcf7_api_data_map'];
		}

		return new self(
			$form_id,
			! empty( $api_data['send_to_api'] ),
			(string) ( $api_data['base_url'] ?? '' ),
			(string) ( $api_data['method'] ?? 'POST' ),
			(string) ( $api_data['input_type'] ?? 'params' ),
			$api_data_map,
			(array) ( $meta['_wpcf7_api_auth'] ?? $meta['wpcf7_api_auth'] ?? array() ),
			(array) ( $meta['custom_headers'] ?? array() ),
			(array) ( $meta['retry_config'] ?? array() ),
			! empty( $api_data['debug_log'] )
		);
	}
}
