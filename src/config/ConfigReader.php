<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the ConfigReader class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\config;

use holonet\common\collection\Registry;
use holonet\common\collection\ConfigRegistry;
use holonet\common\config\parsers\IniConfigParser;
use holonet\common\config\parsers\PhpConfigParser;
use holonet\common\config\parsers\JsonConfigParser;
use holonet\common\config\exception\FileAccessException;
use holonet\common\config\exception\ConfigReaderException;

/**
 * Center class to be used to parse.
 */
class ConfigReader {
	/**
	 * @var array $parsers An array with parser classes / active cached parsers
	 */
	public $parsers = array(
		'ini' => IniConfigParser::class,
		'json' => JsonConfigParser::class,
		'php' => PhpConfigParser::class
	);

	/**
	 * @var Registry $registry Collection keeping the data while parsing
	 */
	public $registry;

	/**
	 * Initialises the internal Registry collection for the data or uses the given one.
	 * @param Registry|null $registry Registry collection to write to
	 */
	public function __construct(Registry $registry = null) {
		if ($registry !== null) {
			$this->registry = $registry;
		} else {
			$this->registry = new ConfigRegistry();
		}
	}

	/**
	 * Method used to read in config items.
	 * @param array|mixed|string $input File name or array of file names
	 * @param string|null $type Allows the user to specify the type of file
	 * @throws ConfigReaderException
	 */
	public function read($input, string $type = null): void {
		if (!is_array($input) && !is_string($input)) {
			throw new ConfigReaderException('Given parameter to ConfigReader::read() must be filename or array of filenames');
		}

		if (is_array($input)) {
			foreach ($input as $file) {
				$this->read($file, $type);
			}
		} else {
			if (!file_exists($input)) {
				throw new ConfigReaderException("File path '{$input}' does not exist");
			}

			if (is_dir($input)) {
				$this->readDir($input, $type);
			} else {
				$this->readFile($input, $type);
			}
		}
	}

	/**
	 * @param string|null $type Allows the user to specify the type of file in the dir
	 * @throws FileAccessException
	 */
	private function readDir(string $filename, string $type = null): void {
		if ($dh = opendir($filename)) {
			while (($file = readdir($dh)) !== false) {
				if ($file !== '.' && $file !== '..') {
					$this->readFile("{$filename}{$file}", $type);
				}
			}
			closedir($dh);
		} else {
			throw new FileAccessException("Could not opendir directory '{$filename}");
		}
	}

	/**
	 * @param string|null $type Allows the user to specify the type of file
	 * @throws FileAccessException
	 */
	private function readFile(string $filename, string $type = null): void {
		if ($type === null) {
			$type = pathinfo($filename, PATHINFO_EXTENSION);
		}

		if (!isset($this->parsers[$type])) {
			throw new FileAccessException("Could not parse config file '{$filename}'; Unknown config file type '{$type}'");
		}

		//cache the initialised config readers
		if (is_string($this->parsers[$type])) {
			/** @psalm-suppress InvalidStringClass */
			$this->parsers[$type] = new $this->parsers[$type]();
		}

		$this->registry->setAll(
			$this->parsers[$type]->read($filename)
		);
	}
}
