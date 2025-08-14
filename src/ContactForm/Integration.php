<?php

/**
 * Contact Form 7 Integration
 *
 * Handles integration with Contact Form 7 forms and submission processing
 * Migrated from the legacy CF7 API plugin with modern standards
 *
 * @package ContactFormToAPI\ContactForm
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.0.0
 * @license Polyform-Noncommercial-1.0.0
 */

namespace ContactFormToAPI\ContactForm;

use ContactFormToAPI\Core\Plugin;
use WPCF7_ContactForm;
use WPCF7_Submission;

// Prevent direct access
if (!defined("ABSPATH")) {
    exit;
}

/**
 * Contact Form 7 Integration Class
 *
 * Manages integration with Contact Form 7 forms and processes submissions
 * Provides direct form-level configuration via admin tabs
 *
 * @since 1.0.0
 */
class Integration
{
  /**
   * Checkbox value constants and detection
   *
   * @since 1.0.0
   */
    private const CHECKBOX_VALUES = ["TRUE", "FALSE", "1", "0", "true", "false", 1, 0, true, false];
    private const CHECKED_VALUES = ["TRUE", "1", "true", 1, true];
    private const CHECKBOX_YES_NO = ["1", "0"];

  /**
   * Plugin instance for accessing shared methods
   *
   * @since 1.0.0
   * @var Plugin
   */
    private Plugin $plugin;

  /**
   * Current form object for processing
   *
   * @since 1.0.0
   * @var WPCF7_ContactForm|null
   */
    private ?WPCF7_ContactForm $current_form = null;

  /**
   * API errors for current request
   *
   * @since 1.0.0
   * @var array
   */
    private array $api_errors = [];

