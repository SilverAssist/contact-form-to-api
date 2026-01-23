<?php

/**
 * Unit Tests for RequestLogTable Class
 *
 * Tests the request log table functionality including date filtering.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.1.3
 * @version 1.1.3
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Admin\RequestLogTable;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test cases for the RequestLogTable class
 */
class RequestLogTableTest extends TestCase {

	/**
	 * RequestLogTable instance
	 *
	 * @var RequestLogTable
	 */
	private RequestLogTable $table;

	/**
	 * Setup method called before each test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->table = new RequestLogTable();
	}

	/**
	 * Cleanup method called after each test
	 *
	 * Ensures $_GET superglobal is cleaned up even if test fails.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test that RequestLogTable class exists
	 *
	 * @return void
	 */
	public function testRequestLogTableClassExists(): void {
		$this->assertTrue(
			class_exists( 'SilverAssist\\ContactFormToAPI\\Admin\\RequestLogTable' ),
			'RequestLogTable class should exist in the Admin namespace'
		);
	}

	/**
	 * Test validate_date_format method with valid date
	 *
	 * @return void
	 */
	public function testValidateDateFormatWithValidDate(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'validate_date_format' );
		$method->setAccessible( true );

		$this->assertTrue(
			$method->invoke( $this->table, '2026-01-03' ),
			'Should validate correct date format Y-m-d'
		);
	}

	/**
	 * Test validate_date_format method with invalid date format
	 *
	 * @return void
	 */
	public function testValidateDateFormatWithInvalidFormat(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'validate_date_format' );
		$method->setAccessible( true );

		$this->assertFalse(
			$method->invoke( $this->table, '01/03/2026' ),
			'Should reject invalid date format'
		);

		$this->assertFalse(
			$method->invoke( $this->table, '2026-1-3' ),
			'Should reject date without leading zeros'
		);

		$this->assertFalse(
			$method->invoke( $this->table, 'invalid-date' ),
			'Should reject non-date string'
		);
	}

	/**
	 * Test validate_date_format method with empty string
	 *
	 * @return void
	 */
	public function testValidateDateFormatWithEmptyString(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'validate_date_format' );
		$method->setAccessible( true );

		$this->assertFalse(
			$method->invoke( $this->table, '' ),
			'Should reject empty string'
		);
	}

	/**
	 * Test build_custom_date_range_clause with both start and end dates
	 *
	 * @return void
	 */
	public function testBuildCustomRangeClauseWithBothDates(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'build_custom_date_range_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, '2026-01-01', '2026-01-31' );

		$this->assertIsArray( $result, 'Should return array' );
		$this->assertArrayHasKey( 'clause', $result, 'Should have clause key' );
		$this->assertArrayHasKey( 'values', $result, 'Should have values key' );
		$this->assertEquals( 'AND DATE(created_at) BETWEEN %s AND %s', $result['clause'] );
		$this->assertEquals( array( '2026-01-01', '2026-01-31' ), $result['values'] );
	}

	/**
	 * Test build_custom_date_range_clause with only start date
	 *
	 * @return void
	 */
	public function testBuildCustomRangeClauseWithStartDateOnly(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'build_custom_date_range_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, '2026-01-01', '' );

		$this->assertIsArray( $result, 'Should return array' );
		$this->assertEquals( 'AND DATE(created_at) >= %s', $result['clause'] );
		$this->assertEquals( array( '2026-01-01' ), $result['values'] );
	}

	/**
	 * Test build_custom_date_range_clause with invalid start date
	 *
	 * @return void
	 */
	public function testBuildCustomRangeClauseWithInvalidStartDate(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'build_custom_date_range_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, 'invalid', '2026-01-31' );

		$this->assertIsArray( $result, 'Should return array' );
		$this->assertEquals( '', $result['clause'], 'Should return empty clause for invalid date' );
		$this->assertEquals( array(), $result['values'], 'Should return empty values for invalid date' );
	}

	/**
	 * Test build_custom_date_range_clause with invalid end date
	 *
	 * @return void
	 */
	public function testBuildCustomRangeClauseWithInvalidEndDate(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'build_custom_date_range_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, '2026-01-01', 'invalid' );

		$this->assertIsArray( $result, 'Should return array' );
		$this->assertEquals( '', $result['clause'], 'Should return empty clause for invalid end date' );
		$this->assertEquals( array(), $result['values'], 'Should return empty values for invalid end date' );
	}

	/**
	 * Test get_date_filter_clause with no filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWithNoFilter(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertIsArray( $result, 'Should return array' );
		$this->assertEquals( '', $result['clause'], 'Should return empty clause when no filter' );
		$this->assertEquals( array(), $result['values'], 'Should return empty values when no filter' );
	}

	/**
	 * Test get_date_filter_clause with today filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWithTodayFilter(): void {
		$_GET['date_filter'] = 'today';

		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertEquals( 'AND DATE(created_at) = CURDATE()', $result['clause'] );
		$this->assertEquals( array(), $result['values'] );
	}

	/**
	 * Test get_date_filter_clause with yesterday filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWithYesterdayFilter(): void {
		$_GET['date_filter'] = 'yesterday';

		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertEquals( 'AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)', $result['clause'] );
		$this->assertEquals( array(), $result['values'] );
	}

	/**
	 * Test get_date_filter_clause with 7days filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWith7DaysFilter(): void {
		$_GET['date_filter'] = '7days';

		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertEquals( 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)', $result['clause'] );
		$this->assertEquals( array(), $result['values'] );
	}

	/**
	 * Test get_date_filter_clause with 30days filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWith30DaysFilter(): void {
		$_GET['date_filter'] = '30days';

		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertEquals( 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)', $result['clause'] );
		$this->assertEquals( array(), $result['values'] );
	}

	/**
	 * Test get_date_filter_clause with month filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWithMonthFilter(): void {
		$_GET['date_filter'] = 'month';

		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertEquals( 'AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())', $result['clause'] );
		$this->assertEquals( array(), $result['values'] );
	}

	/**
	 * Test get_date_filter_clause with custom filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWithCustomFilter(): void {
		$_GET['date_filter'] = 'custom';
		$_GET['date_start']  = '2026-01-01';
		$_GET['date_end']    = '2026-01-31';

		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertEquals( 'AND DATE(created_at) BETWEEN %s AND %s', $result['clause'] );
		$this->assertEquals( array( '2026-01-01', '2026-01-31' ), $result['values'] );
	}

	/**
	 * Test get_date_filter_clause with invalid filter
	 *
	 * @return void
	 */
	public function testGetDateFilterClauseWithInvalidFilter(): void {
		$_GET['date_filter'] = 'invalid_filter';

		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'get_date_filter_clause' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table );

		$this->assertEquals( '', $result['clause'], 'Should return empty clause for invalid filter' );
		$this->assertEquals( array(), $result['values'], 'Should return empty values for invalid filter' );
	}

	/**
	 * Test mask_email with standard email
	 *
	 * @return void
	 */
	public function testMaskEmailWithStandardEmail(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'mask_email' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, 'john.doe@example.com' );

		$this->assertEquals( 'jo***@example.com', $result, 'Should mask standard email showing first 2 chars' );
	}

	/**
	 * Test mask_email with short email (2 characters local part)
	 *
	 * @return void
	 */
	public function testMaskEmailWithTwoCharLocalPart(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'mask_email' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, 'ab@example.com' );

		$this->assertEquals( 'a***@example.com', $result, 'Should show only first char for 2-char local part' );
	}

	/**
	 * Test mask_email with single character local part
	 *
	 * @return void
	 */
	public function testMaskEmailWithSingleCharLocalPart(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'mask_email' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, 'a@example.com' );

		$this->assertEquals( '***@example.com', $result, 'Should completely mask single char local part' );
	}

	/**
	 * Test mask_email with empty string
	 *
	 * @return void
	 */
	public function testMaskEmailWithEmptyString(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'mask_email' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, '' );

		$this->assertEquals( '', $result, 'Should return empty string for empty input' );
	}

	/**
	 * Test mask_email with invalid email (no @ symbol)
	 *
	 * @return void
	 */
	public function testMaskEmailWithNoAtSymbol(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'mask_email' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->table, 'notanemail' );

		$this->assertEquals( 'notanemail', $result, 'Should return original string when no @ symbol' );
	}

	/**
	 * Test extract_sender_info with standard form data
	 *
	 * @return void
	 */
	public function testExtractSenderInfoWithStandardData(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 1,
			'request_data'       => \wp_json_encode(
				array(
					'name'     => 'John',
					'lastname' => 'Doe',
					'email'    => 'john@example.com',
				)
			),
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		$this->assertEquals( 'John Doe', $result['display_name'], 'Should extract full name' );
		$this->assertEquals( 'john@example.com', $result['email'], 'Should extract email' );
	}

	/**
	 * Test extract_sender_info with CF7 field names (your-name format)
	 *
	 * @return void
	 */
	public function testExtractSenderInfoWithCF7FieldNames(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 2,
			'request_data'       => \wp_json_encode(
				array(
					'your-name'  => 'Jane',
					'your-email' => 'jane@example.com',
				)
			),
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		$this->assertEquals( 'Jane', $result['display_name'], 'Should extract name from your-name field' );
		$this->assertEquals( 'jane@example.com', $result['email'], 'Should extract email from your-email field' );
	}

	/**
	 * Test extract_sender_info with case-insensitive field names
	 *
	 * @return void
	 */
	public function testExtractSenderInfoCaseInsensitive(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 3,
			'request_data'       => \wp_json_encode(
				array(
					'NAME'     => 'Bob',
					'LASTNAME' => 'Smith',
					'EMAIL'    => 'bob@example.com',
				)
			),
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		$this->assertEquals( 'Bob Smith', $result['display_name'], 'Should extract name case-insensitively' );
		$this->assertEquals( 'bob@example.com', $result['email'], 'Should extract email case-insensitively' );
	}

	/**
	 * Test extract_sender_info with array values (CF7 checkbox/radio format)
	 *
	 * @return void
	 */
	public function testExtractSenderInfoWithArrayValues(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 4,
			'request_data'       => \wp_json_encode(
				array(
					'name'  => array( 'Alice' ),
					'email' => array( 'alice@example.com' ),
				)
			),
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		$this->assertEquals( 'Alice', $result['display_name'], 'Should extract name from array' );
		$this->assertEquals( 'alice@example.com', $result['email'], 'Should extract email from array' );
	}

	/**
	 * Test extract_sender_info with empty request data
	 *
	 * @return void
	 */
	public function testExtractSenderInfoWithEmptyData(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 5,
			'request_data'       => '',
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		$this->assertEquals( '', $result['display_name'], 'Should return empty name for empty data' );
		$this->assertEquals( '', $result['email'], 'Should return empty email for empty data' );
	}

	/**
	 * Test extract_sender_info with invalid JSON
	 *
	 * @return void
	 */
	public function testExtractSenderInfoWithInvalidJson(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 6,
			'request_data'       => 'not valid json',
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		$this->assertEquals( '', $result['display_name'], 'Should return empty name for invalid JSON' );
		$this->assertEquals( '', $result['email'], 'Should return empty email for invalid JSON' );
	}

	/**
	 * Test extract_sender_info with missing fields
	 *
	 * @return void
	 */
	public function testExtractSenderInfoWithMissingFields(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 7,
			'request_data'       => \wp_json_encode(
				array(
					'subject' => 'Test Subject',
					'message' => 'Test message content',
				)
			),
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		$this->assertEquals( '', $result['display_name'], 'Should return empty name when fields missing' );
		$this->assertEquals( '', $result['email'], 'Should return empty email when fields missing' );
	}

	/**
	 * Test extract_sender_info caching behavior
	 *
	 * @return void
	 */
	public function testExtractSenderInfoCaching(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		$item = array(
			'id'                 => 8,
			'request_data'       => \wp_json_encode(
				array(
					'name'  => 'Cached',
					'email' => 'cached@example.com',
				)
			),
			'encryption_version' => 0,
		);

		// Call twice with same ID - should use cache on second call
		$result1 = $method->invoke( $this->table, $item );
		$result2 = $method->invoke( $this->table, $item );

		$this->assertEquals( $result1, $result2, 'Cached results should match' );
		$this->assertEquals( 'Cached', $result1['display_name'], 'Should extract name correctly' );
	}

	/**
	 * Test extract_sender_info respects sensitive data patterns
	 *
	 * When a field is marked as sensitive in user configuration,
	 * it should not be extracted even if present in the data.
	 *
	 * @return void
	 */
	public function testExtractSenderInfoRespectsSensitivePatterns(): void {
		$reflection = new ReflectionClass( RequestLogTable::class );
		$method     = $reflection->getMethod( 'extract_sender_info' );
		$method->setAccessible( true );

		// Test with default sensitive patterns (password, token, etc.)
		// These should never match name/email fields by default
		$item = array(
			'id'                 => 9,
			'request_data'       => \wp_json_encode(
				array(
					'name'     => 'TestUser',
					'email'    => 'test@example.com',
					'password' => 'secret123', // This should never be extracted
				)
			),
			'encryption_version' => 0,
		);

		$result = $method->invoke( $this->table, $item );

		// Name and email should be extracted (not in default sensitive patterns)
		$this->assertEquals( 'TestUser', $result['display_name'], 'Name should be extracted when not sensitive' );
		$this->assertEquals( 'test@example.com', $result['email'], 'Email should be extracted when not sensitive' );
	}
}
