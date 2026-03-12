<?php
/**
 * Contact Form Submission Controller
 *
 * Handles WordPress hook registration and routing for Contact Form 7 integration.
 * Manages admin interface, form settings, and delegates processing to service layer.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Controller\ContactForm
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Controller\ContactForm;

use SilverAssist\ContactFormToAPI\Core\AssetHelper;
use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Service\ContactForm\SubmissionProcessor;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\View\ContactForm\IntegrationView;
use WPCF7_ContactForm;

\defined( 'ABSPATH' ) || exit;

/**
 * Class SubmissionController
 *
 * Controller for Contact Form 7 integration.
 * Handles hook registration, admin UI, and routes submissions to processor.
 *
 * Responsibilities:
 * - Register WordPress hooks and filters
 * - Manage admin interface (tabs, assets)
 * - Handle form settings persistence
 * - Route submissions to SubmissionProcessor
 * - Provide backward compatibility via legacy hooks
 *
 * @since 2.0.0
 */
class SubmissionController implements LoadableInterface {

	/**
	 * Maximum number of retry attempts.
	 *
	 * @since 2.0.0
	 */
	private const DEFAULT_MAX_RETRIES = 3;

	/**
	 * Default delay between retries in seconds.
	 *
	 * @since 2.0.0
	 */
	private const DEFAULT_RETRY_DELAY = 2;

	/**
	 * Singleton instance
	 *
	 * @var SubmissionController|null
	 */
	private static ?SubmissionController $instance = null;

	/**
	 * Submission processor service
	 *
	 * @var SubmissionProcessor
	 */
	private SubmissionProcessor $processor;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @since 2.0.0
	 * @return SubmissionController
	 */
	public static function instance(): SubmissionController {
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
	 * Initialize Contact Form 7 integration
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		// Initialize processor service via singleton.
		$this->processor = SubmissionProcessor::instance();
		$this->processor->init();

		// Register legacy hook aliases for backward compatibility.
		$this->register_legacy_hooks();

		// Hook into Contact Form 7 submission process.
		\add_action( 'wpcf7_before_send_mail', array( $this, 'handle_form_submission' ) );

		// Add checkbox value handling filters.
		\add_filter( 'cf7_api_set_record_value', array( $this, 'handle_checkbox_value' ), 10, 2 );
		\add_filter( 'cf7_api_create_record', array( $this, 'handle_boolean_checkbox' ), 10, 5 );
		\add_filter( 'cf7_api_create_record', array( $this, 'handle_final_checkbox' ), 20, 1 );

		// Add admin hooks for Contact Form 7.
		if ( \is_admin() ) {
			\add_filter( 'wpcf7_editor_panels', array( $this, 'add_integrations_tab' ) );
			\add_action( 'wpcf7_save_contact_form', array( $this, 'save_form_settings' ) );
			\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}

		// Register properties filter to ensure properties are in CF7 defaults before filtering.
		\add_filter( 'wpcf7_pre_construct_contact_form_properties', array( $this, 'add_form_properties' ), 10, 1 );

		$this->initialized = true;
	}

