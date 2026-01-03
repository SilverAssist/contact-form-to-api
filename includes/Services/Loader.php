<?php
/**
 * Services Loader
 *
 * Loads and initializes all Services components including ApiClient and CheckboxHandler.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Services
 * @since 1.1.0
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Services;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Class Loader
 *
 * Manages loading of Services components.
 */
class Loader implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var Loader|null
	 */
	private static ?Loader $instance = null;

	/**
	 * Whether the component has been initialized
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Loaded services
	 *
	 * @var LoadableInterface[]
	 */
	private array $services = array();

	/**
	 * Get singleton instance
	 *
	 * @return Loader
	 */
	public static function instance(): Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Initialize the loader
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		$this->init_services();
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
	 * Determine if loader should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true;
	}

	/**
	 * Initialize all services
	 *
	 * @return void
	 */
	private function init_services(): void {
		$service_classes = array(
			ApiClient::class,
			CheckboxHandler::class,
			ExportService::class,
		);

		foreach ( $service_classes as $service_class ) {
			if ( ! \class_exists( $service_class ) ) {
				continue;
			}

			try {
				$service = $service_class::instance();
				if ( $service->should_load() ) {
					$service->init();
					$this->services[] = $service;
				}
			} catch ( \Exception $e ) {
				$this->log_error( $service_class, $e->getMessage() );
			}
		}
	}

	/**
	 * Log service loading error
	 *
	 * @param string $service_class Service class name.
	 * @param string $message       Error message.
	 * @return void
	 */
	private function log_error( string $service_class, string $message ): void {
		if ( \class_exists( DebugLogger::class ) ) {
			DebugLogger::instance()->error(
				"Failed to load service: {$service_class}",
				array( 'message' => $message )
			);
		}
	}

	/**
	 * Get loaded services
	 *
	 * @return LoadableInterface[]
	 */
	public function get_services(): array {
		return $this->services;
	}
}
