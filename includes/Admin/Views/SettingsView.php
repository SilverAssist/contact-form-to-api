<?php
/**
 * Settings View
 *
 * Handles HTML rendering for the Settings page.
 * Separates view logic from controller logic.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin\Views
 * @since 1.1.0
 * @version 1.1.3
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin\Views;

use SilverAssist\ContactFormToAPI\Admin\GlobalSettingsController;
use SilverAssist\ContactFormToAPI\Core\Settings;

\defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsView
 *
 * Renders HTML for settings/documentation pages.
 *
 * @since 1.1.0
 */
class SettingsView {

	/**
	 * Render the main settings page
	 *
	 * @param array<int, array{type: string, message: string}> $notices Admin notices to display.
	 * @return void
	 */
	public static function render_page( array $notices = array() ): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="cf7-api-settings-page">
			<?php self::render_accordion_styles(); ?>
			<?php self::render_notices( $notices ); ?>
			<?php self::render_global_settings_section(); ?>
			<?php self::render_how_to_section(); ?>
			<?php self::render_quick_links_section(); ?>
			<?php self::render_status_section(); ?>
		</div>
		<?php
	}

	/**
	 * Render admin notices
	 *
	 * @param array<int, array{type: string, message: string}> $notices Array of notices.
	 * @return void
	 */
	private static function render_notices( array $notices ): void {
		foreach ( $notices as $notice ) {
			$type    = \sanitize_html_class( $notice['type'] );
			$message = $notice['message'];
			?>
			<div class="notice notice-<?php echo \esc_attr( $type ); ?> is-dismissible">
				<p><?php echo \esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Render accordion styles
	 *
	 * @return void
	 */
	private static function render_accordion_styles(): void {
		?>
		<style>
			.cf7-api-accordion-header {
				display: flex;
				align-items: center;
				justify-content: space-between;
				cursor: pointer;
				padding: 12px 15px;
				background: #f6f7f7;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				margin-bottom: 0;
				transition: background-color 0.2s ease;
			}
			.cf7-api-accordion-header:hover {
				background: #f0f0f1;
			}
			.cf7-api-accordion-header h2 {
				margin: 0;
				padding: 0;
				font-size: 14px;
				line-height: 1.4;
			}
			.cf7-api-accordion-toggle {
				font-size: 20px;
				transition: transform 0.2s ease;
			}
			.cf7-api-accordion-content {
				display: none;
				border: 1px solid #c3c4c7;
				border-top: none;
				border-radius: 0 0 4px 4px;
				padding: 20px;
				background: #fff;
			}
			.cf7-api-accordion.is-open .cf7-api-accordion-content {
				display: block;
			}
			.cf7-api-accordion.is-open .cf7-api-accordion-toggle {
				transform: rotate(180deg);
			}
			.cf7-api-accordion.is-open .cf7-api-accordion-header {
				border-radius: 4px 4px 0 0;
			}
		</style>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				var accordionHeaders = document.querySelectorAll('.cf7-api-accordion-header');
				accordionHeaders.forEach(function(header) {
					header.addEventListener('click', function() {
						var accordion = this.closest('.cf7-api-accordion');
						accordion.classList.toggle('is-open');
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Render How To documentation section (accordion, collapsed by default)
	 *
	 * @return void
	 */
	public static function render_how_to_section(): void {
		?>
		<div class="cf7-api-section cf7-api-accordion">
			<div class="cf7-api-accordion-header">
				<h2>
					<span class="dashicons dashicons-book-alt"></span>
					<?php \esc_html_e( 'How to Configure API Integration', 'contact-form-to-api' ); ?>
				</h2>
				<span class="cf7-api-accordion-toggle dashicons dashicons-arrow-down-alt2"></span>
			</div>

			<div class="cf7-api-accordion-content">
				<div class="cf7-api-steps">
					<?php self::render_step_1(); ?>
					<?php self::render_step_2(); ?>
					<?php self::render_step_3(); ?>
					<?php self::render_step_4(); ?>
					<?php self::render_step_5(); ?>
					<?php self::render_step_6(); ?>
					<?php self::render_step_7(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render step 1
	 *
	 * @return void
	 */
	private static function render_step_1(): void {
		?>
		<div class="cf7-api-step">
			<div class="step-number">1</div>
			<div class="step-content">
				<h3><?php \esc_html_e( 'Open your Contact Form 7 form', 'contact-form-to-api' ); ?></h3>
				<p>
					<?php
					printf(
						/* translators: %s: Link to Contact Forms page */
						\esc_html__( 'Go to %s and select the form you want to integrate with an API.', 'contact-form-to-api' ),
						'<a href="' . \esc_url( \admin_url( 'admin.php?page=wpcf7' ) ) . '"><strong>Contact → Contact Forms</strong></a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render step 2
	 *
	 * @return void
	 */
	private static function render_step_2(): void {
		?>
		<div class="cf7-api-step">
			<div class="step-number">2</div>
			<div class="step-content">
				<h3><?php \esc_html_e( 'Navigate to the API Integration tab', 'contact-form-to-api' ); ?></h3>
				<p><?php \esc_html_e( 'In the form editor, click on the "API Integration" tab to access the API settings.', 'contact-form-to-api' ); ?></p>
				<div class="step-tip">
					<span class="dashicons dashicons-lightbulb"></span>
					<?php \esc_html_e( 'Each form can have its own API endpoint and configuration.', 'contact-form-to-api' ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render step 3
	 *
	 * @return void
	 */
	private static function render_step_3(): void {
		?>
		<div class="cf7-api-step">
			<div class="step-number">3</div>
			<div class="step-content">
				<h3><?php \esc_html_e( 'Enable API Integration', 'contact-form-to-api' ); ?></h3>
				<p><?php \esc_html_e( 'Check the "Send to API" checkbox to enable the integration for this form.', 'contact-form-to-api' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render step 4
	 *
	 * @return void
	 */
	private static function render_step_4(): void {
		?>
		<div class="cf7-api-step">
			<div class="step-number">4</div>
			<div class="step-content">
				<h3><?php \esc_html_e( 'Configure your API endpoint', 'contact-form-to-api' ); ?></h3>
				<p><?php \esc_html_e( 'Enter the following settings:', 'contact-form-to-api' ); ?></p>
				<ul class="cf7-api-config-list">
					<li>
						<strong><?php \esc_html_e( 'API URL:', 'contact-form-to-api' ); ?></strong>
						<?php \esc_html_e( 'The full URL of your API endpoint (e.g., https://api.example.com/leads)', 'contact-form-to-api' ); ?>
					</li>
					<li>
						<strong><?php \esc_html_e( 'HTTP Method:', 'contact-form-to-api' ); ?></strong>
						<?php \esc_html_e( 'Usually POST for form submissions', 'contact-form-to-api' ); ?>
					</li>
					<li>
						<strong><?php \esc_html_e( 'Input Type:', 'contact-form-to-api' ); ?></strong>
						<?php \esc_html_e( 'JSON or Form Data, depending on your API requirements', 'contact-form-to-api' ); ?>
					</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Render step 5
	 *
	 * @return void
	 */
	private static function render_step_5(): void {
		?>
		<div class="cf7-api-step">
			<div class="step-number">5</div>
			<div class="step-content">
				<h3><?php \esc_html_e( 'Map your form fields', 'contact-form-to-api' ); ?></h3>
				<p><?php \esc_html_e( 'Use the field mapping section to match your CF7 form fields with the API parameters.', 'contact-form-to-api' ); ?></p>
				<div class="step-example">
					<code>[your-name]</code> → <code>customer_name</code><br>
					<code>[your-email]</code> → <code>email_address</code>
				</div>
				<div class="step-tip">
					<span class="dashicons dashicons-lightbulb"></span>
					<?php \esc_html_e( 'Click on the mail tags to insert them into your field mapping.', 'contact-form-to-api' ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render step 6
	 *
	 * @return void
	 */
	private static function render_step_6(): void {
		?>
		<div class="cf7-api-step">
			<div class="step-number">6</div>
			<div class="step-content">
				<h3><?php \esc_html_e( 'Add authentication (if required)', 'contact-form-to-api' ); ?></h3>
				<p><?php \esc_html_e( 'If your API requires authentication, add the necessary headers:', 'contact-form-to-api' ); ?></p>
				<div class="step-example">
					<code>Authorization: Bearer your-api-token</code><br>
					<code>X-API-Key: your-api-key</code>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render step 7
	 *
	 * @return void
	 */
	private static function render_step_7(): void {
		?>
		<div class="cf7-api-step">
			<div class="step-number">7</div>
			<div class="step-content">
				<h3><?php \esc_html_e( 'Save and test', 'contact-form-to-api' ); ?></h3>
				<p><?php \esc_html_e( 'Save your form and submit a test entry. Check the API Logs to verify the submission was successful.', 'contact-form-to-api' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render global settings section
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private static function render_global_settings_section(): void {
		$settings = Settings::instance();
		?>
		<div class="cf7-api-section">
			<h2>
				<span class="dashicons dashicons-admin-settings"></span>
				<?php \esc_html_e( 'Global Settings', 'contact-form-to-api' ); ?>
			</h2>
			<p class="description">
				<?php \esc_html_e( 'Configure plugin-wide settings for retry limits, sensitive data patterns, logging control, and log retention.', 'contact-form-to-api' ); ?>
			</p>

			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cf7_api_save_global_settings">
				<?php \wp_nonce_field( GlobalSettingsController::get_nonce_action(), GlobalSettingsController::get_nonce_name() ); ?>

				<?php self::render_retry_settings( $settings ); ?>
				<?php self::render_sensitive_patterns_settings( $settings ); ?>
				<?php self::render_logging_settings( $settings ); ?>
				<?php self::render_log_retention_settings( $settings ); ?>

				<?php \submit_button( \__( 'Save Settings', 'contact-form-to-api' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render retry configuration settings
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_retry_settings( Settings $settings ): void {
		$max_manual_retries   = $settings->get_max_manual_retries();
		$max_retries_per_hour = $settings->get_max_retries_per_hour();
		?>
		<h3><?php \esc_html_e( 'Retry Configuration', 'contact-form-to-api' ); ?></h3>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="max_manual_retries">
							<?php \esc_html_e( 'Maximum retries per entry', 'contact-form-to-api' ); ?>
						</label>
					</th>
					<td>
						<input type="number" 
							id="max_manual_retries"
							name="max_manual_retries"
							value="<?php echo \esc_attr( $max_manual_retries ); ?>"
							min="0" 
							max="10" 
							class="small-text">
						<p class="description">
							<?php \esc_html_e( 'Maximum number of times a single failed request can be manually retried.', 'contact-form-to-api' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="max_retries_per_hour">
							<?php \esc_html_e( 'Maximum retries per hour', 'contact-form-to-api' ); ?>
						</label>
					</th>
					<td>
						<input type="number" 
							id="max_retries_per_hour"
							name="max_retries_per_hour"
							value="<?php echo \esc_attr( $max_retries_per_hour ); ?>"
							min="0" 
							max="100" 
							class="small-text">
						<p class="description">
							<?php \esc_html_e( 'Global rate limit for all retry attempts across all requests.', 'contact-form-to-api' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render sensitive data patterns settings
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_sensitive_patterns_settings( Settings $settings ): void {
		$patterns      = $settings->get_sensitive_patterns();
		$patterns_text = \implode( "\n", $patterns );
		?>
		<h3><?php \esc_html_e( 'Sensitive Data Patterns', 'contact-form-to-api' ); ?></h3>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="sensitive_patterns">
							<?php \esc_html_e( 'Field patterns to anonymize', 'contact-form-to-api' ); ?>
						</label>
					</th>
					<td>
						<textarea 
							id="sensitive_patterns"
							name="sensitive_patterns"
							rows="6" 
							cols="50" 
							class="large-text code"><?php echo \esc_textarea( $patterns_text ); ?></textarea>
						<p class="description">
							<?php \esc_html_e( 'Enter one pattern per line. Fields containing these patterns will be redacted (e.g., password, token, secret, api_key).', 'contact-form-to-api' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render logging control settings
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_logging_settings( Settings $settings ): void {
		$logging_enabled = $settings->is_logging_enabled();
		?>
		<h3><?php \esc_html_e( 'Logging', 'contact-form-to-api' ); ?></h3>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<?php \esc_html_e( 'Enable logging', 'contact-form-to-api' ); ?>
					</th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox"
									id="logging_enabled"
									name="logging_enabled"
									value="1"
									<?php \checked( $logging_enabled ); ?>>
								<?php \esc_html_e( 'Enable API request logging', 'contact-form-to-api' ); ?>
							</label>
							<p class="description">
								<?php \esc_html_e( 'When disabled, API requests will not be logged. Useful for GDPR compliance or performance optimization.', 'contact-form-to-api' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render log retention settings
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_log_retention_settings( Settings $settings ): void {
		$retention_days = $settings->get_log_retention_days();
		?>
		<h3><?php \esc_html_e( 'Log Retention', 'contact-form-to-api' ); ?></h3>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="log_retention_days">
							<?php \esc_html_e( 'Delete logs older than', 'contact-form-to-api' ); ?>
						</label>
					</th>
					<td>
						<select id="log_retention_days" name="log_retention_days">
							<option value="0" <?php \selected( $retention_days, 0 ); ?>>
								<?php \esc_html_e( 'Never (keep all logs)', 'contact-form-to-api' ); ?>
							</option>
							<option value="7" <?php \selected( $retention_days, 7 ); ?>>
								<?php
								/* translators: %d: number of days */
								echo \esc_html( \sprintf( \__( '%d days', 'contact-form-to-api' ), 7 ) );
								?>
							</option>
							<option value="14" <?php \selected( $retention_days, 14 ); ?>>
								<?php
								/* translators: %d: number of days */
								echo \esc_html( \sprintf( \__( '%d days', 'contact-form-to-api' ), 14 ) );
								?>
							</option>
							<option value="30" <?php \selected( $retention_days, 30 ); ?>>
								<?php
								/* translators: %d: number of days */
								echo \esc_html( \sprintf( \__( '%d days', 'contact-form-to-api' ), 30 ) );
								?>
							</option>
							<option value="60" <?php \selected( $retention_days, 60 ); ?>>
								<?php
								/* translators: %d: number of days */
								echo \esc_html( \sprintf( \__( '%d days', 'contact-form-to-api' ), 60 ) );
								?>
							</option>
							<option value="90" <?php \selected( $retention_days, 90 ); ?>>
								<?php
								/* translators: %d: number of days */
								echo \esc_html( \sprintf( \__( '%d days', 'contact-form-to-api' ), 90 ) );
								?>
							</option>
						</select>
						<p class="description">
							<?php \esc_html_e( 'Logs older than this period will be automatically deleted daily via WP-Cron.', 'contact-form-to-api' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render available hooks section for developers
	 *
	 * @deprecated 1.2.0 Use the Developer Hooks link in Quick Links instead.
	 * @since 1.1.2
	 * @return void
	 */
	public static function render_hooks_section(): void {
		// Section removed in 1.2.0 - hooks documentation moved to external wiki.
		// Kept for backward compatibility but renders nothing.
	}

	/**
	 * Render quick links section
	 *
	 * @return void
	 */
	public static function render_quick_links_section(): void {
		?>
		<div class="cf7-api-section cf7-api-quick-links">
			<h2>
				<span class="dashicons dashicons-admin-links"></span>
				<?php \esc_html_e( 'Quick Links', 'contact-form-to-api' ); ?>
			</h2>

			<div class="cf7-api-links-grid">
				<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=wpcf7' ) ); ?>" class="cf7-api-link-card">
					<span class="dashicons dashicons-email"></span>
					<span class="link-title"><?php \esc_html_e( 'Contact Forms', 'contact-form-to-api' ); ?></span>
					<span class="link-desc"><?php \esc_html_e( 'Manage your CF7 forms', 'contact-form-to-api' ); ?></span>
				</a>

				<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=cf7-api-logs' ) ); ?>" class="cf7-api-link-card">
					<span class="dashicons dashicons-list-view"></span>
					<span class="link-title"><?php \esc_html_e( 'API Logs', 'contact-form-to-api' ); ?></span>
					<span class="link-desc"><?php \esc_html_e( 'View submission history', 'contact-form-to-api' ); ?></span>
				</a>

				<a href="https://github.com/SilverAssist/contact-form-to-api/wiki/Developer-Hooks" target="_blank" rel="noopener noreferrer" class="cf7-api-link-card">
					<span class="dashicons dashicons-editor-code"></span>
					<span class="link-title"><?php \esc_html_e( 'Developer Hooks', 'contact-form-to-api' ); ?></span>
					<span class="link-desc"><?php \esc_html_e( 'Filters and actions reference', 'contact-form-to-api' ); ?></span>
				</a>

				<a href="https://github.com/SilverAssist/contact-form-to-api/wiki" target="_blank" rel="noopener noreferrer" class="cf7-api-link-card">
					<span class="dashicons dashicons-media-document"></span>
					<span class="link-title"><?php \esc_html_e( 'Documentation', 'contact-form-to-api' ); ?></span>
					<span class="link-desc"><?php \esc_html_e( 'Full plugin documentation', 'contact-form-to-api' ); ?></span>
				</a>

				<a href="https://github.com/SilverAssist/contact-form-to-api/issues" target="_blank" rel="noopener noreferrer" class="cf7-api-link-card">
					<span class="dashicons dashicons-sos"></span>
					<span class="link-title"><?php \esc_html_e( 'Support', 'contact-form-to-api' ); ?></span>
					<span class="link-desc"><?php \esc_html_e( 'Report issues or get help', 'contact-form-to-api' ); ?></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render status section
	 *
	 * @return void
	 */
	public static function render_status_section(): void {
		$cf7_active = \class_exists( 'WPCF7_ContactForm' );
		?>
		<div class="cf7-api-section cf7-api-status">
			<h2>
				<span class="dashicons dashicons-info"></span>
				<?php \esc_html_e( 'Plugin Status', 'contact-form-to-api' ); ?>
			</h2>

			<table class="cf7-api-status-table">
				<tr>
					<th><?php \esc_html_e( 'Plugin Version', 'contact-form-to-api' ); ?></th>
					<td><code><?php echo \esc_html( CF7_API_VERSION ); ?></code></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Contact Form 7', 'contact-form-to-api' ); ?></th>
					<td>
						<?php if ( $cf7_active ) : ?>
							<span class="status-ok">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php \esc_html_e( 'Active', 'contact-form-to-api' ); ?>
							</span>
						<?php else : ?>
							<span class="status-error">
								<span class="dashicons dashicons-warning"></span>
								<?php \esc_html_e( 'Not Active - Plugin requires Contact Form 7', 'contact-form-to-api' ); ?>
							</span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'PHP Version', 'contact-form-to-api' ); ?></th>
					<td><code><?php echo \esc_html( PHP_VERSION ); ?></code></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'WordPress Version', 'contact-form-to-api' ); ?></th>
					<td><code><?php echo \esc_html( \get_bloginfo( 'version' ) ); ?></code></td>
				</tr>
			</table>
		</div>
		<?php
	}
}
