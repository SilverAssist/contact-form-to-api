<?php

/**
 * Contact Form 7 to API
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.0.1
 * @license Polyform-Noncommercial-1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: Contact Form 7 to API
 * Plugin URI: https://github.com/SilverAssist/contact-form-to-api
 * Description: Integrate Contact Form 7 with external APIs. Send form submissions to custom API endpoints with advanced configuration options.
 * Version: 1.0.1
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Silver Assist
 * Author URI: https://silverassist.com
 * License: Polyform-Noncommercial-1.0.0
 * License URI: https://github.com/SilverAssist/contact-form-to-api/blob/main/LICENSE
 * Text Domain: contact-form-to-api
 * Domain Path: /languages
 * Requires Plugins: contact-form-7
 * Network: false
 */

// Prevent direct access.
if (! defined("ABSPATH")) {
    exit("Direct access forbidden.");
}

// Define plugin constants.
define("CONTACT_FORM_TO_API_VERSION", "1.0.0");
define("CONTACT_FORM_TO_API_PLUGIN_FILE", __FILE__);
define("CONTACT_FORM_TO_API_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("CONTACT_FORM_TO_API_PLUGIN_URL", plugin_dir_url(__FILE__));
define("CONTACT_FORM_TO_API_PLUGIN_BASENAME", plugin_basename(__FILE__));
define("CONTACT_FORM_TO_API_TEXT_DOMAIN", "contact-form-to-api");

// Minimum requirements.
define("CONTACT_FORM_TO_API_MIN_PHP_VERSION", "8.0");
define("CONTACT_FORM_TO_API_MIN_WP_VERSION", "6.5");

/**
 * Main plugin class
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @version 1.0.1
 */
final class ContactFormToAPI
{
  /**
   * Plugin instance
   *
   * @since 1.0.0
   * @var ContactFormToAPI|null
   */
    private static $instance = null;

  /**
   * Current PHP version
   *
   * @since 1.0.0
   * @var string
   */
    private string $php_version = PHP_VERSION;

  /**
   * Minimum PHP version required
   *
   * @since 1.0.0
   * @var string
   */
    private string $min_php_version = CONTACT_FORM_TO_API_MIN_PHP_VERSION;

  /**
   * Minimum WordPress version required
   *
   * @since 1.0.0
   * @var string
   */
    private string $min_wp_version = CONTACT_FORM_TO_API_MIN_WP_VERSION;

  /**
   * Plugin directory path
   *
   * @since 1.0.0
   * @var string
   */
    private string $plugin_dir = CONTACT_FORM_TO_API_PLUGIN_DIR;

  /**
   * Text domain for translations
   *
   * @since 1.0.0
   * @var string
   */
    private string $textdomain = CONTACT_FORM_TO_API_TEXT_DOMAIN;

  /**
   * Plugin basename
   *
   * @since 1.0.0
   * @var string
   */
    private string $plugin_basename = CONTACT_FORM_TO_API_PLUGIN_BASENAME;

  /**
   * Plugin version
   *
   * @since 1.0.0
   * @var string
   */
    private string $version = CONTACT_FORM_TO_API_VERSION;

  /**
   * Get plugin instance
   *
   * @since 1.0.0
   * @return ContactFormToAPI
   */
    public static function get_instance(): ?ContactFormToAPI
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

  /**
   * Constructor
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    private function __construct()
    {
        $this->check_requirements();
        $this->init_hooks();
    }

  /**
   * Check minimum requirements
   *
   * @since 1.0.0
   * @version 1.0.1
   * @return boolean
   */
    private function check_requirements()
    {
      // Check PHP version
        if (version_compare($this->php_version, $this->min_php_version, "<")) {
            add_action("admin_notices", [$this, "php_version_notice"]);
            return false;
        }

      // Check WordPress version
        if (version_compare($GLOBALS["wp_version"], $this->min_wp_version, "<")) {
            add_action("admin_notices", [$this, "wp_version_notice"]);
            return false;
        }

      // Check if Contact Form 7 is active
        if (!$this->is_contact_form_7_active()) {
            add_action("admin_notices", [$this, "cf7_dependency_notice"]);
            return false;
        }

        return true;
    }

  /**
   * Initialize plugin hooks
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    private function init_hooks()
    {
      // Register activation/deactivation hooks
        \register_activation_hook(__FILE__, [$this, "activate"]);
        \register_deactivation_hook(__FILE__, [$this, "deactivate"]);

      // Initialize plugin
        \add_action("plugins_loaded", [$this, "init"], 10);
        \add_action("init", [$this, "load_textdomain"]);

      // Load composer autoloader
        $this->load_dependencies();
    }

  /**
   * Load plugin dependencies
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    private function load_dependencies()
    {
        $autoloader = "{$this->plugin_dir}vendor/autoload.php";

        if (file_exists($autoloader)) {
            require_once $autoloader;
        } else {
            \add_action("admin_notices", [$this, "dependencies_notice"]);
        }
    }

  /**
   * Initialize plugin
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function init()
    {
      // Initialize core components
        if (class_exists("ContactFormToAPI\\Core\\Plugin")) {
            ContactFormToAPI\Core\Plugin::get_instance();
        }
    }

  /**
   * Load plugin text domain
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function load_textdomain()
    {
        \load_plugin_textdomain(
            $this->textdomain,
            false,
            "{dirname($this->plugin_basename)}/languages"
        );
    }

  /**
   * Plugin activation
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function activate()
    {
      // Check requirements again
        if (!$this->check_requirements()) {
            \deactivate_plugins($this->plugin_basename);
            \wp_die(
                \esc_html__("Contact Form 7 to API plugin could not be activated due to missing requirements.", $this->textdomain)
            );
        }

      // Create database tables or options if needed
        $this->create_plugin_data();
    }

  /**
   * Plugin deactivation
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function deactivate()
    {
      // Cleanup temporary data
        \delete_transient("contact_form_to_api_cache");
    }

  /**
   * Create plugin data on activation
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    private function create_plugin_data()
    {
      // Create database tables
        $this->create_database_tables();

      // Set default options
        $default_options = [
        "version" => $this->version,
        "api_timeout" => 30,
        "retry_attempts" => 3,
        "log_level" => "error",
        "enable_logging" => true,
        ];

        \add_option("contact_form_to_api_options", $default_options);

      // Set installation timestamp
        if (!\get_option("contact_form_to_api_installed")) {
            \add_option("contact_form_to_api_installed", time());
        }
    }

  /**
   * Create database tables
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    private function create_database_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

      // API logs table
        $table_name = "{$wpdb->prefix}cf7_api_logs";

        $sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            endpoint varchar(500) NOT NULL,
            method varchar(10) NOT NULL,
            status varchar(20) NOT NULL,
            request_data longtext,
            response_data longtext,
            response_code int(4),
            error_message text,
            execution_time float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once(ABSPATH . "wp-admin/includes/upgrade.php");
        \dbDelta($sql);
    }

  /**
   * Check if Contact Form 7 is active
   *
   * @since 1.0.0
   * @version 1.0.1
   * @return boolean
   */
    private function is_contact_form_7_active()
    {
        return \is_plugin_active("contact-form-7/wp-contact-form-7.php") ||
        function_exists("wpcf7");
    }

  /**
   * PHP version notice
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function php_version_notice()
    {
        $message = sprintf(
        /* translators: 1: Required PHP version, 2: Current PHP version */
            \esc_html__('Contact Form 7 to API requires PHP version %1$s or higher. You are running version %2$s.', $this->textdomain),
            $this->min_php_version,
            $this->php_version
        );

        printf("<div class=\"notice notice-error\"><p>%s</p></div>", $message);
    }

  /**
   * WordPress version notice
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function wp_version_notice()
    {
        $message = sprintf(
        /* translators: 1: Required WordPress version, 2: Current WordPress version */
            \esc_html__('Contact Form 7 to API requires WordPress version %1$s or higher. You are running version %2$s.', $this->textdomain),
            $this->min_wp_version,
            $GLOBALS["wp_version"]
        );

        printf("<div class=\"notice notice-error\"><p>%s</p></div>", $message);
    }

  /**
   * Contact Form 7 dependency notice
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function cf7_dependency_notice()
    {
        $message = \esc_html__("Contact Form 7 to API requires Contact Form 7 plugin to be installed and activated.", $this->textdomain);
        printf("<div class=\"notice notice-error\"><p>%s</p></div>", $message);
    }

  /**
   * Dependencies notice
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function dependencies_notice()
    {
        $message = \esc_html__("Contact Form 7 to API: Please run 'composer install' to install required dependencies.", $this->textdomain);
        printf("<div class=\"notice notice-warning\"><p>%s</p></div>", $message);
    }

  /**
   * Prevent cloning
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    private function __clone()
    {
    }

  /**
   * Prevent unserialization
   *
   * @since 1.0.0
   * @version 1.0.1
   */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize plugin
ContactFormToAPI::get_instance();
