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
 * Exception to be thrown when a specified file to read is in a format the parser can't understand.
 */
class UnsupportedFormatException extends ConfigReaderException {
	public function __construct(string $message) {
		parent::__construct($message, static::UNSUPPORTED_FORMAT);
	}
}
