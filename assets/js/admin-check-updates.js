/**
 * Contact Form 7 to API - Check Updates JavaScript
 *
 * Handles the "Check Updates" button functionality in Settings Hub.
 * Called when user clicks the "Check Updates" button on the plugin card.
 *
 * @package ContactFormToAPI
 * @since 1.1.0
 * @author Silver Assist
 * @version 1.0.0
 * @license Polyform-Noncommercial-1.0.0
 */

/**
 * Main function to check for updates
 * This function is called by the Settings Hub action button
 */
function cf7ApiCheckUpdates() {
	// Check if jQuery and data are available
	if (
		typeof jQuery === "undefined" ||
		typeof cf7ApiCheckUpdatesData === "undefined"
	) {
		console.error("CF7 to API: jQuery or update data not available");
		return false;
	}

	var $ = jQuery;
	var data = cf7ApiCheckUpdatesData;

	// Find the check updates button - try multiple selectors for compatibility
	var checkUpdatesBtn = $(
		'.silverassist-plugin-card[data-plugin="contact-form-to-api"] .button'
	);

	// Fallback selectors if the main one doesn't work
	if (!checkUpdatesBtn.length) {
		checkUpdatesBtn = $(
			'[data-plugin="contact-form-to-api"] .button:contains("Check Updates")'
		);
	}
	if (!checkUpdatesBtn.length) {
		checkUpdatesBtn = $('.button:contains("Check Updates")');
	}

	if (!checkUpdatesBtn.length) {
		console.error("CF7 to API: Check updates button not found");
		return false;
	}

	// Store original button state
	var originalText = checkUpdatesBtn.text();
	var originalClass = checkUpdatesBtn.attr("class");

	// Update button to show checking state
	checkUpdatesBtn
		.text(data.strings.checking)
		.prop("disabled", true)
		.removeClass("button-primary")
		.addClass("button-secondary");

	// Perform AJAX call to check for updates
	$.post(data.ajaxurl, {
		action: data.action,
		nonce: data.nonce,
	})
		.done(function (response) {
			if (response.success) {
				if (response.data && response.data.update_available) {
					// Update available - show notice and redirect
					showNotice(
						data.strings.updateAvailable.replace(
							"%s",
							response.data.new_version
						),
						"warning"
					);
					setTimeout(function () {
						window.location.href = data.updateUrl;
					}, 2000);
				} else {
					// Up to date - show success notice
					showNotice(data.strings.upToDate, "success");
					resetButton();
				}
			} else {
				// API error - show error notice
				showNotice(data.strings.checkError, "error");
				resetButton();
			}
		})
		.fail(function (xhr, status, error) {
			// Connection error
			console.error("CF7 to API update check failed:", status, error);
			showNotice(data.strings.connectError, "error");
			resetButton();
		});

	/**
	 * Reset button to original state
	 */
	function resetButton() {
		checkUpdatesBtn
			.text(originalText)
			.prop("disabled", false)
			.attr("class", originalClass);
	}

	/**
	 * Show WordPress admin notice
	 *
	 * @param {string} message Notice message
	 * @param {string} type Notice type (success, warning, error)
	 */
	function showNotice(message, type) {
		var noticeClass =
			type === "success"
				? "notice-success"
				: type === "warning"
					? "notice-warning"
					: "notice-error";

		var $notice = $(
			'<div class="notice ' +
				noticeClass +
				' is-dismissible cf7-api-update-notice"><p>' +
				escapeHtml(message) +
				"</p><button type=\"button\" class=\"notice-dismiss\"><span class=\"screen-reader-text\">Dismiss this notice.</span></button></div>"
		);

		// Remove any existing notices from this script
		$(".cf7-api-update-notice").remove();

		// Insert notice after page title or at top of wrap
		var $target = $(".wrap > h1").first();
		if ($target.length) {
			$target.after($notice);
		} else {
			$(".wrap").first().prepend($notice);
		}

		// Bind dismiss button
		$notice.find(".notice-dismiss").on("click", function () {
			$notice.fadeOut(200, function () {
				$(this).remove();
			});
		});

		// Auto-dismiss success notices after 5 seconds
		if (type === "success") {
			setTimeout(function () {
				$notice.fadeOut(300, function () {
					$(this).remove();
				});
			}, 5000);
		}
	}

	/**
	 * Escape HTML entities
	 *
	 * @param {string} text Text to escape
	 * @returns {string} Escaped text
	 */
	function escapeHtml(text) {
		var div = document.createElement("div");
		div.textContent = text;
		return div.innerHTML;
	}

	return false;
}

/**
 * Initialize when document is ready
 */
jQuery(document).ready(function ($) {
	// Make the function globally available
	window.cf7ApiCheckUpdates = cf7ApiCheckUpdates;
});
