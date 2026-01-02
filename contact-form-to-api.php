<?php

/**
 * CF7 to API
 *
 * @package SilverAssist\ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.0
 * @license Polyform-Noncommercial-1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: CF7 to API
 * Plugin URI: https://github.com/SilverAssist/contact-form-to-api
 * Description: Extend Contact Form 7 functionality by connecting forms to external APIs. Send form submissions to custom API endpoints with advanced configuration options.
 * Version: 1.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Silver Assist
 * Author URI: https://silverassist.com
 * License: Polyform-Noncommercial-1.0.0
 * License URI: https://github.com/SilverAssist/contact-form-to-api/blob/main/LICENSE
 * Text Domain: contact-form-to-api
 * Domain Path: /languages
 * Requires Plugins: contact-form-7
 * Network: false
 * Update URI: https://github.com/SilverAssist/contact-form-to-api
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'CF7_API_VERSION', '1.1.0' );
define( 'CF7_API_FILE', __FILE__ );
define( 'CF7_API_DIR', plugin_dir_path( __FILE__ ) );
define( 'CF7_API_URL', plugin_dir_url( __FILE__ ) );
define( 'CF7_API_BASENAME', plugin_basename( __FILE__ ) );
define( 'CF7_API_TEXT_DOMAIN', 'contact-form-to-api' );

// Minimum requirements.
define( 'CF7_API_MIN_PHP_VERSION', '8.2' );
define( 'CF7_API_MIN_WP_VERSION', '6.5' );

/**
 * Composer autoloader
 */
$cf7_api_autoload_path      = CF7_API_DIR . 'vendor/autoload.php';
$cf7_api_real_autoload_path = realpath( $cf7_api_autoload_path );
$cf7_api_real_path          = realpath( CF7_API_DIR );

// Validate: both paths resolve, autoloader is inside plugin directory.
if (
	$cf7_api_real_autoload_path &&
	$cf7_api_real_path &&
	0 === strpos( $cf7_api_real_autoload_path, $cf7_api_real_path )
) {
	require_once $cf7_api_real_autoload_path;
} else {
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Contact Form 7 to API: Missing or invalid Composer dependencies. Run "composer install".', 'contact-form-to-api' )
			);
		}
	);
	return;
}

// Initialize plugin.
add_action(
	'plugins_loaded',
	function () {
		\SilverAssist\ContactFormToAPI\Core\Plugin::instance()->init();
	}
);

// Register activation hook.
register_activation_hook(
	__FILE__,
	function () {
		\SilverAssist\ContactFormToAPI\Core\Activator::activate();
	}
);

// Register deactivation hook.
register_deactivation_hook(
	__FILE__,
	function () {
		\SilverAssist\ContactFormToAPI\Core\Activator::deactivate();
	}
);
