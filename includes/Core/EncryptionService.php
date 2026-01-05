<?php
/**
 * Encryption Service
 *
 * Provides transparent database-level encryption for sensitive request data
 * using libsodium (Sodium) for secure, authenticated encryption.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since 1.4.0
 * @version 1.4.0
 * @author Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Core;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;
use SilverAssist\ContactFormToAPI\Exceptions\DecryptionException;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

\defined( 'ABSPATH' ) || exit;

/**
 * Class EncryptionService
 *
 * Handles encryption and decryption of sensitive data using libsodium.
 * Uses WordPress AUTH_KEY for key derivation to avoid separate key management.
 *
 * @since 1.4.0
 */
class EncryptionService implements LoadableInterface {

	/**
	 * Encryption version identifier
	 *
	 * @since 1.4.0
	 * @var int
	 */
	private const VERSION = 1;

	/**
	 * Plugin-specific salt for key derivation
	 *
	 * @since 1.4.0
	 * @var string
	 */
	private const PLUGIN_SALT = 'cf7_api_encryption_v1';

	/**
	 * Singleton instance
	 *
	 * @var EncryptionService|null
	 */
	private static ?EncryptionService $instance = null;

	/**
	 * Cached encryption key
	 *
	 * @var string|null
	 */
	private ?string $key = null;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Settings instance
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.4.0
	 * @return EncryptionService
	 */
	public static function instance(): EncryptionService {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		// Empty - initialization happens in init().
	}

	/**
	 * Initialize encryption service
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		// Get settings instance.
		if ( \class_exists( Settings::class ) ) {
			$this->settings = Settings::instance();
		}

		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @since 1.4.0
	 * @return int
	 */
	public function get_priority(): int {
		return 10; // Core priority - load early.
	}

	/**
	 * Determine if should load
	 *
	 * @since 1.4.0
	 * @return bool
	 */
	public function should_load(): bool {
		// Only load if Sodium extension is available.
		return \extension_loaded( 'sodium' );
	}

	/**
	 * Encrypt plaintext data
	 *
	 * Uses libsodium's authenticated encryption (crypto_secretbox) which includes:
	 * - Encryption (XSalsa20)
	 * - Authentication (Poly1305 MAC)
	 * - Unique nonce per encryption
	 *
	 * @since 1.4.0
	 * @param string $plaintext Data to encrypt.
	 * @return string Base64-encoded encrypted data with nonce prepended.
	 * @throws \Exception If encryption fails.
	 */
	public function encrypt( string $plaintext ): string {
		// Check if encryption is enabled.
		if ( ! $this->is_encryption_enabled() ) {
			return $plaintext;
		}

		// Check if Sodium is available.
		if ( ! \extension_loaded( 'sodium' ) ) {
			return $plaintext;
		}

		try {
			// Generate unique nonce for this encryption.
			$nonce = \random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			// Encrypt with authenticated encryption.
			$ciphertext = \sodium_crypto_secretbox( $plaintext, $nonce, $this->get_key() );

			// Prepend nonce to ciphertext and encode.
			$encrypted = \base64_encode( $nonce . $ciphertext );

			// Clear sensitive data from memory.
			\sodium_memzero( $plaintext );
			\sodium_memzero( $nonce );
			\sodium_memzero( $ciphertext );

			return $encrypted;

		} catch ( \Exception $e ) {
			// Log encryption failure (without sensitive data).
			if ( \class_exists( DebugLogger::class ) ) {
				DebugLogger::instance()->error( 'Encryption failed: ' . $e->getMessage() );
			}

			// Return plaintext if encryption fails (graceful degradation).
			return $plaintext;
		}
	}

