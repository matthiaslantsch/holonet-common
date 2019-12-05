<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * PHPUnit test class for the ConfigReader class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use PHPUnit\Framework\TestCase;
use holonet\common\config\ConfigReader;

/**
 * Tests the functionality of the ConfigReader class.
 *
 * @covers  \holonet\common\config\ConfigReader
 *
 * @internal
 *
 * @small
 */
class ConfigReaderTest extends TestCase {
	/**
	 * Return an entry for each test config file there is so we can test all the file formats.
	 */
	public function configTestProvider() {
		$ret = array();
		foreach (glob(__DIR__.'/data/config.*') as $file) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$ret[$ext] = array($file);
		}

		return $ret;
	}

	public function testNonExistingFile(): void {
		$configreader = new ConfigReader();
		$filename = "iSurelyDon'tExist.ini";

		try {
			$configreader->read($filename);
		} catch (\Exception $e) {
			$msg = $e->getMessage();
		}
		static::assertSame("File 'iSurelyDon'tExist.ini' cannot be found/read", $msg);
	}

	/**
	 * @dataProvider configTestProvider
	 * @covers  \holonet\common\config\parsers\IniConfigParser
	 * @covers  \holonet\common\config\parsers\JsonConfigParser
	 * @covers  \holonet\common\config\parsers\PhpConfigParser
	 */
	public function testParseFiles($file): void {
		$expectedData = array('toplevel' => 'value', 'sublevel' => array('config' => 'sub'));

		$configreader = new ConfigReader();
		$configreader->read($file);

		static::assertSame($expectedData, $configreader->registry->getAll());
	}

	public function testUnknownType(): void {
		$configreader = new ConfigReader();
		$filename = 'config.blablabla';

		try {
			$configreader->read($filename);
		} catch (\Exception $e) {
			$msg = $e->getMessage();
		}
		static::assertSame("Could not parse config file '{$filename}'; Unknown config file type 'blablabla'", $msg);
	}
}
