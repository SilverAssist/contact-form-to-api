<?php

/**
 * Contact Form 7 Integration
 *
 * Handles integration with Contact Form 7 forms and submission processing
 * Migrated from the legacy CF7 API plugin with modern standards
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage ContactForm
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.2.0
 */

namespace SilverAssist\ContactFormToAPI\ContactForm;

use SilverAssist\ContactFormToAPI\ContactForm\Views\IntegrationView;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use SilverAssist\ContactFormToAPI\Services\ApiClient;
use SilverAssist\ContactFormToAPI\Services\CheckboxHandler;
use WPCF7_ContactForm;
use WPCF7_Submission;
use WP_Error;

\defined( 'ABSPATH' ) || exit;

/**
 * Contact Form 7 Integration Class
 *
 * Manages integration with Contact Form 7 forms and processes submissions
 * Provides direct form-level configuration via admin tabs
 *
 * @since 1.1.0
 */
class Integration implements LoadableInterface {
	/**
	 * Retry configuration defaults
	 *
	 * @since 1.0.0
	 */
	private const DEFAULT_MAX_RETRIES = 3;
	private const DEFAULT_RETRY_DELAY = 2;

	/**
	 * Singleton instance
	 *
	 * @var Integration|null
	 */
	private static ?Integration $instance = null;

	/**
	 * Current form object for processing
	 *
	 * @since 1.0.0
	 * @var WPCF7_ContactForm|null
	 */
	private ?WPCF7_ContactForm $current_form = null;

	/**
	 * API errors for current request
	 *
	 * @since 1.0.0
	 * @var array<int|string, mixed>
	 */
	private array $api_errors = array();

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return Integration
	 */
	public static function instance(): Integration {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		// Empty - initialization happens in init().
	}

