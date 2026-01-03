<?php

/**
 * Unit Tests for ExportService Class
 *
 * Tests the export service for CSV and JSON generation,
 * data sanitization, and export functionality.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.2.0
 * @version 1.2.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Services\ExportService;

/**
 * Test cases for the ExportService class
 */
class ExportServiceTest extends TestCase {

	/**
	 * ExportService instance
	 *
	 * @var ExportService
	 */
	private ExportService $export_service;

	/**
	 * Setup method called before each test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Get singleton instance.
		$this->export_service = ExportService::instance();
	}

	/**
	 * Test that ExportService class exists
	 *
	 * @return void
	 */
	public function testExportServiceClassExists(): void {
		$this->assertTrue(
			\class_exists( "SilverAssist\\ContactFormToAPI\\Services\\ExportService" ),
			"ExportService class should exist in the Services namespace"
		);
	}

	/**
	 * Test that ExportService implements singleton pattern
	 *
	 * @return void
	 */
	public function testExportServiceSingletonPattern(): void {
		$instance1 = ExportService::instance();
		$instance2 = ExportService::instance();

		$this->assertSame(
			$instance1,
			$instance2,
			"ExportService::instance() should return the same instance"
		);
	}

	/**
	 * Test that ExportService implements LoadableInterface
	 *
	 * @return void
	 */
	public function testExportServiceImplementsLoadableInterface(): void {
		$this->assertInstanceOf(
			"SilverAssist\\ContactFormToAPI\\Core\\Interfaces\\LoadableInterface",
			$this->export_service,
			"ExportService should implement LoadableInterface"
		);
	}

	/**
	 * Test that get_priority returns correct value for services
	 *
	 * @return void
	 */
	public function testGetPriorityReturnsCorrectValue(): void {
		$priority = $this->export_service->get_priority();

		$this->assertIsInt( $priority, "get_priority should return an integer" );
		$this->assertEquals( 20, $priority, "Services should have priority 20" );
	}

	/**
	 * Test that should_load returns true only in admin context
	 *
	 * @return void
	 */
	public function testShouldLoadReturnsCorrectValue(): void {
		$should_load = $this->export_service->should_load();

		$this->assertIsBool( $should_load, "should_load should return a boolean" );

		// In test context with WordPress loaded as admin.
		if ( \function_exists( "is_admin" ) ) {
			$this->assertEquals(
				\is_admin(),
				$should_load,
				"should_load should match is_admin() result"
			);
		}
	}

	/**
	 * Test CSV export generates valid CSV format
	 *
	 * @return void
	 */
	public function testExportCsvGeneratesValidFormat(): void {
		$sample_logs = array(
			array(
				"id"             => 1,
				"form_id"        => 123,
				"endpoint"       => "https://api.example.com/submit",
				"method"         => "POST",
				"status"         => "success",
				"response_code"  => 200,
				"execution_time" => 0.542,
				"retry_count"    => 0,
				"error_message"  => "",
				"created_at"     => "2024-01-15 10:30:00",
			),
		);

		$csv = $this->export_service->export_csv( $sample_logs );

		// Check for UTF-8 BOM.
		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv, "CSV should start with UTF-8 BOM" );

		// Check that CSV contains header row.
		$this->assertStringContainsString( "ID", $csv, "CSV should contain ID header" );
		$this->assertStringContainsString( "Endpoint", $csv, "CSV should contain Endpoint header" );

