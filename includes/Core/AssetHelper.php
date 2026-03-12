<?php
/**
 * Asset Helper
 *
 * Centralizes asset URL resolution with automatic minification support.
 * Returns minified version when SCRIPT_DEBUG is not true.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 2.4.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

\defined( 'ABSPATH' ) || exit;

/**
 * Class AssetHelper
 *
 * Provides a static helper to resolve asset URLs with automatic
 * minification support based on the SCRIPT_DEBUG constant.
 */
class AssetHelper {

	/**
	 * Get asset URL with automatic minification support
	 *
	 * Returns the minified version (.min.css / .min.js) when SCRIPT_DEBUG
	 * is not true, or the original version when debugging.
	 *
	 * @since 2.4.0
	 *
	 * @param string    $asset_path  Relative path to the asset (e.g., 'assets/css/admin.css').
	 * @param bool|null $force_debug Optional. Force debug mode for testing. Defaults to SCRIPT_DEBUG constant.
	 * @return string The full URL to the asset.
	 */
	public static function get_url( string $asset_path, ?bool $force_debug = null ): string {
		if ( $force_debug !== null ) {
			$use_minified = ! $force_debug;
		} else {
			$use_minified = ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
		}

		if ( $use_minified ) {
			$file_info = pathinfo( $asset_path );

			$dirname       = $file_info['dirname'] ?? '';
			$filename      = $file_info['filename'];
			$extension     = $file_info['extension'] ?? '';
			$minified_path = $dirname . '/' . $filename . '.min.' . $extension;

			return CF7_API_URL . $minified_path;
		}

		return CF7_API_URL . $asset_path;
	}
}
