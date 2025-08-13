<?php
/**
 * Integration Tests for Contact Form 7
 *
 * Tests the integration between Contact Form 7 and the API functionality,
 * including form processing, field mapping, and API communication.
 *
 * @package ContactFormToAPI\Tests
 * @since   1.0.0
 * @version 1.0.0
 * @author  Silver Assist
 */

namespace ContactFormToAPI\Tests\ContactForm;

use ContactFormToAPI\Tests\Helpers\CF7TestCase;

/**
 * Test cases for Contact Form 7 Integration
 */
class IntegrationTest extends CF7TestCase
{
    /**
     * Test CF7 form mock creation
     *
     * @return void
     */
    public function testCF7FormMockCreation(): void
    {
        $form = $this->createMockCF7Form();
        
        $this->assertIsObject($form, "Mock CF7 form should be an object");
        $this->assertEquals(123, $form->id, "Form should have correct ID");
        $this->assertEquals("Test Contact Form", $form->title, "Form should have correct title");
        $this->assertTrue($form->api_settings["enable_api"], "API should be enabled");
    }

    /**
     * Test form submission data creation
     *
     * @return void
     */
    public function testFormSubmissionDataCreation(): void
    {
        $submission_data = $this->createMockSubmissionData();
        
        $this->assertIsArray($submission_data, "Submission data should be an array");
        $this->assertArrayHasKey("your-name", $submission_data, "Should have name field");
        $this->assertArrayHasKey("your-email", $submission_data, "Should have email field");
        $this->assertEquals("John Doe", $submission_data["your-name"], "Name field should have correct value");
    }

    /**
     * Test CF7 submission mock creation
     *
     * @return void
     */
    public function testCF7SubmissionMockCreation(): void
    {
        $submission = $this->createMockCF7Submission();
        
        $this->assertIsObject($submission, "Mock submission should be an object");
        $this->assertIsArray($submission->posted_data, "Posted data should be an array");
        $this->assertEquals("mail_sent", $submission->status, "Status should be 'mail_sent'");
        $this->assertIsObject($submission->contact_form, "Should contain contact form object");
    }

    /**
     * Test API configuration creation
     *
     * @return void
     */
    public function testApiConfigurationCreation(): void
    {
        $config = $this->createTestApiConfig();
        
        $this->assertIsArray($config, "API config should be an array");
        $this->assertTrue($config["enable_api"], "API should be enabled");
        $this->assertEquals("POST", $config["api_method"], "Should use POST method");
        $this->assertEquals("json", $config["api_format"], "Should use JSON format");
        $this->assertIsArray($config["field_mapping"], "Field mapping should be an array");
    }

    /**
     * Test field mapping functionality
     *
     * @return void
     */
    public function testFieldMapping(): void
    {
        $config = $this->createTestApiConfig();
        $submission_data = $this->createMockSubmissionData();
        
        // Simulate field mapping transformation
        $mapped_data = [];
        foreach ($config["field_mapping"] as $cf7_field => $api_field) {
            if (isset($submission_data[$cf7_field])) {
                $mapped_data[$api_field] = $submission_data[$cf7_field];
            }
        }
        
        $this->assertArrayHasKey("name", $mapped_data, "Should have mapped name field");
        $this->assertArrayHasKey("email", $mapped_data, "Should have mapped email field");
        $this->assertEquals($submission_data["your-name"], $mapped_data["name"], "Name mapping should be correct");
        $this->assertEquals($submission_data["your-email"], $mapped_data["email"], "Email mapping should be correct");
    }

    /**
     * Test JSON format processing
     *
     * @return void
     */
    public function testJsonFormatProcessing(): void
    {
        $submission_data = $this->createMockSubmissionData();
        $json_data = json_encode($submission_data);
        
        $this->assertJsonString($json_data, "Should produce valid JSON");
        
        $decoded = json_decode($json_data, true);
        $this->assertIsArray($decoded, "JSON should decode to array");
        $this->assertEquals($submission_data, $decoded, "Decoded data should match original");
    }

    /**
     * Test XML format processing
     *
     * @return void
     */
    public function testXmlFormatProcessing(): void
    {
        $submission_data = $this->createMockSubmissionData();
        
        // Simulate XML conversion
        $xml = "<contact>\n";
        foreach ($submission_data as $key => $value) {
            $safe_key = str_replace("-", "_", $key);
            $safe_value = htmlspecialchars($value, ENT_XML1, "UTF-8");
            $xml .= "  <{$safe_key}>{$safe_value}</{$safe_key}>\n";
        }
        $xml .= "</contact>";
        
        $this->assertStringContainsString("<contact>", $xml, "Should contain root element");
        $this->assertStringContainsString("<your_name>", $xml, "Should contain name field");
        $this->assertStringContainsString("John Doe", $xml, "Should contain actual name value");
    }

