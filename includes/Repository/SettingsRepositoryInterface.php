<?php
/**
 * SettingsRepositoryInterface
 *
 * Interface for settings data access operations.
 * Defines contract for configuration persistence layer.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Repository
 * @since      2.0.0
 * @version    2.0.0
 * @author     Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Repository;

use SilverAssist\ContactFormToAPI\Model\FormSettings;

\defined( 'ABSPATH' ) || exit;

/**
 * SettingsRepositoryInterface
 *
 * Defines data access operations for settings.
 * Part of Phase 1 foundation for 2.0.0 architecture refactoring.
 *
 * @since 2.0.0
 */
interface SettingsRepositoryInterface {

	/**
	 * Get global plugin setting
	 *
	 * @since 2.0.0
	 *
	 * @param string $key           Setting key.
	 * @param mixed  $default_value Default value if not set.
	 * @return mixed Setting value.
	 */
	public function get( string $key, $default_value = null );

	/**
	 * Set global plugin setting
	 *
	 * @since 2.0.0
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value ): bool;

	/**
	 * Get form settings
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 * @return FormSettings Form settings.
	 */
	public function get_form_settings( int $form_id ): FormSettings;

	/**
	 * Save form settings
	 *
	 * @since 2.0.0
	 *
	 * @param FormSettings $settings Form settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_form_settings( FormSettings $settings ): bool;

	/**
	 * Delete form settings
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_form_settings( int $form_id ): bool;
}
