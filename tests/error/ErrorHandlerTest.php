<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\error;

use Psr\Log\AbstractLogger;
use PHPUnit\Framework\TestCase;
use holonet\common\error\ErrorHandler;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ErrorHandler::class)]
class ErrorHandlerTest extends TestCase {
	private int $previousErrorReporting;

	protected function setUp(): void {
		$this->previousErrorReporting = error_reporting();
	}

	protected function tearDown(): void {
		error_reporting($this->previousErrorReporting);
	}

	public function test_error_is_mapped_to_psr3_level_and_logged(): void {
		$logger = $this->makeRecordingLogger();
		$handler = new ErrorHandler($logger);

		error_reporting(\E_ALL);
		$this->assertTrue($handler->handleError(\E_USER_WARNING, 'something looks off', '/some/file.php', 42));

		$this->assertSame(array(
			array(
				'level' => 'warning',
				'message' => 'E_USER_WARNING: something looks off',
				'context' => array('code' => \E_USER_WARNING, 'file' => '/some/file.php', 'line' => 42),
			),
		), $logger->records);
	}

	public function test_unknown_error_code_falls_back_to_critical(): void {
		$logger = $this->makeRecordingLogger();
		$handler = new ErrorHandler($logger);

		error_reporting(\E_ALL);
		$this->assertTrue($handler->handleError(\E_USER_DEPRECATED | \E_USER_NOTICE, 'odd combined code'));

		$this->assertCount(1, $logger->records);
		$this->assertSame('critical', $logger->records[0]['level']);
		$this->assertStringStartsWith('E_ERROR:', $logger->records[0]['message']);
	}

	public function test_suppressed_errors_are_not_logged(): void {
		$logger = $this->makeRecordingLogger();
		$handler = new ErrorHandler($logger);

		error_reporting(0);

		$this->assertFalse($handler->handleError(\E_USER_WARNING, 'invisible'));
		$this->assertSame(array(), $logger->records);
	}

	public function test_shutdown_handler_ignores_non_fatal_errors(): void {
		$logger = $this->makeRecordingLogger();
		$handler = new ErrorHandler($logger);

		error_clear_last();
		$handler->handleShutdown();

		@trigger_error('a user level warning', \E_USER_WARNING);
		$handler->handleShutdown();

		$this->assertSame(array(), $logger->records);
	}

	/**
	 * @return AbstractLogger with a public $records array property
	 */
	private function makeRecordingLogger(): AbstractLogger {
		return new class() extends AbstractLogger {
			public array $records = array();

			public function log($level, $message, array $context = array()): void {
				$this->records[] = array('level' => $level, 'message' => (string)$message, 'context' => $context);
			}
		};
	}
}
