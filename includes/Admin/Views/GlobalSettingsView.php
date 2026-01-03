<?php
/**
 * Global Settings View
 *
 * Handles HTML rendering for the Global Settings page.
 * Separates view logic from controller logic.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin\Views
 * @since 1.2.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin\Views;

use SilverAssist\ContactFormToAPI\Admin\GlobalSettingsController;
use SilverAssist\ContactFormToAPI\Core\Settings;

\defined( 'ABSPATH' ) || exit;

/**
 * Class GlobalSettingsView
 *
 * Renders HTML for global settings pages.
 *
 * @since 1.2.0
 */
class GlobalSettingsView {

	/**
	 * Render the main settings page
	 *
	 * @param Settings              $settings Settings instance.
	 * @param array<string, string> $notices  Admin notices to display.
	 * @return void
	 */
	public static function render_page( Settings $settings, array $notices = array() ): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap cf7-api-settings-page">
			<h1><?php \esc_html_e( 'Global Settings', 'contact-form-to-api' ); ?></h1>
			<p class="description">
				<?php \esc_html_e( 'Configure plugin-wide settings for retry limits, sensitive data patterns, logging control, and log retention.', 'contact-form-to-api' ); ?>
			</p>

			<?php self::render_notices( $notices ); ?>

			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cf7_api_save_global_settings">
				<?php \wp_nonce_field( GlobalSettingsController::get_nonce_action(), GlobalSettingsController::get_nonce_name() ); ?>

				<?php self::render_retry_configuration_section( $settings ); ?>
				<?php self::render_sensitive_patterns_section( $settings ); ?>
				<?php self::render_logging_section( $settings ); ?>
				<?php self::render_log_retention_section( $settings ); ?>

				<?php \submit_button( \__( 'Save Settings', 'contact-form-to-api' ) ); ?>
			</form>
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
	 * Render retry configuration section
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_retry_configuration_section( Settings $settings ): void {
		$max_manual_retries   = $settings->get_max_manual_retries();
		$max_retries_per_hour = $settings->get_max_retries_per_hour();
		?>
		<div class="cf7-api-section">
			<h2>
				<span class="dashicons dashicons-update"></span>
				<?php \esc_html_e( 'Retry Configuration', 'contact-form-to-api' ); ?>
			</h2>
			<p class="description">
				<?php \esc_html_e( 'Configure retry limits for failed API requests.', 'contact-form-to-api' ); ?>
			</p>

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
		</div>
		<?php
	}

	/**
	 * Render sensitive data patterns section
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_sensitive_patterns_section( Settings $settings ): void {
		$patterns = $settings->get_sensitive_patterns();
		$patterns_text = \implode( "\n", $patterns );
		?>
		<div class="cf7-api-section">
			<h2>
				<span class="dashicons dashicons-lock"></span>
				<?php \esc_html_e( 'Sensitive Data Patterns', 'contact-form-to-api' ); ?>
			</h2>
			<p class="description">
				<?php \esc_html_e( 'Define field name patterns that should be redacted in logs for security and privacy compliance.', 'contact-form-to-api' ); ?>
			</p>

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
								rows="8" 
								cols="50" 
								class="large-text code"><?php echo \esc_textarea( $patterns_text ); ?></textarea>
							<p class="description">
								<?php \esc_html_e( 'Enter one pattern per line. Fields containing these patterns will be redacted (e.g., password, token, secret, api_key).', 'contact-form-to-api' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render logging control section
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_logging_section( Settings $settings ): void {
		$logging_enabled = $settings->is_logging_enabled();
		?>
		<div class="cf7-api-section">
			<h2>
				<span class="dashicons dashicons-visibility"></span>
				<?php \esc_html_e( 'Logging', 'contact-form-to-api' ); ?>
			</h2>
			<p class="description">
				<?php \esc_html_e( 'Control API request logging. Disabling logging can improve performance and reduce database usage.', 'contact-form-to-api' ); ?>
			</p>

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
									<?php \esc_html_e( 'When disabled, API requests will not be logged to the database. Useful for GDPR compliance or performance optimization.', 'contact-form-to-api' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render log retention section
	 *
	 * @param Settings $settings Settings instance.
	 * @return void
	 */
	private static function render_log_retention_section( Settings $settings ): void {
		$retention_days = $settings->get_log_retention_days();
		?>
		<div class="cf7-api-section">
			<h2>
				<span class="dashicons dashicons-trash"></span>
				<?php \esc_html_e( 'Log Retention', 'contact-form-to-api' ); ?>
			</h2>
			<p class="description">
				<?php \esc_html_e( 'Automatically delete old logs to maintain database health and comply with data retention policies.', 'contact-form-to-api' ); ?>
			</p>

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
		</div>
		<?php
	}
}
