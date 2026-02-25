<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

/**
 * The Error class wraps around an error that was thrown.
 */
class Error {
	public string $errorfile;

	/**
	 * The psr-3 error level.
	 */
	public string $errorlevel;

	public ?int $errorline;

	public string $errormsg;

	public int $errorno;

	/**
	 * @param string $level The psr-3 error code
	 * @param int $errno The php error code / exception error code
	 * @param string $errstr The error message
	 * @param string $errfile The error file path
	 * @param int|null $errline Error line number
	 */
	public function __construct(string $level, int $errno, string $errstr = '', string $errfile = '', ?int $errline = null) {
		$this->errorlevel = $level;
		$this->errorno = $errno;
		$this->errormsg = $errstr;
		$this->errorfile = $errfile;
		$this->errorline = $errline;
	}

	/**
	 * @return string with a message describing this error
	 */
	public function __toString(): string {
		return sprintf(
			'[%s] %s(%s): %s in the file %s on line %d',
			date('D M d H:i:s Y'),
			mb_strtoupper($this->errorlevel),
			$this->errorno,
			$this->errormsg,
			$this->errorfile,
			$this->errorline ?? ''
		);
	}
}
