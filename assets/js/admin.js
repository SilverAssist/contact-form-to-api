/**
 * Contact Form 7 to API - Admin JavaScript
 *
 * JavaScript functionality for the Contact Form 7 API integration admin interface
 * Migrated from legacy plugin with modern ES6+ standards and double quotes
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version     1.1.1
 * @license Polyform-Noncommercial-1.0.0
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
      this.setupCustomHeaders();
    }

    /**
     * Bind event handlers
     *
     * @since 1.0.0
     * @return {void}
     */
    bindEvents() {
      // Input type change handler - corrected selector
      $(document).on("change", "#wpcf7-sf-input-type", (e) => {
        this.handleInputTypeChange($(e.target).val());
      });

      // Method change handler - corrected selector
      $(document).on("change", "#wpcf7-sf-method", (e) => {
        this.handleMethodChange($(e.target).val());
      });

      // Mail tag insertion
      $(document).on("click", ".xml_mailtag", (e) => {
        this.insertMailTag($(e.target));
      });

      // Debug log toggle
      $(document).on("click", ".debug-log-trigger", (e) => {
        this.toggleDebugLog($(e.currentTarget));
      });

      // API URL validation - corrected selector
      $(document).on("blur", "#wpcf7-sf-base-url", (e) => {
        this.validateUrl($(e.target).val());
      });

      // Custom headers management
      $(document).on("click", "#cf7-api-add-header", () => {
        this.addHeaderRow();
      });

      $(document).on("click", ".cf7-api-remove-header", (e) => {
        this.removeHeaderRow($(e.currentTarget));
      });

      $(document).on("click", ".cf7-api-preset-header", (e) => {
        this.addPresetHeader($(e.currentTarget));
      });

      // Test API connection
      $(document).on("click", "#test-api-connection", () => {
        this.testApiConnection();
      });

      // Send to API checkbox toggle
      $(document).on("change", "#wpcf7-sf-send-to-api", (e) => {
        this.toggleApiSectionVisibility($(e.target).is(":checked"));
      });
    }

    /**
     * Setup input type toggle functionality
     *
     * @since 1.0.0
     * @return {void}
     */
    setupInputTypeToggle() {
      const inputType = $("#wpcf7-sf-input-type").val();
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

      // Show/hide relevant sections - corrected selector
      $("fieldset[data-cf7index]").hide();
      $(`fieldset[data-cf7index="${selectedType}"]`).show();

      // Show/hide method selection for applicable types - corrected selector
      const $methodRow = $(".cf7_row[data-cf7index]");
      if (selectedType === "xml") {
        $methodRow.hide();
        $("#wpcf7-sf-method").val("POST");
      } else {
        $methodRow.show();
      }

      // Update placeholder text based on type
      this.updatePlaceholders(selectedType);
    }

    /**
     * Handle method change
     *
     * @since 1.0.0
     * @param {string} method The selected method
     * @return {void}
     */
    handleMethodChange(method) {
      // Add any specific handling for method changes
      console.log("Method changed to:", method);
    }

    /**
     * Update placeholder text based on input type
     *
     * @since 1.0.0
     * @param {string} type The input type
     * @return {void}
     */
    updatePlaceholders(type) {
      const $baseUrl = $("#wpcf7-sf-base-url");

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
     * @param {jQuery} $trigger The clicked trigger button
     * @return {void}
     */
    toggleDebugLog($trigger) {
      const $wrap = $trigger.next(".debug-log-wrap");

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
     * @param {jQuery} $element The clicked mail tag element
     * @return {void}
     */
    insertMailTag($element) {
      const tagText = $element.text();
      
      // Find the currently active textarea
      let $textarea = null;
      
      // Check which fieldset is currently visible
      const currentInputType = $("#wpcf7-sf-input-type").val();
      
      if (currentInputType === "xml") {
        $textarea = $('textarea[name="template"]');
      } else if (currentInputType === "json") {
        $textarea = $('textarea[name="json_template"]');
      }

      if (!$textarea || $textarea.length === 0) {
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
      const $baseUrl = $("#wpcf7-sf-base-url");
      if ($baseUrl.length && $baseUrl.val()) {
        this.validateUrl($baseUrl.val());
      }
    }

    /**
     * Validate URL format
     *
     * @since 1.0.0
     * @param {string} url The URL to validate
     * @return {void}
     */
    validateUrl(url) {
      const $input = $("#wpcf7-sf-base-url");
      
      // Remove existing validation classes
      $input.removeClass("valid invalid");

      if (!url.trim()) {
        return;
      }

      try {
        new URL(url);
        $input.addClass("valid");
        this.showValidationMessage($input[0], "Valid URL format", "success");
      } catch (e) {
        $input.addClass("invalid");
        this.showValidationMessage($input[0], "Invalid URL format", "error");
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
        // Also trigger the input type change to show the correct section
        this.handleInputTypeChange($("#wpcf7-sf-input-type").val());
      } else {
        $apiSections.slideUp(300);
        $fieldsets.slideUp(300);
      }
    }

    /**
     * Test API connection
     *
     * @since 1.0.0
     * @return {Promise} Test result promise
     */
    async testApiConnection() {
      const $testButton = $("#test-api-connection");
      const originalText = $testButton.text();
      const url = $("#wpcf7-sf-base-url").val();

      if (!url.trim()) {
        this.showApiTestResult("Please enter an API URL first", "error");
        return;
      }

      try {
        // Show loading state
        $testButton.prop("disabled", true).text("Testing...");

        // Simple test - just check if URL is reachable
        const response = await fetch(url, {
          method: "HEAD",
          mode: "no-cors"
        });

        this.showApiTestResult("URL appears to be reachable", "success");

      } catch (error) {
        this.showApiTestResult("Could not reach the URL: " + error.message, "error");
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
      $("#test-api-connection").after($result);

      // Auto-hide after 5 seconds
      setTimeout(() => {
        $result.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    }

    /**
     * Setup custom headers functionality
     *
     * @since 1.1.1
     * @return {void}
     */
    setupCustomHeaders() {
      // Reindex headers on page load to ensure proper ordering
      this.reindexHeaders();
    }

    /**
     * Add a new header row to the custom headers table
     *
     * @since 1.1.1
     * @return {void}
     */
    addHeaderRow() {
      const $tbody = $("#cf7-api-headers-list");
      const currentRows = $tbody.find("tr").length;
      
      const newRow = `
        <tr class="cf7-api-header-row">
          <td>
            <input type="text" 
                   name="custom_headers[${currentRows}][name]" 
                   class="cf7-header-name large-text"
                   placeholder="e.g., Authorization">
          </td>
          <td>
            <input type="text" 
                   name="custom_headers[${currentRows}][value]" 
                   class="cf7-header-value large-text"
                   placeholder="e.g., Bearer your-api-token">
          </td>
          <td>
            <button type="button" class="button cf7-api-remove-header" title="Remove header">
              <span class="dashicons dashicons-trash"></span>
            </button>
          </td>
        </tr>
      `;
      
      $tbody.append(newRow);
      
      // Focus the new name input
      $tbody.find("tr:last .cf7-header-name").focus();
    }

    /**
     * Remove a header row from the custom headers table
     *
     * @since 1.1.1
     * @param {jQuery} $button The clicked remove button
     * @return {void}
     */
    removeHeaderRow($button) {
      const $tbody = $("#cf7-api-headers-list");
      const rowCount = $tbody.find("tr").length;
      
      // Keep at least one row
      if (rowCount <= 1) {
        // Just clear the inputs instead of removing
        const $row = $button.closest("tr");
        $row.find("input").val("");
        return;
      }
      
      // Store reference to this for callback
      const self = this;
      
      // Remove the row
      $button.closest("tr").fadeOut(200, function() {
        $(this).remove();
        // Reindex remaining rows
        self.reindexHeaders();
      });
    }

    /**
     * Add a preset header from the quick add buttons
     *
     * @since 1.1.1
     * @param {jQuery} $button The clicked preset button
     * @return {void}
     */
    addPresetHeader($button) {
      const headerName = $button.data("header-name");
      const headerValue = $button.data("header-value");
      const $tbody = $("#cf7-api-headers-list");
      
      // Find an empty row or create a new one
      let $targetRow = null;
      $tbody.find("tr").each(function() {
        const $nameInput = $(this).find(".cf7-header-name");
        const $valueInput = $(this).find(".cf7-header-value");
        if (!$nameInput.val() && !$valueInput.val()) {
          $targetRow = $(this);
          return false; // break
        }
      });
      
      // If no empty row, add a new one first
      if (!$targetRow) {
        this.addHeaderRow();
        $targetRow = $tbody.find("tr:last");
      }
      
      const $nameInput = $targetRow.find(".cf7-header-name");
      const $valueInput = $targetRow.find(".cf7-header-value");
      
      // Set the header name and value from data attributes
      $nameInput.val(headerName);
      $valueInput.val(headerValue).focus();
      
      // Visual feedback
      $targetRow.css("background-color", "#e7f5ea");
      setTimeout(() => {
        $targetRow.css("background-color", "");
      }, 1000);
    }

    /**
     * Reindex header rows to ensure proper array indexing
     *
     * @since 1.1.1
     * @return {void}
     */
    reindexHeaders() {
      const $tbody = $("#cf7-api-headers-list");
      $tbody.find("tr").each(function(index) {
        $(this).find(".cf7-header-name").attr("name", `custom_headers[${index}][name]`);
        $(this).find(".cf7-header-value").attr("name", `custom_headers[${index}][value]`);
      });
    }

    /**
     * Handle form submission
     *
     * @since 1.0.0
     * @param {Event} e The form submit event
     * @return {boolean} Whether to allow submission
     */
    handleFormSubmission(e) {
      // Basic validation before submission
      const $baseUrl = $("#wpcf7-sf-base-url");
      const $sendToApi = $("#wpcf7-sf-send-to-api");

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
    if ($(".wpcf7-form-table").length || $("#wpcf7-sf-input-type").length) {
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
