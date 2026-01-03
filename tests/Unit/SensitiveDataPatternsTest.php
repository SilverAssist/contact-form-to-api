<?php

/**
 * Unit Tests for SensitiveDataPatterns Class
 *
 * Tests the centralized sensitive data patterns definition,
 * pattern matching, and field name detection.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.2.0
 * @version 1.2.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Core\SensitiveDataPatterns;

/**
 * Test cases for the SensitiveDataPatterns class
 */
class SensitiveDataPatternsTest extends TestCase {

	/**
	 * Test that SensitiveDataPatterns class exists
	 *
	 * @return void
	 */
	public function testSensitiveDataPatternsClassExists(): void {
		$this->assertTrue(
			\class_exists( "SilverAssist\\ContactFormToAPI\\Core\\SensitiveDataPatterns" ),
			"SensitiveDataPatterns class should exist in the Core namespace"
		);
	}

	/**
	 * Test that get_all() returns an array of patterns
	 *
	 * @return void
	 */
	public function testGetAllReturnsArray(): void {
		$patterns = SensitiveDataPatterns::get_all();

		$this->assertIsArray( $patterns, "get_all() should return an array" );
		$this->assertNotEmpty( $patterns, "get_all() should return non-empty array" );
	}

	/**
	 * Test that all expected patterns are present
	 *
	 * @return void
	 */
	public function testAllExpectedPatternsPresent(): void {
		$patterns = SensitiveDataPatterns::get_all();

		$expected_patterns = array(
			"password",
			"passwd",
			"secret",
			"api_key",
			"api-key",
			"apikey",
			"token",
			"auth",
			"authorization",
			"bearer",
			"ssn",
			"social_security",
			"credit_card",
			"card_number",
		);

		foreach ( $expected_patterns as $expected ) {
			$this->assertContains(
				$expected,
				$patterns,
				"Pattern '{$expected}' should be in the patterns array"
			);
		}
	}

	/**
	 * Test that constants are defined correctly
	 *
	 * @return void
	 */
	public function testConstantsDefinedCorrectly(): void {
		$this->assertEquals( "password", SensitiveDataPatterns::PASSWORD );
		$this->assertEquals( "passwd", SensitiveDataPatterns::PASSWD );
		$this->assertEquals( "secret", SensitiveDataPatterns::SECRET );
		$this->assertEquals( "api_key", SensitiveDataPatterns::API_KEY );
		$this->assertEquals( "api-key", SensitiveDataPatterns::API_KEY_HYPHEN );
		$this->assertEquals( "apikey", SensitiveDataPatterns::APIKEY );
		$this->assertEquals( "token", SensitiveDataPatterns::TOKEN );
		$this->assertEquals( "auth", SensitiveDataPatterns::AUTH );
		$this->assertEquals( "authorization", SensitiveDataPatterns::AUTHORIZATION );
		$this->assertEquals( "bearer", SensitiveDataPatterns::BEARER );
		$this->assertEquals( "ssn", SensitiveDataPatterns::SSN );
		$this->assertEquals( "social_security", SensitiveDataPatterns::SOCIAL_SECURITY );
		$this->assertEquals( "credit_card", SensitiveDataPatterns::CREDIT_CARD );
		$this->assertEquals( "card_number", SensitiveDataPatterns::CARD_NUMBER );
	}

	/**
	 * Test is_sensitive() with sensitive field names
	 *
	 * @return void
	 */
	public function testIsSensitiveDetectsSensitiveFields(): void {
		$sensitive_fields = array(
			"user_password",
			"api_key",
			"Authorization",
			"Bearer-Token",
			"secret_key",
			"credit_card_number",
			"ssn_number",
			"user_passwd",
			"auth_token",
			"apikey_value",
		);

		foreach ( $sensitive_fields as $field ) {
			$this->assertTrue(
				SensitiveDataPatterns::is_sensitive( $field ),
				"Field '{$field}' should be detected as sensitive"
			);
		}
	}

	/**
	 * Test is_sensitive() with non-sensitive field names
	 *
	 * @return void
	 */
	public function testIsSensitiveIgnoresNonSensitiveFields(): void {
		$non_sensitive_fields = array(
			"username",
			"email",
			"name",
			"address",
			"phone",
			"user_id",
			"status",
			"created_at",
			"updated_at",
			"message",
		);

		foreach ( $non_sensitive_fields as $field ) {
			$this->assertFalse(
				SensitiveDataPatterns::is_sensitive( $field ),
				"Field '{$field}' should NOT be detected as sensitive"
			);
		}
	}

	/**
	 * Test is_sensitive() is case-insensitive
	 *
	 * @return void
	 */
	public function testIsSensitiveCaseInsensitive(): void {
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "PASSWORD" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "Password" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "password" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "PaSsWoRd" ) );

		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "API_KEY" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "Api_Key" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "api_key" ) );

		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "AUTHORIZATION" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "Authorization" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "authorization" ) );
	}

	/**
	 * Test is_sensitive() with partial matches
	 *
	 * @return void
	 */
	public function testIsSensitivePartialMatches(): void {
		// Should match if pattern is contained in field name
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "user_password_hash" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "my_secret_value" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "access_token_expires" ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( "bearer_auth_header" ) );
	}

	/**
	 * Test that class cannot be instantiated
	 *
	 * @return void
	 */
	public function testClassCannotBeInstantiated(): void {
		$reflection = new \ReflectionClass( SensitiveDataPatterns::class );
		$constructor = $reflection->getConstructor();

		$this->assertTrue(
			$constructor->isPrivate(),
			"Constructor should be private to prevent instantiation"
		);
	}

	/**
	 * Test that no duplicates exist in patterns
	 *
	 * @return void
	 */
	public function testNoDuplicatePatterns(): void {
		$patterns = SensitiveDataPatterns::get_all();
		$unique_patterns = \array_unique( $patterns );

		$this->assertSame(
			\count( $patterns ),
			\count( $unique_patterns ),
			"Patterns array should not contain duplicates"
		);
	}

	/**
	 * Test integration with RequestLogger patterns
	 *
	 * @return void
	 */
	public function testPatternsMatchRequestLoggerRequirements(): void {
		// These are the patterns that were originally in RequestLogger
		$original_patterns = array(
			"password",
			"passwd",
			"secret",
			"api_key",
			"api-key",
			"apikey",
			"token",
			"auth",
			"authorization",
			"ssn",
			"social_security",
			"credit_card",
			"card_number",
		);

		$current_patterns = SensitiveDataPatterns::get_all();

		foreach ( $original_patterns as $pattern ) {
			$this->assertContains(
				$pattern,
				$current_patterns,
				"Pattern '{$pattern}' from original RequestLogger should be present"
			);
		}
	}

	/**
	 * Test integration with ExportService patterns
	 *
	 * @return void
	 */
	public function testPatternsMatchExportServiceRequirements(): void {
		// These are the patterns that were originally in ExportService
		$original_patterns = array(
			"password",
			"passwd",
			"secret",
			"api_key",
			"api-key",
			"apikey",
			"token",
			"auth",
			"authorization",
			"bearer", // ExportService had this extra pattern
			"ssn",
			"social_security",
			"credit_card",
			"card_number",
		);

		$current_patterns = SensitiveDataPatterns::get_all();

		foreach ( $original_patterns as $pattern ) {
			$this->assertContains(
				$pattern,
				$current_patterns,
				"Pattern '{$pattern}' from original ExportService should be present"
			);
		}
	}
}
