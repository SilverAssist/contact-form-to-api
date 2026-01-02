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
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin\Views;

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
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="cf7-api-settings-page">
			<?php self::render_how_to_section(); ?>
			<?php self::render_quick_links_section(); ?>
			<?php self::render_status_section(); ?>
		</div>
		<?php
	}

	/**
	 * Render How To documentation section
	 *
	 * @return void
	 */
	public static function render_how_to_section(): void {
		?>
		<div class="cf7-api-section cf7-api-how-to">
			<h2>
				<span class="dashicons dashicons-book-alt"></span>
				<?php \esc_html_e( 'How to Configure API Integration', 'contact-form-to-api' ); ?>
			</h2>

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
