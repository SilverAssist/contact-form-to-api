/**
 * Migration JavaScript
 *
 * Handles AJAX-based migration of legacy unencrypted logs.
 * Provides real-time progress updates and user interaction.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Assets
 * @since 1.3.4
 * @version 2.2.0
 * @author Silver Assist
 */

(function($) {
	'use strict';

	/**
	 * Migration handler class
	 *
	 * @property {boolean} isRunning - Whether migration is currently running
	 * @property {boolean} isDryRun - Whether this is a dry run (preview only)
	 * @property {number} totalCount - Total number of logs to migrate
	 * @property {number} processedCount - Number of logs processed so far
	 * @property {number} batchSize - Number of logs to process per batch
	 * @property {string} nonce - Security nonce for AJAX requests
	 * @property {number} batchDelay - Delay in milliseconds between batches
	 * @property {number|null} reloadTimeout - Timeout reference for page reload
	 */
	class MigrationHandler {
		/**
		 * Constructor - initializes class properties and event handlers
		 */
		constructor() {
			this.isRunning = false;
			this.isDryRun = false;
			this.totalCount = 0;
			this.processedCount = 0;
			this.batchSize = 100;
			this.nonce = '';
			this.batchDelay = 500; // Delay in milliseconds between batches
			this.reloadTimeout = null;

			this.init();
		}

		/**
		 * Initialize event handlers
		 */
		init() {
			const self = this;

			// Start migration button
			$('#cf7-api-start-migration').on('click', function() {
				const nonce = $(this).data('nonce');
				if (!confirm(cf7ApiMigration.i18n.confirmStart)) {
					return;
				}
				self.startMigration(nonce, false);
			});

			// Dry run button
			$('#cf7-api-dry-run').on('click', function() {
				const nonce = $(this).data('nonce');
				self.startMigration(nonce, true);
			});

			// Cancel button
			$('#cf7-api-cancel-migration').on('click', function() {
				if (!confirm(cf7ApiMigration.i18n.confirmCancel)) {
					return;
				}
				self.cancelMigration();
			});
		}

		/**
		 * Start migration process
		 *
		 * @param {string} nonce Security nonce
		 * @param {boolean} dryRun Whether this is a dry run
		 */
		startMigration(nonce, dryRun) {
			const self = this;
			this.nonce = nonce;
			this.isDryRun = dryRun;
			this.isRunning = true;
			this.processedCount = 0;

			// Show progress UI
			this.showProgress();

			// Update status
			this.updateStatus(cf7ApiMigration.i18n.migrationStarted, 0);

			// Start migration via AJAX
			$.ajax({
				url: cf7ApiMigration.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cf7_api_migration_start',
					nonce: nonce,
					dry_run: dryRun ? '1' : '0'
				},
				success: function(response) {
					if (response.success) {
						self.totalCount = response.data.progress.unencrypted;
						self.processBatch();
					} else {
						self.showError(response.data.message);
					}
				},
				error: function(xhr, status, error) {
					var fallback = (cf7ApiMigration.i18n && cf7ApiMigration.i18n.networkError) ? cf7ApiMigration.i18n.networkError : 'Network error';
					var message = error || status || fallback;
					self.showError(cf7ApiMigration.i18n.migrationError + ' ' + message);
				}
			});
		}

		/**
		 * Process a single batch
		 */
		processBatch() {
			const self = this;

			if (!this.isRunning) {
				return;
			}

			// Update status
			this.updateStatus(cf7ApiMigration.i18n.processingBatch);

			// Process batch via AJAX
			$.ajax({
				url: cf7ApiMigration.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cf7_api_migration_batch',
					nonce: this.nonce,
					batch_size: this.batchSize,
					dry_run: this.isDryRun ? '1' : '0'
				},
				success: function(response) {
					if (response.success) {
						const result = response.data.result;
						const progress = response.data.progress;
						const isComplete = response.data.is_complete;

						// Update counts
						self.processedCount += result.processed;

						// Update progress bar
						const percentage = progress.percentage;
						self.updateProgressBar(percentage);

						// Update status text
						const statusText = self.processedCount + ' / ' + self.totalCount + ' ' + cf7ApiMigration.i18n.logsProcessed;
						self.updateStatus(statusText, percentage);

						// Check for errors
						if (result.errors && result.errors.length > 0) {
							console.error('Migration errors:', result.errors);
						}

						// Continue or complete
						if (isComplete) {
							self.completeMigration(result);
						} else {
							// Process next batch
							setTimeout(function() {
								self.processBatch();
							}, self.batchDelay);
						}
					} else {
						self.showError(response.data.message);
					}
				},
				error: function(xhr, status, error) {
					var fallback = (cf7ApiMigration.i18n && cf7ApiMigration.i18n.networkError) ? cf7ApiMigration.i18n.networkError : 'Network error';
					var message = error || status || fallback;
					self.showError(cf7ApiMigration.i18n.migrationError + ' ' + message);
				}
			});
		}

		/**
		 * Cancel migration
		 */
		cancelMigration() {
			const self = this;
			this.isRunning = false;

			$.ajax({
				url: cf7ApiMigration.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cf7_api_migration_cancel',
					nonce: this.nonce
				},
				success: function(response) {
					if (response.success) {
						self.showResult(cf7ApiMigration.i18n.migrationCancelled, 'warning');
					} else {
						self.showError(response.data.message);
					}
				},
				error: function(xhr, status, error) {
					var fallback = (cf7ApiMigration.i18n && cf7ApiMigration.i18n.networkError) ? cf7ApiMigration.i18n.networkError : 'Network error';
					var message = error || status || fallback;
					self.showError(cf7ApiMigration.i18n.migrationError + ' ' + message);
				}
			});
		}

		/**
		 * Complete migration
		 *
		 * @param {Object} result Final batch result
		 */
		completeMigration(result) {
			this.isRunning = false;

			let message = cf7ApiMigration.i18n.migrationComplete;
			if (this.isDryRun) {
				message = this.processedCount + ' ' + cf7ApiMigration.i18n.logsEncrypted;
			} else if (result.failed > 0) {
				message += ' ' + result.failed + ' ' + cf7ApiMigration.i18n.logsFailed;
			}

			this.showResult(message, this.isDryRun ? 'info' : 'success');
		}

		/**
		 * Show progress UI
		 */
		showProgress() {
			$('#cf7-api-start-migration').prop('disabled', true);
			$('#cf7-api-dry-run').prop('disabled', true);
			$('#cf7-api-cancel-migration').show();
			$('#cf7-api-migration-progress').show();
			$('#cf7-api-migration-result').hide();
		}

		/**
		 * Hide progress UI
		 */
		hideProgress() {
			$('#cf7-api-start-migration').prop('disabled', false);
			$('#cf7-api-dry-run').prop('disabled', false);
			$('#cf7-api-cancel-migration').hide();
			$('#cf7-api-migration-progress').hide();
		}

		/**
		 * Update progress bar
		 *
		 * @param {number} percentage Progress percentage (0-100)
		 */
		updateProgressBar(percentage) {
			const $progressBar = $('.cf7-api-progress-bar');
			const $progressText = $('.cf7-api-progress-text');

			$progressBar.css('width', percentage + '%');
			$progressText.text(Math.round(percentage) + '%');
		}

		/**
		 * Update status text
		 *
		 * @param {string} message Status message
		 * @param {number} percentage Optional percentage for details
		 */
		updateStatus(message, percentage) {
			$('#cf7-api-migration-status-text').text(message);
			$('#cf7-api-processed-count').text(this.processedCount);
		}

		/**
		 * Show error message
		 *
		 * @param {string} message Error message
		 */
		showError(message) {
			this.isRunning = false;
			this.hideProgress();

			const html = '<div class="notice notice-error"><p><strong>' + 
				cf7ApiMigration.i18n.migrationError + '</strong> ' + message + '</p></div>';
			$('#cf7-api-migration-result').html(html).show();
		}

		/**
		 * Show result message
		 *
		 * @param {string} message Result message
		 * @param {string} type Message type (success, warning, info)
		 */
		showResult(message, type) {
			const self = this;
			this.hideProgress();

			// Clear any existing reload timeout
			if (this.reloadTimeout) {
				clearTimeout(this.reloadTimeout);
				this.reloadTimeout = null;
			}

			const html = '<div class="notice notice-' + type + '"><p>' + message + '</p></div>';
			$('#cf7-api-migration-result').html(html).show();

			// Reload page after delay if migration was successful
			if (type === 'success') {
				this.reloadTimeout = setTimeout(function() {
					self.reloadTimeout = null;
					window.location.reload();
				}, 2000);
			}
		}
	}

	// Initialize on document ready
	$(document).ready(function() {
		if ($('#cf7-api-start-migration').length > 0) {
			new MigrationHandler();
		}
	});

})(jQuery);
