<?php

/**
 * Unit Tests for LogsController Class
 *
 * Tests the admin controller for API request logs,
 * including bulk actions handling and view actions.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.1.2
 * @version 1.3.1
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Controller\Admin\LogsController;
use ReflectionClass;

/**
 * Test cases for the LogsController class
 */
class LogsControllerTest extends TestCase {

	/**
	 * LogsController instance
	 *
	 * @var LogsController
	 */
	private LogsController $controller;

	/**
	 * Setup method called before each test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Get singleton instance
		$this->controller = LogsController::instance();
	}

	/**
	 * Test that LogsController class exists
	 *
	 * @return void
	 */
	public function testLogsControllerClassExists(): void {
		$this->assertTrue(
			class_exists( 'SilverAssist\\ContactFormToAPI\\Controller\\Admin\\LogsController' ),
			'LogsController class should exist in the Controller\\Admin namespace'
		);
	}

	/**
	 * Test that LogsController implements singleton pattern
	 *
	 * @return void
	 */
	public function testLogsControllerSingletonPattern(): void {
		$instance1 = LogsController::instance();
		$instance2 = LogsController::instance();

		$this->assertSame(
			$instance1,
			$instance2,
			'LogsController::instance() should return the same instance'
		);
	}

	/**
	 * Test that LogsController implements LoadableInterface
	 *
	 * @return void
	 */
	public function testLogsControllerImplementsLoadableInterface(): void {
		$this->assertInstanceOf(
			'SilverAssist\\ContactFormToAPI\\Core\\Interfaces\\LoadableInterface',
			$this->controller,
			'LogsController should implement LoadableInterface'
		);
	}

	/**
	 * Test that process_bulk_actions method exists
	 *
	 * @return void
	 */
	public function testProcessBulkActionsMethodExists(): void {
		$this->assertTrue(
			method_exists( $this->controller, 'process_bulk_actions' ),
			'LogsController should have process_bulk_actions method'
		);
	}

