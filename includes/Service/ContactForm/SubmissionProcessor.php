<?php
/**
 * Contact Form Submission Processor
 *
 * Handles business logic for Contact Form 7 submission processing.
 * Transforms form data, sends to API, manages retries and logging.
 * Extracted from Integration.php as part of Phase 3 refactoring.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Service\ContactForm
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Service\ContactForm;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Services\ApiClient;
use SilverAssist\ContactFormToAPI\Services\CheckboxHandler;
use WPCF7_ContactForm;
use WPCF7_Submission;
use WP_Error;

\defined( 'ABSPATH' ) || exit;

/**
 * Class SubmissionProcessor
 *
 * Service layer for processing Contact Form 7 submissions.
 * Handles data transformation, API communication, and error handling.
 *
 * Responsibilities:
 * - Process form submissions
 * - Build API records from CF7 data
 * - Send requests to API endpoints
 * - Handle errors and retries
 * - Manage checkbox value transformations
 *
 * @since 2.0.0
 */
class SubmissionProcessor implements LoadableInterface {

	/**
	 * Retry configuration defaults
	 *
	 * @since 2.0.0
	 */
	private const DEFAULT_MAX_RETRIES = 3;
	private const DEFAULT_RETRY_DELAY = 2;

	/**
	 * Singleton instance
	 *
	 * @since 2.0.0
	 * @var SubmissionProcessor|null
	 */
	private static ?SubmissionProcessor $instance = null;

	/**
	 * Initialization flag
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Current form object for processing
	 *
	 * @since 2.0.0
	 * @var WPCF7_ContactForm|null
	 */
	private ?WPCF7_ContactForm $current_form = null;

	/**
	 * API errors for current request
	 *
	 * @since 2.0.0
	 * @var array<int|string, mixed>
	 */
	private array $api_errors = array();

	/**
	 * Get singleton instance
	 *
	 * @since 2.0.0
	 * @return SubmissionProcessor
	 */
	public static function instance(): SubmissionProcessor {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		// Empty - initialization happens in init().
	}

	/**
	 * Initialize the service
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;
	}

	/**
	 * Get component priority
	 *
	 * @since 2.0.0
	 * @return int Priority (20 for services)
	 */
	public function get_priority(): int {
		return 20;
	}

	/**
	 * Check if component should load
	 *
	 * @since 2.0.0
	 * @return bool True if Contact Form 7 is active
	 */
	public function should_load(): bool {
		return \class_exists( 'WPCF7_ContactForm' );
	}

