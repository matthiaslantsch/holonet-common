<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * PHPUnit test class for the utility functions in the functions.php file
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common as co;

/**
 * Tests the functionality of utility functions in functions.php
 * 
 * @author  matthias.lantsch
 * @package holonet\common\tests
 */
class FunctionsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers registry()
	 * @uses   Registry
	 */
	public function testRegistry() {
		$data = ["one" => 1, "two" => 2];
		co\Registry::setAll($data);

		co\registry("one", "one");
		$this->assertEquals("one", co\Registry::get("one"));

		$this->assertEquals(co\registry("one"), co\Registry::get("one"));
	}

	/**
	 * @covers trigger_error_context()
	 */
	public function testTrigger_error_context() {
		$msg = "";

		try {
			co\trigger_error_context("oh nos");
		} catch (\PHPUnit_Framework_Error $e) {
			$msg = $e->getMessage();
		}

		$expected = "oh nos in file ".__FILE__." on line 46";
		$this->assertEquals($expected, $msg);
	}

	/**
	 * @covers reldirpath()
	 * @covers relfilepath()
	 * @covers dirpath()
	 * @covers filepath()
	 */
	public function testRelativePaths() {
		$expected = implode(DIRECTORY_SEPARATOR, [__DIR__, "subfolder", "subsubfolder"]).DIRECTORY_SEPARATOR;
		$this->assertEquals($expected, co\reldirpath("subfolder", "subsubfolder"));

		$expected .= "test.txt";
		$this->assertEquals($expected, co\relfilepath("subfolder", "subsubfolder", "test.txt"));
	}

	/**
	 * @covers dirpath()
	 * @covers filepath()
	 */
	public function testAbsolutePaths() {
		$expected = implode(DIRECTORY_SEPARATOR, [__DIR__, "subfolder", "subsubfolder"]).DIRECTORY_SEPARATOR;
		$this->assertEquals($expected, co\dirpath(__DIR__, "subfolder", "subsubfolder"));

		$expected .= "test.txt";
		$this->assertEquals($expected, co\filepath(__DIR__, "subfolder", "subsubfolder", "test.txt"));
	}

}
