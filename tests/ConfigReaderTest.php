<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use Exception;
use PHPUnit\Framework\TestCase;
use holonet\common\config\ConfigReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use holonet\common\config\parsers\IniConfigParser;
use holonet\common\config\parsers\PhpConfigParser;
use holonet\common\config\parsers\JsonConfigParser;

#[CoversClass(ConfigReader::class)]
#[CoversClass(IniConfigParser::class)]
#[CoversClass(JsonConfigParser::class)]
#[CoversClass(PhpConfigParser::class)]
class ConfigReaderTest extends TestCase {
	/**
	 * Return an entry for each test config file there is so we can test all the file formats.
	 */
	public static function configTestProvider(): array {
		$ret = array();
		foreach (glob(__DIR__.'/data/config.*') as $file) {
			$ext = pathinfo($file, \PATHINFO_EXTENSION);
			$ret[$ext] = array($file);
		}

		return $ret;
	}

	public function testNonExistingFile(): void {
		$configreader = new ConfigReader();
		$filename = "iSurelyDon'tExist.ini";

		try {
			$configreader->read($filename);
		} catch (Exception $e) {
			$msg = $e->getMessage();
		}
		$this->assertSame("File path 'iSurelyDon'tExist.ini' does not exist", $msg);
	}

	#[DataProvider('configTestProvider')]
	public function testParseFiles(string $file): void {
		$expectedData = array('toplevel' => 'value', 'sublevel' => array('config' => 'sub'));

		$configreader = new ConfigReader();
		$configreader->read($file);

		$this->assertSame($expectedData, $configreader->registry->all());
	}

	public function testUnknownType(): void {
		$configreader = new ConfigReader();
		$filename = __DIR__.'/data/configfile.blablabla';

		try {
			$configreader->read($filename);
		} catch (Exception $e) {
			$msg = $e->getMessage();
		}
		$this->assertSame("Could not parse config file '{$filename}'; Unknown config file type 'blablabla'", $msg);
	}
}
