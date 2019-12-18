<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * PHPUnit test class for the utility functions in the functions.php file
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common as co;
use PHPUnit\Framework\TestCase;

/**
 * Tests the functionality of utility functions in functions.php.
 *
 * @internal
 *
 * @small
 * @coversNothing
 */
class FunctionsTest extends TestCase {
	/**
	 * @covers \holonet\common\FilesystemUtils::dirpath()
	 * @covers \holonet\common\FilesystemUtils::filepath()
	 */
	public function testAbsolutePaths(): void {
		$expected = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).DIRECTORY_SEPARATOR;
		static::assertSame($expected, co\FilesystemUtils::dirpath(__DIR__, 'subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		static::assertSame($expected, co\FilesystemUtils::filepath(__DIR__, 'subfolder', 'subsubfolder', 'test.txt'));
	}

	/**
	 * @covers \holonet\common\FilesystemUtils::dirpath()
	 * @covers \holonet\common\FilesystemUtils::filepath()
	 * @covers \holonet\common\FilesystemUtils::reldirpath()
	 * @covers \holonet\common\FilesystemUtils::relfilepath()
	 */
	public function testRelativePaths(): void {
		$expected = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).DIRECTORY_SEPARATOR;
		static::assertSame($expected, co\FilesystemUtils::reldirpath('subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		static::assertSame($expected, co\FilesystemUtils::relfilepath('subfolder', 'subsubfolder', 'test.txt'));
	}

	/**
	 * @covers \holonet\common\trigger_error_context()
	 */
	public function testTriggerErrorContext(): void {
		$msg = '';

		try {
			co\trigger_error_context('oh nos');
		} catch (\PHPUnit\Exception $e) {
			$msg = $e->getMessage();
		}

		$expected = 'oh nos in file '.__FILE__.' on line 59';
		static::assertSame($expected, $msg);
	}
}
