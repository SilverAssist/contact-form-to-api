/**
 * Contact Form 7 to API - Admin JavaScript
 *
 * JavaScript functionality for the Contact Form 7 API integration admin interface
 * Migrated from legacy plugin with modern ES6+ standards and double quotes
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.0.0
 * @license GPL-2.0+
 */

(function ($) {
  "use strict";

  /**
   * CF7 API Integration Admin Class
   *
   * Handles all admin interface functionality for the Contact Form 7 API integration
   * Migrated from legacy plugin with modern JavaScript standards
   *
   * @since 1.0.0
   */
  class CF7ApiAdmin {

    /**
     * Constructor - Initialize the admin interface
     *
     * @since 1.0.0
     */
    constructor() {
      this.init();
    }

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     * @return {void}
     */
    init() {
      this.bindEvents();
      this.setupInputTypeToggle();
      this.setupDebugLogToggle();
      this.setupMailTagInsertion();
      this.validateApiUrl();
    }

    /**
     * Bind event handlers
     *
     * @since 1.0.0
     * @return {void}
     */
    bindEvents() {
      // Input type change handler
      $(document).on("change", "#wpcf7-api-input-type", (e) => {
        this.handleInputTypeChange(e.target.value);
      });

      // Debug log toggle handler  
      $(document).on("click", ".debug-log-trigger", (e) => {
        e.preventDefault();
        this.toggleDebugLog();
      });

      // Mail tag insertion handlers
      $(document).on("click", ".xml_mailtag", (e) => {
        this.insertMailTag(e.target, "template");
      });

      $(document).on("click", ".json_mailtag", (e) => {
        this.insertMailTag(e.target, "json_template");
      });

      // Form validation handlers
      $(document).on("blur", "#wpcf7-api-base-url", (e) => {
        this.validateUrl(e.target);
      });

      // Send to API checkbox handler
      $(document).on("change", "#wpcf7-api-send-to-api", (e) => {
        this.toggleApiSectionVisibility(e.target.checked);
      });

      // Auto-save functionality
      $(document).on("change", "input, select, textarea", (e) => {
        if ($(e.target).closest("#cf7-api-integration").length) {
          this.markFormAsChanged();
        }
      });
    }

    /**
     * Setup input type toggle functionality
     *
     * @since 1.0.0
     * @return {void}
     */
    setupInputTypeToggle() {
      const inputType = $("#wpcf7-api-input-type").val();
      if (inputType) {
        this.handleInputTypeChange(inputType);
      }
    }

    /**
     * Handle input type change
     *
     * @since 1.0.0
     * @param {string} selectedType The selected input type
     * @return {void}
     */
    handleInputTypeChange(selectedType) {
      // Remove existing body classes
      $("body").removeClass("cf7-input-type-params cf7-input-type-xml cf7-input-type-json");

      // Add new body class
      $("body").addClass(`cf7-input-type-${selectedType}`);

      // Show/hide relevant sections
      $("fieldset[data-cf7index]").hide();
      $(`fieldset[data-cf7index="${selectedType}"]`).show();

      // Show/hide method selection for applicable types
      const $methodRow = $(".cf7_row[data-cf7index]");
      if (selectedType === "xml") {
        $methodRow.hide();
        $("#wpcf7-api-method").val("POST");
      } else {
        $methodRow.show();
      }

      // Update placeholder text based on type
      this.updatePlaceholders(selectedType);
    }

    /**
     * Update placeholder text based on input type
     *
     * @since 1.0.0
     * @param {string} type The input type
     * @return {void}
     */
    updatePlaceholders(type) {
      const $baseUrl = $("#wpcf7-api-base-url");

      switch (type) {
        case "params":
          $baseUrl.attr("placeholder", "https://api.example.com/endpoint");
          break;
        case "xml":
          $baseUrl.attr("placeholder", "https://api.example.com/xml-endpoint");
          break;
        case "json":
          $baseUrl.attr("placeholder", "https://api.example.com/json-endpoint");
          break;
      }
    }

    /**
     * Setup debug log toggle functionality
     *
     * @since 1.0.0
     * @return {void}
     */
    setupDebugLogToggle() {
      // Initially hide debug log
      $(".debug-log-wrap").hide();
    }

    /**
     * Toggle debug log visibility
     *
     * @since 1.0.0
     * @return {void}
     */
    toggleDebugLog() {
      const $trigger = $(".debug-log-trigger");
      const $wrap = $(".debug-log-wrap");

      if ($wrap.is(":visible")) {
        $wrap.slideUp(300);
        $trigger.text($trigger.text().replace("- ", "+ "));
      } else {
        $wrap.slideDown(300);
        $trigger.text($trigger.text().replace("+ ", "- "));
      }
    }

    /**
     * Setup mail tag insertion functionality
     *
     * @since 1.0.0
     * @return {void}
     */
    setupMailTagInsertion() {
      // Add click handlers are already bound in bindEvents()
      // This method can be used for additional setup if needed
    }

    /**
     * Insert mail tag into template
     *
     * @since 1.0.0
     * @param {HTMLElement} element The clicked mail tag element
     * @param {string} targetField The target textarea field name
     * @return {void}
     */
    insertMailTag(element, targetField) {
      const $element = $(element);
      const tagText = $element.text();
      const $textarea = $(`textarea[name="${targetField}"]`);

      if ($textarea.length === 0) {
        return;
      }

      const textarea = $textarea[0];
      const startPos = textarea.selectionStart;
      const endPos = textarea.selectionEnd;
      const textBefore = textarea.value.substring(0, startPos);
      const textAfter = textarea.value.substring(endPos);

      // Insert the tag at cursor position
      textarea.value = textBefore + tagText + textAfter;

      // Move cursor to end of inserted tag
      const newPos = startPos + tagText.length;
      textarea.setSelectionRange(newPos, newPos);

      // Focus the textarea
      textarea.focus();

      // Trigger change event
      $textarea.trigger("change");

      // Visual feedback
      $element.addClass("inserted");
      setTimeout(() => {
        $element.removeClass("inserted");
      }, 500);
    }

    /**
     * Validate API URL
     *
     * @since 1.0.0
     * @return {void}
     */
    validateApiUrl() {
      const $baseUrl = $("#wpcf7-api-base-url");
      if ($baseUrl.length && $baseUrl.val()) {
        this.validateUrl($baseUrl[0]);
      }
    }

    /**
     * Validate URL format
     *
     * @since 1.0.0
     * @param {HTMLElement} input The URL input element
     * @return {void}
     */
    validateUrl(input) {
      const $input = $(input);
      const url = $input.val().trim();

      // Remove existing validation classes
      $input.removeClass("valid invalid");

      if (!url) {
        return;
      }

      try {
        new URL(url);
        $input.addClass("valid");
        this.showValidationMessage(input, "Valid URL format", "success");
      } catch (e) {
        $input.addClass("invalid");
        this.showValidationMessage(input, "Invalid URL format", "error");
      }
    }

    /**
     * Show validation message
     *
     * @since 1.0.0
     * @param {HTMLElement} input The input element
     * @param {string} message The validation message
     * @param {string} type The message type (success, error, warning)
     * @return {void}
     */
    showValidationMessage(input, message, type) {
      const $input = $(input);

      // Remove existing validation message
      $input.next(".validation-message").remove();

      // Create new validation message
      const $message = $(`<div class="validation-message ${type}">${message}</div>`);

      // Insert after input
      $input.after($message);

      // Auto-hide after 3 seconds
      setTimeout(() => {
        $message.fadeOut(300, function () {
          $(this).remove();
        });
      }, 3000);
    }

    /**
     * Toggle API section visibility based on send to API checkbox
     *
     * @since 1.0.0
     * @param {boolean} isChecked Whether the checkbox is checked
     * @return {void}
     */
    toggleApiSectionVisibility(isChecked) {
      const $apiSections = $(".cf7_row").not(":first");
      const $fieldsets = $("fieldset[data-cf7index]");

      if (isChecked) {
        $apiSections.slideDown(300);
        $fieldsets.slideDown(300);
      } else {
        $apiSections.slideUp(300);
        $fieldsets.slideUp(300);
      }
    }

    /**
     * Mark form as changed
     *
     * @since 1.0.0
     * @return {void}
     */
    markFormAsChanged() {
      if (!window.cf7ApiFormChanged) {
        window.cf7ApiFormChanged = true;

        // Add visual indicator
        const $saveButton = $("#publishing-action .button-primary");
        if ($saveButton.length) {
          $saveButton.addClass("cf7-api-needs-save");
        }
      }
    }

    /**
     * Test API connection
     *
     * @since 1.0.0
     * @param {string} url The API URL to test
     * @param {object} settings The API settings
     * @return {Promise} Test result promise
     */
    async testApiConnection(url, settings) {
      const $testButton = $(".cf7-api-test-connection");
      const originalText = $testButton.text();

      try {
        // Show loading state
        $testButton.prop("disabled", true).text("Testing...");

        const response = await $.ajax({
          url: cf7_api_admin.ajax_url,
          method: "POST",
          data: {
            action: "cf7_api_test_connection",
            nonce: cf7_api_admin.nonce,
            api_url: url,
            settings: settings
          }
        });

        if (response.success) {
          this.showApiTestResult("Connection successful!", "success");
        } else {
          this.showApiTestResult(response.data.message || "Connection failed", "error");
        }

      } catch (error) {
        this.showApiTestResult("Connection test failed: " + error.responseText, "error");
      } finally {
        // Restore button state
        $testButton.prop("disabled", false).text(originalText);
      }
    }

    /**
     * Show API test result
     *
     * @since 1.0.0
     * @param {string} message The result message
     * @param {string} type The result type (success, error)
     * @return {void}
     */
    showApiTestResult(message, type) {
      // Remove existing result
      $(".cf7-api-test-result").remove();

      // Create result element
      const $result = $(`<div class="cf7-api-test-result cf7-api-notice ${type}">${message}</div>`);

      // Insert after test button
      $(".cf7-api-test-connection").after($result);

      // Auto-hide after 5 seconds
      setTimeout(() => {
        $result.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    }

    /**
     * Handle form submission
     *
     * @since 1.0.0
     * @param {Event} e The form submit event
     * @return {void}
     */
    handleFormSubmission(e) {
      // Basic validation before submission
      const $baseUrl = $("#wpcf7-api-base-url");
      const $sendToApi = $("#wpcf7-api-send-to-api");

      if ($sendToApi.is(":checked") && !$baseUrl.val().trim()) {
        e.preventDefault();
        this.showValidationMessage($baseUrl[0], "API URL is required when 'Send to API' is enabled", "error");
        $baseUrl.focus();
        return false;
      }

      return true;
    }
  }

  /**
   * Initialize admin functionality when document is ready
   *
   * @since 1.0.0
   */
  $(document).ready(() => {
    // Only initialize on CF7 admin pages
    if ($(".wpcf7-form-table").length || $("#cf7-api-integration").length) {
      new CF7ApiAdmin();
    }
  });

  /**
   * Add custom CSS for validation states
   *
   * @since 1.0.0
   */
  $(() => {
    const validationCSS = `
            <style>
                .cf7_row input.valid {
                    border-color: #00a32a;
                }
                .cf7_row input.invalid {
                    border-color: #d63638;
                }
                .validation-message {
                    display: block;
                    margin-top: 5px;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-size: 13px;
                }
                .validation-message.success {
                    background: #d1f2eb;
                    color: #155724;
                    border: 1px solid #00a32a;
                }
                .validation-message.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #d63638;
                }
                .validation-message.warning {
                    background: #fff3cd;
                    color: #856404;
                    border: 1px solid #dba617;
                }
                .xml_mailtag.inserted, .json_mailtag.inserted {
                    background: #00a32a !important;
                    color: white !important;
                    transform: scale(1.05);
                    transition: all 0.3s ease;
                }
                .cf7-api-needs-save {
                    background: #dba617 !important;
                    border-color: #dba617 !important;
                }
                .cf7-api-test-result {
                    margin-top: 10px;
                    padding: 8px 12px;
                    border-radius: 4px;
                }
                .cf7-api-test-result.success {
                    background: #d1f2eb;
                    color: #155724;
                    border: 1px solid #00a32a;
                }
                .cf7-api-test-result.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #d63638;
                }
            </style>
        `;
    $("head").append(validationCSS);
  });

})(jQuery);
