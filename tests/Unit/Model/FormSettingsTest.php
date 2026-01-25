<?php
/**
 * Tests for FormSettings Model
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit\Model
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Model;

use SilverAssist\ContactFormToAPI\Model\FormSettings;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * FormSettings test case.
 *
 * @group unit
 * @group model
 * @covers \SilverAssist\ContactFormToAPI\Model\FormSettings
 */
class FormSettingsTest extends TestCase {

	/**
	 * Test FormSettings construction with all parameters
	 */
	public function testConstructor(): void {
		$settings = new FormSettings(
			form_id: 123,
			enabled: true,
			endpoint: 'https://api.example.com/webhook',
			method: 'POST',
			input_type: 'json',
			field_mappings: array(
				'email' => 'user_email',
				'name'  => 'full_name',
			),
			auth_config: array(
				'type'  => 'bearer',
				'token' => 'secret',
			),
			custom_headers: array( 'X-Custom' => 'value' ),
			retry_config: array(
				'max_retries' => 3,
				'delay'       => 300,
			),
			debug_mode: true
		);

		$this->assertSame( 123, $settings->get_form_id() );
		$this->assertTrue( $settings->is_enabled() );
		$this->assertSame( 'https://api.example.com/webhook', $settings->get_endpoint() );
		$this->assertSame( 'POST', $settings->get_method() );
		$this->assertSame( 'json', $settings->get_input_type() );
		$this->assertSame(
			array(
				'email' => 'user_email',
				'name'  => 'full_name',
			),
			$settings->get_field_mappings()
		);
		$this->assertSame(
			array(
				'type'  => 'bearer',
				'token' => 'secret',
			),
			$settings->get_auth_config()
		);
		$this->assertSame( array( 'X-Custom' => 'value' ), $settings->get_custom_headers() );
		$this->assertSame(
			array(
				'max_retries' => 3,
				'delay'       => 300,
			),
			$settings->get_retry_config()
		);
		$this->assertTrue( $settings->is_debug_mode() );
	}

	/**
	 * Test FormSettings construction with default values
	 */
	public function testConstructorWithDefaults(): void {
		$settings = new FormSettings(
			form_id: 456,
			enabled: false,
			endpoint: 'https://api.test'
		);

		$this->assertSame( 456, $settings->get_form_id() );
		$this->assertFalse( $settings->is_enabled() );
		$this->assertSame( 'https://api.test', $settings->get_endpoint() );
		$this->assertSame( 'POST', $settings->get_method() );
		$this->assertSame( 'params', $settings->get_input_type() );
		$this->assertSame( array(), $settings->get_field_mappings() );
		$this->assertSame( array(), $settings->get_auth_config() );
		$this->assertSame( array(), $settings->get_custom_headers() );
		$this->assertSame( array(), $settings->get_retry_config() );
		$this->assertFalse( $settings->is_debug_mode() );
	}

	/**
	 * Test is_enabled method
	 */
	public function testIsEnabled(): void {
		$enabled_settings = new FormSettings(
			form_id: 1,
			enabled: true,
			endpoint: 'https://api.test'
		);

		$disabled_settings = new FormSettings(
			form_id: 2,
			enabled: false,
			endpoint: 'https://api.test'
		);

		$this->assertTrue( $enabled_settings->is_enabled() );
		$this->assertFalse( $disabled_settings->is_enabled() );
	}

	/**
	 * Test is_debug_mode method
	 */
	public function testIsDebugMode(): void {
		$debug_settings = new FormSettings(
			form_id: 1,
			enabled: true,
			endpoint: 'https://api.test',
			debug_mode: true
		);

		$normal_settings = new FormSettings(
			form_id: 2,
			enabled: true,
			endpoint: 'https://api.test',
			debug_mode: false
		);

		$this->assertTrue( $debug_settings->is_debug_mode() );
		$this->assertFalse( $normal_settings->is_debug_mode() );
	}

	/**
	 * Test to_array method
	 */
	public function testToArray(): void {
		$settings = new FormSettings(
			form_id: 123,
			enabled: true,
			endpoint: 'https://api.example.com',
			method: 'PUT',
			input_type: 'json',
			field_mappings: array( 'name' => 'full_name' ),
			auth_config: array( 'type' => 'basic' ),
			custom_headers: array( 'X-API-Key' => '12345' ),
			retry_config: array( 'max_retries' => 5 ),
			debug_mode: true
		);

		$array = $settings->to_array();

		$this->assertSame( 123, $array['form_id'] );
		$this->assertTrue( $array['enabled'] );
		$this->assertSame( 'https://api.example.com', $array['endpoint'] );
		$this->assertSame( 'PUT', $array['method'] );
		$this->assertSame( 'json', $array['input_type'] );
		$this->assertSame( array( 'name' => 'full_name' ), $array['field_mappings'] );
		$this->assertSame( array( 'type' => 'basic' ), $array['auth_config'] );
		$this->assertSame( array( 'X-API-Key' => '12345' ), $array['custom_headers'] );
		$this->assertSame( array( 'max_retries' => 5 ), $array['retry_config'] );
		$this->assertTrue( $array['debug_mode'] );
	}

