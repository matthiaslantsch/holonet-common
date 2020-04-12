<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the Error class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

/**
 * The Error class wraps around an error that was thrown.
 */
class Error {
	/**
	 * @var string $errorfile the path to the file where the error was triggered
	 */
	public $errorfile;

	/**
	 * @var string $errorlevel The psr-3 error level
	 */
	public $errorlevel;

	/**
	 * @var int|null $errorline error line number inside the file where the error was triggered
	 */
	public $errorline;

	/**
	 * @var string $errormsg the error message describing the error
	 */
	public $errormsg;

	/**
	 * @var int|string $errorno the error code used to identify the error
	 */
	public $errorno;

	/**
	 * @param string $level The psr-3 error code
	 * @param int|string $errno The php error code / exception error code
	 * @param string $errstr The error message
	 * @param string $errfile The error file path
	 * @param int|null $errline Error line number
	 */
	public function __construct(string $level, $errno, string $errstr = '', string $errfile = '', int $errline = null) {
		$this->errorlevel = $level;
		$this->errorno = $errno;
		$this->errormsg = $errstr;
		$this->errorfile = $errfile;
		$this->errorline = $errline;
	}

	/**
	 * @return string with a message describing this error
	 */
	public function __toString() {
		return sprintf('[%s] %s(%s): %s in the file %s on line %d',
			date('D M d H:i:s Y'), mb_strtoupper($this->errorlevel), $this->errorno, $this->errormsg, $this->errorfile, $this->errorline ?? ''
		);
	}
}
