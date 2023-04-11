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

/**
 * Parse php config files.
 */
class PhpConfigParser extends AbstractParser {
	/**
	 * {@inheritDoc}
	 */
	protected function readFile(string $filename): array {
		/**
		 * @psalm-suppress UnresolvableInclude
		 */
		$ret = require $filename;
		//either the user sets a variable called "config" or returns an array
		/** @psalm-suppress UndefinedVariable */
		if (!isset($config) && ($config = $ret) !== 1) {
			throw new FileAccessException("Could not parse php config file '{$filename}'; File must either return an array or define the variable \$config");
		}
		if (!is_array($config)) {
			throw new FileAccessException("Could not parse php config file '{$filename}'; Value defined by the config file must be an array");
		}

		return $config;
	}
}
