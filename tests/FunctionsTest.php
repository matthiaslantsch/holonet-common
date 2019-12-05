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
	 * @covers \holonet\common\dirpath()
	 * @covers \holonet\common\filepath()
	 */
	public function testAbsolutePaths(): void {
		$expected = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).DIRECTORY_SEPARATOR;
		static::assertSame($expected, co\dirpath(__DIR__, 'subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		static::assertSame($expected, co\filepath(__DIR__, 'subfolder', 'subsubfolder', 'test.txt'));
	}

	/**
	 * @covers \holonet\common\dirpath()
	 * @covers \holonet\common\filepath()
	 * @covers \holonet\common\reldirpath()
	 * @covers \holonet\common\relfilepath()
	 */
	public function testRelativePaths(): void {
		$expected = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).DIRECTORY_SEPARATOR;
		static::assertSame($expected, co\reldirpath('subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		static::assertSame($expected, co\relfilepath('subfolder', 'subsubfolder', 'test.txt'));
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
