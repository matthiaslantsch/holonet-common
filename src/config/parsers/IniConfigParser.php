<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the IniConfigParser class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\config\parsers;

use holonet\common\config\exception\FileAccessException;

/**
 * Parse ini config files.
 */
class IniConfigParser extends AbstractParser {
	/**
	 * {@inheritdoc}
	 */
	protected function readFile(string $filename): array {
		$contents = @parse_ini_file($filename, true, INI_SCANNER_TYPED);
		if ($contents === false) {
			throw new FileAccessException("Could not parse_ini_file() '{$filename}'");
		}

		return $contents;
	}
}
