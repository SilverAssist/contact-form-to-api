<?php
/**
 * API Client Service
 *
 * Handles HTTP requests to external APIs with retry logic,
 * timeout handling, and logging integration.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Services
 * @since 1.1.0
 * @version 1.2.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Services;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Core\RequestLogger;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class ApiClient
 *
 * HTTP client for making API requests with retry logic and logging.
 */
class ApiClient implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var ApiClient|null
	 */
	private static ?ApiClient $instance = null;

	/**
	 * Whether the component has been initialized
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Default maximum retry attempts
	 *
	 * @var int
	 */
	public const DEFAULT_MAX_RETRIES = 2;

	/**
	 * Default retry delay in seconds
	 *
	 * @var int
	 */
	public const DEFAULT_RETRY_DELAY = 1;

	/**
	 * Retry delay multiplier for exponential backoff
	 *
	 * @var float
	 */
	public const RETRY_MULTIPLIER = 1.5;

	/**
	 * Default request timeout in seconds
	 *
	 * @var int
	 */
	public const DEFAULT_TIMEOUT = 30;

	/**
	 * Get singleton instance
	 *
	 * @return ApiClient
	 */
	public static function instance(): ApiClient {
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

		// Register legacy hook aliases for backward compatibility.
		$this->register_legacy_hooks();

		$this->initialized = true;
	}

	/**
	 * Register legacy hook aliases for backward compatibility
	 *
	 * Maps old qs_cf7_api_* hooks to new cf7_api_* hooks.
	 * Uses priority 5 to run BEFORE user hooks (priority 10), allowing
	 * user code that hooks into qs_cf7_api_* to still work.
	 *
	 * @since 1.1.2
	 * @return void
	 */
	private function register_legacy_hooks(): void {
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

		// Legacy: qs_cf7_api_post_args (new) with fallback to qs_cf7_api_get_args.
		// The original plugin used qs_cf7_api_get_args for both GET and POST.
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
	 * Send HTTP request with retry logic
	 *
	 * @param array<string, mixed> $request_config Request configuration.
	 * @return array<string, mixed>|WP_Error Response data or error.
	 */
	public function send( array $request_config ) {
		// Ensure hooks are registered before sending.
		if ( ! $this->initialized ) {
			$this->init();
		}

		$url          = $request_config['url'] ?? '';
		$method       = strtoupper( $request_config['method'] ?? 'GET' );
		$body         = $request_config['body'] ?? null;
		$headers      = $request_config['headers'] ?? array();
		$content_type = $request_config['content_type'] ?? 'params';
		$form_id      = $request_config['form_id'] ?? 0;
		$retry_config = $request_config['retry_config'] ?? array();
		$retry_of     = $request_config['retry_of'] ?? null;

		// Build request arguments.
		$args = $this->build_request_args( $method, $body, $headers, $content_type );
		if ( \is_wp_error( $args ) ) {
			return $args;
		}

		// Build URL with query params if needed.
		$url = $this->build_url( $url, $body, $method, $content_type );

		// Start logging.
		$logger = $this->get_api_logger();
		$log_id = false;
		if ( $logger && $form_id > 0 ) {
			$log_id = $logger->start_request(
				$form_id,
				$url,
				$method,
				$args['body'] ?? '',
				$args['headers'] ?? array(),
				$retry_of
			);
		}

		// Execute request with retries.
		$result      = $this->execute_with_retries( $url, $method, $args, $retry_config, $logger, $log_id );
		$retry_count = $result['retry_count'];
		$response    = $result['response'];

		// Complete logging.
		if ( $log_id && $logger ) {
			$logger->complete_request( $response, $retry_count );
		}

		return $response;
	}

	/**
	 * Retry request from log entry
	 *
	 * Replays a failed API request from log history.
	 * Creates a new log entry linked to the original via retry_of.
	 *
	 * @since 1.2.0
	 * @param int $log_id Original log entry ID to retry
	 * @return array<string, mixed> Result with success status and details
	 */
	public function retry_from_log( int $log_id ): array {
		$logger = $this->get_api_logger();

		if ( ! $logger ) {
			return array(
				'success' => false,
				'error'   => \__( 'Logger not available', 'contact-form-to-api' ),
			);
		}

		$request_data = $logger->get_request_for_retry( $log_id );

		if ( ! $request_data ) {
			return array(
				'success' => false,
				'error'   => \__( 'Invalid or non-retryable log entry', 'contact-form-to-api' ),
			);
		}

		// Build request configuration with retry_of set
		$config = array(
			'url'      => $request_data['url'],
			'method'   => $request_data['method'],
			'body'     => $request_data['body'],
			'headers'  => $request_data['headers'],
			'form_id'  => $request_data['form_id'],
			'retry_of' => $request_data['original_log_id'],
		);

		// Determine content type based on Content-Type header
		// Determine content type based on Content-Type header (case-insensitive lookup per RFC 7230)
		$content_type        = 'params';
		$content_type_header = null;

		if ( ! empty( $request_data['headers'] ) && \is_array( $request_data['headers'] ) ) {
			foreach ( $request_data['headers'] as $header_name => $header_value ) {
				if ( \strtolower( (string) $header_name ) === 'content-type' ) {
					$content_type_header = $header_value;
					break;
				}
			}
		}

		if ( null !== $content_type_header ) {
			$ct = (string) $content_type_header;
			if ( \stripos( $ct, 'application/json' ) !== false ) {
				$content_type = 'json';
			} elseif ( \stripos( $ct, 'text/xml' ) !== false || \stripos( $ct, 'application/xml' ) !== false ) {
				$content_type = 'xml';
			}
		}

		$config['content_type'] = $content_type;

		// Send the retry request
		$response = $this->send( $config );

		// Determine success based on response
		if ( \is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'log_id'  => null,
			);
		}

		$response_code = \wp_remote_retrieve_response_code( $response );
		$is_success    = $response_code >= 200 && $response_code < 300;

		return array(
			'success'       => $is_success,
			'response_code' => $response_code,
			'retry_of'      => $log_id,
			'log_id'        => null, // Note: log_id is private in RequestLogger, use get_last_log_id() when available
		);
	}

	/**
	 * Build request arguments
	 *
	 * @param string               $method       HTTP method.
	 * @param mixed                $body         Request body.
	 * @param array<string, mixed> $headers      Request headers.
	 * @param string               $content_type Content type (params, json, xml).
	 * @return array<string, mixed>|WP_Error Request args or error.
	 */
	private function build_request_args( string $method, $body, array $headers, string $content_type ) {
		global $wp_version;

		$args = array(
			'timeout'     => self::DEFAULT_TIMEOUT,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent'  => "WordPress/{$wp_version}; " . \home_url(),
			'blocking'    => true,
			'headers'     => $headers,
			'cookies'     => array(),
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			'filename'    => null,
		);

		// Handle body based on method and content type.
		if ( $method !== 'GET' || $content_type === 'json' ) {
			$processed_body = $this->process_body( $body, $content_type );
			if ( \is_wp_error( $processed_body ) ) {
				return $processed_body;
			}

			$args['body'] = $processed_body['body'];
			if ( ! empty( $processed_body['content_type'] ) ) {
				$args['headers']['Content-Type'] = $processed_body['content_type'];
			}
		}

		// Apply filters for customization.
		if ( $method === 'GET' ) {
			$args = \apply_filters( 'cf7_api_get_args', $args );
		} else {
			$args = \apply_filters( 'cf7_api_post_args', $args );
		}

		return $args;
	}

	/**
	 * Process request body based on content type
	 *
	 * @param mixed  $body         Request body.
	 * @param string $content_type Content type (params, json, xml).
	 * @return array<string, mixed>|WP_Error Processed body data or error.
	 */
	private function process_body( $body, string $content_type ) {
		$result = array(
			'body'         => $body,
			'content_type' => '',
		);

		switch ( $content_type ) {
			case 'json':
				$result['content_type'] = 'application/json';
				if ( is_string( $body ) ) {
					$json = $this->parse_json( $body );
					if ( \is_wp_error( $json ) ) {
						return $json;
					}
					$result['body'] = $json;
				} else {
					$result['body'] = \wp_json_encode( $body );
				}
				break;

			case 'xml':
				$result['content_type'] = 'text/xml';
				if ( is_string( $body ) ) {
					$xml = $this->parse_xml( $body );
					if ( \is_wp_error( $xml ) ) {
						return $xml;
					}
					$result['body'] = $xml->asXML();
				}
				break;

			default:
				// params - keep as is for POST, will be query string for GET.
				break;
		}

		return $result;
	}

	/**
	 * Build URL with query parameters if needed
	 *
	 * @param string $url          Base URL.
	 * @param mixed  $body         Request body.
	 * @param string $method       HTTP method.
	 * @param string $content_type Content type.
	 * @return string Final URL.
	 */
	private function build_url( string $url, $body, string $method, string $content_type ): string {
		// Add query params for GET with params content type.
		if ( $method === 'GET' && $content_type === 'params' && is_array( $body ) ) {
			$query_string = \http_build_query( $body );
			$url          = strpos( $url, '?' ) !== false
				? "{$url}&{$query_string}"
				: "{$url}?{$query_string}";
		}

		// Apply URL filter.
		if ( $method === 'GET' ) {
			$url = \apply_filters(
				'cf7_api_get_url',
				$url,
				array(
					'fields' => $body,
					'url'    => $url,
				)
			);
		} else {
			$url = \apply_filters( 'cf7_api_post_url', $url );
		}

		return $url;
	}

	/**
	 * Execute request with retry logic
	 *
	 * @param string                      $url          Request URL.
	 * @param string                      $method       HTTP method.
	 * @param array<string, mixed>        $args         Request arguments.
	 * @param array<string, mixed>        $retry_config Retry configuration.
	 * @param RequestLogger|null          $logger       API logger instance.
	 * @param int|false                   $log_id       Log entry ID.
	 * @return array<string, mixed> Response with retry count.
	 */
	private function execute_with_retries( string $url, string $method, array $args, array $retry_config, ?RequestLogger $logger, $log_id ): array {
		$max_retries      = $retry_config['max_retries'] ?? self::DEFAULT_MAX_RETRIES;
		$retry_delay      = $retry_config['retry_delay'] ?? self::DEFAULT_RETRY_DELAY;
		$retry_on_timeout = $retry_config['retry_on_timeout'] ?? true;

		$retry_count = 0;
		$response    = null;

		for ( $attempt = 0; $attempt <= $max_retries; $attempt++ ) {
			// Make the request.
			$response = $this->make_request( $url, $method, $args );

			// Check if successful.
			if ( ! \is_wp_error( $response ) ) {
				$response_code = \wp_remote_retrieve_response_code( $response );

				// Success (2xx).
				if ( $response_code >= 200 && $response_code < 300 ) {
					break;
				}

				// Server errors (5xx) might be transient.
				if ( $response_code >= 500 && $attempt < $max_retries ) {
					++$retry_count;
					$this->log_retry( $logger, $log_id, $retry_count );
					$this->wait_with_backoff( $retry_delay, $attempt );
					continue;
				}

				// Client errors (4xx) should not be retried.
				break;
			}

			// Handle WP_Error with retry logic.
			if ( $retry_on_timeout && $attempt < $max_retries ) {
				$error_code = $response->get_error_code();

				// Retry on timeout and connection errors.
				$retryable_errors = array( 'http_request_failed', 'timeout', 'connect_timeout' );
				if ( in_array( $error_code, $retryable_errors, true ) ) {
					++$retry_count;
					$this->log_retry( $logger, $log_id, $retry_count );
					$this->wait_with_backoff( $retry_delay, $attempt );
					continue;
				}
			}

			// Non-retryable error.
			break;
		}

		return array(
			'response'    => $response,
			'retry_count' => $retry_count,
		);
	}

	/**
	 * Make HTTP request
	 *
	 * @param string               $url    Request URL.
	 * @param string               $method HTTP method.
	 * @param array<string, mixed> $args   Request arguments.
	 * @return array<string, mixed>|WP_Error Response or error.
	 */
	private function make_request( string $url, string $method, array $args ) {
		if ( $method === 'GET' ) {
			return \wp_remote_get( $url, $args );
		}
		return \wp_remote_post( $url, $args );
	}

	/**
	 * Log retry attempt
	 *
	 * @param RequestLogger|null $logger      API logger instance.
	 * @param int|false          $log_id      Log entry ID.
	 * @param int                $retry_count Retry count.
	 * @return void
	 */
	private function log_retry( ?RequestLogger $logger, $log_id, int $retry_count ): void {
		if ( $log_id && $logger ) {
			$logger->log_retry( $retry_count );
		}

		// Also log to plugin logger.
		if ( \class_exists( DebugLogger::class ) ) {
			DebugLogger::instance()->info(
				'API request retry',
				array(
					'retry_count' => $retry_count,
					'log_id'      => $log_id,
				)
			);
		}
	}

	/**
	 * Wait with exponential backoff
	 *
	 * @param int $base_delay Base delay in seconds.
	 * @param int $attempt    Current attempt number.
	 * @return void
	 */
	private function wait_with_backoff( int $base_delay, int $attempt ): void {
		$delay = (int) ( $base_delay * pow( self::RETRY_MULTIPLIER, $attempt ) );
		\sleep( $delay );
	}

	/**
	 * Parse JSON string
	 *
	 * @param string $json_string JSON string.
	 * @return string|WP_Error Parsed JSON or error.
	 */
	public function parse_json( string $json_string ) {
		$json = json_decode( $json_string );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			$encoded = wp_json_encode( $json );
			return $encoded !== false ? $encoded : '';
		}

		return new WP_Error( 'json-error', 'Invalid JSON: ' . json_last_error_msg() );
	}

	/**
	 * Parse XML string
	 *
	 * @param string $xml_string XML string.
	 * @return \SimpleXMLElement|WP_Error Parsed XML or error.
	 */
	public function parse_xml( string $xml_string ) {
		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new WP_Error( 'xml-error', \__( 'XML functions not available', 'contact-form-to-api' ) );
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_string );

		if ( $xml === false ) {
			return new WP_Error( 'xml-error', \__( 'XML Structure is incorrect', 'contact-form-to-api' ) );
		}

		return $xml;
	}

	/**
	 * Get API Logger instance
	 *
	 * @return RequestLogger|null
	 */
	private function get_api_logger(): ?RequestLogger {
		if ( \class_exists( RequestLogger::class ) ) {
			return new RequestLogger();
		}
		return null;
	}
}
