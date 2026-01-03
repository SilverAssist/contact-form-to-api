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
}
