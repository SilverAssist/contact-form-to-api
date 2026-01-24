<?php
/**
 * Migration View
 *
 * Handles HTML rendering for the legacy log migration UI.
 * Separates view logic from controller logic.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Admin\Views
 * @since 1.3.4
 * @version 1.3.13
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Admin\Views;

use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;

\defined( 'ABSPATH' ) || exit;

/**
 * Class MigrationView
 *
 * Renders HTML for migration UI components.
 *
 * @since 1.3.4
 */
class MigrationView {

	/**
	 * Render migration section
	 *
	 * Displays migration UI with progress bar and controls.
	 *
	 * @since 1.3.4
	 * @param array{total: int, encrypted: int, unencrypted: int, percentage: float} $stats Encryption statistics.
	 * @return void
	 */
	public static function render_migration_section( array $stats ): void {
		$unencrypted_count = $stats['unencrypted'];
		$sodium_available  = EncryptionService::is_sodium_available();

		// Don't show migration UI if sodium not available.
		if ( ! $sodium_available ) {
			return;
		}

		?>
		<div class="cf7-api-migration-section">
			<h4>
				<span class="dashicons dashicons-database-import"></span>
				<?php \esc_html_e( 'Legacy Data Migration', 'contact-form-to-api' ); ?>
			</h4>

			<?php if ( $unencrypted_count > 0 ) : ?>
				<div class="cf7-api-migration-warning">
					<span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
					<strong>
						<?php
						echo \esc_html(
							\sprintf(
								/* translators: %d: number of unencrypted logs */
								\_n(
									'%d unencrypted log found',
									'%d unencrypted logs found',
									$unencrypted_count,
									'contact-form-to-api'
								),
								\number_format_i18n( $unencrypted_count )
							)
						);
						?>
					</strong>
					<p class="description">
						<?php \esc_html_e( 'These logs were created before encryption was enabled and contain unencrypted sensitive data.', 'contact-form-to-api' ); ?>
					</p>
				</div>

				<div class="cf7-api-migration-info">
					<p>
						<span class="dashicons dashicons-info" style="color: #2271b1;"></span>
						<?php \esc_html_e( 'The migration tool will encrypt existing logs in batches to avoid timeouts.', 'contact-form-to-api' ); ?>
					</p>
					<p>
						<strong><?php \esc_html_e( 'Recommendation:', 'contact-form-to-api' ); ?></strong>
						<?php \esc_html_e( 'Create a database backup before proceeding.', 'contact-form-to-api' ); ?>
					</p>
				</div>

				<div class="cf7-api-migration-controls">
					<button type="button" 
						id="cf7-api-start-migration" 
						class="button button-primary"
						data-nonce="<?php echo \esc_attr( \wp_create_nonce( 'cf7_api_migration' ) ); ?>">
						<span class="dashicons dashicons-update"></span>
						<?php \esc_html_e( 'Start Migration', 'contact-form-to-api' ); ?>
					</button>

					<button type="button" 
						id="cf7-api-dry-run" 
						class="button button-secondary"
						data-nonce="<?php echo \esc_attr( \wp_create_nonce( 'cf7_api_migration' ) ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<?php \esc_html_e( 'Dry Run (Preview)', 'contact-form-to-api' ); ?>
					</button>

					<button type="button" 
						id="cf7-api-cancel-migration" 
						class="button button-secondary" 
						style="display: none;">
						<span class="dashicons dashicons-no"></span>
						<?php \esc_html_e( 'Cancel', 'contact-form-to-api' ); ?>
					</button>
				</div>

				<div id="cf7-api-migration-progress" style="display: none;">
					<div class="cf7-api-progress-bar-container">
						<div class="cf7-api-progress-bar" style="width: 0%;">
							<span class="cf7-api-progress-text">0%</span>
						</div>
					</div>
					<div class="cf7-api-migration-status">
						<p id="cf7-api-migration-status-text">
							<?php \esc_html_e( 'Preparing migration...', 'contact-form-to-api' ); ?>
						</p>
						<p id="cf7-api-migration-details">
							<span id="cf7-api-processed-count">0</span> / 
							<span id="cf7-api-total-count"><?php echo \esc_html( \number_format_i18n( $unencrypted_count ) ); ?></span>
							<?php \esc_html_e( 'logs processed', 'contact-form-to-api' ); ?>
						</p>
					</div>
				</div>

				<div id="cf7-api-migration-result" style="display: none;"></div>

			<?php else : ?>
				<div class="cf7-api-migration-complete">
					<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
					<strong><?php \esc_html_e( 'All logs are encrypted', 'contact-form-to-api' ); ?></strong>
					<p class="description">
						<?php \esc_html_e( 'No legacy unencrypted logs found. All logs are using encrypted storage.', 'contact-form-to-api' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render dry run results
	 *
	 * Displays preview of what would be migrated.
	 *
	 * @since 1.3.4
	 * @param array{processed: int, success: int, failed: int, remaining: int} $results Dry run results.
	 * @return void
	 */
	public static function render_dry_run_results( array $results ): void {
		?>
		<div class="notice notice-info is-dismissible">
			<h3><?php \esc_html_e( 'Dry Run Results', 'contact-form-to-api' ); ?></h3>
			<p>
				<strong><?php \esc_html_e( 'Preview completed:', 'contact-form-to-api' ); ?></strong>
				<?php
				echo \esc_html(
					\sprintf(
						/* translators: %d: number of logs that would be encrypted */
						\_n(
							'%d log would be encrypted',
							'%d logs would be encrypted',
							$results['success'],
							'contact-form-to-api'
						),
						\number_format_i18n( $results['success'] )
					)
				);
				?>
			</p>
			<p class="description">
				<?php \esc_html_e( 'No changes were made during this preview. Click "Start Migration" to encrypt the logs.', 'contact-form-to-api' ); ?>
			</p>
		</div>
		<?php
	}
}
