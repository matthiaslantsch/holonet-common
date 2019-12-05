<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the JsonConfigParser class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\config\parsers;

use holonet\common\config\exception\FileAccessException;
use holonet\common\config\exception\ParseErrorException;
use holonet\common\config\exception\ConfigReaderException;

/**
 * Parse json config files.
 */
class JsonConfigParser extends AbstractParser {
	/**
	 * @throws ConfigReaderException
	 * @return array with parsed config data
	 */
	protected function readFile(string $filename): array {
		$contents = @file_get_contents($filename);
		if ($contents === false) {
			throw new FileAccessException("Could not file_get_contents() '{$filename}'");
		}

		$data = json_decode($contents, true);
		if ($data === null) {
			throw new ParseErrorException("Could not json_decode() contents of '{$filename}': ".json_last_error_msg());
		}

		return $data;
	}
}
