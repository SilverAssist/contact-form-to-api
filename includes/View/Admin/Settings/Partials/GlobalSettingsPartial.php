<?php
/**
 * Global Settings Partial View
 *
 * Renders the global settings form for the plugin.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage View\Admin\Settings\Partials
 * @since 2.0.0
 * @version 2.4.1
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\View\Admin\Settings\Partials;

use SilverAssist\ContactFormToAPI\Config\Settings;
use SilverAssist\ContactFormToAPI\Controller\Admin\SettingsController;
use SilverAssist\ContactFormToAPI\View\Admin\Settings\SettingsView;

\defined( 'ABSPATH' ) || exit;

/**
 * Class GlobalSettingsPartial
 *
 * Handles rendering of the global settings form.
 *
 * @since 2.0.0
 */
class GlobalSettingsPartial {

	/**
	 * Render global settings section
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function render(): void {
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
				<?php \wp_nonce_field( SettingsController::get_nonce_action(), SettingsController::get_nonce_name() ); ?>

				<?php
				// Call methods from SettingsView for backward compatibility.
				// These could be further extracted into sub-partials in future iterations.
				SettingsView::render_retry_settings_partial( $settings );
				SettingsView::render_sensitive_patterns_partial( $settings );
				SettingsView::render_logging_settings_partial( $settings );
				SettingsView::render_log_retention_partial( $settings );
				SettingsView::render_encryption_settings_partial( $settings );
				SettingsView::render_email_alerts_partial( $settings );
				?>

				<?php \submit_button( \__( 'Save Settings', 'contact-form-to-api' ) ); ?>
			</form>
		</div>
		<?php
	}
}