  /**
   * Constructor
   *
   * @since 1.0.0
   * @param Plugin $plugin Plugin instance for accessing shared methods
   */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->init();
    }

  /**
   * Initialize Contact Form 7 integration
   *
   * @since 1.0.0
   * @return void
   */
    private function init(): void
    {
      // Hook into Contact Form 7 submission process
        \add_action("wpcf7_before_send_mail", [$this, "send_data_to_api"]);

      // Add checkbox value handling filters
        \add_filter("cf7_api_set_record_value", [$this, "cf7_api_checkbox_value_handler"], 10, 2);
        \add_filter("cf7_api_create_record", [$this, "cf7_api_handle_boolean_checkbox"], 10, 5);
        \add_filter("cf7_api_create_record", [$this, "cf7_api_final_checkbox_handler"], 20, 1);

      // Add admin hooks for Contact Form 7
        if (\is_admin()) {
            \add_filter("wpcf7_editor_panels", [$this, "add_integrations_tab"]);
            \add_action("wpcf7_save_contact_form", [$this, "save_contact_form_details"]);
            \add_filter("wpcf7_contact_form_properties", [$this, "add_form_properties"], 10, 1);
            \add_action("admin_enqueue_scripts", [$this, "enqueue_admin_scripts"]);
        }
    }

  /**
   * Add form properties for API integration
   *
   * @since 1.0.0
   * @param array $properties Form properties
   * @return array Modified properties
   */
    public function add_form_properties(array $properties): array
    {
        $properties["wpcf7_api_data"] ??= [];
        $properties["wpcf7_api_data_map"] ??= [];
        $properties["template"] ??= "";
        $properties["json_template"] ??= "";

        return $properties;
    }

  /**
   * Add integrations tab to Contact Form 7 admin
   *
   * @since 1.0.0
   * @param array $panels Existing panels
   * @return array Modified panels with API integration tab
   */
    public function add_integrations_tab(array $panels): array
    {
        $panels["cf7-api-integration"] = [
        "title" => \__("API Integration", $this->plugin->get_textdomain()),
        "callback" => [$this, "render_integration_panel"]
        ];

        return $panels;
    }

  /**
   * Collect mail tags from form
   *
   * @since 1.0.0
   * @param WPCF7_ContactForm $post The contact form object to scan for tags
   * @param array             $args Optional arguments to filter tags by type
   * @return array Array of WPCF7_FormTag objects for use in templates
   */
    private function get_mail_tags(WPCF7_ContactForm $form, array $args): array
    {
      /** @var WPCF7_FormTag[] $tags */
        $tags = apply_filters("paubox_cf7_collect_mail_tags", $form->scan_form_tags());

        foreach ((array) $tags as $tag) {
            $type = trim($tag["type"], "*");
            if (empty($type) || empty($tag["name"])) {
                continue;
            } elseif (!empty($args["include"])) {
                if (!in_array($type, $args["include"])) {
                    continue;
                }
            } elseif (!empty($args["exclude"])) {
                if (in_array($type, $args["exclude"])) {
                    continue;
                }
            }
            $mailtags[] = $tag;
        }

        return $mailtags;
    }

  /**
   * Render API integration panel
   *
   * @since 1.0.0
   * @param WPCF7_ContactForm $post Contact form object (CF7)
   * @return void
   */
    public function render_integration_panel(WPCF7_ContactForm $post): void
    {
      // Get form data using the SAME METHOD as old plugin for compatibility
        $wpcf7 = WPCF7_ContactForm::get_current();
        $form_id = $wpcf7->id();

      // Use the SAME approach as the old plugin - get from properties first, fallback to post_meta
        $wpcf7_api_data = $wpcf7->prop("wpcf7_api_data") ?: \get_post_meta($form_id, "_wpcf7_api_data", true);
        $wpcf7_api_data_map = $wpcf7->prop("wpcf7_api_data_map") ?: \get_post_meta($form_id, "_wpcf7_api_data_map", true);
        $wpcf7_api_data_template = $wpcf7->prop("template") ?: \get_post_meta($form_id, "_template", true);
        $wpcf7_api_json_data_template = stripslashes($wpcf7->prop("json_template") ?: \get_post_meta($form_id, "_json_template", true));

        $mail_tags = $this->get_mail_tags($post, []);

      // Set defaults - same as old plugin
        if (!is_array($wpcf7_api_data)) {
            $wpcf7_api_data = [];
        }
        $wpcf7_api_data["base_url"] ??= "";
        $wpcf7_api_data["send_to_api"] ??= "";
        $wpcf7_api_data["input_type"] ??= "params";
        $wpcf7_api_data["method"] ??= "GET";
        $wpcf7_api_data["debug_log"] = true;

      // Get debug information
        $debug_url = \get_post_meta($form_id, "cf7_api_debug_url", true);
        $debug_result = \get_post_meta($form_id, "cf7_api_debug_result", true);
        $debug_params = \get_post_meta($form_id, "cf7_api_debug_params", true);
        $error_logs = \get_post_meta($form_id, "api_errors", true);

      // Placeholders
        $xml_placeholder = \__("*** THIS IS AN EXAMPLE ** USE YOUR XML ACCORDING TO YOUR API DOCUMENTATION **
<update>
    <user clientid=\"\" username=\"user_name\" password=\"mypassword\" />
    <reports>
        <report tag=\"NEW\">
            <fields>
               <field id=\"1\" name=\"REFERENCE_ID\" value=\"[your-name]\" />
               <field id=\"2\" name=\"DESCRIPTION\" value=\"[your-email]\" />
            </fields>
        </report>
    </reports>
</update>", $this->plugin->get_textdomain());

        $json_placeholder = \__("*** THIS IS AN EXAMPLE ** USE YOUR JSON ACCORDING TO YOUR API DOCUMENTATION **
{ \"name\":\"[fullname]\", \"age\":30, \"car\":null }", $this->plugin->get_textdomain());

        ?>
    <div id="cf7-api-integration">
      <h2><?php \esc_html_e("API Integration", $this->plugin->get_textdomain()); ?></h2>

      <fieldset>
        <?php \do_action("cf7_api_before_base_fields", $post); ?>

        <div class="cf7_row">
          <label for="wpcf7-sf-send-to-api">
            <input type="checkbox" id="wpcf7-sf-send-to-api" name="wpcf7-sf[send_to_api]" <?php \checked($wpcf7_api_data["send_to_api"], "on"); ?> />
            <?php \esc_html_e("Send to API?", $this->plugin->get_textdomain()); ?>
          </label>
        </div>

        <div class="cf7_row">
          <label for="wpcf7-sf-base-url">
            <?php \esc_html_e("Base URL", $this->plugin->get_textdomain()); ?>
            <input type="text" id="wpcf7-sf-base-url" name="wpcf7-sf[base_url]" class="large-text"
              value="<?php echo \esc_attr($wpcf7_api_data["base_url"]); ?>" />
          </label>
        </div>

        <hr>

        <div class="cf7_row">
          <label for="wpcf7-sf-input-type">
            <span class="cf7-label-in"><?php \esc_html_e("Input type", $this->plugin->get_textdomain()); ?></span>
            <select id="wpcf7-sf-input-type" name="wpcf7-sf[input_type]">
              <option value="params" <?php \selected($wpcf7_api_data["input_type"], "params"); ?>>
                <?php \esc_html_e("Parameters - GET/POST", $this->plugin->get_textdomain()); ?>
              </option>
              <option value="xml" <?php \selected($wpcf7_api_data["input_type"], "xml"); ?>>
                <?php \esc_html_e("XML", $this->plugin->get_textdomain()); ?>
              </option>
              <option value="json" <?php \selected($wpcf7_api_data["input_type"], "json"); ?>>
                <?php \esc_html_e("JSON", $this->plugin->get_textdomain()); ?>
              </option>
            </select>
          </label>
        </div>

        <div class="cf7_row" data-cf7index="params,json">
          <label for="wpcf7-sf-method">
            <span class="cf7-label-in"><?php \esc_html_e("Method", $this->plugin->get_textdomain()); ?></span>
            <select id="wpcf7-sf-method" name="wpcf7-sf[method]">
              <option value="GET" <?php \selected($wpcf7_api_data["method"], "GET"); ?>>GET</option>
              <option value="POST" <?php \selected($wpcf7_api_data["method"], "POST"); ?>>POST</option>
            </select>
          </label>
        </div>

        <?php \do_action("cf7_api_after_base_fields", $post); ?>
      </fieldset>

      <!-- Parameters Mapping Section -->
      <fieldset data-cf7index="params">
        <div class="cf7_row">
          <h2><?php \esc_html_e("Form fields", $this->plugin->get_textdomain()); ?></h2>

          <table>
            <tr>
              <th><?php \esc_html_e("Form fields", $this->plugin->get_textdomain()); ?></th>
              <th><?php \esc_html_e("API Key", $this->plugin->get_textdomain()); ?></th>
              <th></th>
            </tr>
            <?php foreach ($mail_tags as $mail_tag) : ?>
                <?php if ($mail_tag->type === "checkbox") : ?>
                    <?php foreach ($mail_tag->values as $checkbox_row) : ?>
                  <tr>
                    <th style="text-align:left;"><?php echo \esc_html("{$mail_tag->name} ({$checkbox_row})"); ?></th>
                    <td>
                      <input type="text"
                        name="qs_wpcf7_api_map[<?php echo \esc_attr($mail_tag->name); ?>][<?php echo \esc_attr($checkbox_row); ?>]"
                        class="large-text"
                        value="<?php echo \esc_attr($wpcf7_api_data_map[$mail_tag->name][$checkbox_row] ?? ""); ?>" />
                    </td>
                  </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                <tr>
                  <th style="text-align:left;"><?php echo \esc_html($mail_tag->name); ?></th>
                  <td>
                    <input type="text" name="qs_wpcf7_api_map[<?php echo \esc_attr($mail_tag->name); ?>]" class="large-text"
                      value="<?php echo \esc_attr($wpcf7_api_data_map[$mail_tag->name] ?? ""); ?>" />
                  </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
          </table>
        </div>
      </fieldset>

      <!-- XML Template Section -->
      <fieldset data-cf7index="xml">
        <div class="cf7_row">
          <h2><?php \esc_html_e("XML Template", $this->plugin->get_textdomain()); ?></h2>

          <legend>
            <?php foreach ($mail_tags as $mail_tag) : ?>
              <span class="xml_mailtag mailtag code">[<?php echo \esc_html($mail_tag->name); ?>]</span>
            <?php endforeach; ?>
          </legend>

          <textarea name="template" rows="12" dir="ltr"
            placeholder="<?php echo \esc_attr($xml_placeholder); ?>"><?php echo \esc_textarea($wpcf7_api_data_template); ?></textarea>
        </div>
      </fieldset>

      <!-- JSON Template Section -->
      <fieldset data-cf7index="json">
        <div class="cf7_row">
          <h2><?php \esc_html_e("JSON Template", $this->plugin->get_textdomain()); ?></h2>

          <legend>
            <?php foreach ($mail_tags as $mail_tag) : ?>
                <?php if ($mail_tag->type === "checkbox") : ?>
                    <?php foreach ($mail_tag->values as $checkbox_row) : ?>
                  <span class="xml_mailtag mailtag code">[<?php echo \esc_html("{$mail_tag->name}-{$checkbox_row}"); ?>]</span>
                    <?php endforeach; ?>
                <?php else : ?>
                <span class="xml_mailtag mailtag code">[<?php echo \esc_html($mail_tag->name); ?>]</span>
                <?php endif; ?>
            <?php endforeach; ?>
          </legend>

          <textarea name="json_template" rows="12" dir="ltr"
            placeholder="<?php echo \esc_attr($json_placeholder); ?>"><?php echo \esc_textarea($wpcf7_api_json_data_template); ?></textarea>
        </div>
      </fieldset>

      <!-- Debug Log Section -->
        <?php if ($wpcf7_api_data["debug_log"]) : ?>
        <fieldset>
          <div class="cf7_row">
            <label class="debug-log-trigger">
              + <?php \esc_html_e("DEBUG LOG (View last transmission attempt)", $this->plugin->get_textdomain()); ?>
            </label>
            <div class="debug-log-wrap">
              <h3 class="debug_log_title"><?php \esc_html_e("LAST API CALL", $this->plugin->get_textdomain()); ?></h3>
              <div class="debug_log">
                <h4><?php \esc_html_e("Called URL", $this->plugin->get_textdomain()); ?>:</h4>
                <textarea rows="1"><?php echo \esc_textarea(trim($debug_url)); ?></textarea>

                <h4><?php \esc_html_e("Params", $this->plugin->get_textdomain()); ?>:</h4>
                <textarea rows="10"><?php print_r($debug_params); ?></textarea>

                <h4><?php \esc_html_e("Remote server result", $this->plugin->get_textdomain()); ?>:</h4>
                <textarea rows="10"><?php print_r($debug_result); ?></textarea>

                <h4><?php \esc_html_e("Error logs", $this->plugin->get_textdomain()); ?>:</h4>
                <textarea rows="10"><?php print_r($error_logs); ?></textarea>
              </div>
            </div>
          </div>
        </fieldset>
        <?php endif; ?>
    </div>
        <?php
    }

  /**
   * Save API settings when form is saved
   *
   * @since 1.0.0
   * @param WPCF7_ContactForm $contact_form Contact form object (CF7)
   * @return void
   */
    public function save_contact_form_details(WPCF7_ContactForm $contact_form): void
    {
        $form_id = $contact_form->id();

      // Use the same approach as the old plugin and Paubox - ONLY properties, no post_meta
        $properties = $contact_form->get_properties();

      // Use the same POST field names as the old plugin for backward compatibility
        $properties["wpcf7_api_data"] = $_POST["wpcf7-sf"] ?? [];
        $properties["wpcf7_api_data_map"] = $_POST["qs_wpcf7_api_map"] ?? [];
        $properties["template"] = $_POST["template"] ?? "";
        $properties["json_template"] = stripslashes($_POST["json_template"] ?? "");

      // Set properties using CF7's native method (same as old plugin and Paubox)
        $contact_form->set_properties($properties);
    }

    /**
     * Send form data to API
     *
     * @since 1.0.0
     * @param WPCF7_ContactForm $contact_form Contact form object (CF7)
     * @return void
     */
    public function send_data_to_api(WPCF7_ContactForm $contact_form): void
    {
        $this->clear_error_log($contact_form->id());
        $this->current_form = $contact_form;

        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $form_id = $contact_form->id();

      // Use the same approach as old plugin - try properties first, fallback to post_meta
        $api_data = $contact_form->prop("wpcf7_api_data") ?: \get_post_meta($form_id, "_wpcf7_api_data", true);
        $api_data_map = $contact_form->prop("wpcf7_api_data_map") ?: \get_post_meta($form_id, "_wpcf7_api_data_map", true);
        $api_data_template = $contact_form->prop("template") ?: \get_post_meta($form_id, "_template", true);
        $api_json_template = stripslashes($contact_form->prop("json_template") ?: \get_post_meta($form_id, "_json_template", true));

      // Always enable debug logging (same as old plugin)
        $api_data["debug_log"] = true;

      // Check if form should be sent to API (same logic as old plugin)
        if (empty($api_data["send_to_api"]) || $api_data["send_to_api"] !== "on") {
            return;
        }

        $record_type = $api_data["input_type"] ?? "params";

        if ($record_type === "json") {
            $api_data_template = stripslashes($api_json_template);
        }

        $record = $this->get_record($submission, $api_data_map, $record_type, $api_data_template);
        $record["url"] = $api_data["base_url"];

        if (!empty($record["url"])) {
            \do_action("cf7_api_before_send_to_api", $record);

            $response = $this->send_lead($record, $api_data["debug_log"], $api_data["method"], $record_type);

            if (\is_wp_error($response)) {
                $this->log_error($response, $contact_form->id());
            } else {
                \do_action("cf7_api_after_send_to_api", $record, $response);
            }
        }
    }

  /**
   * Convert form data to API record format
   *
   * @since 1.0.0
   * @param WPCF7_Submission $submission Form submission (CF7 Submission object)
   * @param array            $data_map   Field mapping
   * @param string           $type       Record type (params, xml, json)
   * @param string           $template   Template for xml/json
   * @return array API record data
   */
    private function get_record(WPCF7_Submission $submission, array $data_map, string $type = "params", string $template = ""): array
    {
        $submitted_data = $submission->get_posted_data();
        $record = [];

        if ($type === "params") {
            foreach ($data_map as $form_key => $api_form_key) {
                if (!$api_form_key) {
                    continue;
                }

                if (is_array($api_form_key)) {
                  // Handle checkbox arrays
                    foreach ($submitted_data[$form_key] as $value) {
                        if ($value) {
                                $record["fields"][$api_form_key[$value]] = \apply_filters("cf7_api_set_record_value", $value, $api_form_key);
                        }
                    }
                } else {
                    $value = $submitted_data[$form_key] ?? "";

                  // Flatten radio button values
                    if (is_array($value)) {
                        $value = reset($value);
                    }

                    $record["fields"][$api_form_key] = \apply_filters("cf7_api_set_record_value", $value, $api_form_key);
                }
            }
        } elseif ($type === "xml" || $type === "json") {
            foreach ($data_map as $form_key => $api_form_key) {
                if (is_array($api_form_key)) {
                    // Handle checkbox arrays
                    foreach ($submitted_data[$form_key] as $value) {
                        if ($value) {
                              $value = \apply_filters("cf7_api_set_record_value", $value, $api_form_key);
                              $template = str_replace("[{$form_key}-{$value}]", $value, $template);
                        }
                    }
                } else {
                    $value = $submitted_data[$form_key] ?? "";

                  // Flatten radio button values
                    if (is_array($value)) {
                        $value = reset($value);
                    }

                    $value = \apply_filters("cf7_api_set_record_value", $value, $api_form_key);
                    $template = str_replace("[{$form_key}]", $value, $template);
                }
            }

          // Clean unchanged tags
            foreach ($data_map as $form_key => $api_form_key) {
                if (is_array($api_form_key)) {
                    foreach ($api_form_key as $field_suffix => $api_name) {
                        $template = str_replace("[{$form_key}-{$field_suffix}]", "", $template);
                    }
                }
            }

            $record["fields"] = $template;
        }

        $record = \apply_filters("cf7_api_create_record", $record, $submitted_data, $data_map, $type, $template);

        return $record;
    }

  /**
   * Send lead data to API endpoint
   *
   * @since 1.0.0
   * @param array   $record      Record data
   * @param boolean $debug       Enable debug logging
   * @param string  $method      HTTP method
   * @param string  $record_type Record type
   * @return array|\WP_Error Response data or error
   */
    private function send_lead(array $record, bool $debug = false, string $method = "GET", string $record_type = "params")
    {
        global $wp_version;

        $lead = $record["fields"];
        $url = $record["url"];

        $args = [
        "timeout" => 30,
        "redirection" => 5,
        "httpversion" => "1.1",
        "user-agent" => "WordPress/{$wp_version}; " . \home_url(),
        "blocking" => true,
        "headers" => [],
        "cookies" => [],
        "compress" => false,
        "decompress" => true,
        "sslverify" => true,
        "stream" => false,
        "filename" => null
        ];

        if ($method === "GET" && ($record_type === "params" || $record_type === "json")) {
            if ($record_type === "json") {
                $args["headers"]["Content-Type"] = "application/json";

                $json = $this->parse_json($lead);
                if (\is_wp_error($json)) {
                    return $json;
                }

                $args["body"] = $json;
            } else {
                $lead_string = http_build_query($lead);
                $url = strpos($url, "?") !== false ? "{$url}&{$lead_string}" : "{$url}?{$lead_string}";
            }

            $args = \apply_filters("cf7_api_get_args", $args);
            $url = \apply_filters("cf7_api_get_url", $url, $record);

            $result = \wp_remote_get($url, $args);
        } else {
            $args["body"] = $lead;

            if ($record_type === "xml") {
                $args["headers"]["Content-Type"] = "text/xml";

                $xml = $this->get_xml($lead);
                if (\is_wp_error($xml)) {
                    return $xml;
                }

                $args["body"] = $xml->asXML();
            } elseif ($record_type === "json") {
                $args["headers"]["Content-Type"] = "application/json";

                $json = $this->parse_json($lead);
                if (\is_wp_error($json)) {
                    return $json;
                }

                $args["body"] = $json;
            }

            $args = \apply_filters("cf7_api_post_args", $args);
            $url = \apply_filters("cf7_api_post_url", $url);

            $result = \wp_remote_post($url, $args);
        }

        if ($debug && $this->current_form) {
            \update_post_meta($this->current_form->id(), "cf7_api_debug_url", $record["url"]);
            \update_post_meta($this->current_form->id(), "cf7_api_debug_params", $lead);

            if (\is_wp_error($result)) {
                $result->add_data($args);
            }

            \update_post_meta($this->current_form->id(), "cf7_api_debug_result", $result);
        }

        return \apply_filters("cf7_api_after_send_lead", $result, $record);
    }

  /**
   * Parse JSON string
   *
   * @since 1.0.0
   * @param string $string JSON string
   * @return string|\WP_Error Parsed JSON or error
   */
    private function parse_json(string $string)
    {
        $json = json_decode($string);

        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($json);
        }

        return new \WP_Error("json-error", "Invalid JSON: " . json_last_error_msg());
    }

  /**
   * Parse XML string
   *
   * @since 1.0.0
   * @param string $lead XML string
   * @return \SimpleXMLElement|\WP_Error Parsed XML or error
   */
    private function get_xml(string $lead)
    {
        if (!function_exists("simplexml_load_string")) {
            return new \WP_Error("xml-error", \__("XML functions not available", $this->plugin->get_textdomain()));
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($lead);

        if ($xml === false) {
            return new \WP_Error("xml-error", \__("XML Structure is incorrect", $this->plugin->get_textdomain()));
        }

        return $xml;
    }

  /**
   * Log API error
   *
   * @since 1.0.0
   * @param \WP_Error $wp_error WordPress error
   * @param integer   $form_id  Form ID
   * @return void
   */
    private function log_error(\WP_Error $wp_error, int $form_id): void
    {
        $this->api_errors[] = $wp_error;
        \update_post_meta($form_id, "api_errors", $this->api_errors);

      // Also log to database
        global $wpdb;
        $table_name = "{$wpdb->prefix}cf7_api_logs";

        $wpdb->insert(
            $table_name,
            [
            "form_id" => $form_id,
            "endpoint" => "",
            "method" => "UNKNOWN",
            "status" => "error",
            "request_data" => "",
            "response_data" => $wp_error->get_error_message(),
            "response_code" => 0,
            "error_message" => $wp_error->get_error_message(),
            "execution_time" => 0,
            "created_at" => \current_time("mysql"),
            ],
            ["%d", "%s", "%s", "%s", "%s", "%s", "%d", "%s", "%f", "%s"]
        );
    }

  /**
   * Clear error log for form
   *
   * @since 1.0.0
   * @param integer $form_id Form ID
   * @return void
   */
    private function clear_error_log(int $form_id): void
    {
        \delete_post_meta($form_id, "api_errors");
    }

  /**
   * Enqueue admin scripts
   *
   * @since 1.0.0
   * @param string $hook Current admin page
   * @return void
   */
    public function enqueue_admin_scripts(string $hook): void
    {
        if (strpos($hook, "wpcf7") === false) {
            return;
        }

        $plugin_url = $this->plugin->get_plugin_url();

        \wp_enqueue_style(
            "cf7-api-admin",
            "{$plugin_url}assets/css/admin.css",
            [],
            $this->plugin->get_version()
        );

        \wp_enqueue_script(
            "cf7-api-admin",
            "{$plugin_url}assets/js/admin.js",
            ["jquery"],
            $this->plugin->get_version(),
            true
        );
    }

  /**
   * Handle checkbox values for CF7 API using set_record_value filter
   * Auto-detects checkbox fields based on their values and converts them to "yes"/"no" format
   *
   * @since 1.0.0
   * @param mixed $value          The field value to process
   * @param mixed $api_field_name The API field name (expected to be string for processing)
   * @return mixed The processed value ("yes"/"no" for checkboxes, original value otherwise)
   */
    public function cf7_api_checkbox_value_handler($value, $api_field_name)
    {
      // Check if api_field_name is a string and value is a checkbox-style value
        if (is_string($api_field_name) && $this->is_checkbox_value($value)) {
            return $this->convert_checkbox_value($value);
        }

        return $value;
    }

  /**
   * Handle checkbox values for CF7 API in JSON/XML template mode
   *
   * @since 1.0.0
   * @param array  $record          The record data containing fields and other information
   * @param array  $submitted_data  The submitted form data from Contact Form 7
   * @param array  $qs_cf7_data_map The field mapping configuration from CF7 API plugin
   * @param string $type            The template type (json, xml, etc.)
   * @param string $template        The original template string with placeholders
   * @return array The modified record with processed checkbox values
   */
    public function cf7_api_handle_boolean_checkbox(array $record, array $submitted_data, array $qs_cf7_data_map, string $type, string $template): array
    {
        if ("json" !== $type && "xml" !== $type) {
            return $record;
        }

      // Try to decode and process the template as JSON
        $decoded_template = json_decode($template, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_template)) {
          // Fallback to original template if JSON decode fails
            $record["fields"] = $template;
            return $record;
        }

      // Look for checkbox fields in the mapping and update their values in the decoded template
        foreach ($qs_cf7_data_map as $form_field_name => $field_mapping) {
          // Only process checkbox fields (arrays with exactly one element)
            if (!is_array($field_mapping) || 1 !== count($field_mapping)) {
                continue;
            }

          // This is a checkbox field, check if it was submitted
            $is_checked = isset($submitted_data[$form_field_name]) &&
            is_array($submitted_data[$form_field_name]) &&
            !empty($submitted_data[$form_field_name]) &&
            !empty($submitted_data[$form_field_name][0]) &&
            $submitted_data[$form_field_name][0] !== "false" &&
            $submitted_data[$form_field_name][0] !== false &&
            $submitted_data[$form_field_name][0] !== "0";

            $checkbox_value = $is_checked ? self::CHECKBOX_YES_NO[0] : self::CHECKBOX_YES_NO[1];

          // Look for this field in the decoded template and update its value
            foreach ($decoded_template as $template_key => $template_value) {
              // Check if this template field corresponds to our checkbox field and is empty
                if (
                    ($template_value === "" || $template_value === null) &&
                    $this->fields_match($template_key, $form_field_name)
                ) {
                    $decoded_template[$template_key] = $checkbox_value;
                    break;
                }
            }
        }

      // Update the record with our processed template
        $record["fields"] = json_encode($decoded_template);
        return $record;
    }

  /**
   * Final handler for checkbox values - processes JSON strings for checkbox replacements
   * This is the last filter in the chain, handling empty checkbox fields in JSON format
   *
   * @since 1.0.0
   * @param array $record The record data containing fields and other information
   * @return array The modified record with processed checkbox values
   */
    public function cf7_api_final_checkbox_handler(array $record): array
    {
      // Check if this is JSON data
        if (isset($record["fields"]) && is_string($record["fields"])) {
            $json_data = $record["fields"];

          // Auto-detect and replace empty checkbox fields with "no"
          // Look for patterns like "fieldName":"" and replace with "fieldName":"no"
          // This handles cases where checkboxes were not checked
            $json_data = $this->auto_detect_and_fix_checkbox_fields($json_data);

            $record["fields"] = $json_data;
        }

        return $record;
    }

  /**
   * Auto-detect checkbox fields in JSON and fix empty values
   * Analyzes JSON data to find empty fields that correspond to checkbox submissions
   * and converts them to appropriate "yes"/"no" values based on original form data
   *
   * @since 1.0.0
   * @param string $json_data The JSON string to process
   * @return string The processed JSON string with checkbox values converted
   */
    private function auto_detect_and_fix_checkbox_fields(string $json_data): string
    {
      // Try to decode JSON to work with it
        $decoded = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $json_data;
        }

        $modified = false;

      // Get the current form submission data to check original values
        $submission = WPCF7_Submission::get_instance();
        $submitted_data = $submission ? $submission->get_posted_data() : [];

      // Look for fields with empty string values that might be checkboxes
        foreach ($decoded as $field_name => $field_value) {
            if ($field_value === "" || $field_value === null) {
              // Convert camelCase to kebab-case for field matching
                $kebab_field_name = $this->camel_to_kebab($field_name);

              // Check if this field was submitted and has checkbox-like values
                if (isset($submitted_data[$kebab_field_name]) && is_array($submitted_data[$kebab_field_name])) {
                    $original_value = $submitted_data[$kebab_field_name][0] ?? null;

                    // Check if the original value looks like a checkbox value
                    if ($this->is_checkbox_value($original_value)) {
                    // Determine if checkbox was checked based on original value
                        $new_value = $this->convert_checkbox_value($original_value);

                        $decoded[$field_name] = $new_value;
                        $modified = true;
                    }
                }
            }
        }

        if ($modified) {
            $json_data = json_encode($decoded);
        }

        return $json_data;
    }

  /**
   * Convert camelCase to kebab-case
   * Transforms camelCase field names to kebab-case format for form field matching
   * Example: "infoForLife" becomes "info-for-life"
   *
   * @since 1.0.0
   * @param string $input The camelCase string to convert
   * @return string The converted kebab-case string
   */
    private function camel_to_kebab(string $input): string
    {
        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1-$2", $input));
    }

  /**
   * Convert kebab-case to camelCase
   * Transforms kebab-case field names to camelCase format for form field matching
   * Example: "info-for-life" becomes "infoForLife"
   *
   * @since 1.0.0
   * @param string $input The kebab-case string to convert
   * @return string The converted camelCase string
   */
    private function kebab_to_camel(string $input): string
    {
        return lcfirst(str_replace("-", "", ucwords($input, "-")));
    }

  /**
   * Check if two field names match using various naming conventions
   *
   * @since 1.0.0
   * @param string $template_key    The field name from the template
   * @param string $form_field_name The field name from the form
   * @return boolean True if the fields match, false otherwise
   */
    private function fields_match(string $template_key, string $form_field_name): bool
    {
      // Direct comparison
        if ($template_key === $form_field_name) {
            return true;
        }

      // Compare kebab-case versions
        if ($this->camel_to_kebab($template_key) === $this->camel_to_kebab($form_field_name)) {
            return true;
        }

      // Check if form field matches template key in different cases
        $possible_matches = [
        $form_field_name,
        $this->camel_to_kebab($form_field_name),
        $this->kebab_to_camel($template_key)
        ];

        return in_array($form_field_name, $possible_matches) || in_array($template_key, $possible_matches);
    }

  /**
   * Check if a value looks like a checkbox value
   * Determines if a given value matches typical checkbox submission values
   *
   * @since 1.0.0
   * @param mixed $value The value to check
   * @return boolean True if the value appears to be from a checkbox, false otherwise
   */
    private function is_checkbox_value($value): bool
    {
        return in_array($value, self::CHECKBOX_VALUES, true);
    }

  /**
   * Determine if a checkbox value represents "checked" state
   * Evaluates checkbox values to determine if they represent a checked or unchecked state
   *
   * @since 1.0.0
   * @param mixed $value The checkbox value to evaluate
   * @return boolean True if the value represents a checked checkbox, false otherwise
   */
    private function is_checkbox_checked($value): bool
    {
        return in_array($value, self::CHECKED_VALUES, true);
    }

  /**
   * Convert checkbox value to yes/no format
   *
   * @since 1.0.0
   * @param mixed $value The checkbox value to convert
   * @return string "1" or "0"
   */
    private function convert_checkbox_value($value): string
    {
        return $this->is_checkbox_checked($value) ? self::CHECKBOX_YES_NO[0] : self::CHECKBOX_YES_NO[1];
    }
}
