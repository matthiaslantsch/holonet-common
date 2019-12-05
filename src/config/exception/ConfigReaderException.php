<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the ConfigReaderException class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\config\exception;

use Exception;

/**
 * Package level exception so the user can catch those specifically.
 */
class ConfigReaderException extends Exception {
	public const FILE_ACCESS = 1000;

	public const PARSE_ERROR = 1050;

	public const UNSUPPORTED_FORMAT = 1100;
}
