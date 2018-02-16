<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * PHPUnit test class for the ConfigReader class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common\ConfigReader;
use holonet\common\Registry;

/**
 * Tests the functionality of the ConfigReader class
 * 
 * @author  matthias.lantsch
 * @package holonet\common\tests
 */
class ConfigReaderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * sets up the test environment for each test method
	 * we have to clear the Registry every time because of the way it's supposed to work
	 * (one Registry reachable globally)
	 * 
	 * @access protected
	 * @return void
	 */
	protected function setUp() {

		die(var_dump(LOG_EMERG, 
LOG_ALERT, 
LOG_CRIT, 
LOG_ERR, 
LOG_WARNING, 
LOG_NOTICE, 
LOG_INFO, 
LOG_DEBUG));
		Registry::clear();
	}

	/**
	 * Return an entry for each test config file there is so we can test all the file formats
	 */
	public function configTestProvider() {
		$testdir = __DIR__.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR;
		$ret = [];
		foreach (glob($testdir."config.*") as $file) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$ret[$ext] = [$file];
		}
		return $ret;
	}

	/**
	 * @covers ConfigReader::__callStatic()
	 * @uses   ConfigReader
	 */
	public function testUnknownType() {
		$msg = "";
		try {
			ConfigReader::doesntExistType();
		} catch (\Exception $e) {
			$msg = $e->getMessage();
		}
		$this->assertEquals("Unknown config file type 'doesntExistType'", $msg);
	}

	/**
	 * @covers ConfigReader::__callStatic()
	 * @uses   ConfigReader
	 */
	public function testInvalidFileType() {
		$msg = "";
		try {
			ConfigReader::ini();
		} catch (\Exception $e) {
			$msg = $e->getMessage();
		}
		$this->assertEquals("No config file path was specified", $msg);

		$msg = "";
		try {
			ConfigReader::ini("test.ini");
		} catch (\Exception $e) {
			$msg = $e->getMessage();
		}
		$this->assertEquals("Config file 'test.ini' doesn't exist or cannot be read", $msg);
	}

	/**
	 * @dataProvider configTestProvider
	 * @covers ConfigReader::read()
	 * @covers ConfigReader::__callStatic()
	 * @covers ConfigReader::readINI()
	 * @covers ConfigReader::readPHP()
	 * @uses   ConfigReader
	 * @uses   Registry
	 */
	public function testParseFiles($file) {
		$expectedData = ["toplevel" => "value", "sublevel" => ["config" => "sub"]];

		//compare it directly
		$this->assertEquals($expectedData, ConfigReader::read($file, true));
		//now to the registry
		ConfigReader::read($file);
		$this->assertEquals($expectedData, Registry::getAll());
	}

}
