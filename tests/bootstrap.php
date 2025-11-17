<?php

/**
 * PHPUnit Bootstrap for Contact Form 7 to API Plugin Tests
 *
 * This file is responsible for setting up the testing environment,
 * loading WordPress test suite, and initializing the plugin for testing.
 *
 * @package SilverAssist\ContactFormToAPI
 * @since   1.0.0
 * @version 1.0.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Test bootstrap needs to mock WordPress globals
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter -- Mock functions don't use all parameters

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Mock WordPress functions FIRST (in global namespace)
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		return true;
	}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( $handle, $object_name, $data ) {
		return true;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '', $scheme = 'admin' ) {
		return 'http://example.com/wp-admin/' . $path;
	}
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'test_nonce_' . $action;
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

// Define testing constants
if ( ! defined( 'CONTACT_FORM_TO_API_TESTING' ) ) {
	define( 'CONTACT_FORM_TO_API_TESTING', true );
}

if ( ! defined( 'CONTACT_FORM_TO_API_TEST_MODE' ) ) {
	define( 'CONTACT_FORM_TO_API_TEST_MODE', true );
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress Test Suite constants
if ( ! defined( 'CF7_TESTING' ) ) {
	define( 'CF7_TESTING', true );
}

// WordPress test environment constants
if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
	define( 'WP_TESTS_DOMAIN', 'example.org' );
}

if ( ! defined( 'WP_TESTS_EMAIL' ) ) {
	define( 'WP_TESTS_EMAIL', 'admin@example.org' );
}

if ( ! defined( 'WP_TESTS_TITLE' ) ) {
	define( 'WP_TESTS_TITLE', 'Test Blog' );
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

// Plugin constants
if ( ! defined( 'CONTACT_FORM_TO_API_VERSION' ) ) {
	define( 'CONTACT_FORM_TO_API_VERSION', '1.0.0' );
}

if ( ! defined( 'CONTACT_FORM_TO_API_FILE' ) ) {
	define( 'CONTACT_FORM_TO_API_FILE', dirname( __DIR__ ) . '/contact-form-to-api.php' );
}

if ( ! defined( 'CONTACT_FORM_TO_API_DIR' ) ) {
	define( 'CONTACT_FORM_TO_API_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'CONTACT_FORM_TO_API_TEXT_DOMAIN' ) ) {
	define( 'CONTACT_FORM_TO_API_TEXT_DOMAIN', 'contact-form-to-api' );
}

if ( ! defined( 'CONTACT_FORM_TO_API_MIN_PHP_VERSION' ) ) {
	define( 'CONTACT_FORM_TO_API_MIN_PHP_VERSION', '8.2' );
}

if ( ! defined( 'CONTACT_FORM_TO_API_MIN_WP_VERSION' ) ) {
	define( 'CONTACT_FORM_TO_API_MIN_WP_VERSION', '6.5' );
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress Test Suite bootstrap variables
// Load Composer autoloader
$composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
}

// Try to load WordPress test suite
$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
$wp_core_dir  = getenv( 'WP_CORE_DIR' );

// If WP_TESTS_DIR is not set, try common locations
if ( ! $wp_tests_dir ) {
	$possible_locations = array(
		'/tmp/wordpress-tests-lib',
		'/var/www/html/wp-tests',
		dirname( __DIR__ ) . '/vendor/wordpress/wordpress-develop/tests/phpunit',
		'/usr/local/src/wordpress-tests-lib',
	);

	foreach ( $possible_locations as $location ) {
		if ( file_exists( $location . '/includes/functions.php' ) ) {
			$wp_tests_dir = $location;
			break;
		}
	}
}

// If WP_CORE_DIR is not set, try common locations
if ( ! $wp_core_dir ) {
	$possible_core_locations = array(
		'/tmp/wordpress',
		'/var/www/html',
		dirname( __DIR__ ) . '/vendor/wordpress/wordpress',
		'/usr/local/src/wordpress',
	);

	foreach ( $possible_core_locations as $location ) {
		if ( file_exists( $location . '/wp-settings.php' ) ) {
			$wp_core_dir = $location;
			break;
		}
	}
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

// Define ABSPATH if we have WP core directory
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress core constant
if ( $wp_core_dir && ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $wp_core_dir . '/' );
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

// Load WordPress test functions
if ( $wp_tests_dir && file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	require_once $wp_tests_dir . '/includes/functions.php';

	/**
	 * Manually load the plugin being tested
	 */
	function _manually_load_plugin() {
		// Load Contact Form 7 first (dependency)
		if ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ) {
			require_once WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php';
		}

		// Load our plugin
		require_once dirname( __DIR__ ) . '/contact-form-to-api.php';
	}

	\tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	// Start up the WP testing environment
	require_once $wp_tests_dir . '/includes/bootstrap.php';
} else {
	// Minimal setup if WordPress test suite is not available
	echo "Warning: WordPress test suite not found. Running tests in isolation mode.\n";
}

// Test helpers are autoloaded via composer
echo "Contact Form 7 to API Test Environment Initialized\n";
