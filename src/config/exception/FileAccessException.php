<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the FileAccessException class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\config\exception;

/**
 * Exception to be thrown when a specified file to read cannot be found / read.
 */
class FileAccessException extends ConfigReaderException {
	/**
	 * FileNotFoundException constructor.
	 * Overwritten so we can submit the constant error code.
	 */
	public function __construct(string $message) {
		parent::__construct($message, static::FILE_ACCESS);
	}
}
