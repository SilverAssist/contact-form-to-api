<?php
/**
 * Encryption Service Tests
 *
 * Tests for the EncryptionService class functionality including encryption,
 * decryption, key derivation, and error handling.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.3.0
 * @version 1.3.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Security;

use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;
use SilverAssist\ContactFormToAPI\Exception\DecryptionException;
use WP_UnitTestCase;

/**
 * Test cases for EncryptionService class
 */
class EncryptionServiceTest extends WP_UnitTestCase {

	/**
	 * Encryption service instance
	 *
	 * @var EncryptionService
	 */
	private EncryptionService $service;

	/**
	 * Set up before each test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable encryption for tests.
		\update_option( 'cf7_api_global_settings', array( 'encryption_enabled' => true ) );

		$this->service = EncryptionService::instance();
		$this->service->init();
	}

	/**
	 * Tear down after each test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up settings.
		\delete_option( 'cf7_api_global_settings' );

		parent::tearDown();
	}

	/**
	 * Test encryption service can be instantiated
	 *
	 * @return void
	 */
	public function test_encryption_service_can_be_instantiated(): void {
		$this->assertInstanceOf( EncryptionService::class, $this->service );
	}

	/**
	 * Test Sodium extension is available
	 *
	 * @return void
	 */
	public function test_sodium_extension_is_available(): void {
		$this->assertTrue( EncryptionService::is_sodium_available() );
		$this->assertTrue( \extension_loaded( 'sodium' ) );
	}

	/**
	 * Test encrypt-decrypt roundtrip
	 *
	 * @return void
	 */
	public function test_encrypt_decrypt_roundtrip(): void {
		$original = '{"email": "test@example.com", "phone": "123456789"}';

		$encrypted = $this->service->encrypt( $original );
		$decrypted = $this->service->decrypt( $encrypted );

		$this->assertNotEquals( $original, $encrypted, 'Encrypted data should differ from original' );
		$this->assertEquals( $original, $decrypted, 'Decrypted data should match original' );
	}

	/**
	 * Test unique nonce per encryption
	 *
	 * Same data encrypted twice should produce different ciphertext
	 * because of unique nonces.
	 *
	 * @return void
	 */
	public function test_unique_nonce_per_encryption(): void {
		$data = 'same data';

		$encrypted1 = $this->service->encrypt( $data );
		$encrypted2 = $this->service->encrypt( $data );

		$this->assertNotEquals( $encrypted1, $encrypted2, 'Same data should produce different ciphertext due to unique nonces' );

		// Both should decrypt to same plaintext.
		$decrypted1 = $this->service->decrypt( $encrypted1 );
		$decrypted2 = $this->service->decrypt( $encrypted2 );

		$this->assertEquals( $data, $decrypted1 );
		$this->assertEquals( $data, $decrypted2 );
	}

	/**
	 * Test decrypt legacy plaintext JSON
	 *
	 * Should return plaintext JSON as-is without attempting decryption.
	 *
	 * @return void
	 */
	public function test_decrypt_legacy_plaintext(): void {
		$plaintext = '{"name": "John Doe", "email": "john@test.com"}';

		// Decrypting plaintext should return it unchanged.
		$result = $this->service->decrypt( $plaintext );

		$this->assertEquals( $plaintext, $result );
	}

	/**
	 * Test tampered data fails decryption
	 *
	 * Authenticated encryption should detect tampering.
	 *
	 * @return void
	 */
	public function test_tampered_data_fails_decryption(): void {
		$encrypted = $this->service->encrypt( 'test data' );

		// Tamper with the ciphertext (modify last 5 characters).
		$tampered = \substr( $encrypted, 0, -5 ) . 'XXXXX';

		// Should throw DecryptionException.
		$this->expectException( DecryptionException::class );
		$this->service->decrypt( $tampered );
	}

	/**
	 * Test empty string encryption
	 *
	 * @return void
	 */
	public function test_encrypt_empty_string(): void {
		$empty = '';

		$encrypted = $this->service->encrypt( $empty );
		$decrypted = $this->service->decrypt( $encrypted );

		$this->assertEquals( $empty, $decrypted );
	}

	/**
	 * Test large data encryption
	 *
	 * Verify encryption works with larger payloads.
	 *
	 * @return void
	 */
	public function test_encrypt_large_data(): void {
		// Create ~10KB payload.
		$large_data = \str_repeat( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 200 );

		$encrypted = $this->service->encrypt( $large_data );
		$decrypted = $this->service->decrypt( $encrypted );

		$this->assertEquals( $large_data, $decrypted );
		$this->assertNotEquals( $large_data, $encrypted );
	}

