<?php
/**
 * Loadable Interface
 *
 * Defines the contract for loadable plugin components following SilverAssist architecture pattern.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core\Interfaces
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core\Interfaces;

\defined( 'ABSPATH' ) || exit;

/**
 * Interface LoadableInterface
 *
 * All plugin components must implement this interface to ensure consistent
 * initialization patterns and priority-based loading.
 */
interface LoadableInterface {
	/**
	 * Initialize the component
	 *
	 * This method is called during plugin initialization and should handle
	 * all component setup including hooks, filters, and dependencies.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get the component loading priority
	 *
	 * Lower numbers indicate higher priority (loaded earlier).
	 * Recommended priorities:
	 * - Core components: 10
	 * - Services: 20
	 * - Admin components: 30
	 * - Utils: 40
	 *
	 * @return int Loading priority (lower = higher priority)
	 */
	public function get_priority(): int;

	/**
	 * Determine if the component should be loaded
	 *
	 * This method allows conditional loading based on current context,
	 * user capabilities, plugin dependencies, or other factors.
	 *
	 * @return bool True if component should be loaded, false otherwise
	 */
	public function should_load(): bool;
}
