/**
 * API Log Admin JavaScript
 *
 * Client-side functionality for API logs admin interface.
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 */

(function ($) {
	"use strict";

	/**
	 * Initialize API Log Admin
	 */
	const CF7ApiLogAdmin = {
		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
			this.initTooltips();
			this.initDateFilter();
		},

		/**
		 * Bind events
		 */
		bindEvents: function () {
			// Confirm delete action
			$(".submitdelete").on("click", function (e) {
				if (!confirm(window.cf7ApiAdmin?.confirmDelete || "Are you sure you want to delete this log entry?")) {
					e.preventDefault();
					return false;
				}
			});

			// Confirm bulk delete
			$("#doaction, #doaction2").on("click", function (e) {
				const action = $(this).siblings("select").val();
				if (action === "delete") {
					const checked = $("input[name='log[]']:checked").length;
					if (checked === 0) {
						e.preventDefault();
						alert(window.cf7ApiAdmin?.selectItems || "Please select at least one item.");
						return false;
					}
					if (!confirm(window.cf7ApiAdmin?.confirmBulkDelete || "Are you sure you want to delete the selected log entries?")) {
						e.preventDefault();
						return false;
					}
				}
			});

			// Auto-refresh stats (optional)
			if (window.cf7ApiAdmin?.autoRefresh) {
				setInterval(function () {
					// Could implement AJAX refresh here
				}, 30000);
			}
		},

		/**
		 * Initialize tooltips
		 */
		initTooltips: function () {
			// Add tooltips to truncated endpoints
			$(".column-endpoint a").each(function () {
				const fullUrl = $(this).attr("href");
				if (fullUrl && fullUrl.includes("log_id=")) {
					// Could add title attribute with full endpoint
				}
			});
		},

		/**
		 * Initialize date filter functionality
		 */
		initDateFilter: function () {
			const $dateFilter = $("#date_filter");
			const $customDateRange = $("#custom-date-range");
			const $dateStart = $("#date_start");
			const $dateEnd = $("#date_end");

			// Toggle custom date range visibility
			$dateFilter.on("change", function () {
				const value = $(this).val();
				if (value === "custom") {
					$customDateRange.slideDown(200);
					$dateStart.focus();
				} else {
					$customDateRange.slideUp(200);
					// Clear custom date inputs when switching to preset filter
					if (value !== "") {
						$("#cf7-date-filter-form").submit();
					}
				}
			});

			// Validate date inputs
			$dateStart.on("change", function () {
				const startDate = $(this).val();
				const endDate = $dateEnd.val();

				if (startDate && endDate && startDate > endDate) {
					alert("Start date must be before or equal to end date.");
					$(this).val("");
					return;
				}
			});

			$dateEnd.on("change", function () {
				const startDate = $dateStart.val();
				const endDate = $(this).val();

				if (startDate && endDate && startDate > endDate) {
					alert("End date must be after or equal to start date.");
					$(this).val("");
					return;
				}
			});
		},
	};

	// Initialize on document ready
	$(document).ready(function () {
		CF7ApiLogAdmin.init();
	});
})(jQuery);
