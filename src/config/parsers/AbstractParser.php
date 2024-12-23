<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\config\parsers;

use holonet\common\config\exception\FileAccessException;
use holonet\common\config\exception\ConfigReaderException;

/**
 * Consolidate file validation logic in this class and let the extending classes do only the parsing.
 */
abstract class AbstractParser {
	/**
	 * Read a path into an array.
	 * Can be either a file or a directory.
	 * @param string $filename The filename to be parsed
	 * @throws ConfigReaderException
	 * @throws FileAccessException
	 * @return array with parsed config data
	 */
	public function read(string $filename): array {
		if (!is_readable($filename) || !is_file($filename)) {
			throw new FileAccessException("File '{$filename}' cannot be found/read");
		}

		return $this->readFile($filename);
	}

	/**
	 * @param string $filename The filename to be parsed
	 * @throws ConfigReaderException
	 * @return array with parsed config data
	 */
	abstract protected function readFile(string $filename): array;
}
