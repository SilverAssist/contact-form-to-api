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

// NOTE: This file MUST be in global namespace to work with WordPress Test Suite
// The tests_add_filter() function expects callbacks in global namespace

// Load Composer autoloader
$cf7_api_composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $cf7_api_composer_autoload ) ) {
	require_once $cf7_api_composer_autoload;
}

// Define testing constants
if ( ! defined( 'CF7_API_TESTING' ) ) {
	define( 'CF7_API_TESTING', true );
}

if ( ! defined( 'CF7_API_TEST_MODE' ) ) {
	define( 'CF7_API_TEST_MODE', true );
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress Test Suite constants
if ( ! defined( 'CF7_TESTING' ) ) {
	define( 'CF7_TESTING', true );
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

// NOTE: WP_TESTS_* constants are defined by wp-tests-config.php
// NOTE: CF7_API_* constants are defined by contact-form-to-api.php when loaded
// Do NOT define them here to avoid "already defined" warnings

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress Test Suite bootstrap variables

// Get WordPress test suite location
$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

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

// Fail if WordPress test suite is not found
if ( ! $wp_tests_dir || ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	echo "\n";
	echo "ERROR: WordPress Test Suite not found!\n";
	echo "\n";
	echo "Please install the WordPress Test Suite using:\n";
	echo "  ./scripts/install-wp-tests.sh\n";
	echo "\n";
	echo "Or set the WP_TESTS_DIR environment variable:\n";
	echo "  export WP_TESTS_DIR=/path/to/wordpress-tests-lib\n";
	echo "\n";
	exit( 1 );
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

// Load WordPress test functions
require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function cf7_api_manually_load_plugin() {
	// Load Contact Form 7 first (dependency)
	if ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ) {
		require_once WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php';
	}

	// Load our plugin
	require_once dirname( __DIR__ ) . '/contact-form-to-api.php';
}

tests_add_filter( 'muplugins_loaded', 'cf7_api_manually_load_plugin' );

// Start up the WP testing environment
require $wp_tests_dir . '/includes/bootstrap.php';

// Test environment initialized
echo "CF7 to API Test Environment Initialized\n";