	/**
	 * Test from_meta factory method with _wpcf7_api_data structure
	 */
	public function testFromMetaWithApiData(): void {
		$form_id = 789;
		$meta    = array(
			'_wpcf7_api_data'     => array(
				'send_to_api' => '1',
				'base_url'    => 'https://api.example.com/submit',
				'method'      => 'POST',
				'input_type'  => 'json',
				'debug_log'   => '1',
			),
			'_wpcf7_api_data_map' => array(
				'your-email' => 'email',
				'your-name'  => 'name',
			),
			'_wpcf7_api_auth'     => array(
				'type'  => 'bearer',
				'token' => 'test-token',
			),
		);

		$settings = FormSettings::from_meta( $form_id, $meta );

		$this->assertSame( 789, $settings->get_form_id() );
		$this->assertTrue( $settings->is_enabled() );
		$this->assertSame( 'https://api.example.com/submit', $settings->get_endpoint() );
		$this->assertSame( 'POST', $settings->get_method() );
		$this->assertSame( 'json', $settings->get_input_type() );
		$this->assertSame(
			array(
				'your-email' => 'email',
				'your-name'  => 'name',
			),
			$settings->get_field_mappings()
		);
		$this->assertSame(
			array(
				'type'  => 'bearer',
				'token' => 'test-token',
			),
			$settings->get_auth_config()
		);
		$this->assertTrue( $settings->is_debug_mode() );
	}

	/**
	 * Test from_meta factory method with wpcf7_api_data structure (without underscore)
	 */
	public function testFromMetaWithoutUnderscore(): void {
		$form_id = 456;
		$meta    = array(
			'wpcf7_api_data'     => array(
				'send_to_api' => '1',
				'base_url'    => 'https://webhook.site/test',
				'method'      => 'PUT',
				'input_type'  => 'params',
			),
			'wpcf7_api_data_map' => array(
				'field1' => 'mapped_field1',
			),
			'wpcf7_api_auth'     => array(
				'type' => 'none',
			),
		);

		$settings = FormSettings::from_meta( $form_id, $meta );

		$this->assertSame( 456, $settings->get_form_id() );
		$this->assertTrue( $settings->is_enabled() );
		$this->assertSame( 'https://webhook.site/test', $settings->get_endpoint() );
		$this->assertSame( 'PUT', $settings->get_method() );
		$this->assertSame( 'params', $settings->get_input_type() );
		$this->assertSame( array( 'field1' => 'mapped_field1' ), $settings->get_field_mappings() );
	}

	/**
	 * Test from_meta factory method with empty/minimal meta
	 */
	public function testFromMetaWithEmptyData(): void {
		$form_id = 111;
		$meta    = array();

		$settings = FormSettings::from_meta( $form_id, $meta );

		$this->assertSame( 111, $settings->get_form_id() );
		$this->assertFalse( $settings->is_enabled() );
		$this->assertSame( '', $settings->get_endpoint() );
		$this->assertSame( 'POST', $settings->get_method() );
		$this->assertSame( 'params', $settings->get_input_type() );
		$this->assertSame( array(), $settings->get_field_mappings() );
		$this->assertFalse( $settings->is_debug_mode() );
	}

	/**
	 * Test from_meta with disabled integration
	 */
	public function testFromMetaWithDisabledIntegration(): void {
		$form_id = 222;
		$meta    = array(
			'_wpcf7_api_data' => array(
				'send_to_api' => '',
				'base_url'    => 'https://api.test',
				'method'      => 'POST',
			),
		);

		$settings = FormSettings::from_meta( $form_id, $meta );

		$this->assertFalse( $settings->is_enabled() );
		$this->assertSame( 'https://api.test', $settings->get_endpoint() );
	}

	/**
	 * Test different HTTP methods
	 *
	 * @dataProvider httpMethodProvider
	 */
	public function testHttpMethods( string $method ): void {
		$settings = new FormSettings(
			form_id: 1,
			enabled: true,
			endpoint: 'https://api.test',
			method: $method
		);

		$this->assertSame( $method, $settings->get_method() );
	}

	/**
	 * Data provider for HTTP methods
	 *
	 * @return array<string, array<string>>
	 */
	public static function httpMethodProvider(): array {
		return array(
			'GET method'    => array( 'GET' ),
			'POST method'   => array( 'POST' ),
			'PUT method'    => array( 'PUT' ),
			'PATCH method'  => array( 'PATCH' ),
			'DELETE method' => array( 'DELETE' ),
		);
	}

	/**
	 * Test different input types
	 *
	 * @dataProvider inputTypeProvider
	 */
	public function testInputTypes( string $input_type ): void {
		$settings = new FormSettings(
			form_id: 1,
			enabled: true,
			endpoint: 'https://api.test',
			input_type: $input_type
		);

		$this->assertSame( $input_type, $settings->get_input_type() );
	}

	/**
	 * Data provider for input types
	 *
	 * @return array<string, array<string>>
	 */
	public static function inputTypeProvider(): array {
		return array(
			'params type' => array( 'params' ),
			'json type'   => array( 'json' ),
			'body type'   => array( 'body' ),
		);
	}
}
