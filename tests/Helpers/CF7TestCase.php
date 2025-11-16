<?php

/**
 * Contact Form 7 Specific Test Case
 *
 * Provides utilities and helpers specifically for testing Contact Form 7
 * integration functionality within the Contact Form 7 to API plugin.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.0.0
 * @version 1.0.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Helpers;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

/**
 * Contact Form 7 specific test case class
 */
abstract class CF7TestCase extends TestCase {

	/**
	 * Mock Contact Form 7 form object
	 *
	 * @var object|null
	 */
	protected $mock_cf7_form;

	/**
	 * Mock form submission data
	 *
	 * @var array
	 */
	protected $mock_form_data;

	/**
	 * Setup method for CF7 tests
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->initializeCF7Mocks();
	}

	/**
	 * Initialize Contact Form 7 mocks and test data
	 *
	 * @return void
	 */
	protected function initializeCF7Mocks(): void {
		$this->mock_form_data = array(
			'your-name'    => 'John Doe',
			'your-email'   => 'john.doe@example.com',
			'your-subject' => 'Test Subject',
			'your-message' => 'This is a test message from Contact Form 7',
			'phone'        => '+1234567890',
			'company'      => 'Test Company Ltd.',
		);

		$this->mock_cf7_form = $this->createMockCF7Form();
	}

	/**
	 * Create a mock Contact Form 7 form object
	 *
	 * @param array $form_config Optional form configuration
	 * @return object Mock CF7 form object
	 */
	protected function createMockCF7Form( array $form_config = array() ): object {
		$default_config = array(
			'id'           => 123,
			'title'        => 'Test Contact Form',
			'form'         => $this->getDefaultCF7FormMarkup(),
			'mail'         => array(
				'subject'   => 'Test Subject',
				'sender'    => 'wordpress@example.com',
				'body'      => "Name: [your-name]\nEmail: [your-email]\nMessage: [your-message]",
				'recipient' => 'admin@example.com',
			),
			'api_settings' => array(
				'enable_api' => true,
				'api_url'    => 'https://api.example.com/webhooks/contact',
				'api_method' => 'POST',
				'api_format' => 'json',
			),
		);

		$config = array_merge( $default_config, $form_config );

		return (object) $config;
	}

	/**
	 * Get default CF7 form markup
	 *
	 * @return string CF7 form markup
	 */
	protected function getDefaultCF7FormMarkup(): string {
		return '<label> Your Name (required)
    [text* your-name] </label>

<label> Your Email (required)
    [email* your-email] </label>

<label> Subject
    [text your-subject] </label>

<label> Your Message
    [textarea your-message] </label>

<label> Phone
    [tel phone] </label>

<label> Company
    [text company] </label>

[submit "Send"]';
	}

	/**
	 * Create mock form submission data
	 *
	 * @param array $override_data Data to override defaults
	 * @return array Mock submission data
	 */
	protected function createMockSubmissionData( array $override_data = array() ): array {
		return array_merge( $this->mock_form_data, $override_data );
	}

	/**
	 * Mock Contact Form 7 submission object
	 *
	 * @param array $submission_data Form data
	 * @return object Mock submission object
	 */
	protected function createMockCF7Submission( array $submission_data = array() ): object {
		$data = empty( $submission_data ) ? $this->mock_form_data : $submission_data;

		return (object) array(
			'posted_data'    => $data,
			'uploaded_files' => array(),
			'status'         => 'mail_sent',
			'response'       => 'Thank you for your message. It has been sent.',
			'contact_form'   => $this->mock_cf7_form,
		);
	}

	/**
	 * Assert that API data matches expected CF7 field mapping
	 *
	 * @param array $expected_mapping Expected field mapping
	 * @param array $actual_api_data  Actual API data sent
	 * @param string $message         Optional assertion message
	 * @return void
	 */
	protected function assertCF7ApiMapping( array $expected_mapping, array $actual_api_data, string $message = '' ): void {
		foreach ( $expected_mapping as $cf7_field => $api_field ) {
			$this->assertArrayHasKey(
				$api_field,
				$actual_api_data,
				$message ?: 'API data missing field: ' . $api_field . ' (mapped from CF7 field: ' . $cf7_field . ')'
			);

			if ( isset( $this->mock_form_data[ $cf7_field ] ) ) {
				$this->assertEquals(
					$this->mock_form_data[ $cf7_field ],
					$actual_api_data[ $api_field ],
					$message ?: 'API field value mismatch for: ' . $api_field
				);
			}
		}
	}

	/**
	 * Create test API configuration
	 *
	 * @param array $config_override Configuration overrides
	 * @return array API configuration
	 */
	protected function createTestApiConfig( array $config_override = array() ): array {
		$default_config = array(
			'enable_api'     => true,
			'api_url'        => 'https://httpbin.org/post',
			'api_method'     => 'POST',
			'api_format'     => 'json',
			'api_headers'    => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'Contact Form 7 to API Plugin',
			),
			'field_mapping'  => array(
				'your-name'    => 'name',
				'your-email'   => 'email',
				'your-subject' => 'subject',
				'your-message' => 'message',
				'phone'        => 'phone_number',
				'company'      => 'company_name',
			),
			'debug_mode'     => true,
			'retry_attempts' => 3,
			'timeout'        => 30,
		);

		return array_merge( $default_config, $config_override );
	}

	/**
	 * Assert that CF7 mail tags are properly processed
	 *
	 * @param string $template_with_tags Template containing mail tags
	 * @param string $processed_content  Processed content
	 * @param array  $form_data         Form data for processing
	 * @return void
	 */
	protected function assertCF7MailTagsProcessed( string $template_with_tags, string $processed_content, array $form_data ): void {
		// Check that mail tags have been replaced
		$this->assertStringNotContainsString(
			'[your-name]',
			$processed_content,
			'Mail tag [your-name] was not processed'
		);

		$this->assertStringNotContainsString(
			'[your-email]',
			$processed_content,
			'Mail tag [your-email] was not processed'
		);

		// Check that actual values are present
		foreach ( $form_data as $value ) {
			if ( ! empty( $value ) && is_string( $value ) ) {
				$this->assertStringContainsString(
					$value,
					$processed_content,
					"Form data value '{$value}' not found in processed content"
				);
			}
		}
	}

	/**
	 * Mock successful API response
	 *
	 * @param array $response_data Response data
	 * @return array Mock response
	 */
	protected function mockSuccessfulApiResponse( array $response_data = array() ): array {
		$default_response = array(
			'success'   => true,
			'message'   => 'Data received successfully',
			'id'        => 'test_' . uniqid(),
			'timestamp' => date( 'Y-m-d H:i:s' ),
		);

		return $this->createMockHttpResponse(
			array_merge( $default_response, $response_data ),
			200
		);
	}

	/**
	 * Mock failed API response
	 *
	 * @param string $error_message Error message
	 * @param int    $status_code   HTTP status code
	 * @return array Mock response
	 */
	protected function mockFailedApiResponse( string $error_message = 'API Error', int $status_code = 400 ): array {
		return $this->createMockHttpResponse(
			array(
				'success' => false,
				'error'   => $error_message,
				'code'    => $status_code,
			),
			$status_code
		);
	}
}