	/**
	 * Test special characters encryption
	 *
	 * Verify encryption handles unicode and special characters.
	 *
	 * @return void
	 */
	public function test_encrypt_special_characters(): void {
		$special = '{"emoji": "😀", "unicode": "Ñoño", "symbols": "<>&\'\""}';

		$encrypted = $this->service->encrypt( $special );
		$decrypted = $this->service->decrypt( $encrypted );

		$this->assertEquals( $special, $decrypted );
	}

	/**
	 * Test encryption version
	 *
	 * @return void
	 */
	public function test_get_version(): void {
		$version = $this->service->get_version();

		$this->assertIsInt( $version );
		$this->assertEquals( 1, $version, 'Current encryption version should be 1' );
	}

	/**
	 * Test is_encrypted detection
	 *
	 * @return void
	 */
	public function test_is_encrypted_detection(): void {
		$plaintext = '{"test": "data"}';
		$encrypted = $this->service->encrypt( $plaintext );

		$this->assertTrue( $this->service->is_encrypted( $encrypted ), 'Should detect encrypted data' );
		$this->assertFalse( $this->service->is_encrypted( $plaintext ), 'Should not detect plaintext JSON as encrypted' );
	}

	/**
	 * Test JSON array encryption
	 *
	 * @return void
	 */
	public function test_encrypt_json_array(): void {
		$data = \wp_json_encode(
			array(
				'name'  => 'Test User',
				'email' => 'test@example.com',
				'phone' => '555-0123',
			)
		);

		$encrypted = $this->service->encrypt( $data );
		$decrypted = $this->service->decrypt( $encrypted );

		$this->assertEquals( $data, $decrypted );

		// Verify JSON structure is preserved.
		$decoded = \json_decode( $decrypted, true );
		$this->assertIsArray( $decoded );
		$this->assertEquals( 'Test User', $decoded['name'] );
	}

	/**
	 * Test invalid base64 decryption
	 *
	 * @return void
	 */
	public function test_invalid_base64_decryption(): void {
		$invalid = 'Not valid base64!@#$%';

		// Should return as-is if not valid encrypted data.
		$result = $this->service->decrypt( $invalid );
		$this->assertEquals( $invalid, $result );
	}

	/**
	 * Test encryption performance
	 *
	 * Verify encryption meets performance requirements (< 5ms per operation).
	 *
	 * @return void
	 */
	public function test_encrypt_performance(): void {
		$data = \wp_json_encode(
			array(
				'email'   => 'test@example.com',
				'message' => \str_repeat( 'x', 1000 ),
			)
		);

		$iterations = 100;
		$start      = \microtime( true );

		for ( $i = 0; $i < $iterations; $i++ ) {
			$this->service->encrypt( $data );
		}

		$elapsed = ( \microtime( true ) - $start ) * 1000; // Convert to milliseconds.
		$avg     = $elapsed / $iterations;

		// 100 encryptions should take less than 50ms (0.5ms each on average).
		$this->assertLessThan( 50, $elapsed, "100 encryptions took {$elapsed}ms (expected < 50ms)" );
		$this->assertLessThan( 1, $avg, "Average encryption time {$avg}ms (expected < 1ms)" );
	}

	/**
	 * Test decryption performance
	 *
	 * Verify decryption meets performance requirements (< 3ms per operation).
	 *
	 * @return void
	 */
	public function test_decrypt_performance(): void {
		$data      = \wp_json_encode(
			array(
				'email'   => 'test@example.com',
				'message' => \str_repeat( 'x', 1000 ),
			)
		);
		$encrypted = $this->service->encrypt( $data );

		$iterations = 100;
		$start      = \microtime( true );

		for ( $i = 0; $i < $iterations; $i++ ) {
			$this->service->decrypt( $encrypted );
		}

		$elapsed = ( \microtime( true ) - $start ) * 1000; // Convert to milliseconds.
		$avg     = $elapsed / $iterations;

		// 100 decryptions should take less than 30ms (0.3ms each on average).
		$this->assertLessThan( 30, $elapsed, "100 decryptions took {$elapsed}ms (expected < 30ms)" );
		$this->assertLessThan( 1, $avg, "Average decryption time {$avg}ms (expected < 1ms)" );
	}
}
