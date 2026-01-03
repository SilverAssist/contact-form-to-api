/**
 * Settings Page JavaScript
 *
 * Handles interactive functionality for the Global Settings page.
 *
 * @package SilverAssist\ContactFormToAPI
 * @since 1.2.0
 * @version 1.2.0
 */

(function ($) {
	'use strict';

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		initTestEmailButton();
	});

	/**
	 * Initialize test email button functionality
	 *
	 * @since 1.2.0
	 * @return {void}
	 */
	function initTestEmailButton() {
		const button = $('#cf7-api-send-test-email');
		const resultSpan = $('#cf7-api-test-email-result');
		const recipientField = $('#alert_recipients');

		if (!button.length || !recipientField.length) {
			return;
		}

		button.on('click', function (e) {
			e.preventDefault();

			// Get recipient email.
			const recipient = recipientField.val().trim();

			// Use first email if multiple provided.
			const firstEmail = recipient.split(',')[0].trim();

			if (!firstEmail) {
				showResult(resultSpan, 'error', 'Please enter a recipient email address.');
				return;
			}

			// Disable button and show loading state.
			button.prop('disabled', true);
			button.text('Sending...');
			resultSpan.html('');

			// Send AJAX request.
			$.ajax({
				url: cf7ApiSettings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cf7_api_send_test_email',
					nonce: cf7ApiSettings.nonce,
					recipient: firstEmail
				},
				success: function (response) {
					if (response.success) {
						showResult(resultSpan, 'success', response.data.message);
					} else {
						showResult(resultSpan, 'error', response.data.message);
					}
				},
				error: function () {
					showResult(resultSpan, 'error', 'An error occurred while sending the test email.');
				},
				complete: function () {
					// Re-enable button and restore text.
					button.prop('disabled', false);
					button.text('Send Test Email');
				}
			});
		});
	}

	/**
	 * Show result message
	 *
	 * @since 1.2.0
	 * @param {jQuery} element Result element.
	 * @param {string} type    Message type (success or error).
	 * @param {string} message Message text.
	 * @return {void}
	 */
	function showResult(element, type, message) {
		const icon = type === 'success' ? '✓' : '✗';
		const color = type === 'success' ? '#46b450' : '#d63638';

		element.html(
			'<span style="color: ' + color + '; font-weight: bold;">' +
			icon + ' ' + message +
			'</span>'
		);

		// Auto-hide after 5 seconds.
		setTimeout(function () {
			element.fadeOut(function () {
				element.html('').show();
			});
		}, 5000);
	}

})(jQuery);