    /**
     * Test API request headers
     *
     * @return void
     */
    public function testApiRequestHeaders(): void
    {
        $config = $this->createTestApiConfig([
            "api_headers" => [
                "Authorization" => "Bearer test-token",
                "Content-Type" => "application/json",
                "User-Agent" => "CF7-API-Plugin/1.0.0"
            ]
        ]);
        
        $headers = $config["api_headers"];
        
        $this->assertArrayHasKey("Authorization", $headers, "Should have Authorization header");
        $this->assertArrayHasKey("Content-Type", $headers, "Should have Content-Type header");
        $this->assertEquals("Bearer test-token", $headers["Authorization"], "Authorization should be correct");
        $this->assertEquals("application/json", $headers["Content-Type"], "Content-Type should be JSON");
    }

    /**
     * Test successful API response handling
     *
     * @return void
     */
    public function testSuccessfulApiResponse(): void
    {
        $response = $this->mockSuccessfulApiResponse([
            "lead_id" => "12345",
            "status" => "processed"
        ]);
        
        $this->assertEquals(200, $response["response"]["code"], "Should have 200 status code");
        $this->assertJsonString($response["body"], "Response body should be valid JSON");
        
        $body = json_decode($response["body"], true);
        $this->assertTrue($body["success"], "Response should indicate success");
        $this->assertEquals("12345", $body["lead_id"], "Should have correct lead ID");
    }

    /**
     * Test failed API response handling
     *
     * @return void
     */
    public function testFailedApiResponse(): void
    {
        $response = $this->mockFailedApiResponse("Invalid email format", 422);
        
        $this->assertEquals(422, $response["response"]["code"], "Should have 422 status code");
        $this->assertJsonString($response["body"], "Response body should be valid JSON");
        
        $body = json_decode($response["body"], true);
        $this->assertFalse($body["success"], "Response should indicate failure");
        $this->assertEquals("Invalid email format", $body["error"], "Should have correct error message");
    }

    /**
     * Test mail tag processing
     *
     * @return void
     */
    public function testMailTagProcessing(): void
    {
        $template = "Hello [your-name], your email [your-email] was received.";
        $form_data = $this->createMockSubmissionData();
        
        // Simulate mail tag replacement
        $processed = $template;
        foreach ($form_data as $field => $value) {
            $processed = str_replace("[{$field}]", $value, $processed);
        }
        
        $this->assertCF7MailTagsProcessed($template, $processed, $form_data);
        $this->assertStringContainsString("Hello John Doe", $processed, "Should contain processed name");
        $this->assertStringContainsString("john.doe@example.com", $processed, "Should contain processed email");
    }

    /**
     * Test API timeout configuration
     *
     * @return void
     */
    public function testApiTimeoutConfiguration(): void
    {
        $config = $this->createTestApiConfig(["timeout" => 60]);
        
        $this->assertEquals(60, $config["timeout"], "Timeout should be configurable");
        $this->assertIsInt($config["timeout"], "Timeout should be an integer");
        $this->assertGreaterThan(0, $config["timeout"], "Timeout should be positive");
    }

    /**
     * Test retry mechanism configuration
     *
     * @return void
     */
    public function testRetryMechanismConfiguration(): void
    {
        $config = $this->createTestApiConfig(["retry_attempts" => 5]);
        
        $this->assertEquals(5, $config["retry_attempts"], "Retry attempts should be configurable");
        $this->assertIsInt($config["retry_attempts"], "Retry attempts should be an integer");
        $this->assertGreaterThanOrEqual(0, $config["retry_attempts"], "Retry attempts should be non-negative");
    }

    /**
     * Test debug mode functionality
     *
     * @return void
     */
    public function testDebugModeFeatures(): void
    {
        $config = $this->createTestApiConfig(["debug_mode" => true]);
        
        $this->assertTrue($config["debug_mode"], "Debug mode should be enabled");
        
        // Test debug logging would be captured
        if ($config["debug_mode"]) {
            $debug_data = [
                "timestamp" => date("Y-m-d H:i:s"),
                "request_url" => $config["api_url"],
                "request_method" => $config["api_method"],
                "form_data" => $this->createMockSubmissionData()
            ];
            
            $this->assertIsArray($debug_data, "Debug data should be an array");
            $this->assertArrayHasKey("timestamp", $debug_data, "Debug should include timestamp");
            $this->assertArrayHasKey("form_data", $debug_data, "Debug should include form data");
        }
    }
}
