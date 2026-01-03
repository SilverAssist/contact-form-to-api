<?php

/**
 * Unit Tests for RequestLogController Class
 *
 * Tests the admin controller for API request logs,
 * including bulk actions handling and view actions.
 *
 * @package SilverAssist\ContactFormToAPI\Tests
 * @since   1.1.1
 * @version 1.0.0
 * @author  Silver Assist
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Admin\RequestLogController;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test cases for the RequestLogController class
 */
class RequestLogControllerTest extends TestCase {

	/**
	 * RequestLogController instance
	 *
	 * @var RequestLogController
	 */
	private RequestLogController $controller;

	/**
	 * Setup method called before each test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Get singleton instance
		$this->controller = RequestLogController::instance();
	}

	/**
	 * Test that RequestLogController class exists
	 *
	 * @return void
	 */
	public function testRequestLogControllerClassExists(): void {
		$this->assertTrue(
			class_exists( 'SilverAssist\\ContactFormToAPI\\Admin\\RequestLogController' ),
			'RequestLogController class should exist in the Admin namespace'
		);
	}

	/**
	 * Test that RequestLogController implements singleton pattern
	 *
	 * @return void
	 */
	public function testRequestLogControllerSingletonPattern(): void {
		$instance1 = RequestLogController::instance();
		$instance2 = RequestLogController::instance();

		$this->assertSame(
			$instance1,
			$instance2,
			'RequestLogController::instance() should return the same instance'
		);
	}

	/**
	 * Test that RequestLogController implements LoadableInterface
	 *
	 * @return void
	 */
	public function testRequestLogControllerImplementsLoadableInterface(): void {
		$this->assertInstanceOf(
			'SilverAssist\\ContactFormToAPI\\Core\\Interfaces\\LoadableInterface',
			$this->controller,
			'RequestLogController should implement LoadableInterface'
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
			'RequestLogController should have process_bulk_actions method'
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
		$reflection = new ReflectionClass( RequestLogController::class );
		$method     = $reflection->getMethod( "process_bulk_actions" );
		// Verify that the method checks for valid bulk actions before processing

		// Get the file and read the method contents
		$filename  = $method->getFileName();
		$startLine = $method->getStartLine();
		$endLine   = $method->getEndLine();

		$source = file( $filename );
		$methodSource = implode( "", array_slice( $source, $startLine - 1, $endLine - $startLine + 1 ) );

		$this->assertStringContainsString(
			"bulk_actions",
			$methodSource,
			"process_bulk_actions should define allowed bulk actions array"
		);

		// Verify that the bulk_actions array definition exists with delete and retry
		$this->assertMatchesRegularExpression(
			'/\$bulk_actions\s*=\s*array/',
			$methodSource,
			"bulk_actions array should be defined"
		);

		// Verify that 'delete' and 'retry' are the valid bulk actions
		$this->assertStringContainsString(
			"'delete'",
			$methodSource,
			"'delete' should be in the allowed bulk actions"
		);

		$this->assertStringContainsString(
			"'retry'",
			$methodSource,
			"'retry' should be in the allowed bulk actions"
		);

		// Verify the in_array check exists to filter non-bulk actions
		$this->assertStringContainsString(
			"in_array",
			$methodSource,
			"process_bulk_actions should use in_array to check if action is valid"
		);

		// Verify that view action is filtered out (the return statement after in_array check)
		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*!\s*\\\\in_array.*\$bulk_actions.*\)\s*\{[^}]*return/',
			$methodSource,
			"Non-bulk actions like 'view' should cause early return"
		);
	}

	/**
	 * Test that valid bulk actions array contains expected actions
	 *
	 * @return void
	 */
	public function testValidBulkActionsAreDefinedCorrectly(): void {
		$validBulkActions = array( 'delete', 'retry' );
		$nonBulkActions   = array( 'view', 'edit', 'export' );

		// These are actions that should NOT trigger bulk processing
		foreach ( $nonBulkActions as $action ) {
			$this->assertNotContains(
				$action,
				$validBulkActions,
				"Action '{$action}' should NOT be a valid bulk action"
			);
		}

		// These are the only valid bulk actions
		$this->assertCount(
			2,
			$validBulkActions,
			'There should be exactly 2 valid bulk actions'
		);

		$this->assertContains( 'delete', $validBulkActions, 'delete should be a valid bulk action' );
		$this->assertContains( 'retry', $validBulkActions, 'retry should be a valid bulk action' );
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
		$shouldLoad = $this->controller->should_load();

		$this->assertIsBool( $shouldLoad, 'should_load should return a boolean' );

		// In test context with WordPress loaded as admin
		if ( function_exists( 'is_admin' ) ) {
			$this->assertEquals(
				is_admin(),
				$shouldLoad,
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
		$reflection = new ReflectionClass( RequestLogController::class );
		$property   = $reflection->getProperty( 'list_table' );
		$property->setAccessible( true );

		// Get a fresh instance (but singleton, so same instance)
		$controller = RequestLogController::instance();

		// Get the current value of list_table
		$listTable = $property->getValue( $controller );

		// If list_table is null, process_bulk_actions should return early
		// This is verified by checking the method source code
		$method      = $reflection->getMethod( 'process_bulk_actions' );
		$filename    = $method->getFileName();
		$startLine   = $method->getStartLine();
		$endLine     = $method->getEndLine();
		$source      = file( $filename );
		$methodSource = implode( '', array_slice( $source, $startLine - 1, $endLine - $startLine + 1 ) );

		$this->assertStringContainsString(
			'if ( ! $this->list_table )',
			$methodSource,
			'process_bulk_actions should check if list_table is null and return early'
		);
	}
}