	/**
	 * Register legacy hook aliases for backward compatibility
	 *
	 * Maps old qs_cf7_* hooks to new cf7_api_* hooks.
	 * This allows themes and plugins using the legacy Query Solutions plugin hooks
	 * to continue working with this plugin without code changes.
	 *
	 * CENTRALIZED: All legacy hooks are registered here (previously split between
	 * Integration.php and ApiClient.php).
	 *
	 * Legacy Hook Mappings:
	 * - qs_cf7_collect_mail_tags     -> cf7_api_collect_mail_tags
	 * - qs_cf7_api_before_sent_to_api -> cf7_api_before_send_to_api (note: "sent" vs "send")
	 * - qs_cf7_api_after_sent_to_api  -> cf7_api_after_send_to_api
	 * - set_record_value             -> cf7_api_set_record_value
	 * - cf7api_create_record         -> cf7_api_create_record
	 * - qs_cf7_api_get_args          -> cf7_api_get_args / cf7_api_post_args
	 * - qs_cf7_api_get_url           -> cf7_api_get_url
	 * - qs_cf7_api_post_url          -> cf7_api_post_url
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_legacy_hooks(): void {
		// =====================================================================
		// Form Processing Hooks (from Integration)
		// =====================================================================

		// Legacy: qs_cf7_collect_mail_tags -> cf7_api_collect_mail_tags.
		\add_filter(
			'cf7_api_collect_mail_tags',
			function ( $tags ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'qs_cf7_collect_mail_tags', $tags );
			},
			5
		);

		// Legacy: qs_cf7_api_before_sent_to_api -> cf7_api_before_send_to_api.
		// Note: The legacy hook used "sent" (past tense), new hook uses "send" (present).
		\add_action(
			'cf7_api_before_send_to_api',
			function ( $record ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				\do_action( 'qs_cf7_api_before_sent_to_api', $record );
			},
			5
		);

		// Legacy: qs_cf7_api_after_sent_to_api -> cf7_api_after_send_to_api.
		\add_action(
			'cf7_api_after_send_to_api',
			function ( $record, $response ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				\do_action( 'qs_cf7_api_after_sent_to_api', $record, $response );
			},
			5,
			2
		);

		// Legacy: set_record_value -> cf7_api_set_record_value.
		\add_filter(
			'cf7_api_set_record_value',
			function ( $value, $api_field_name ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'set_record_value', $value, $api_field_name );
			},
			5,
			2
		);

		// Legacy: cf7api_create_record -> cf7_api_create_record.
		\add_filter(
			'cf7_api_create_record',
			function ( $record, $submitted_data, $data_map, $type, $template ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'cf7api_create_record', $record, $submitted_data, $data_map, $type, $template );
			},
			5,
			5
		);

		// =====================================================================
		// API Client Hooks (moved from ApiClient.php for centralization)
		// =====================================================================

		// Legacy: qs_cf7_api_get_args -> cf7_api_get_args.
		// Priority 5 so it runs before default (10), applying legacy hooks first.
		\add_filter(
			'cf7_api_get_args',
			function ( $args ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'qs_cf7_api_get_args', $args );
			},
			5
		);

		// Legacy: qs_cf7_api_get_args -> cf7_api_post_args.
		// The original plugin used qs_cf7_api_get_args for both GET and POST methods.
		\add_filter(
			'cf7_api_post_args',
			function ( $args ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'qs_cf7_api_get_args', $args );
			},
			5
		);

		// Legacy: qs_cf7_api_get_url -> cf7_api_get_url.
		\add_filter(
			'cf7_api_get_url',
			function ( $url, $record ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'qs_cf7_api_get_url', $url, $record );
			},
			5,
			2
		);

		// Legacy: qs_cf7_api_post_url -> cf7_api_post_url.
		\add_filter(
			'cf7_api_post_url',
			function ( $url ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook for backward compatibility.
				return \apply_filters( 'qs_cf7_api_post_url', $url );
			},
			5
		);
	}

	/**
	 * Get loading priority
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function get_priority(): int {
		return 30; // Controller priority (admin/UI layer).
	}

	/**
	 * Determine if controller should load
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function should_load(): bool {
		return \class_exists( 'WPCF7_ContactForm' );
	}

	/**
	 * Add form properties for API integration
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $properties Form properties.
	 * @return array<string, mixed> Modified properties.
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
	 * @since 2.0.0
	 * @param array<string, array<string, mixed>> $panels Existing panels.
	 * @return array<string, array<string, mixed>> Modified panels with API integration tab.
	 */
	public function add_integrations_tab( array $panels ): array {
		$panels['cf7-api-integration'] = array(
			'title'    => \__( 'API Integration', 'contact-form-to-api' ),
			'callback' => array( $this, 'render_integration_panel' ),
		);

		return $panels;
	}