		// Check that CSV contains data.
		$this->assertStringContainsString( "https://api.example.com/submit", $csv, "CSV should contain endpoint data" );
	}

	/**
	 * Test JSON export generates valid JSON format
	 *
	 * @return void
	 */
	public function testExportJsonGeneratesValidFormat(): void {
		$sample_logs = array(
			array(
				"id"             => 1,
				"form_id"        => 123,
				"endpoint"       => "https://api.example.com/submit",
				"method"         => "POST",
				"status"         => "success",
				"response_code"  => 200,
				"execution_time" => 0.542,
				"retry_count"    => 0,
				"error_message"  => "",
				"created_at"     => "2024-01-15 10:30:00",
			),
		);

		$json = $this->export_service->export_json( $sample_logs );

		// Validate JSON.
		$this->assertJsonString( $json, "Export should generate valid JSON" );

		// Check that JSON contains data.
		$decoded = \json_decode( $json, true );
		$this->assertIsArray( $decoded, "JSON should decode to an array" );
		$this->assertCount( 1, $decoded, "JSON should contain 1 log entry" );
		$this->assertEquals( 123, $decoded[0]["form_id"], "JSON should contain correct form_id" );
	}

	/**
	 * Test that sensitive data is sanitized in CSV export
	 *
	 * @return void
	 */
	public function testSensitiveDataSanitizedInCsv(): void {
		$sample_logs = array(
			array(
				"id"              => 1,
				"form_id"         => 123,
				"endpoint"        => "https://api.example.com/submit",
				"method"          => "POST",
				"status"          => "success",
				"response_code"   => 200,
				"execution_time"  => 0.542,
				"retry_count"     => 0,
				"error_message"   => "",
				"created_at"      => "2024-01-15 10:30:00",
				"request_headers" => \wp_json_encode( array(
					"Authorization" => "Bearer secret_token_123",
					"Content-Type"  => "application/json",
				) ),
				"request_data"    => \wp_json_encode( array(
					"name"     => "John Doe",
					"password" => "supersecret123",
				) ),
			),
		);

		$csv = $this->export_service->export_csv( $sample_logs );

		// Check that sensitive data is redacted.
		$this->assertStringNotContainsString( "secret_token_123", $csv, "CSV should not contain bearer token" );
		$this->assertStringNotContainsString( "supersecret123", $csv, "CSV should not contain password" );
		$this->assertStringContainsString( "***REDACTED***", $csv, "CSV should contain redaction marker" );
	}

	/**
	 * Test that sensitive data is sanitized in JSON export
	 *
	 * @return void
	 */
	public function testSensitiveDataSanitizedInJson(): void {
		$sample_logs = array(
			array(
				"id"              => 1,
				"form_id"         => 123,
				"endpoint"        => "https://api.example.com/submit",
				"method"          => "POST",
				"status"          => "success",
				"response_code"   => 200,
				"execution_time"  => 0.542,
				"retry_count"     => 0,
				"error_message"   => "",
				"created_at"      => "2024-01-15 10:30:00",
				"request_headers" => \wp_json_encode( array(
					"Api-Key"      => "api_key_12345",
					"Content-Type" => "application/json",
				) ),
				"response_data"   => \wp_json_encode( array(
					"status"  => "success",
					"token"   => "response_token_xyz",
					"user_id" => 456,
				) ),
			),
		);

		$json = $this->export_service->export_json( $sample_logs );

		// Check that sensitive data is redacted.
		$this->assertStringNotContainsString( "api_key_12345", $json, "JSON should not contain API key" );
		$this->assertStringNotContainsString( "response_token_xyz", $json, "JSON should not contain response token" );
		$this->assertStringContainsString( "***REDACTED***", $json, "JSON should contain redaction marker" );
	}

	/**
	 * Test export filename generation
	 *
	 * @return void
	 */
	public function testExportFilenameGeneration(): void {
		$csv_filename  = $this->export_service->get_export_filename( "csv" );
		$json_filename = $this->export_service->get_export_filename( "json" );

		// Check format.
		$this->assertStringStartsWith( "cf7-api-logs_", $csv_filename, "CSV filename should have correct prefix" );
		$this->assertStringEndsWith( ".csv", $csv_filename, "CSV filename should have .csv extension" );

		$this->assertStringStartsWith( "cf7-api-logs_", $json_filename, "JSON filename should have correct prefix" );
		$this->assertStringEndsWith( ".json", $json_filename, "JSON filename should have .json extension" );

		// Check that timestamp is included (format: YYYY-MM-DD_HH-MM-SS).
		$this->assertMatchesRegularExpression(
			"/cf7-api-logs_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.csv/",
			$csv_filename,
			"CSV filename should contain timestamp"
		);
	}

	/**
	 * Test CSV export with empty logs array
	 *
	 * @return void
	 */
	public function testExportCsvWithEmptyLogs(): void {
		$csv = $this->export_service->export_csv( array() );

		// Should still have headers.
		$this->assertStringContainsString( "ID", $csv, "CSV should contain headers even when empty" );
		$this->assertStringContainsString( "Endpoint", $csv, "CSV should contain headers even when empty" );
	}

	/**
	 * Test JSON export with empty logs array
	 *
	 * @return void
	 */
	public function testExportJsonWithEmptyLogs(): void {
		$json = $this->export_service->export_json( array() );

		// Should be valid empty JSON array.
		$this->assertEquals( "[]", $json, "Empty logs should export as empty JSON array" );
	}

	/**
	 * Test CSV export with multiple logs
	 *
	 * @return void
	 */
	public function testExportCsvWithMultipleLogs(): void {
		$sample_logs = array(
			array(
				"id"             => 1,
				"form_id"        => 123,
				"endpoint"       => "https://api.example.com/submit",
				"method"         => "POST",
				"status"         => "success",
				"response_code"  => 200,
				"execution_time" => 0.542,
				"retry_count"    => 0,
				"error_message"  => "",
				"created_at"     => "2024-01-15 10:30:00",
			),
			array(
				"id"             => 2,
				"form_id"        => 124,
				"endpoint"       => "https://api.example.com/failed",
				"method"         => "POST",
				"status"         => "error",
				"response_code"  => 500,
				"execution_time" => 1.234,
				"retry_count"    => 2,
				"error_message"  => "Server error",
				"created_at"     => "2024-01-15 11:45:00",
			),
		);

		$csv = $this->export_service->export_csv( $sample_logs );

		// Count lines (header + 2 data rows + BOM line).
		$lines = \explode( "\n", \trim( $csv ) );
		$this->assertGreaterThanOrEqual( 3, \count( $lines ), "CSV should have header and 2 data rows" );
	}
}
