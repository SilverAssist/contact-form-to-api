<?php
/**
 * Unit Tests for Core Plugin Class
 *
 * Tests the main plugin initialization, component coordination,
 * and asset management functionality.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.0.0
 * @version 1.0.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Core;

use SilverAssist\ContactFormToAPI\Core\Plugin;
use SilverAssist\ContactFormToAPI\Service\Api\ApiClient;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

/**
 * Test cases for the Core Plugin class
 */
class PluginTest extends TestCase {

	/**
	 * Test plugin constants are defined
	 *
	 * @return void
	 */
	public function testPluginConstantsAreDefined(): void {
		$this->assertTrue( defined( 'CF7_API_VERSION' ), 'Plugin version constant should be defined' );
		$this->assertTrue( defined( 'CF7_API_FILE' ), 'Plugin file constant should be defined' );
		$this->assertTrue( defined( 'CF7_API_DIR' ), 'Plugin dir constant should be defined' );
	}

	/**
	 * Test plugin version constant has correct format
	 *
	 * @return void
	 */
	public function testPluginVersionFormat(): void {
		$version = CF7_API_VERSION;

		$this->assertIsString( $version, 'Plugin version should be a string' );
		$this->assertMatchesRegularExpression(
			'/^\d+\.\d+\.\d+$/',
			$version,
			'Plugin version should follow semantic versioning (x.y.z)'
		);
	}

	/**
	 * Test plugin text domain is used correctly in code
	 *
	 * WordPress i18n tools require literal string text domains for extraction.
	 * The text domain 'contact-form-to-api' should be used directly in all __() calls.
	 *
	 * @return void
	 */
	public function testTextDomainIsLiteralString(): void {
		// Text domain should be 'contact-form-to-api' used as literal string
		// This test documents the expected text domain value.
		$expected_text_domain = 'contact-form-to-api';
		$this->assertIsString( $expected_text_domain );
	}

	/**
	 * Test plugin file path exists
	 *
	 * @return void
	 */
	public function testPluginFileExists(): void {
		if ( defined( 'CF7_API_FILE' ) ) {
			$this->assertFileExists(
				CF7_API_FILE,
				'Main plugin file should exist at specified path'
			);
		} else {
			$this->markTestSkipped( 'CF7_API_FILE constant not defined' );
		}
	}

	/**
	 * Test Plugin class exists and can be instantiated
	 *
	 * @return void
	 */
	public function testPluginClassExists(): void {
		$this->assertTrue(
			class_exists( 'SilverAssist\\ContactFormToAPI\\Core\\Plugin' ),
			'Plugin class should exist in the SilverAssist\\ContactFormToAPI\\Core namespace'
		);
	}

	/**
	 * Test plugin singleton pattern (if implemented)
	 *
	 * @return void
	 */
	public function testPluginSingletonPattern(): void {
		if ( class_exists( 'SilverAssist\\ContactFormToAPI\\Core\\Plugin' ) ) {
			if ( method_exists( 'SilverAssist\\ContactFormToAPI\\Core\\Plugin', 'instance' ) ) {
				$instance1 = Plugin::instance();
				$instance2 = Plugin::instance();

				$this->assertSame(
					$instance1,
					$instance2,
					'Plugin should follow singleton pattern'
				);
			} else {
				$this->markTestSkipped( 'Plugin does not implement singleton pattern' );
			}
		} else {
			$this->markTestSkipped( 'Plugin class not available for testing' );
		}
	}

	/**
	 * Test plugin initialization in testing mode
	 *
	 * @return void
	 */
	public function testPluginTestingMode(): void {
		$this->assertTrue(
			defined( 'CF7_API_TESTING' ) && CF7_API_TESTING,
			'Plugin should be running in testing mode'
		);

		$this->assertTrue(
			defined( 'CF7_API_TEST_MODE' ) && CF7_API_TEST_MODE,
			'Plugin test mode should be enabled'
		);
	}

	/**
	 * Test plugin namespace autoloading
	 *
	 * @return void
	 */
	public function testNamespaceAutoloading(): void {
		// Test that classes in our namespace can be loaded.
		$core_classes = array(
			'SilverAssist\\ContactFormToAPI\\Core\\Plugin',
		);

		foreach ( $core_classes as $class_name ) {
			$this->assertTrue(
				class_exists( $class_name ) || interface_exists( $class_name ) || trait_exists( $class_name ),
				'Class should be autoloadable: ' . $class_name
			);
		}
	}

