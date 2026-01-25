<?php

/**
 * Unit Tests for SensitiveDataPatterns Class
 *
 * Tests the centralized sensitive data patterns definition,
 * pattern matching, and field name detection.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.2.0
 * @version 1.3.6
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Security;

use SilverAssist\ContactFormToAPI\Config\Settings;
use SilverAssist\ContactFormToAPI\Service\Security\SensitiveDataPatterns;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

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
			\class_exists( 'SilverAssist\\ContactFormToAPI\\Service\\Security\\SensitiveDataPatterns' ),
			'SensitiveDataPatterns class should exist in the Service\\Security namespace'
		);
	}

	/**
	 * Test that get_all() returns an array of patterns
	 *
	 * @return void
	 */
	public function testGetAllReturnsArray(): void {
		$patterns = SensitiveDataPatterns::get_all();

		$this->assertIsArray( $patterns, 'get_all() should return an array' );
		$this->assertNotEmpty( $patterns, 'get_all() should return non-empty array' );
	}

	/**
	 * Test that all expected patterns are present
	 *
	 * @return void
	 */
	public function testAllExpectedPatternsPresent(): void {
		$patterns = SensitiveDataPatterns::get_all();

		$expected_patterns = array(
			'password',
			'passwd',
			'secret',
			'api_key',
			'api-key',
			'apikey',
			'token',
			'auth',
			'authorization',
			'bearer',
			'ssn',
			'social_security',
			'credit_card',
			'card_number',
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
		$this->assertEquals( 'password', SensitiveDataPatterns::PASSWORD );
		$this->assertEquals( 'passwd', SensitiveDataPatterns::PASSWD );
		$this->assertEquals( 'secret', SensitiveDataPatterns::SECRET );
		$this->assertEquals( 'api_key', SensitiveDataPatterns::API_KEY );
		$this->assertEquals( 'api-key', SensitiveDataPatterns::API_KEY_HYPHEN );
		$this->assertEquals( 'apikey', SensitiveDataPatterns::APIKEY );
		$this->assertEquals( 'token', SensitiveDataPatterns::TOKEN );
		$this->assertEquals( 'auth', SensitiveDataPatterns::AUTH );
		$this->assertEquals( 'authorization', SensitiveDataPatterns::AUTHORIZATION );
		$this->assertEquals( 'bearer', SensitiveDataPatterns::BEARER );
		$this->assertEquals( 'ssn', SensitiveDataPatterns::SSN );
		$this->assertEquals( 'social_security', SensitiveDataPatterns::SOCIAL_SECURITY );
		$this->assertEquals( 'credit_card', SensitiveDataPatterns::CREDIT_CARD );
		$this->assertEquals( 'card_number', SensitiveDataPatterns::CARD_NUMBER );
	}

	/**
	 * Test is_sensitive() with sensitive field names
	 *
	 * @return void
	 */
	public function testIsSensitiveDetectsSensitiveFields(): void {
		$sensitive_fields = array(
			'user_password',
			'api_key',
			'Authorization',
			'Bearer-Token',
			'secret_key',
			'credit_card_number',
			'ssn_number',
			'user_passwd',
			'auth_token',
			'apikey_value',
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
			'username',
			'email',
			'name',
			'address',
			'phone',
			'user_id',
			'status',
			'created_at',
			'updated_at',
			'message',
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
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'PASSWORD' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'Password' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'password' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'PaSsWoRd' ) );

		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'API_KEY' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'Api_Key' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'api_key' ) );

		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'AUTHORIZATION' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'Authorization' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'authorization' ) );
	}

	/**
	 * Test is_sensitive() with partial matches
	 *
	 * @return void
	 */
	public function testIsSensitivePartialMatches(): void {
		// Should match if pattern is contained in field name
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'user_password_hash' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'my_secret_value' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'access_token_expires' ) );
		$this->assertTrue( SensitiveDataPatterns::is_sensitive( 'bearer_auth_header' ) );
	}

	/**
	 * Test that class cannot be instantiated
	 *
	 * @return void
	 */
	public function testClassCannotBeInstantiated(): void {
		$reflection  = new \ReflectionClass( SensitiveDataPatterns::class );
		$constructor = $reflection->getConstructor();

		$this->assertTrue(
			$constructor->isPrivate(),
			'Constructor should be private to prevent instantiation'
		);
	}

	/**
	 * Test that no duplicates exist in patterns
	 *
	 * @return void
	 */
	public function testNoDuplicatePatterns(): void {
		$patterns        = SensitiveDataPatterns::get_all();
		$unique_patterns = \array_unique( $patterns );

		$this->assertSame(
			\count( $patterns ),
			\count( $unique_patterns ),
			'Patterns array should not contain duplicates'
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
			'password',
			'passwd',
			'secret',
			'api_key',
			'api-key',
			'apikey',
			'token',
			'auth',
			'authorization',
			'ssn',
			'social_security',
			'credit_card',
			'card_number',
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
			'password',
			'passwd',
			'secret',
			'api_key',
			'api-key',
			'apikey',
			'token',
			'auth',
			'authorization',
			'bearer', // ExportService had this extra pattern
			'ssn',
			'social_security',
			'credit_card',
			'card_number',
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

	/**
	 * Test is_sensitive() with custom patterns containing mixed case
	 *
	 * Custom patterns added by users (e.g., "primaryPhone", "primaryEmail")
	 * should match field names regardless of case.
	 *
	 * @return void
	 */
	public function testIsSensitiveWithMixedCaseCustomPatterns(): void {
		// Test camelCase field names with lowercase default patterns.
		// The fix ensures pattern comparison is case-insensitive on BOTH sides.

		// "userPassword" contains "password" (default pattern).
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'userPassword' ),
			'CamelCase field "userPassword" should match pattern "password"'
		);

		// "accessToken" contains "token" (default pattern).
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'accessToken' ),
			'CamelCase field "accessToken" should match pattern "token"'
		);

		// "apiSecretKey" contains "secret" (default pattern).
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'apiSecretKey' ),
			'CamelCase field "apiSecretKey" should match pattern "secret"'
		);

		// "myApiKey" contains "apikey" (default pattern, no underscore).
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'myApiKey' ),
			'CamelCase field "myApiKey" should match pattern "apikey"'
		);

		// "bearerToken" contains both "bearer" and "token".
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'bearerToken' ),
			'CamelCase field "bearerToken" should match pattern "bearer" or "token"'
		);

		// "AuthorizationHeader" contains "authorization".
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'AuthorizationHeader' ),
			'PascalCase field "AuthorizationHeader" should match pattern "authorization"'
		);

		// Test that non-matching fields still return false.
		$this->assertFalse(
			SensitiveDataPatterns::is_sensitive( 'firstName' ),
			'Field "firstName" should not match any sensitive pattern'
		);

		$this->assertFalse(
			SensitiveDataPatterns::is_sensitive( 'postalCode' ),
			'Field "postalCode" should not match any sensitive pattern'
		);

		$this->assertFalse(
			SensitiveDataPatterns::is_sensitive( 'emailAddress' ),
			'Field "emailAddress" should not match any default sensitive pattern'
		);
	}

	/**
	 * Test is_sensitive() with custom patterns from Settings
	 *
	 * This test verifies the fix for the bug where custom patterns with
	 * uppercase letters (e.g., "primaryPhone") didn't match field names
	 * because the pattern wasn't converted to lowercase before comparison.
	 *
	 * @return void
	 */
	public function testIsSensitiveWithCustomPatternsFromSettings(): void {
		// Get the settings instance.
		$settings = Settings::instance();

		// Store original patterns to restore later.
		$original_patterns = $settings->get_sensitive_patterns();

		// Add custom patterns with mixed case (as a user might enter them).
		$custom_patterns = array(
			'primaryPhone',  // CamelCase
			'primaryEmail',  // CamelCase
			'SSN_Number',    // Mixed case with underscore
			'CreditScore',   // PascalCase
		);
		$settings->set( 'sensitive_patterns', $custom_patterns );

		// Verify custom patterns are in get_all().
		$all_patterns = SensitiveDataPatterns::get_all();
		foreach ( $custom_patterns as $pattern ) {
			$this->assertContains(
				$pattern,
				$all_patterns,
				"Custom pattern '{$pattern}' should be included in get_all()"
			);
		}

		// Test that is_sensitive() matches with case variations.
		// This is the core test for the bug fix.
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'primaryPhone' ),
			'Field "primaryPhone" should match custom pattern "primaryPhone"'
		);
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'primaryphone' ),
			'Field "primaryphone" (lowercase) should match custom pattern "primaryPhone"'
		);
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'PRIMARYPHONE' ),
			'Field "PRIMARYPHONE" (uppercase) should match custom pattern "primaryPhone"'
		);

		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'primaryEmail' ),
			'Field "primaryEmail" should match custom pattern "primaryEmail"'
		);
		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'primaryemail' ),
			'Field "primaryemail" (lowercase) should match custom pattern "primaryEmail"'
		);

		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'user_ssn_number' ),
			'Field "user_ssn_number" should match custom pattern "SSN_Number"'
		);

		$this->assertTrue(
			SensitiveDataPatterns::is_sensitive( 'applicantCreditScore' ),
			'Field "applicantCreditScore" should match custom pattern "CreditScore"'
		);

		// Non-matching fields should still return false.
		$this->assertFalse(
			SensitiveDataPatterns::is_sensitive( 'firstName' ),
			'Field "firstName" should not match any pattern'
		);
		$this->assertFalse(
			SensitiveDataPatterns::is_sensitive( 'postalCode' ),
			'Field "postalCode" should not match any pattern'
		);

		// Restore original patterns.
		$settings->set( 'sensitive_patterns', $original_patterns );
	}
}
