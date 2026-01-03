#!/usr/bin/env php
<?php
/**
 * Manual Class Structure Verification
 *
 * Verifies that all new classes can be loaded and have correct structure.
 */

// Get the plugin root directory (parent of scripts/)
$plugin_root = dirname( __DIR__ );

// Define WordPress constants that might be needed
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $plugin_root . '/' );
}
if ( ! defined( 'CF7_API_TEXT_DOMAIN' ) ) {
	define( 'CF7_API_TEXT_DOMAIN', 'contact-form-to-api' );
}
if ( ! defined( 'CF7_API_URL' ) ) {
	define( 'CF7_API_URL', 'http://example.com/wp-content/plugins/contact-form-to-api/' );
}
if ( ! defined( 'CF7_API_VERSION' ) ) {
	define( 'CF7_API_VERSION', '1.1.3' );
}

echo "=== Manual Class Verification ===\n\n";

// Check if files exist
$files = [
	'includes/Core/RequestLogger.php',
	'includes/Admin/DashboardWidget.php',
	'includes/Admin/Views/DashboardWidgetView.php',
	'includes/Admin/Loader.php',
	'assets/css/dashboard-widget.css',
	'tests/Unit/RequestLoggerStatisticsTest.php',
];

echo "1. Checking file existence:\n";
$all_exist = true;
foreach ( $files as $file ) {
	$exists = file_exists( $plugin_root . '/' . $file );
	$status = $exists ? '✓' : '✗';
	echo "   {$status} {$file}\n";
	if ( ! $exists ) {
		$all_exist = false;
	}
}

if ( ! $all_exist ) {
	echo "\n❌ Some files are missing!\n";
	exit( 1 );
}

echo "\n2. Checking PHP syntax:\n";
$php_files = array_filter( $files, function( $f ) {
	return substr( $f, -4 ) === '.php';
});

$syntax_ok = true;
foreach ( $php_files as $file ) {
	$output = [];
	$return = 0;
	exec( "php -l " . escapeshellarg( $plugin_root . '/' . $file ) . " 2>&1", $output, $return );
	$status = $return === 0 ? '✓' : '✗';
	echo "   {$status} {$file}\n";
	if ( $return !== 0 ) {
		$syntax_ok = false;
		echo "      Error: " . implode( "\n      ", $output ) . "\n";
	}
}

if ( ! $syntax_ok ) {
	echo "\n❌ Syntax errors found!\n";
	exit( 1 );
}

echo "\n3. Checking class structure (without autoload):\n";

// Manually include files to check class existence
require_once $plugin_root . '/includes/Core/Interfaces/LoadableInterface.php';
require_once $plugin_root . '/includes/Core/RequestLogger.php';

echo "   ✓ RequestLogger class loaded\n";

// Check if new methods exist
$logger_reflection = new ReflectionClass( 'SilverAssist\ContactFormToAPI\Core\RequestLogger' );
$new_methods = [
	'get_count_last_hours',
	'get_success_rate_last_hours',
	'get_avg_response_time_last_hours',
	'get_recent_errors',
];

echo "\n4. Checking RequestLogger new methods:\n";
foreach ( $new_methods as $method ) {
	$has_method = $logger_reflection->hasMethod( $method );
	$status = $has_method ? '✓' : '✗';
	echo "   {$status} {$method}()\n";
	if ( ! $has_method ) {
		echo "\n❌ Method {$method} not found!\n";
		exit( 1 );
	}
}

// Check DashboardWidget class structure
require_once $plugin_root . '/includes/Admin/DashboardWidget.php';
echo "   ✓ DashboardWidget class loaded\n";

$widget_reflection = new ReflectionClass( 'SilverAssist\ContactFormToAPI\Admin\DashboardWidget' );

echo "\n5. Checking DashboardWidget implements LoadableInterface:\n";
$implements = $widget_reflection->implementsInterface( 'SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface' );
$status = $implements ? '✓' : '✗';
echo "   {$status} LoadableInterface\n";

if ( ! $implements ) {
	echo "\n❌ DashboardWidget does not implement LoadableInterface!\n";
	exit( 1 );
}

echo "\n6. Checking DashboardWidget required methods:\n";
$required_methods = [ 'instance', 'init', 'get_priority', 'should_load', 'register_widget', 'render' ];
foreach ( $required_methods as $method ) {
	$has_method = $widget_reflection->hasMethod( $method );
	$status = $has_method ? '✓' : '✗';
	echo "   {$status} {$method}()\n";
	if ( ! $has_method ) {
		echo "\n❌ Method {$method} not found!\n";
		exit( 1 );
	}
}

// Check DashboardWidgetView
require_once $plugin_root . '/includes/Admin/Views/DashboardWidgetView.php';
echo "\n7. Checking DashboardWidgetView:\n";
echo "   ✓ DashboardWidgetView class loaded\n";

$view_reflection = new ReflectionClass( 'SilverAssist\ContactFormToAPI\Admin\Views\DashboardWidgetView' );
$has_render = $view_reflection->hasMethod( 'render' );
$status = $has_render ? '✓' : '✗';
echo "   {$status} render() method\n";

echo "\n✅ All verification checks passed!\n";
echo "\nSummary:\n";
echo "- All files exist and have correct syntax\n";
echo "- RequestLogger has 4 new statistics methods\n";
echo "- DashboardWidget implements LoadableInterface correctly\n";
echo "- DashboardWidget has all required methods\n";
echo "- DashboardWidgetView has render method\n";
echo "- CSS file exists for styling\n";
echo "- Unit tests file exists\n";
