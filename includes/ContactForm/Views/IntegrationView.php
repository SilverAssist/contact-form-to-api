<?php
/**
 * Integration Panel View
 *
 * Handles HTML rendering for the CF7 API Integration panel.
 * Separates view logic from controller logic following MVC pattern.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage ContactForm\Views
 * @since 1.1.0
 * @version 1.2.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\ContactForm\Views;

use WPCF7_ContactForm;
use WPCF7_FormTag;

\defined( 'ABSPATH' ) || exit;

/**
 * Class IntegrationView
 *
 * Static view class for rendering CF7 API Integration panel HTML.
 *
 * @since 1.1.0
 */
class IntegrationView {

	/**
	 * Render the complete integration panel
	 *
	 * @since 1.1.0
	 * @param WPCF7_ContactForm                     $post              Contact form object
	 * @param array<string, mixed>                 $wpcf7_api_data    API configuration data
	 * @param array<string, mixed>                 $wpcf7_api_data_map Field mapping data
	 * @param string                               $wpcf7_api_data_template XML template
	 * @param string                               $wpcf7_api_json_data_template JSON template
	 * @param array<string, mixed>                 $retry_config      Retry configuration
	 * @param array<int, WPCF7_FormTag>                   $mail_tags         Available mail tags
	 * @param array<int, array<string, mixed>>     $recent_logs       Recent API logs
	 * @param array<string, int|float>             $statistics        API statistics
	 * @param array<string, mixed>                 $debug_info        Legacy debug information
	 * @param array<int, array<string, string>>    $custom_headers    Custom HTTP headers
	 * @return void
	 */
	public static function render_panel(
		WPCF7_ContactForm $post,
		array $wpcf7_api_data,
		array $wpcf7_api_data_map,
		string $wpcf7_api_data_template,
		string $wpcf7_api_json_data_template,
		array $retry_config,
		array $mail_tags,
		array $recent_logs,
		array $statistics,
		array $debug_info,
		array $custom_headers = array()
	): void {
		$xml_placeholder  = self::get_xml_placeholder();
		$json_placeholder = self::get_json_placeholder();
		?>
		<div id="cf7-api-integration">
			<h2><?php \esc_html_e( 'API Integration', 'contact-form-to-api' ); ?></h2>

			<fieldset>
				<?php \do_action( 'cf7_api_before_base_fields', $post ); ?>

				<?php self::render_base_fields( $wpcf7_api_data ); ?>

				<hr>

				<?php self::render_input_type_field( $wpcf7_api_data ); ?>
				<?php self::render_method_field( $wpcf7_api_data ); ?>

				<hr>

				<?php self::render_retry_config( $retry_config ); ?>

				<?php \do_action( 'cf7_api_after_base_fields', $post ); ?>
			</fieldset>

			<?php self::render_authentication_section( $custom_headers ); ?>

			<?php self::render_params_mapping( $mail_tags, $wpcf7_api_data_map ); ?>

			<?php self::render_xml_template( $mail_tags, $wpcf7_api_data_template, $xml_placeholder ); ?>

			<?php self::render_json_template( $mail_tags, $wpcf7_api_json_data_template, $json_placeholder ); ?>

			<?php if ( $wpcf7_api_data['debug_log'] ) : ?>
				<?php self::render_debug_section( $recent_logs, $statistics, $debug_info ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render base fields (send to API and base URL)
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $wpcf7_api_data API configuration data
	 * @return void
	 */
	private static function render_base_fields( array $wpcf7_api_data ): void {
		?>
		<div class="cf7_row">
			<label for="wpcf7-sf-send-to-api">
				<input type="checkbox" id="wpcf7-sf-send-to-api" name="wpcf7-sf[send_to_api]" <?php \checked( $wpcf7_api_data['send_to_api'], 'on' ); ?> />
				<?php \esc_html_e( 'Send to API?', 'contact-form-to-api' ); ?>
			</label>
		</div>

		<div class="cf7_row">
			<label for="wpcf7-sf-base-url">
				<?php \esc_html_e( 'Base URL', 'contact-form-to-api' ); ?>
				<input type="text" id="wpcf7-sf-base-url" name="wpcf7-sf[base_url]" class="large-text"
					value="<?php echo \esc_attr( $wpcf7_api_data['base_url'] ); ?>" />
			</label>
		</div>
		<?php
	}

	/**
	 * Render input type selector
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $wpcf7_api_data API configuration data
	 * @return void
	 */
	private static function render_input_type_field( array $wpcf7_api_data ): void {
		?>
		<div class="cf7_row">
			<label for="wpcf7-sf-input-type">
				<span class="cf7-label-in"><?php \esc_html_e( 'Input type', 'contact-form-to-api' ); ?></span>
				<select id="wpcf7-sf-input-type" name="wpcf7-sf[input_type]">
					<option value="params" <?php \selected( $wpcf7_api_data['input_type'], 'params' ); ?>>
						<?php \esc_html_e( 'Parameters - GET/POST', 'contact-form-to-api' ); ?>
					</option>
					<option value="xml" <?php \selected( $wpcf7_api_data['input_type'], 'xml' ); ?>>
						<?php \esc_html_e( 'XML', 'contact-form-to-api' ); ?>
					</option>
					<option value="json" <?php \selected( $wpcf7_api_data['input_type'], 'json' ); ?>>
						<?php \esc_html_e( 'JSON', 'contact-form-to-api' ); ?>
					</option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Render HTTP method selector
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $wpcf7_api_data API configuration data
	 * @return void
	 */
	private static function render_method_field( array $wpcf7_api_data ): void {
		?>
		<div class="cf7_row" data-cf7index="params,json">
			<label for="wpcf7-sf-method">
				<span class="cf7-label-in"><?php \esc_html_e( 'Method', 'contact-form-to-api' ); ?></span>
				<select id="wpcf7-sf-method" name="wpcf7-sf[method]">
					<option value="GET" <?php \selected( $wpcf7_api_data['method'], 'GET' ); ?>>GET</option>
					<option value="POST" <?php \selected( $wpcf7_api_data['method'], 'POST' ); ?>>POST</option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Render retry configuration section
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $retry_config Retry configuration
	 * @return void
	 */
	private static function render_retry_config( array $retry_config ): void {
		?>
		<h3><?php \esc_html_e( 'Retry Configuration', 'contact-form-to-api' ); ?></h3>
		<p class="description"><?php \esc_html_e( 'Configure automatic retry behavior for failed API requests.', 'contact-form-to-api' ); ?></p>

		<div class="cf7_row">
			<label for="wpcf7-retry-max-retries">
				<?php \esc_html_e( 'Maximum Retries', 'contact-form-to-api' ); ?>
				<input type="number" id="wpcf7-retry-max-retries" name="retry_config[max_retries]"
					min="0" max="10" value="<?php echo \esc_attr( $retry_config['max_retries'] ); ?>" />
			</label>
			<p class="description"><?php \esc_html_e( 'Number of times to retry a failed request (0-10). Default: 3', 'contact-form-to-api' ); ?></p>
		</div>

		<div class="cf7_row">
			<label for="wpcf7-retry-delay">
				<?php \esc_html_e( 'Retry Delay (seconds)', 'contact-form-to-api' ); ?>
				<input type="number" id="wpcf7-retry-delay" name="retry_config[retry_delay]"
					min="1" max="60" value="<?php echo \esc_attr( $retry_config['retry_delay'] ); ?>" />
			</label>
			<p class="description"><?php \esc_html_e( 'Initial delay between retries in seconds (uses exponential backoff). Default: 2', 'contact-form-to-api' ); ?></p>
		</div>

		<div class="cf7_row">
			<label for="wpcf7-retry-on-timeout">
				<input type="checkbox" id="wpcf7-retry-on-timeout" name="retry_config[retry_on_timeout]" <?php \checked( $retry_config['retry_on_timeout'], true ); ?> />
				<?php \esc_html_e( 'Retry on timeout errors', 'contact-form-to-api' ); ?>
			</label>
			<p class="description"><?php \esc_html_e( 'Automatically retry when API request times out.', 'contact-form-to-api' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render authentication/custom headers section
	 *
	 * @since 1.1.2
	 * @param array<int, array<string, string>> $custom_headers Custom HTTP headers
	 * @return void
	 */
	private static function render_authentication_section( array $custom_headers ): void {
		// Ensure we have at least one empty row for adding new headers.
		if ( empty( $custom_headers ) ) {
			$custom_headers = array(
				array(
					'name'  => '',
					'value' => '',
				),
			);
		}
		?>
		<fieldset class="cf7-api-authentication">
			<h3><?php \esc_html_e( 'Authentication & Custom Headers', 'contact-form-to-api' ); ?></h3>
			<p class="description">
				<?php \esc_html_e( 'Add custom HTTP headers for authentication or other purposes. Common examples:', 'contact-form-to-api' ); ?>
				<code>Authorization: Bearer your-token</code>,
				<code>X-API-Key: your-key</code>
			</p>

			<div class="cf7-api-headers-wrapper">
				<table class="cf7-api-headers-table widefat">
					<thead>
						<tr>
							<th class="header-name"><?php \esc_html_e( 'Header Name', 'contact-form-to-api' ); ?></th>
							<th class="header-value"><?php \esc_html_e( 'Header Value', 'contact-form-to-api' ); ?></th>
							<th class="header-actions"><?php \esc_html_e( 'Actions', 'contact-form-to-api' ); ?></th>
						</tr>
					</thead>
					<tbody id="cf7-api-headers-list">
						<?php foreach ( $custom_headers as $index => $header ) : ?>
							<tr class="cf7-api-header-row">
								<td>
									<input type="text"
										name="custom_headers[<?php echo \esc_attr( $index ); ?>][name]"
										class="cf7-header-name large-text"
										value="<?php echo \esc_attr( $header['name'] ?? '' ); ?>"
										placeholder="<?php \esc_attr_e( 'e.g., Authorization', 'contact-form-to-api' ); ?>" />
								</td>
								<td>
									<input type="text"
										name="custom_headers[<?php echo \esc_attr( $index ); ?>][value]"
										class="cf7-header-value large-text"
										value="<?php echo \esc_attr( $header['value'] ?? '' ); ?>"
										placeholder="<?php \esc_attr_e( 'e.g., Bearer your-api-token', 'contact-form-to-api' ); ?>" />
								</td>
								<td>
									<button type="button" class="button cf7-api-remove-header" title="<?php \esc_attr_e( 'Remove header', 'contact-form-to-api' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<button type="button" class="button cf7-api-add-header" id="cf7-api-add-header">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php \esc_html_e( 'Add Header', 'contact-form-to-api' ); ?>
					</button>
				</p>
			</div>

			<div class="cf7-api-auth-presets">
				<h4><?php \esc_html_e( 'Quick Add Authentication', 'contact-form-to-api' ); ?></h4>
				<p class="description"><?php \esc_html_e( 'Click a preset to add common authentication headers:', 'contact-form-to-api' ); ?></p>
				<p>
					<button type="button" class="button cf7-api-preset-header" data-header-name="Authorization" data-header-value="Bearer ">
						<?php \esc_html_e( 'Bearer Token', 'contact-form-to-api' ); ?>
					</button>
					<button type="button" class="button cf7-api-preset-header" data-header-name="Authorization" data-header-value="Basic ">
						<?php \esc_html_e( 'Basic Auth', 'contact-form-to-api' ); ?>
					</button>
					<button type="button" class="button cf7-api-preset-header" data-header-name="X-API-Key" data-header-value="">
						<?php \esc_html_e( 'API Key', 'contact-form-to-api' ); ?>
					</button>
					<button type="button" class="button cf7-api-preset-header" data-header-name="Content-Type" data-header-value="application/json">
						<?php \esc_html_e( 'Content-Type JSON', 'contact-form-to-api' ); ?>
					</button>
				</p>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render parameters mapping section
	 *
	 * @since 1.1.0
	 * @param array<int, WPCF7_FormTag>   $mail_tags           Available mail tags
	 * @param array<string, mixed> $wpcf7_api_data_map  Field mapping data
	 * @return void
	 */
	private static function render_params_mapping( array $mail_tags, array $wpcf7_api_data_map ): void {
		?>
		<fieldset data-cf7index="params">
			<div class="cf7_row">
				<h2><?php \esc_html_e( 'Form fields', 'contact-form-to-api' ); ?></h2>

				<table>
					<tr>
						<th><?php \esc_html_e( 'Form fields', 'contact-form-to-api' ); ?></th>
						<th><?php \esc_html_e( 'API Key', 'contact-form-to-api' ); ?></th>
						<th></th>
					</tr>
					<?php foreach ( $mail_tags as $mail_tag ) : ?>
						<?php if ( $mail_tag->type === 'checkbox' ) : ?>
							<?php foreach ( $mail_tag->values as $checkbox_row ) : ?>
								<tr>
									<th style="text-align:left;"><?php echo \esc_html( "{$mail_tag->name} ({$checkbox_row})" ); ?></th>
									<td>
										<input type="text"
											name="qs_wpcf7_api_map[<?php echo \esc_attr( $mail_tag->name ); ?>][<?php echo \esc_attr( $checkbox_row ); ?>]"
											class="large-text"
											value="<?php echo \esc_attr( $wpcf7_api_data_map[ $mail_tag->name ][ $checkbox_row ] ?? '' ); ?>" />
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<th style="text-align:left;"><?php echo \esc_html( $mail_tag->name ); ?></th>
								<td>
									<input type="text" name="qs_wpcf7_api_map[<?php echo \esc_attr( $mail_tag->name ); ?>]" class="large-text"
										value="<?php echo \esc_attr( $wpcf7_api_data_map[ $mail_tag->name ] ?? '' ); ?>" />
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</table>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render XML template section
	 *
	 * @since 1.1.0
	 * @param array<int, WPCF7_FormTag> $mail_tags       Available mail tags
	 * @param string             $template        Current template content
	 * @param string             $xml_placeholder Placeholder text
	 * @return void
	 */
	private static function render_xml_template( array $mail_tags, string $template, string $xml_placeholder ): void {
		?>
		<fieldset data-cf7index="xml">
			<div class="cf7_row">
				<h2><?php \esc_html_e( 'XML Template', 'contact-form-to-api' ); ?></h2>

				<legend>
					<?php foreach ( $mail_tags as $mail_tag ) : ?>
						<span class="xml_mailtag mailtag code">[<?php echo \esc_html( $mail_tag->name ); ?>]</span>
					<?php endforeach; ?>
				</legend>

				<textarea name="template" rows="12" dir="ltr"
					placeholder="<?php echo \esc_attr( $xml_placeholder ); ?>"><?php echo \esc_textarea( $template ); ?></textarea>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render JSON template section
	 *
	 * @since 1.1.0
	 * @param array<int, WPCF7_FormTag> $mail_tags        Available mail tags
	 * @param string             $template         Current template content
	 * @param string             $json_placeholder Placeholder text
	 * @return void
	 */
	private static function render_json_template( array $mail_tags, string $template, string $json_placeholder ): void {
		?>
		<fieldset data-cf7index="json">
			<div class="cf7_row">
				<h2><?php \esc_html_e( 'JSON Template', 'contact-form-to-api' ); ?></h2>

				<legend>
					<?php foreach ( $mail_tags as $mail_tag ) : ?>
						<?php if ( $mail_tag->type === 'checkbox' ) : ?>
							<?php foreach ( $mail_tag->values as $checkbox_row ) : ?>
								<span class="xml_mailtag mailtag code">[<?php echo \esc_html( "{$mail_tag->name}-{$checkbox_row}" ); ?>]</span>
							<?php endforeach; ?>
						<?php else : ?>
							<span class="xml_mailtag mailtag code">[<?php echo \esc_html( $mail_tag->name ); ?>]</span>
						<?php endif; ?>
					<?php endforeach; ?>
				</legend>

				<textarea name="json_template" rows="12" dir="ltr"
					placeholder="<?php echo \esc_attr( $json_placeholder ); ?>"><?php echo \esc_textarea( $template ); ?></textarea>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render debug and statistics section
	 *
	 * @since 1.1.0
	 * @param array<int, array<string, mixed>> $recent_logs Recent API logs
	 * @param array<string, int|float>         $statistics  API statistics
	 * @param array<string, mixed>             $debug_info  Legacy debug information
	 * @return void
	 */
	private static function render_debug_section( array $recent_logs, array $statistics, array $debug_info ): void {
		?>
		<fieldset>
			<div class="cf7_row">
				<h3><?php \esc_html_e( 'API Call Logs & Statistics', 'contact-form-to-api' ); ?></h3>

				<?php self::render_statistics( $statistics ); ?>
				<?php self::render_recent_logs( $recent_logs ); ?>
				<?php self::render_legacy_debug( $debug_info ); ?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render statistics table
	 *
	 * @since 1.1.0
	 * @param array<string, int|float> $statistics API statistics
	 * @return void
	 */
	private static function render_statistics( array $statistics ): void {
		if ( empty( $statistics ) || $statistics['total_requests'] <= 0 ) {
			return;
		}
		?>
		<div class="cf7-api-stats">
			<h4><?php \esc_html_e( 'Overall Statistics', 'contact-form-to-api' ); ?></h4>
			<table class="widefat">
				<tr>
					<th><?php \esc_html_e( 'Total Requests', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( $statistics['total_requests'] ); ?></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Successful', 'contact-form-to-api' ); ?></th>
					<td>
						<?php echo \esc_html( $statistics['successful_requests'] ); ?>
						(<?php echo \esc_html( $statistics['total_requests'] > 0 ? \round( ( $statistics['successful_requests'] / $statistics['total_requests'] ) * 100, 1 ) : 0 ); ?>%)
					</td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Failed', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( $statistics['failed_requests'] ); ?></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Avg Response Time', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( \number_format( (float) $statistics['avg_execution_time'], 3 ) ); ?> <?php \esc_html_e( 'seconds', 'contact-form-to-api' ); ?></td>
				</tr>
				<tr>
					<th><?php \esc_html_e( 'Max Retries Used', 'contact-form-to-api' ); ?></th>
					<td><?php echo \esc_html( $statistics['max_retries'] ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render recent logs table
	 *
	 * @since 1.1.0
	 * @param array<int, array<string, mixed>> $recent_logs Recent API logs
	 * @return void
	 */
	private static function render_recent_logs( array $recent_logs ): void {
		?>
		<button type="button" class="debug-log-trigger">
			+ <?php \esc_html_e( 'Recent API Calls (Last 5)', 'contact-form-to-api' ); ?>
		</button>
		<div class="debug-log-wrap">
			<?php if ( ! empty( $recent_logs ) ) : ?>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php \esc_html_e( 'Date', 'contact-form-to-api' ); ?></th>
							<th><?php \esc_html_e( 'Endpoint', 'contact-form-to-api' ); ?></th>
							<th><?php \esc_html_e( 'Method', 'contact-form-to-api' ); ?></th>
							<th><?php \esc_html_e( 'Status', 'contact-form-to-api' ); ?></th>
							<th><?php \esc_html_e( 'Response Code', 'contact-form-to-api' ); ?></th>
							<th><?php \esc_html_e( 'Time (s)', 'contact-form-to-api' ); ?></th>
							<th><?php \esc_html_e( 'Retries', 'contact-form-to-api' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_logs as $log ) : ?>
							<tr>
								<td><?php echo \esc_html( $log['created_at'] ); ?></td>
								<td title="<?php echo \esc_attr( $log['endpoint'] ); ?>">
									<?php echo \esc_html( \strlen( $log['endpoint'] ) > 50 ? \substr( $log['endpoint'], 0, 47 ) . '...' : $log['endpoint'] ); ?>
								</td>
								<td><?php echo \esc_html( $log['method'] ); ?></td>
								<td>
									<span class="cf7-api-status cf7-api-status-<?php echo \esc_attr( $log['status'] ); ?>">
										<?php echo \esc_html( \ucfirst( \str_replace( '_', ' ', $log['status'] ) ) ); ?>
									</span>
								</td>
								<td><?php echo \esc_html( $log['response_code'] ?? '-' ); ?></td>
								<td><?php echo \esc_html( \number_format( (float) $log['execution_time'], 3 ) ); ?></td>
								<td><?php echo \esc_html( $log['retry_count'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php \esc_html_e( 'No API calls logged yet. Submit a form to see logs here.', 'contact-form-to-api' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render legacy debug information
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $debug_info Legacy debug information
	 * @return void
	 */
	private static function render_legacy_debug( array $debug_info ): void {
		?>
		<button type="button" class="debug-log-trigger">
			+ <?php \esc_html_e( 'Legacy Debug Info (Last Transmission)', 'contact-form-to-api' ); ?>
		</button>
		<div class="debug-log-wrap">
			<div class="debug_log">
				<h4><?php \esc_html_e( 'Called URL', 'contact-form-to-api' ); ?>:</h4>
				<textarea rows="1"><?php echo \esc_textarea( \trim( $debug_info['url'] ?? '' ) ); ?></textarea>

				<h4><?php \esc_html_e( 'Params', 'contact-form-to-api' ); ?>:</h4>
				<textarea rows="10"><?php \print_r( $debug_info['params'] ?? '' ); ?></textarea>

				<h4><?php \esc_html_e( 'Remote server result', 'contact-form-to-api' ); ?>:</h4>
				<textarea rows="10"><?php \print_r( $debug_info['result'] ?? '' ); ?></textarea>

				<h4><?php \esc_html_e( 'Error logs', 'contact-form-to-api' ); ?>:</h4>
				<textarea rows="10"><?php \print_r( $debug_info['errors'] ?? '' ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Get XML placeholder text
	 *
	 * @since 1.1.0
	 * @return string
	 */
	private static function get_xml_placeholder(): string {
		return \__(
			'*** THIS IS AN EXAMPLE ** USE YOUR XML ACCORDING TO YOUR API DOCUMENTATION **
<update>
    <user clientid="" username="" auth="" />
    <reports>
        <report tag="NEW">
            <fields>
              <field id="1" name="REFERENCE_ID" value="[your-name]" />
              <field id="2" name="DESCRIPTION" value="[your-email]" />
            </fields>
        </report>
    </reports>
</update>',
			'contact-form-to-api'
		);
	}

	/**
	 * Get JSON placeholder text
	 *
	 * @since 1.1.0
	 * @return string
	 */
	private static function get_json_placeholder(): string {
		return \__(
			'*** THIS IS AN EXAMPLE ** USE YOUR JSON ACCORDING TO YOUR API DOCUMENTATION **
{ "name":"[fullname]", "age":30, "car":null }',
			'contact-form-to-api'
		);
	}
}