	/**
	 * Decrypt ciphertext data
	 *
	 * Handles both encrypted data and legacy plaintext data.
	 * Automatically detects format and returns appropriate result.
	 *
	 * @since 1.4.0
	 * @param string $data Data to decrypt (or plaintext).
	 * @return string Decrypted plaintext data.
	 * @throws DecryptionException If decryption fails due to tampering or corruption.
	 */
	public function decrypt( string $data ): string {
		// Check if encryption is enabled.
		if ( ! $this->is_encryption_enabled() ) {
			return $data;
		}

		// Check if Sodium is available.
		if ( ! \extension_loaded( 'sodium' ) ) {
			return $data;
		}

		// Check if data is legacy plaintext (not encrypted).
		if ( $this->is_plaintext_json( $data ) ) {
			return $data;
		}

		// Check if data looks encrypted (base64).
		if ( ! $this->is_encrypted( $data ) ) {
			return $data;
		}

		try {
			// Decode from base64.
			$decoded = \base64_decode( $data, true );
			if ( false === $decoded ) {
				throw new DecryptionException( 'Invalid base64 encoding' );
			}

			// Extract nonce and ciphertext.
			$nonce      = \substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = \substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			// Decrypt with authenticated decryption.
			$plaintext = \sodium_crypto_secretbox_open( $ciphertext, $nonce, $this->get_key() );

			// Check for decryption failure (returns false on tampering/corruption).
			if ( false === $plaintext ) {
				throw new DecryptionException( 'Decryption failed - data may be tampered or corrupted' );
			}

			// Clear sensitive data from memory.
			\sodium_memzero( $decoded );
			\sodium_memzero( $nonce );
			\sodium_memzero( $ciphertext );

			return $plaintext;

		} catch ( DecryptionException $e ) {
			// Re-throw decryption exceptions.
			throw $e;
		} catch ( \Exception $e ) {
			// Log decryption failure (without sensitive data).
			if ( \class_exists( DebugLogger::class ) ) {
				DebugLogger::instance()->error( 'Decryption failed: ' . $e->getMessage() );
			}

			// Throw exception to indicate failure.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception passed to constructor, not output.
			throw new DecryptionException( 'Decryption failed: ' . $e->getMessage(), 0, $e );
		}
	}

	/**
	 * Check if data is encrypted
	 *
	 * Determines if data appears to be encrypted by checking format.
	 *
	 * @since 1.4.0
	 * @param string $data Data to check.
	 * @return bool True if data appears encrypted.
	 */
	public function is_encrypted( string $data ): bool {
		// Encrypted data should be base64 encoded.
		if ( ! \preg_match( '/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data ) ) {
			return false;
		}

		// Try to decode.
		$decoded = \base64_decode( $data, true );
		if ( false === $decoded ) {
			return false;
		}

		// Check if length is appropriate (nonce + ciphertext).
		$min_length = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + 1;
		if ( \strlen( $decoded ) < $min_length ) {
			return false;
		}

		return true;
	}

	/**
	 * Get encryption version
	 *
	 * Returns current encryption version identifier.
	 *
	 * @since 1.4.0
	 * @return int Version number.
	 */
	public function get_version(): int {
		return self::VERSION;
	}

	/**
	 * Check if plaintext data is valid JSON
	 *
	 * Used to detect legacy unencrypted data in database.
	 *
	 * @since 1.4.0
	 * @param string $data Data to check.
	 * @return bool True if data is valid JSON.
	 */
	private function is_plaintext_json( string $data ): bool {
		\json_decode( $data );
		return \json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Derive encryption key from WordPress salts
	 *
	 * Uses HKDF (HMAC-based Key Derivation Function) to derive a secure
	 * encryption key from WordPress AUTH_KEY constant.
	 *
	 * @since 1.4.0
	 * @return string Binary encryption key (32 bytes).
	 */
	private function derive_key(): string {
		// Cache the key to avoid repeated derivation.
		if ( null !== $this->key ) {
			return $this->key;
		}

		// Use WordPress AUTH_KEY as input key material.
		$ikm = \defined( 'AUTH_KEY' ) ? AUTH_KEY : 'cf7_api_fallback_key_' . \ABSPATH;

		// Derive a 32-byte key using HKDF with SHA-256.
		$this->key = \hash_hkdf(
			'sha256',
			$ikm,
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
			self::PLUGIN_SALT
		);

		return $this->key;
	}

	/**
	 * Get encryption key
	 *
	 * Returns the encryption key, deriving it if needed.
	 *
	 * @since 1.4.0
	 * @return string Binary encryption key.
	 */
	private function get_key(): string {
		return $this->derive_key();
	}

	/**
	 * Check if encryption is enabled via settings
	 *
	 * @since 1.4.0
	 * @return bool
	 */
	private function is_encryption_enabled(): bool {
		// Default to enabled if settings not available.
		if ( ! $this->settings ) {
			return true;
		}

		// Check settings.
		return (bool) $this->settings->get( 'encryption_enabled', true );
	}

	/**
	 * Check if Sodium extension is available
	 *
	 * Public method for external checks.
	 *
	 * @since 1.4.0
	 * @return bool True if Sodium is available.
	 */
	public static function is_sodium_available(): bool {
		return \extension_loaded( 'sodium' );
	}
}
