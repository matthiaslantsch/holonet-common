<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * PHPUnit test class for the Registry class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common\Registry;

/**
 * Tests the functionality of the Registry class
 * 
 * @author  matthias.lantsch
 * @package holonet\common\tests
 */
class RegistryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * sets up the test environment for each test method
	 * we have to clear the Registry every time because of the way it's supposed to work
	 * (one Registry reachable globally)
	 * 
	 * @access protected
	 * @return void
	 */
	protected function setUp() {
		Registry::clear();
	}

	/**
	 * @covers Registry::set()
	 * @covers Registry::setInternal()
	 * @covers Registry::get()
	 * @covers Registry::getInternal()
	 * @uses   Registry
	 */
	public function testSimplePair() {
		Registry::set("test", "value");

		$this->assertEquals(["test" => "value"], Registry::getAll());

		$this->assertEquals("value", Registry::get("test"));

		$this->assertEquals(null, Registry::get("notexisting"));
	}

	/**
	 * @covers Registry::set()
	 * @covers Registry::setInternal()
	 * @uses   Registry
	 */
	public function testSetMultilevel() {
		//test multi level set
		Registry::set("test.subone.sub2", "subvalue");
		$expected["test"]["subone"]["sub2"] = "subvalue";
		$this->assertEquals($expected, Registry::getAll());

		//test multi level set with overwrite
		Registry::set("test.subone", "overwrite");
		$expected["test"]["subone"] = "overwrite";
		$this->assertEquals($expected, Registry::getAll());

		//test same level 2 values set
		Registry::set("test.subonebrother", "nexttoit");
		$expected["test"] = ["subonebrother" => "nexttoit", "subone" => "overwrite"];
		$this->assertEquals($expected, Registry::getAll());
	}

	/**
	 * @covers Registry::get()
	 * @covers Registry::getInternal()
	 * @uses   Registry
	 */
	public function testGetMultilevel() {
		$data = [
			"array" => [
				"lowerval" => "lower",
				"arrayarray" => [
					"lowestval" => "lowest"
				]
			]
		];
		Registry::setAll($data);

		$this->assertEquals("lower", Registry::get("array.lowerval"));
		$this->assertEquals(null, Registry::get("array.notexisting"));
		$this->assertEquals("lowest", Registry::get("array.arrayarray.lowestval"));
		$this->assertEquals(null, Registry::get("array.lowerval.stillnotexisting"));
	}

}