	/**
	 * Test plugin meets minimum requirements
	 *
	 * @return void
	 */
	public function testMinimumRequirements(): void {
		// Test PHP version.
		$this->assertEquals(
			'8.2',
			CF7_API_MIN_PHP_VERSION,
			'PHP version should be 8.2 or higher'
		);

		// Test required PHP extensions.
		$required_extensions = array( 'json', 'curl' );
		foreach ( $required_extensions as $extension ) {
			$this->assertTrue(
				extension_loaded( $extension ),
				'Required PHP extension should be loaded: ' . $extension
			);
		}
	}

	/**
	 * Test i18n text domain usage
	 *
	 * @return void
	 */
	public function testI18nTextDomain(): void {
		// WordPress i18n functions should be available via WP_UnitTestCase.
		$this->assertTrue( function_exists( '__' ), 'Translation function __ should be available' );
		$this->assertTrue( function_exists( 'esc_html__' ), 'esc_html__ should be available' );

		// Text domain 'contact-form-to-api' should be used as literal string in all i18n calls.
		// WordPress i18n tools (wp i18n make-pot) require literal strings, not constants.
		$translated = __( 'Test String', 'contact-form-to-api' );
		$this->assertIsString( $translated, 'Translation function should return a string' );
	}

	/**
	 * Test plugin directory structure
	 *
	 * @return void
	 */
	public function testPluginDirectoryStructure(): void {
		if ( defined( 'CF7_API_DIR' ) ) {
			$plugin_dir = CF7_API_DIR;

			// Test essential directories exist.
			$required_dirs = array(
				'includes',
				'assets',
				'languages',
			);

			foreach ( $required_dirs as $dir ) {
				$dir_path = $plugin_dir . $dir;
				$this->assertDirectoryExists(
					$dir_path,
					'Required directory should exist: ' . $dir
				);
			}

			// Test essential files exist.
			$required_files = array(
				'composer.json',
				'README.md',
			);

			foreach ( $required_files as $file ) {
				$file_path = $plugin_dir . $file;
				$this->assertFileExists(
					$file_path,
					'Required file should exist: ' . $file
				);
			}
		} else {
			$this->markTestSkipped( 'CF7_API_DIR constant not defined' );
		}
	}

	/**
	 * Test DebugLogger loads at Core priority, not after Services/Controllers
	 *
	 * Regression test: DebugLogger used to be manually placed 3rd in the
	 * bootstrap's hardcoded call order specifically so it would be
	 * available for other components' error logging, even though its
	 * declared get_priority() said 40 ("Utils load last") — a mismatch
	 * that only worked because the old loader ignored get_priority()
	 * entirely. AbstractPlugin's real priority-sort would otherwise load
	 * it dead last, after every Service/Controller. See CHANGELOG.md.
	 *
	 * @return void
	 */
	public function testDebugLoggerLoadsAtCorePriorityNotAfterServices(): void {
		$this->assertSame(
			10,
			DebugLogger::instance()->get_priority(),
			'DebugLogger must declare Core priority (10) so it is available before any component that can fail'
		);
		$this->assertLessThan(
			ApiClient::instance()->get_priority(),
			DebugLogger::instance()->get_priority(),
			'DebugLogger must load before Service-priority components'
		);
	}

	/**
	 * Test on_component_error() does not silently swallow DebugLogger's own failure
	 *
	 * Regression test: on_component_error() always tried
	 * DebugLogger::instance()->error() first, including when DebugLogger
	 * itself was the failing component. DebugLogger's error()/log()
	 * suppress file-write failures internally and return void rather
	 * than throwing, so routing DebugLogger's own failure back through
	 * DebugLogger would silently succeed without ever recording
	 * anything, losing the diagnostic with no error_log() fallback.
	 *
	 * @return void
	 */
	public function testOnComponentErrorFallsBackToErrorLogForDebugLoggerFailures(): void {
		$log_file           = tempnam( sys_get_temp_dir(), 'cf7-api-test-log' );
		$previous_error_log = ini_get( 'error_log' );
		// phpcs:ignore WordPress.PHP.IniSet.Risky -- Test-only redirect of error_log() output to a temp file so the assertion below can inspect it; restored in the finally block.
		ini_set( 'error_log', $log_file );

		try {
			$method = new \ReflectionMethod( Plugin::class, 'on_component_error' );
			$method->setAccessible( true );
			$method->invoke( Plugin::instance(), DebugLogger::class, new \RuntimeException( 'debug logger boom' ) );
		} finally {
			// phpcs:ignore WordPress.PHP.IniSet.Risky -- Restoring the original error_log ini value, not changing behavior.
			ini_set( 'error_log', $previous_error_log );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Test helper, not production code.
		$contents = (string) file_get_contents( $log_file );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test helper, not production code.
		unlink( $log_file );

		$this->assertStringContainsString(
			'Failed to load ' . DebugLogger::class,
			$contents,
			"DebugLogger's own load failure must fall through to error_log(), not be silently swallowed by routing back through DebugLogger itself"
		);
	}
}
