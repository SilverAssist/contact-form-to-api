<?php
/**
 * Debug Logger
 *
 * Provides PSR-3 compliant logging functionality for the Contact Form to API plugin.
 * Supports multiple log levels and configurable output destinations with file rotation.
 *
 * This logger is for general plugin debugging, distinct from Core\RequestLogger which
 * handles API request/response logging to the database.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Utils
 * @since 1.1.0
 * @version 1.2.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Utils;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class DebugLogger
 *
 * Simple PSR-3 compliant logging system for plugin debugging and monitoring.
 */
class DebugLogger implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var DebugLogger|null
	 */
	private static ?DebugLogger $instance = null;

	/**
	 * Whether the component has been initialized
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Log levels (PSR-3 compliant)
	 */
	public const EMERGENCY = 'emergency';
	public const ALERT     = 'alert';
	public const CRITICAL  = 'critical';
	public const ERROR     = 'error';
	public const WARNING   = 'warning';
	public const NOTICE    = 'notice';
	public const INFO      = 'info';
	public const DEBUG     = 'debug';

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private string $log_file;

	/**
	 * Maximum log file size in bytes (5MB)
	 *
	 * @var int
	 */
	private const MAX_FILE_SIZE = 5242880;

	/**
	 * Get singleton instance
	 *
	 * @return DebugLogger
	 */
	public static function instance(): DebugLogger {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$upload_dir = \wp_upload_dir();

		$this->log_file = $upload_dir['basedir'] . '/cf7-to-api-debug.log';
	}

	/**
	 * Initialize the logger
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Create log directory if it doesn't exist.
		$log_dir = dirname( $this->log_file );
		if ( ! file_exists( $log_dir ) ) {
			\wp_mkdir_p( $log_dir );
		}

		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 40; // Utils load last.
	}

	/**
	 * Determine if logger should load
	 *
	 * Logger always loads to handle error logging,
	 * but verbose logging respects WP_DEBUG setting.
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true;
	}

	/**
	 * Log a message with context (PSR-3 interface)
	 *
	 * @param string               $level   Log level.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function log( string $level, string $message, array $context = array() ): void {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$log_entry = $this->format_log_entry( $level, $message, $context );
		$this->write_to_file( $log_entry );
	}

	/**
	 * Log emergency message
	 *
	 * System is unusable.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void {
		$this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Log alert message
	 *
	 * Action must be taken immediately.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void {
		$this->log( self::ALERT, $message, $context );
	}

	/**
	 * Log critical message
	 *
	 * Critical conditions.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void {
		$this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Log error message
	 *
	 * Runtime errors that do not require immediate action.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Log warning message
	 *
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Log notice message
	 *
	 * Normal but significant events.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void {
		$this->log( self::NOTICE, $message, $context );
	}

	/**
	 * Log info message
	 *
	 * Interesting events.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Log debug message
	 *
	 * Detailed debug information.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Check if should log based on level and WP_DEBUG setting
	 *
	 * @param string $level Log level.
	 * @return bool
	 */
	private function should_log( string $level ): bool {
		// Always log errors and critical messages.
		$critical_levels = array( self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR );
		if ( in_array( $level, $critical_levels, true ) ) {
			return true;
		}

		// Log other levels only if WP_DEBUG is enabled.
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Format log entry
	 *
	 * @param string               $level   Log level.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return string
	 */
	private function format_log_entry( string $level, string $message, array $context ): string {
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$formatted = sprintf( '[%s] [%s] %s', $timestamp, strtoupper( $level ), $message );

		if ( ! empty( $context ) ) {
			$formatted .= ' | Context: ' . \wp_json_encode( $context, JSON_UNESCAPED_SLASHES );
		}

		return $formatted . PHP_EOL;
	}

	/**
	 * Write log entry to file
	 *
	 * @param string $log_entry Formatted log entry.
	 * @return void
	 */
	private function write_to_file( string $log_entry ): void {
		// Check if file exists and is writable.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Direct check required.
		if ( file_exists( $this->log_file ) && ! is_writable( $this->log_file ) ) {
			return;
		}

		// Check file size (rotate if too large - 5MB limit).
		if ( file_exists( $this->log_file ) && filesize( $this->log_file ) > self::MAX_FILE_SIZE ) {
			$this->rotate_log_file();
		}

		// Write to file.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Direct file operation required.
		@file_put_contents( $this->log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Rotate log file when it gets too large
	 *
	 * @return void
	 */
	private function rotate_log_file(): void {
		if ( ! file_exists( $this->log_file ) ) {
			return;
		}

		$backup_file = $this->log_file . '.old';

		// Remove old backup if exists.
		if ( file_exists( $backup_file ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Direct file operation required.
			@unlink( $backup_file );
		}

		// Move current log to backup.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Direct file operation required.
		@rename( $this->log_file, $backup_file );
	}

	/**
	 * Get log file path
	 *
	 * @return string
	 */
	public function get_log_file(): string {
		return $this->log_file;
	}

	/**
	 * Clear log file
	 *
	 * @return bool
	 */
	public function clear_log(): bool {
		if ( file_exists( $this->log_file ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Direct file operation required.
			return @unlink( $this->log_file );
		}
		return true;
	}

	/**
	 * Get log contents
	 *
	 * @param int $lines Number of lines to return from the end.
	 * @return string
	 */
	public function get_log_contents( int $lines = 100 ): string {
		if ( ! file_exists( $this->log_file ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Direct file read required.
		$contents = file_get_contents( $this->log_file );
		if ( false === $contents ) {
			return '';
		}

		$all_lines = explode( PHP_EOL, trim( $contents ) );
		$total     = count( $all_lines );

		if ( $total <= $lines ) {
			return $contents;
		}

		return implode( PHP_EOL, array_slice( $all_lines, -$lines ) );
	}
}