	/**
	 * Process form submission
	 *
	 * Main entry point for form submission processing.
	 * Handles data extraction, transformation, and API communication.
	 *
	 * @since 2.0.0
	 * @param WPCF7_ContactForm $contact_form Contact form object (CF7).
	 * @return void
	 */
	public function process_submission( WPCF7_ContactForm $contact_form ): void {
		$this->clear_error_log( $contact_form->id() );
		$this->current_form = $contact_form;

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$form_id = $contact_form->id();

		// Get from properties first, fallback to post_meta for backward compatibility.
		$api_data          = $contact_form->prop( 'wpcf7_api_data' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data', true );
		$api_data_map      = $contact_form->prop( 'wpcf7_api_data_map' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data_map', true );
		$api_data_template = $contact_form->prop( 'template' ) ?: \get_post_meta( $form_id, '_template', true );
		$api_json_template = \stripslashes( $contact_form->prop( 'json_template' ) ?: \get_post_meta( $form_id, '_json_template', true ) );
		$retry_config      = $contact_form->prop( 'retry_config' ) ?: array();
		$custom_headers    = $contact_form->prop( 'custom_headers' ) ?: array();

		// Set default retry configuration if not provided.
		if ( ! \is_array( $retry_config ) ) {
			$retry_config = array();
		}
		$retry_config['max_retries']      ??= self::DEFAULT_MAX_RETRIES;
		$retry_config['retry_delay']      ??= self::DEFAULT_RETRY_DELAY;
		$retry_config['retry_on_timeout'] ??= true;

		// Always enable debug logging.
		$api_data['debug_log'] = true;

		// Check if form should be sent to API.
		if ( empty( $api_data['send_to_api'] ) || $api_data['send_to_api'] !== 'on' ) {
			return;
		}

		$record_type = $api_data['input_type'] ?? 'params';

		if ( $record_type === 'json' ) {
			$api_data_template = \stripslashes( $api_json_template );
		}

		$record        = $this->build_api_record( $submission, $api_data_map, $record_type, $api_data_template );
		$record['url'] = $api_data['base_url'];

		if ( ! empty( $record['url'] ) ) {
			\do_action( 'cf7_api_before_send_to_api', $record );

			$response = $this->send_api_request( $record, $api_data['debug_log'], $api_data['method'], $record_type, $retry_config, $custom_headers );

			if ( \is_wp_error( $response ) ) {
				$this->log_api_error( $response, $contact_form->id() );
			} else {
				\do_action( 'cf7_api_after_send_to_api', $record, $response );
			}
		}
	}

	/**
	 * Build API record from form submission
	 *
	 * Converts CF7 submission data to API record format.
	 * Handles params, JSON, and XML record types.
	 *
	 * @since 2.0.0
	 * @param WPCF7_Submission       $submission Form submission (CF7 Submission object).
	 * @param array<string, mixed>  $data_map   Field mapping.
	 * @param string                $type       Record type (params, xml, json).
	 * @param string                $template   Template for xml/json.
	 * @return array<string, mixed> API record data.
	 */
	public function build_api_record( WPCF7_Submission $submission, array $data_map, string $type = 'params', string $template = '' ): array {
		$submitted_data = $submission->get_posted_data();
		$record         = array();

		if ( $type === 'params' ) {
			foreach ( $data_map as $form_key => $api_form_key ) {
				if ( ! $api_form_key ) {
					continue;
				}

				if ( \is_array( $api_form_key ) ) {
					// Handle checkbox arrays.
					$field_value = $submitted_data[ $form_key ] ?? null;
					if ( ! \is_array( $field_value ) ) {
						continue;
					}
					foreach ( $field_value as $value ) {
						if ( $value ) {
								$record['fields'][ $api_form_key[ $value ] ] = \apply_filters( 'cf7_api_set_record_value', $value, $api_form_key );
						}
					}
				} else {
					$value = $submitted_data[ $form_key ] ?? '';

					// Flatten radio button values.
					if ( \is_array( $value ) ) {
						$value = reset( $value );
					}

					$record['fields'][ $api_form_key ] = \apply_filters( 'cf7_api_set_record_value', $value, $api_form_key );
				}
			}
		} elseif ( $type === 'xml' || $type === 'json' ) {
			foreach ( $data_map as $form_key => $api_form_key ) {
				if ( \is_array( $api_form_key ) ) {
					// Handle checkbox arrays.
					$field_value = $submitted_data[ $form_key ] ?? null;
					if ( ! \is_array( $field_value ) ) {
						continue;
					}
					foreach ( $field_value as $value ) {
						if ( $value ) {
								$value    = \apply_filters( 'cf7_api_set_record_value', $value, $api_form_key );
								$template = str_replace( "[{$form_key}-{$value}]", $value, $template );
						}
					}
				} else {
					$value = $submitted_data[ $form_key ] ?? '';

					// Flatten radio button values.
					if ( \is_array( $value ) ) {
						$value = reset( $value );
					}

					$value    = \apply_filters( 'cf7_api_set_record_value', $value, $api_form_key );
					$template = str_replace( "[{$form_key}]", $value, $template );
				}
			}

			// Clean unchanged tags.
			foreach ( $data_map as $form_key => $api_form_key ) {
				if ( \is_array( $api_form_key ) ) {
					foreach ( $api_form_key as $field_suffix => $api_name ) {
						$template = str_replace( "[{$form_key}-{$field_suffix}]", '', $template );
					}
				}
			}

			$record['fields'] = $template;
		}

		$record = \apply_filters( 'cf7_api_create_record', $record, $submitted_data, $data_map, $type, $template );

		return $record;
	}

	/**
	 * Send API request with retry support
	 *
	 * Sends lead data to API endpoint using ApiClient service.
	 * Handles retries, debug logging, and error handling.
	 *
	 * @since 2.0.0
	 * @param array<string, mixed>                $record         Record data.
	 * @param bool                                $debug          Enable debug logging.
	 * @param string                              $method         HTTP method.
	 * @param string                              $record_type    Record type (params, json, xml).
	 * @param array<string, mixed>                $retry_config   Retry configuration.
	 * @param array<int, array<string, string>>   $custom_headers Custom HTTP headers.
	 * @return array<string, mixed>|WP_Error Response data or error.
	 */
	public function send_api_request( array $record, bool $debug = false, string $method = 'GET', string $record_type = 'params', array $retry_config = array(), array $custom_headers = array() ) {
		$lead = $record['fields'];
		$url  = $record['url'];

		// Build headers array from custom_headers.
		$headers = array();
		if ( ! empty( $custom_headers ) ) {
			foreach ( $custom_headers as $header ) {
				if ( ! empty( $header['name'] ) ) {
					$headers[ $header['name'] ] = $header['value'] ?? '';
				}
			}
		}

		// Build request configuration for ApiClient.
		$request_config = array(
			'url'          => $url,
			'method'       => $method,
			'body'         => $lead,
			'headers'      => $headers,
			'content_type' => $record_type,
			'form_id'      => $this->current_form ? $this->current_form->id() : 0,
			'retry_config' => array(
				'max_retries'      => $retry_config['max_retries'] ?? self::DEFAULT_MAX_RETRIES,
				'retry_delay'      => $retry_config['retry_delay'] ?? self::DEFAULT_RETRY_DELAY,
				'retry_on_timeout' => $retry_config['retry_on_timeout'] ?? true,
			),
		);

		// Send via ApiClient.
		$result = ApiClient::instance()->send( $request_config );

		// Legacy debug logging (for backward compatibility).
		if ( $debug && $this->current_form ) {
			\update_post_meta( $this->current_form->id(), 'cf7_api_debug_url', $url );
			\update_post_meta( $this->current_form->id(), 'cf7_api_debug_params', $lead );

			if ( \is_wp_error( $result ) ) {
				$result->add_data( $request_config );
			}

			\update_post_meta( $this->current_form->id(), 'cf7_api_debug_result', $result );
		}

		return \apply_filters( 'cf7_api_after_send_lead', $result, $record );
	}

	/**
	 * Log API error
	 *
	 * Keeps legacy error logging for backward compatibility.
	 *
	 * @since 2.0.0
	 * @param WP_Error $wp_error WordPress error.
	 * @param integer   $form_id  Form ID.
	 * @return void
	 */
	public function log_api_error( WP_Error $wp_error, int $form_id ): void {
		$this->api_errors[] = $wp_error;
		\update_post_meta( $form_id, 'api_errors', $this->api_errors );
	}

	/**
	 * Clear error log for form
	 *
	 * @since 2.0.0
	 * @param integer $form_id Form ID.
	 * @return void
	 */
	public function clear_error_log( int $form_id ): void {
		\delete_post_meta( $form_id, 'api_errors' );
	}

	/**
	 * Handle checkbox values for CF7 API using set_record_value filter
	 *
	 * Delegates to CheckboxHandler service for consistent processing.
	 *
	 * @since 2.0.0
	 * @param mixed $value          The field value to process.
	 * @param mixed $api_field_name The API field name.
	 * @return mixed The processed value.
	 */
	public function handle_checkbox_value( $value, $api_field_name ) {
		return CheckboxHandler::instance()->process_value( $value, $api_field_name );
	}

	/**
	 * Handle checkbox values for CF7 API in JSON/XML template mode
	 *
	 * Delegates to CheckboxHandler service for template processing.
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $record         The record data.
	 * @param array<string, mixed> $submitted_data The submitted form data.
	 * @param array<string, mixed> $qs_cf7_data_map The field mapping configuration.
	 * @param string               $type           The template type.
	 * @param string               $template       The original template string.
	 * @return array<string, mixed> The modified record.
	 */
	public function handle_boolean_checkbox( array $record, array $submitted_data, array $qs_cf7_data_map, string $type, string $template ): array {
		return CheckboxHandler::instance()->process_template_record( $record, $submitted_data, $qs_cf7_data_map, $type, $template );
	}

	/**
	 * Final handler for checkbox values
	 *
	 * Delegates to CheckboxHandler service for final processing.
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $record The record data.
	 * @return array<string, mixed> The modified record.
	 */
	public function handle_final_checkbox( array $record ): array {
		return CheckboxHandler::instance()->process_final( $record );
	}
}
