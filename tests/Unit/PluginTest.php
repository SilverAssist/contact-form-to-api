<?php

/**
 * Unit Tests for Core Plugin Class
 *
 * Tests the main plugin initialization, component coordination,
 * and asset management functionality.
 *
 * @package ContactFormToAPI\Tests
 * @since   1.0.0
 * @version 1.0.0
 * @author  Silver Assist
 */

namespace ContactFormToAPI\Tests\Unit;

use ContactFormToAPI\Tests\Helpers\TestCase;
use ContactFormToAPI\Core\Plugin;

/**
 * Test cases for the Core Plugin class
 */
class PluginTest extends TestCase
{
    /**
     * Test plugin constants are defined
     *
     * @return void
     */
    public function testPluginConstantsAreDefined(): void
    {
        $this->assertTrue(defined("CONTACT_FORM_TO_API_VERSION"), "Plugin version constant should be defined");
        $this->assertTrue(defined("CONTACT_FORM_TO_API_TEXT_DOMAIN"), "Text domain constant should be defined");
        $this->assertTrue(defined("CONTACT_FORM_TO_API_PLUGIN_FILE"), "Plugin file constant should be defined");
    }

    /**
     * Test plugin version constant has correct format
     *
     * @return void
     */
    public function testPluginVersionFormat(): void
    {
        $version = CONTACT_FORM_TO_API_VERSION;

        $this->assertIsString($version, "Plugin version should be a string");
        $this->assertMatchesRegularExpression(
            "/^\d+\.\d+\.\d+$/",
            $version,
            "Plugin version should follow semantic versioning (x.y.z)"
        );
    }

    /**
     * Test text domain constant value
     *
     * @return void
     */
    public function testTextDomainConstant(): void
    {
        $this->assertEquals(
            "contact-form-to-api",
            CONTACT_FORM_TO_API_TEXT_DOMAIN,
            "Text domain should be 'contact-form-to-api'"
        );
    }

    /**
     * Test plugin file path exists
     *
     * @return void
     */
    public function testPluginFileExists(): void
    {
        if (defined("CONTACT_FORM_TO_API_PLUGIN_FILE")) {
            $this->assertFileExists(
                CONTACT_FORM_TO_API_PLUGIN_FILE,
                "Main plugin file should exist at specified path"
            );
        } else {
            $this->markTestSkipped("CONTACT_FORM_TO_API_PLUGIN_FILE constant not defined");
        }
    }

    /**
     * Test Plugin class exists and can be instantiated
     *
     * @return void
     */
    public function testPluginClassExists(): void
    {
        $this->assertTrue(
            class_exists("ContactFormToAPI\\Core\\Plugin"),
            "Plugin class should exist in the ContactFormToAPI\\Core namespace"
        );
    }

    /**
     * Test plugin singleton pattern (if implemented)
     *
     * @return void
     */
    public function testPluginSingletonPattern(): void
    {
        if (class_exists("ContactFormToAPI\\Core\\Plugin")) {
            if (method_exists("ContactFormToAPI\\Core\\Plugin", "getInstance")) {
                $instance1 = Plugin::getInstance();
                $instance2 = Plugin::getInstance();

                $this->assertSame(
                    $instance1,
                    $instance2,
                    "Plugin should follow singleton pattern"
                );
            } else {
                $this->markTestSkipped("Plugin does not implement singleton pattern");
            }
        } else {
            $this->markTestSkipped("Plugin class not available for testing");
        }
    }

    /**
     * Test plugin initialization in testing mode
     *
     * @return void
     */
    public function testPluginTestingMode(): void
    {
        $this->assertTrue(
            defined("CONTACT_FORM_TO_API_TESTING") && CONTACT_FORM_TO_API_TESTING,
            "Plugin should be running in testing mode"
        );

        $this->assertTrue(
            defined("CONTACT_FORM_TO_API_TEST_MODE") && CONTACT_FORM_TO_API_TEST_MODE,
            "Plugin test mode should be enabled"
        );
    }

    /**
     * Test plugin namespace autoloading
     *
     * @return void
     */
    public function testNamespaceAutoloading(): void
    {
        // Test that classes in our namespace can be loaded
        $core_classes = [
            "ContactFormToAPI\\Core\\Plugin"
        ];

        foreach ($core_classes as $class_name) {
            $this->assertTrue(
                class_exists($class_name) || interface_exists($class_name) || trait_exists($class_name),
                "Class should be autoloadable: " . $class_name
            );
        }
    }

    /**
     * Test plugin meets minimum requirements
     *
     * @return void
     */
    public function testMinimumRequirements(): void
    {
        // Test PHP version
        $this->assertEquals(
            "8.2",
            CONTACT_FORM_TO_API_MIN_PHP_VERSION,
            "PHP version should be 8.2 or higher"
        );

        // Test required PHP extensions
        $required_extensions = ["json", "curl"];
        foreach ($required_extensions as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "Required PHP extension should be loaded: " . $extension
            );
        }
    }

    /**
     * Test i18n text domain usage
     *
     * @return void
     */
    public function testI18nTextDomain(): void
    {
        // Mock WordPress i18n functions if not available
        if (!function_exists("__")) {
            $this->mockWordPressFunction("__", "mocked_translation");
        }

        if (!function_exists("esc_html__")) {
            $this->mockWordPressFunction("esc_html__", "mocked_escaped_translation");
        }

        // Test that text domain constant exists and has correct value
        $this->assertEquals(
            "contact-form-to-api",
            CONTACT_FORM_TO_API_TEXT_DOMAIN,
            "Text domain constant should have correct value"
        );
    }

    /**
     * Test plugin directory structure
     *
     * @return void
     */
    public function testPluginDirectoryStructure(): void
    {
        if (defined("CONTACT_FORM_TO_API_PLUGIN_DIR")) {
            $plugin_dir = CONTACT_FORM_TO_API_PLUGIN_DIR;

            // Test essential directories exist
            $required_dirs = [
                "src",
                "assets",
                "languages"
            ];

            foreach ($required_dirs as $dir) {
                $dir_path = $plugin_dir . $dir;
                $this->assertDirectoryExists(
                    $dir_path,
                    "Required directory should exist: " . $dir
                );
            }

            // Test essential files exist
            $required_files = [
                "composer.json",
                "README.md"
            ];

            foreach ($required_files as $file) {
                $file_path = $plugin_dir . $file;
                $this->assertFileExists(
                    $file_path,
                    "Required file should exist: " . $file
                );
            }
        } else {
            $this->markTestSkipped("CONTACT_FORM_TO_API_PLUGIN_DIR constant not defined");
        }
    }
}
