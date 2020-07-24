<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\config\exception;

/**
 * Exception to be thrown when a specified file failed to be parsed.
 */
class ParseErrorException extends ConfigReaderException {
	public function __construct(string $message) {
		parent::__construct($message, static::PARSE_ERROR);
	}
}