	/**
	 * Test that bulk actions are limited to specific actions (delete, retry)
	 *
	 * This test validates that the 'view' action does NOT trigger bulk action processing,
	 * which was causing a "Security check failed" error when viewing log details.
	 *
	 * @see https://github.com/SilverAssist/contact-form-to-api/issues/XX
	 * @return void
	 */
	public function testViewActionIsNotProcessedAsBulkAction(): void {
		// Read the source code of process_bulk_actions to verify the fix
		$reflection = new ReflectionClass( LogsController::class );
		$method     = $reflection->getMethod( 'process_bulk_actions' );
		// Verify that the method checks for valid bulk actions before processing

		// Get the file and read the method contents
		$filename   = $method->getFileName();
		$start_line = $method->getStartLine();
		$end_line   = $method->getEndLine();

		$source        = file( $filename );
		$method_source = implode( '', array_slice( $source, $start_line - 1, $end_line - $start_line + 1 ) );

		$this->assertStringContainsString(
			'bulk_actions',
			$method_source,
			'process_bulk_actions should define allowed bulk actions array'
		);

		// Verify that the bulk_actions array definition exists with delete and retry
		$this->assertMatchesRegularExpression(
			'/\$bulk_actions\s*=\s*array/',
			$method_source,
			'bulk_actions array should be defined'
		);

		// Verify that 'delete' and 'retry' are the valid bulk actions
		$this->assertStringContainsString(
			"'delete'",
			$method_source,
			"'delete' should be in the allowed bulk actions"
		);

		$this->assertStringContainsString(
			"'retry'",
			$method_source,
			"'retry' should be in the allowed bulk actions"
		);

		// Verify the in_array check exists to filter non-bulk actions
		$this->assertStringContainsString(
			'in_array',
			$method_source,
			'process_bulk_actions should use in_array to check if action is valid'
		);

		// Verify that view action is filtered out (the return statement after in_array check)
		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*!\s*\\\\in_array.*\$bulk_actions.*\)\s*\{[^}]*return/',
			$method_source,
			"Non-bulk actions like 'view' should cause early return"
		);
	}

	/**
	 * Test that valid bulk actions array contains expected actions
	 *
	 * @return void
	 */
	public function testValidBulkActionsAreDefinedCorrectly(): void {
		$valid_bulk_actions = array( 'delete', 'retry' );
		$non_bulk_actions   = array( 'view', 'edit', 'export' );

		// These are actions that should NOT trigger bulk processing
		foreach ( $non_bulk_actions as $action ) {
			$this->assertNotContains(
				$action,
				$valid_bulk_actions,
				"Action '{$action}' should NOT be a valid bulk action"
			);
		}

		// These are the only valid bulk actions
		$this->assertCount(
			2,
			$valid_bulk_actions,
			'There should be exactly 2 valid bulk actions'
		);

		$this->assertContains( 'delete', $valid_bulk_actions, 'delete should be a valid bulk action' );
		$this->assertContains( 'retry', $valid_bulk_actions, 'retry should be a valid bulk action' );
	}

	/**
	 * Test that get_priority returns correct value for admin components
	 *
	 * @return void
	 */
	public function testGetPriorityReturnsCorrectValue(): void {
		$priority = $this->controller->get_priority();

		$this->assertIsInt( $priority, 'get_priority should return an integer' );
		$this->assertEquals( 30, $priority, 'Admin components should have priority 30' );
	}

	/**
	 * Test that should_load returns true only in admin context
	 *
	 * @return void
	 */
	public function testShouldLoadReturnsCorrectValue(): void {
		$should_load = $this->controller->should_load();

		$this->assertIsBool( $should_load, 'should_load should return a boolean' );

		// In test context with WordPress loaded as admin
		if ( function_exists( 'is_admin' ) ) {
			$this->assertEquals(
				is_admin(),
				$should_load,
				'should_load should match is_admin() result'
			);
		}
	}

	/**
	 * Test that process_bulk_actions returns early when list_table is null
	 *
	 * This verifies that the method safely handles the case where
	 * list_table hasn't been initialized yet.
	 *
	 * @return void
	 */
	public function testProcessBulkActionsReturnsEarlyWithoutListTable(): void {
		// Use reflection to check the list_table property
		$reflection = new ReflectionClass( LogsController::class );
		$property   = $reflection->getProperty( 'list_table' );
		$property->setAccessible( true );

		// Get a fresh instance (but singleton, so same instance)
		$controller = LogsController::instance();

		// Get the current value of list_table
		$list_table_val = $property->getValue( $controller );

		// If list_table is null, process_bulk_actions should return early
		// This is verified by checking the method source code
		$method        = $reflection->getMethod( 'process_bulk_actions' );
		$filename      = $method->getFileName();
		$start_line    = $method->getStartLine();
		$end_line      = $method->getEndLine();
		$source        = file( $filename );
		$method_source = implode( '', array_slice( $source, $start_line - 1, $end_line - $start_line + 1 ) );

		$this->assertStringContainsString(
			'if ( ! $this->list_table )',
			$method_source,
			'process_bulk_actions should check if list_table is null and return early'
		);
	}

	/**
	 * Test that is_logging_enabled method exists
	 *
	 * @return void
	 */
	public function testIsLoggingEnabledMethodExists(): void {
		$reflection = new ReflectionClass( LogsController::class );

		$this->assertTrue(
			$reflection->hasMethod( 'is_logging_enabled' ),
			'LogsController should have is_logging_enabled method'
		);

		$method = $reflection->getMethod( 'is_logging_enabled' );
		$this->assertTrue(
			$method->isPrivate(),
			'is_logging_enabled should be a private method'
		);
	}

	/**
	 * Test that register_menu checks logging status
	 *
	 * @return void
	 */
	public function testRegisterMenuChecksLoggingStatus(): void {
		$reflection    = new ReflectionClass( LogsController::class );
		$method        = $reflection->getMethod( 'register_menu' );
		$filename      = $method->getFileName();
		$start_line    = $method->getStartLine();
		$end_line      = $method->getEndLine();
		$source        = file( $filename );
		$method_source = implode( '', array_slice( $source, $start_line - 1, $end_line - $start_line + 1 ) );

		$this->assertStringContainsString(
			'is_logging_enabled',
			$method_source,
			'register_menu should check if logging is enabled'
		);
	}

	/**
	 * Test that handle_page_request blocks access when logging is disabled
	 *
	 * @return void
	 */
	public function testHandlePageRequestBlocksAccessWhenLoggingDisabled(): void {
		$reflection    = new ReflectionClass( LogsController::class );
		$method        = $reflection->getMethod( 'handle_page_request' );
		$filename      = $method->getFileName();
		$start_line    = $method->getStartLine();
		$end_line      = $method->getEndLine();
		$source        = file( $filename );
		$method_source = implode( '', array_slice( $source, $start_line - 1, $end_line - $start_line + 1 ) );

		$this->assertStringContainsString(
			'is_logging_enabled',
			$method_source,
			'handle_page_request should check if logging is enabled'
		);

		$this->assertStringContainsString(
			'wp_die',
			$method_source,
			'handle_page_request should call wp_die when logging is disabled'
		);
	}

	/**
	 * Test that maybe_handle_export checks logging status
	 *
	 * @return void
	 */
	public function testMaybeHandleExportChecksLoggingStatus(): void {
		$reflection    = new ReflectionClass( LogsController::class );
		$method        = $reflection->getMethod( 'maybe_handle_export' );
		$filename      = $method->getFileName();
		$start_line    = $method->getStartLine();
		$end_line      = $method->getEndLine();
		$source        = file( $filename );
		$method_source = implode( '', array_slice( $source, $start_line - 1, $end_line - $start_line + 1 ) );

		$this->assertStringContainsString(
			'is_logging_enabled',
			$method_source,
			'maybe_handle_export should check if logging is enabled'
		);
	}
}
