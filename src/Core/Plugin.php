<?php

/**
 * Contact Form 7 to API - Plugin Core Class
 *
 * Main plugin class that handles initialization, hooks, and core functionality
 *
 * @package ContactFormToAPI\Core
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.0.1
 * @license Polyform-Noncommercial-1.0.0
 */

namespace ContactFormToAPI\Core;

use ContactFormToAPI\ContactForm\Integration;

// Prevent direct access
if (!defined("ABSPATH")) {
    exit;
}

/**
 * Main Plugin Class
 *
 * Handles plugin initialization, hooks, and coordinates between components
 *
 * @since 1.0.0
 */
class Plugin
{
  /**
   * Plugin version
   *
   * @since 1.0.0
   * @var string
   */
    private string $version = CONTACT_FORM_TO_API_VERSION;

  /**
   * Plugin main file path
   *
   * @since 1.0.0
   * @var string
   */
    private string $plugin_file = CONTACT_FORM_TO_API_PLUGIN_FILE;

  /**
   * Plugin URL
   *
   * @since 1.0.0
   * @var string|null
   */
    private ?string $plugin_url = null;

  /**
   * Text domain for translations
   *
   * @since 1.0.0
   * @var string
   */
    private string $textdomain = CONTACT_FORM_TO_API_TEXT_DOMAIN;

  /**
   * Plugin singleton instance
   *
   * @since 1.0.0
   * @var Plugin|null
   */
    private static $instance = null;

  /**
   * Contact Form 7 integration instance
   *
   * @since 1.0.0
   * @var Integration|null
   */
    private $cf7_integration;

  /**
   * GitHub updater instance
   *
   * @since 1.0.0
   * @var Updater|null
   */
    private $updater;

  /**
   * Get plugin instance (singleton)
   *
   * @since 1.0.0
   * @return Plugin
   */
    public static function get_instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

  /**
   * Private constructor to prevent direct instantiation
   *
   * @since 1.0.0
   */
    private function __construct()
    {
        $this->init();
    }

  /**
   * Initialize the plugin
   *
   * @since 1.0.0
   * @return void
   */
    private function init(): void
    {
      // Initialize components
        $this->init_components();

      // Register hooks
        $this->register_hooks();

      // Plugin is ready
        \do_action("cf7_api_loaded");
    }

  /**
   * Initialize plugin components
   *
   * @since 1.0.0
   * @return void
   */
    private function init_components(): void
    {
      // Initialize Contact Form 7 Integration
        $this->cf7_integration = new Integration($this);

      // Initialize GitHub updater if not in development mode
        $this->init_updater();
    }

  /**
   * Register WordPress hooks
   *
   * @since 1.0.0
   * @return void
   */
    private function register_hooks(): void
    {
      // Admin hooks
        if (\is_admin()) {
            \add_action("admin_enqueue_scripts", [$this, "admin_enqueue_scripts"]);
        }
    }

  /**
   * Enqueue admin scripts and styles
   *
   * @since 1.0.0
   * @param string $hook Current admin page hook
   * @return void
   */
    public function admin_enqueue_scripts(string $hook): void
    {
      // Only load on Contact Form 7 pages
        if (strpos($hook, "wpcf7") === false) {
            return;
        }

      // Enqueue admin CSS
        \wp_enqueue_style(
            "cf7-api-admin",
            "{$this->get_plugin_url()}assets/css/admin.css",
            [],
            $this->version
        );

      // Enqueue admin JavaScript
        \wp_enqueue_script(
            "cf7-api-admin",
            "{$this->get_plugin_url()}assets/js/admin.js",
            ["jquery"],
            $this->version,
            true
        );

      // Localize script
        \wp_localize_script("cf7-api-admin", "cf7_api_admin_vars", [
        "ajax_url" => \admin_url("admin-ajax.php"),
        "nonce" => \wp_create_nonce("cf7_api_admin_nonce"),
        "translations" => [
        "testing" => \__("Testing...", $this->textdomain),
        "test_connection" => \__("Test Connection", $this->textdomain),
        "connection_failed" => \__("Connection failed", $this->textdomain),
        "select_field" => \__("Select Field", $this->textdomain),
        "confirm_delete" => \__("Are you sure you want to delete this integration?", $this->textdomain),
        "json_error" => \__("Invalid JSON format", $this->textdomain),
        "xml_error" => \__("Invalid XML format", $this->textdomain),
        "saving" => \__("Saving...", $this->textdomain),
        "saved" => \__("Saved successfully", $this->textdomain),
        "save_error" => \__("Error saving settings", $this->textdomain),
        ],
        ]);
    }

  /**
   * Get plugin URL
   *
   * @since 1.0.0
   * @return string
   */
    public function get_plugin_url(): string
    {
        if ($this->plugin_url === null) {
            $this->plugin_url = defined("CONTACT_FORM_TO_API_PLUGIN_URL")
              ? CONTACT_FORM_TO_API_PLUGIN_URL
              : \plugin_dir_url(dirname(dirname(__DIR__)) . "/contact-form-to-api.php");
        }

        return $this->plugin_url;
    }

  /**
   * Get plugin version
   *
   * @since 1.0.0
   * @return string
   */
    public function get_version(): string
    {
        return $this->version;
    }

  /**
   * Get plugin text domain
   *
   * @since 1.0.0
   * @return string
   */
    public function get_textdomain(): string
    {
        return $this->textdomain;
    }

  /**
   * Get plugin main file path
   *
   * @since 1.0.0
   * @return string
   */
    public function get_plugin_file(): string
    {
        return $this->plugin_file;
    }

  /**
   * Initialize GitHub updater
   *
   * @since 1.0.0
   * @return void
   */
    private function init_updater(): void
    {
        try {
            // Check if updater class exists (composer package available)
            if (\class_exists("\\ContactFormToAPI\\Core\\Updater")) {
                $this->updater = new Updater(
                    $this->plugin_file,
                    "SilverAssist/contact-form-to-api"
                );
            }
        } catch (\Exception $e) {
            // Fail silently if updater package is not available
            \error_log("Contact Form to API: GitHub updater not available - " . $e->getMessage());
        }
    }
}

// Initialize the plugin
Plugin::get_instance();
