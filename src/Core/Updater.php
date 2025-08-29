<?php

/**
 * Contact Form to API Updater - GitHub Updates Integration
 *
 * Integrates the reusable silverassist/wp-github-updater package for automatic updates
 * from public GitHub releases. Provides seamless WordPress admin updates.
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.0.1
 * @license Polyform-Noncommercial-1.0.0
 */

namespace ContactFormToAPI\Core;

// Prevent direct access
if (!\defined("ABSPATH")) {
    exit;
}

use SilverAssist\WpGithubUpdater\Updater as GitHubUpdater;
use SilverAssist\WpGithubUpdater\UpdaterConfig;

/**
 * Class Updater
 *
 * Extends the reusable GitHub updater package with Contact Form to API specific configuration.
 * This approach reduces code duplication and centralizes update logic maintenance.
 *
 * @since 1.0.0
 */
class Updater extends GitHubUpdater
{
    /**
     * Initialize the Contact Form to API updater with specific configuration
     *
     * @since 1.0.0
     * @param string $plugin_file Path to main plugin file
     * @param string $github_repo GitHub repository (username/repository)
     */
    public function __construct(string $plugin_file, string $github_repo)
    {
        $config = new UpdaterConfig(
            $plugin_file,
            $github_repo,
            [
                "plugin_name" => "Contact Form 7 to API",
                "plugin_description" => "WordPress plugin that integrates Contact Form 7 with external APIs, " .
                    "allowing form submissions to be sent to custom API endpoints with advanced configuration options.",
                "plugin_author" => "Silver Assist",
                "plugin_homepage" => "https://github.com/{$github_repo}",
                "requires_wordpress" => CONTACT_FORM_TO_API_MIN_WP_VERSION,
                "requires_php" => CONTACT_FORM_TO_API_MIN_PHP_VERSION,
                "asset_pattern" => "contact-form-to-api-v{version}.zip",
                "cache_duration" => 12 * 3600, // 12 hours
                "ajax_action" => "contact_form_to_api_check_version",
                "ajax_nonce" => "contact_form_to_api_version_check"
            ]
        );

        parent::__construct($config);
    }
}