	/**
	 * Render API integration panel
	 *
	 * @since 2.0.0
	 * @param WPCF7_ContactForm $post Contact form object (CF7).
	 * @return void
	 */
	public function render_integration_panel( WPCF7_ContactForm $post ): void {
		// Get form data from CF7 properties with post_meta fallback.
		$wpcf7 = WPCF7_ContactForm::get_current();
		if ( null === $wpcf7 ) {
			return;
		}
		$form_id = $wpcf7->id();

		// Get from properties first, fallback to post_meta for backward compatibility.
		$wpcf7_api_data               = $wpcf7->prop( 'wpcf7_api_data' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data', true );
		$wpcf7_api_data_map           = $wpcf7->prop( 'wpcf7_api_data_map' ) ?: \get_post_meta( $form_id, '_wpcf7_api_data_map', true );
		$wpcf7_api_data_template      = $wpcf7->prop( 'template' ) ?: \get_post_meta( $form_id, '_template', true );
		$wpcf7_api_json_data_template = \stripslashes( $wpcf7->prop( 'json_template' ) ?: \get_post_meta( $form_id, '_json_template', true ) );
		$retry_config                 = $wpcf7->prop( 'retry_config' ) ?: array();
		$custom_headers               = $wpcf7->prop( 'custom_headers' ) ?: array();

		$mail_tags = $this->get_mail_tags( $post, array() );

		// Set defaults.
		if ( ! \is_array( $wpcf7_api_data ) ) {
			$wpcf7_api_data = array();
		}
		$wpcf7_api_data['base_url']    ??= '';
		$wpcf7_api_data['send_to_api'] ??= '';
		$wpcf7_api_data['input_type']  ??= 'params';
		$wpcf7_api_data['method']      ??= 'GET';
		$wpcf7_api_data['debug_log']     = true;

		// Set retry configuration defaults.
		if ( ! \is_array( $retry_config ) ) {
			$retry_config = array();
		}
		$retry_config['max_retries']      ??= self::DEFAULT_MAX_RETRIES;
		$retry_config['retry_delay']      ??= self::DEFAULT_RETRY_DELAY;
		$retry_config['retry_on_timeout'] ??= true;

		// Get debug information.
		$debug_url    = \get_post_meta( $form_id, 'cf7_api_debug_url', true );
		$debug_result = \get_post_meta( $form_id, 'cf7_api_debug_result', true );
		$debug_params = \get_post_meta( $form_id, 'cf7_api_debug_params', true );
		$error_logs   = \get_post_meta( $form_id, 'api_errors', true );

		// Get recent logs and statistics using new logging services.
		$log_reader  = new LogReader();
		$log_stats   = new LogStatistics();
		$recent_logs = $log_reader->get_recent_logs( $form_id, 5 );
		$statistics  = $log_stats->get_statistics( $form_id );

		// Prepare debug info array.
		$debug_info = array(
			'url'    => $debug_url,
			'params' => $debug_params,
			'result' => $debug_result,
			'errors' => $error_logs,
		);

		// Render using view.
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
	 * Called by Contact Form 7's wpcf7_save_contact_form hook.
	 * CF7 handles nonce verification before this hook is triggered.
	 *
	 * @since 2.0.0
	 * @param WPCF7_ContactForm $contact_form Contact form object (CF7).
	 * @return void
	 */
	public function save_form_settings( WPCF7_ContactForm $contact_form ): void {
		// Use CF7's native properties method for storing form configuration.
		$properties = $contact_form->get_properties();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- CF7 handles nonce verification before calling this hook.
		// Get POST data for API configuration.
		$properties['wpcf7_api_data']     = $_POST['wpcf7-sf'] ?? array();
		$properties['wpcf7_api_data_map'] = $_POST['qs_wpcf7_api_map'] ?? array();
		$properties['template']           = $_POST['template'] ?? '';
		$properties['json_template']      = \stripslashes( $_POST['json_template'] ?? '' );

		// Get retry configuration.
		$retry_config = $_POST['retry_config'] ?? array();
		// Convert checkbox value.
		if ( isset( $retry_config['retry_on_timeout'] ) ) {
			$retry_config['retry_on_timeout'] = true;
		} else {
			$retry_config['retry_on_timeout'] = false;
		}
		// Ensure numeric values.
		if ( isset( $retry_config['max_retries'] ) ) {
			$retry_config['max_retries'] = (int) $retry_config['max_retries'];
		}
		if ( isset( $retry_config['retry_delay'] ) ) {
			$retry_config['retry_delay'] = (int) $retry_config['retry_delay'];
		}
		$properties['retry_config'] = $retry_config;

		// Get custom headers and sanitize.
		$raw_headers = $_POST['custom_headers'] ?? array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$custom_headers = array();
		if ( \is_array( $raw_headers ) ) {
			foreach ( $raw_headers as $header ) {
				$name  = \sanitize_text_field( $header['name'] ?? '' );
				$value = \sanitize_text_field( $header['value'] ?? '' );
				// Only save non-empty headers.
				if ( ! empty( $name ) ) {
					$custom_headers[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}
			}
		}
		$properties['custom_headers'] = $custom_headers;

		// Set properties using CF7's native method.
		$contact_form->set_properties( $properties );
	}

	/**
	 * Handle form submission
	 *
	 * Routes submission to processor service.
	 *
	 * @since 2.0.0
	 * @param WPCF7_ContactForm $contact_form Contact form object (CF7).
	 * @return void
	 */
	public function handle_form_submission( WPCF7_ContactForm $contact_form ): void {
		$this->processor->process_submission( $contact_form );
	}

	/**
	 * Handle checkbox values for CF7 API using set_record_value filter
	 *
	 * Routes to processor for processing.
	 *
	 * @since 2.0.0
	 * @param mixed $value          The field value to process.
	 * @param mixed $api_field_name The API field name.
	 * @return mixed The processed value.
	 */
	public function handle_checkbox_value( $value, $api_field_name ) {
		return $this->processor->handle_checkbox_value( $value, $api_field_name );
	}

	/**
	 * Handle checkbox values for CF7 API in JSON/XML template mode
	 *
	 * Routes to processor for template processing.
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
		return $this->processor->handle_boolean_checkbox( $record, $submitted_data, $qs_cf7_data_map, $type, $template );
	}

	/**
	 * Final handler for checkbox values
	 *
	 * Routes to processor for final processing.
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $record The record data.
	 * @return array<string, mixed> The modified record.
	 */
	public function handle_final_checkbox( array $record ): array {
		return $this->processor->handle_final_checkbox( $record );
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @since 2.0.0
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( \strpos( $hook, 'wpcf7' ) === false ) {
			return;
		}

		\wp_enqueue_style(
			'cf7-api-admin',
			AssetHelper::get_url( 'assets/css/admin.css' ),
			array( 'cf7-api-variables' ),
			CF7_API_VERSION
		);

		\wp_enqueue_script(
			'cf7-api-admin',
			AssetHelper::get_url( 'assets/js/admin.js' ),
			array( 'jquery' ),
			CF7_API_VERSION,
			true
		);
	}

	/**
	 * Collect mail tags from form
	 *
	 * @since 2.0.0
	 * @param WPCF7_ContactForm      $form The contact form object to scan for tags.
	 * @param array<string, mixed>  $args Optional arguments to filter tags by type.
	 * @return array<int, array<string, string>> Array of WPCF7_FormTag objects for use in templates.
	 */
	private function get_mail_tags( WPCF7_ContactForm $form, array $args ): array {
		/** @var array<array{type: string, name: string}> $tags */
		$tags = \apply_filters( 'cf7_api_collect_mail_tags', $form->scan_form_tags() );

		$mailtags = array();
		foreach ( (array) $tags as $tag ) {
			$type = trim( $tag['type'], '*' );
			if ( empty( $type ) || empty( $tag['name'] ) ) {
				continue;
			} elseif ( ! empty( $args['include'] ) ) {
				if ( ! \in_array( $type, $args['include'], true ) ) {
					continue;
				}
			} elseif ( ! empty( $args['exclude'] ) ) {
				if ( \in_array( $type, $args['exclude'], true ) ) {
					continue;
				}
			}
			$mailtags[] = $tag;
		}

		return $mailtags;
	}
}