	/**
	 * Initialize Contact Form 7 integration
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		// Register legacy hook aliases for backward compatibility.
		$this->register_legacy_hooks();

		// Hook into Contact Form 7 submission process.
		\add_action( 'wpcf7_before_send_mail', array( $this, 'send_data_to_api' ) );

		// Add checkbox value handling filters.
		\add_filter( 'cf7_api_set_record_value', array( $this, 'cf7_api_checkbox_value_handler' ), 10, 2 );
		\add_filter( 'cf7_api_create_record', array( $this, 'cf7_api_handle_boolean_checkbox' ), 10, 5 );
		\add_filter( 'cf7_api_create_record', array( $this, 'cf7_api_final_checkbox_handler' ), 20, 1 );

		// Add admin hooks for Contact Form 7.
		if ( \is_admin() ) {
			\add_filter( 'wpcf7_editor_panels', array( $this, 'add_integrations_tab' ) );
			\add_action( 'wpcf7_save_contact_form', array( $this, 'save_contact_form_details' ) );
			\add_filter( 'wpcf7_contact_form_properties', array( $this, 'add_form_properties' ), 10, 1 );
			\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		}

		$this->initialized = true;
	}

	/**
	 * Register legacy hook aliases for backward compatibility
	 *
	 * Maps old qs_cf7_* hooks to new cf7_api_* hooks.
	 *
	 * @since 1.1.2
	 * @return void
	 */
	private function register_legacy_hooks(): void {
		// Legacy: qs_cf7_collect_mail_tags -> cf7_api_collect_mail_tags.
		\add_filter(
			'cf7_api_collect_mail_tags',
			function ( $tags ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'qs_cf7_collect_mail_tags', $tags );
			},
			5
		);
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 20; // CF7 integration.
	}

	/**
	 * Determine if integration should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return \class_exists( 'WPCF7_ContactForm' );
	}

	/**
	 * Add form properties for API integration
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $properties Form properties
	 * @return array<string, mixed> Modified properties
	 */
	public function add_form_properties( array $properties ): array {
		$properties['wpcf7_api_data']     ??= array();
		$properties['wpcf7_api_data_map'] ??= array();
		$properties['template']           ??= '';
		$properties['json_template']      ??= '';
		$properties['retry_config']       ??= array();
		$properties['custom_headers']     ??= array();

		return $properties;
	}

	/**
	 * Add integrations tab to Contact Form 7 admin
	 *
	 * @since 1.0.0
	 * @param array<string, array<string, mixed>> $panels Existing panels
	 * @return array<string, array<string, mixed>> Modified panels with API integration tab
	 */
	public function add_integrations_tab( array $panels ): array {
		$panels['cf7-api-integration'] = array(
			'title'    => \__( 'API Integration', 'contact-form-to-api' ),
			'callback' => array( $this, 'render_integration_panel' ),
		);

		return $panels;
	}

	/**
	 * Collect mail tags from form
	 *
	 * @since 1.0.0
	 * @param WPCF7_ContactForm      $form The contact form object to scan for tags
	 * @param array<string, mixed>  $args Optional arguments to filter tags by type
	 * @return array<int, array<string, string>> Array of WPCF7_FormTag objects for use in templates
	 */
	private function get_mail_tags( WPCF7_ContactForm $form, array $args ): array {
		/** @var array<array{type: string, name: string}> $tags */
		$tags = \apply_filters( 'cf7_api_collect_mail_tags', $form->scan_form_tags() );

		foreach ( (array) $tags as $tag ) {
			$type = trim( $tag['type'], '*' );
			if ( empty( $type ) || empty( $tag['name'] ) ) {
				continue;
			} elseif ( ! empty( $args['include'] ) ) {
				if ( ! \in_array( $type, $args['include'] ) ) {
					continue;
				}
			} elseif ( ! empty( $args['exclude'] ) ) {
				if ( \in_array( $type, $args['exclude'] ) ) {
					continue;
				}
			}
			$mailtags[] = $tag;
		}

		return $mailtags;
	}

	/**
	 * Render API integration panel
	 *
	 * @since 1.0.0
	 * @param WPCF7_ContactForm $post Contact form object (CF7)
	 * @return void
	 */
	public function render_integration_panel( WPCF7_ContactForm $post ): void {
		// Get form data from CF7 properties with post_meta fallback
		$wpcf7 = WPCF7_ContactForm::get_current();
		if ( null === $wpcf7 ) {
			return;
		}
		$form_id = $wpcf7->id();

		// Get from properties first, fallback to post_meta for backward compatibility
		$wpcf7_api_data               = $wpcf7->prop( 'wpcf7_api_data' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data', true );
		$wpcf7_api_data_map           = $wpcf7->prop( 'wpcf7_api_data_map' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data_map', true );
		$wpcf7_api_data_template      = $wpcf7->prop( 'template' ) ?: \get_post_meta( $form_id, '_template', true );
		$wpcf7_api_json_data_template = \stripslashes( $wpcf7->prop( 'json_template' ) ?: \get_post_meta( $form_id, '_json_template', true ) );
		$retry_config                 = $wpcf7->prop( 'retry_config' ) ?: array();
		$custom_headers               = $wpcf7->prop( 'custom_headers' ) ?: array();

		$mail_tags = $this->get_mail_tags( $post, array() );

		// Set defaults
		if ( ! \is_array( $wpcf7_api_data ) ) {
			$wpcf7_api_data = array();
		}
		$wpcf7_api_data['base_url']    ??= '';
		$wpcf7_api_data['send_to_api'] ??= '';
		$wpcf7_api_data['input_type']  ??= 'params';
		$wpcf7_api_data['method']      ??= 'GET';
		$wpcf7_api_data['debug_log']     = true;

		// Set retry configuration defaults
		if ( ! \is_array( $retry_config ) ) {
			$retry_config = array();
		}
		$retry_config['max_retries']      ??= self::DEFAULT_MAX_RETRIES;
		$retry_config['retry_delay']      ??= self::DEFAULT_RETRY_DELAY;
		$retry_config['retry_on_timeout'] ??= true;

		// Get debug information
		$debug_url    = \get_post_meta( $form_id, 'cf7_api_debug_url', true );
		$debug_result = \get_post_meta( $form_id, 'cf7_api_debug_result', true );
		$debug_params = \get_post_meta( $form_id, 'cf7_api_debug_params', true );
		$error_logs   = \get_post_meta( $form_id, 'api_errors', true );

		// Get recent logs and statistics
		$logger      = new RequestLogger();
		$recent_logs = $logger->get_recent_logs( $form_id, 5 );
		$statistics  = $logger->get_statistics( $form_id );

		// Prepare debug info array
		$debug_info = array(
			'url'    => $debug_url,
			'params' => $debug_params,
			'result' => $debug_result,
			'errors' => $error_logs,
		);

		// Render using view
		IntegrationView::render_panel(
			$post,
			$wpcf7_api_data,
			\is_array( $wpcf7_api_data_map ) ? $wpcf7_api_data_map : array(),
			$wpcf7_api_data_template ?: '',
			$wpcf7_api_json_data_template ?: '',
			$retry_config,
			$mail_tags,
			$recent_logs,
			$statistics,
			$debug_info,
			\is_array( $custom_headers ) ? $custom_headers : array()
		);
	}

	/**
	 * Save API settings when form is saved
	 *
	 * @since 1.0.0
	 * @param WPCF7_ContactForm $contact_form Contact form object (CF7)
	 * @return void
	 */
	public function save_contact_form_details( WPCF7_ContactForm $contact_form ): void {
		$form_id = $contact_form->id();

		// Use CF7's native properties method for storing form configuration
		$properties = $contact_form->get_properties();

		// Get POST data for API configuration
		$properties['wpcf7_api_data']     = $_POST['wpcf7-sf'] ?? array();
		$properties['wpcf7_api_data_map'] = $_POST['qs_wpcf7_api_map'] ?? array();
		$properties['template']           = $_POST['template'] ?? '';
		$properties['json_template']      = \stripslashes( $_POST['json_template'] ?? '' );

		// Get retry configuration
		$retry_config = $_POST['retry_config'] ?? array();
		// Convert checkbox value
		if ( isset( $retry_config['retry_on_timeout'] ) ) {
			$retry_config['retry_on_timeout'] = true;
		} else {
			$retry_config['retry_on_timeout'] = false;
		}
		// Ensure numeric values
		if ( isset( $retry_config['max_retries'] ) ) {
			$retry_config['max_retries'] = (int) $retry_config['max_retries'];
		}
		if ( isset( $retry_config['retry_delay'] ) ) {
			$retry_config['retry_delay'] = (int) $retry_config['retry_delay'];
		}
		$properties['retry_config'] = $retry_config;

		// Get custom headers and sanitize
		$raw_headers    = $_POST['custom_headers'] ?? array();
		$custom_headers = array();
		if ( \is_array( $raw_headers ) ) {
			foreach ( $raw_headers as $header ) {
				$name  = \sanitize_text_field( $header['name'] ?? '' );
				$value = \sanitize_text_field( $header['value'] ?? '' );
				// Only save non-empty headers
				if ( ! empty( $name ) ) {
					$custom_headers[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}
			}
		}
		$properties['custom_headers'] = $custom_headers;

		// Set properties using CF7's native method
		$contact_form->set_properties( $properties );
	}

	/**
	 * Send form data to API
	 *
	 * @since 1.0.0
	 * @param WPCF7_ContactForm $contact_form Contact form object (CF7)
	 * @return void
	 */
	public function send_data_to_api( WPCF7_ContactForm $contact_form ): void {
		$this->clear_error_log( $contact_form->id() );
		$this->current_form = $contact_form;

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$form_id = $contact_form->id();

		// Get from properties first, fallback to post_meta for backward compatibility
		$api_data          = $contact_form->prop( 'wpcf7_api_data' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data', true );
		$api_data_map      = $contact_form->prop( 'wpcf7_api_data_map' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data_map', true );
		$api_data_template = $contact_form->prop( 'template' ) ?: \get_post_meta( $form_id, '_template', true );
		$api_json_template = \stripslashes( $contact_form->prop( 'json_template' ) ?: \get_post_meta( $form_id, '_json_template', true ) );
		$retry_config      = $contact_form->prop( 'retry_config' ) ?: array();
		$custom_headers    = $contact_form->prop( 'custom_headers' ) ?: array();

		// Set default retry configuration if not provided
		if ( ! \is_array( $retry_config ) ) {
			$retry_config = array();
		}
		$retry_config['max_retries']      ??= self::DEFAULT_MAX_RETRIES;
		$retry_config['retry_delay']      ??= self::DEFAULT_RETRY_DELAY;
		$retry_config['retry_on_timeout'] ??= true;

		// Always enable debug logging
		$api_data['debug_log'] = true;

		// Check if form should be sent to API
		if ( empty( $api_data['send_to_api'] ) || $api_data['send_to_api'] !== 'on' ) {
			return;
		}

		$record_type = $api_data['input_type'] ?? 'params';

		if ( $record_type === 'json' ) {
			$api_data_template = \stripslashes( $api_json_template );
		}

		$record        = $this->get_record( $submission, $api_data_map, $record_type, $api_data_template );
		$record['url'] = $api_data['base_url'];

		if ( ! empty( $record['url'] ) ) {
			\do_action( 'cf7_api_before_send_to_api', $record );

			$response = $this->send_lead( $record, $api_data['debug_log'], $api_data['method'], $record_type, $retry_config, $custom_headers );

			if ( \is_wp_error( $response ) ) {
				$this->log_error( $response, $contact_form->id() );
			} else {
				\do_action( 'cf7_api_after_send_to_api', $record, $response );
			}
		}
	}

	/**
	 * Convert form data to API record format
	 *
	 * @since 1.0.0
	 * @param WPCF7_Submission       $submission Form submission (CF7 Submission object)
	 * @param array<string, mixed>  $data_map   Field mapping
	 * @param string                $type       Record type (params, xml, json)
	 * @param string                $template   Template for xml/json
	 * @return array<string, mixed> API record data
	 */
	private function get_record( WPCF7_Submission $submission, array $data_map, string $type = 'params', string $template = '' ): array {
		$submitted_data = $submission->get_posted_data();
		$record         = array();

		if ( $type === 'params' ) {
			foreach ( $data_map as $form_key => $api_form_key ) {
				if ( ! $api_form_key ) {
					continue;
				}

				if ( is_array( $api_form_key ) ) {
					// Handle checkbox arrays
					$field_value = $submitted_data[ $form_key ] ?? null;
					if ( ! is_array( $field_value ) ) {
						continue;
					}
					foreach ( $field_value as $value ) {
						if ( $value ) {
								$record['fields'][ $api_form_key[ $value ] ] = \apply_filters( 'cf7_api_set_record_value', $value, $api_form_key );
						}
					}
				} else {
					$value = $submitted_data[ $form_key ] ?? '';

					// Flatten radio button values
					if ( \is_array( $value ) ) {
						$value = reset( $value );
					}

					$record['fields'][ $api_form_key ] = \apply_filters( 'cf7_api_set_record_value', $value, $api_form_key );
				}
			}
		} elseif ( $type === 'xml' || $type === 'json' ) {
			foreach ( $data_map as $form_key => $api_form_key ) {
				if ( \is_array( $api_form_key ) ) {
					// Handle checkbox arrays
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

					// Flatten radio button values
					if ( \is_array( $value ) ) {
						$value = reset( $value );
					}

					$value    = \apply_filters( 'cf7_api_set_record_value', $value, $api_form_key );
					$template = str_replace( "[{$form_key}]", $value, $template );
				}
			}

			// Clean unchanged tags
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
	 * Send lead data to API endpoint with retry support
	 *
	 * Uses ApiClient service for HTTP requests with retry logic.
	 *
	 * @since 1.1.0
	 * @param array<string, mixed>                $record         Record data.
	 * @param bool                                $debug          Enable debug logging.
	 * @param string                              $method         HTTP method.
	 * @param string                              $record_type    Record type (params, json, xml).
	 * @param array<string, mixed>                $retry_config   Retry configuration.
	 * @param array<int, array<string, string>>   $custom_headers Custom HTTP headers.
	 * @return array<string, mixed>|WP_Error Response data or error.
	 */
	private function send_lead( array $record, bool $debug = false, string $method = 'GET', string $record_type = 'params', array $retry_config = array(), array $custom_headers = array() ) {
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
	 * @since 1.0.0
	 * @param WP_Error $wp_error WordPress error
	 * @param integer   $form_id  Form ID
	 * @return void
	 */
	private function log_error( WP_Error $wp_error, int $form_id ): void {
		$this->api_errors[] = $wp_error;
		\update_post_meta( $form_id, 'api_errors', $this->api_errors );
	}

	/**
	 * Clear error log for form
	 *
	 * @since 1.1.0
	 * @param integer $form_id Form ID
	 * @return void
	 */
	private function clear_error_log( int $form_id ): void {
		\delete_post_meta( $form_id, 'api_errors' );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page
	 * @return void
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		if ( strpos( $hook, 'wpcf7' ) === false ) {
			return;
		}

		$plugin_url = CF7_API_URL;

		\wp_enqueue_style(
			'cf7-api-admin',
			"{$plugin_url}assets/css/admin.css",
			array(),
			CF7_API_VERSION
		);

		\wp_enqueue_script(
			'cf7-api-admin',
			"{$plugin_url}assets/js/admin.js",
			array( 'jquery' ),
			CF7_API_VERSION,
			true
		);
	}

	/**
	 * Handle checkbox values for CF7 API using set_record_value filter
	 *
	 * Delegates to CheckboxHandler service for consistent processing.
	 *
	 * @since 1.0.0
	 * @param mixed $value          The field value to process.
	 * @param mixed $api_field_name The API field name.
	 * @return mixed The processed value.
	 */
	public function cf7_api_checkbox_value_handler( $value, $api_field_name ) {
		return CheckboxHandler::instance()->process_value( $value, $api_field_name );
	}

	/**
	 * Handle checkbox values for CF7 API in JSON/XML template mode
	 *
	 * Delegates to CheckboxHandler service for template processing.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $record         The record data.
	 * @param array<string, mixed> $submitted_data The submitted form data.
	 * @param array<string, mixed> $qs_cf7_data_map The field mapping configuration.
	 * @param string               $type           The template type.
	 * @param string               $template       The original template string.
	 * @return array<string, mixed> The modified record.
	 */
	public function cf7_api_handle_boolean_checkbox( array $record, array $submitted_data, array $qs_cf7_data_map, string $type, string $template ): array {
		return CheckboxHandler::instance()->process_template_record( $record, $submitted_data, $qs_cf7_data_map, $type, $template );
	}

	/**
	 * Final handler for checkbox values
	 *
	 * Delegates to CheckboxHandler service for final processing.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $record The record data.
	 * @return array<string, mixed> The modified record.
	 */
	public function cf7_api_final_checkbox_handler( array $record ): array {
		return CheckboxHandler::instance()->process_final( $record );
	}
}
