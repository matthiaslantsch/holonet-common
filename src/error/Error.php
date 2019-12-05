<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the Error base class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

/**
 * The Error class wraps around an error that was thrown.
 *
 * @author  matthias.lantsch
 */
class Error {
	/**
	 * error level constant for "DEBUG"
	 * Follows RFC 5424.
	 *
	 * @var int DEBUG Error level constant
	 */
	const DEBUG = 7;

	/**
	 * error level constant for "ERROR"
	 * Follows RFC 5424.
	 *
	 * @var int ERROR Error level constant
	 */
	const ERROR = 3;

	/**
	 * error level constant for "NOTICE"
	 * Follows RFC 5424.
	 *
	 * @var int NOTICE Error level constant
	 */
	const NOTICE = 5;

	/**
	 * error level constant for "WARNING"
	 * Follows RFC 5424.
	 *
	 * @var int WARNING Error level constant
	 */
	const WARNING = 4;

	/**
	 * field contains the path to the file where the error was triggered.
	 *
	 * @var string The error file path
	 */
	public $errfile;

	/**
	 * field contains the error level integer
	 * is one of the class constants in here.
	 *
	 * @var int The error level integer
	 */
	public $errlevel;

	/**
	 * field contains the line number inside the file where the error was triggered.
	 *
	 * @var int Error line number
	 */
	public $errline;

	/**
	 * field contains an error code used to identify the error.
	 *
	 * @var int The error code
	 */
	public $errno;

	/**
	 * field contains an error message describing the error.
	 *
	 * @var string The error message
	 */
	public $errstr;

	/**
	 * constructor method.
	 *
	 * @param int $level
	 * @param int|string $errno The error code
	 * @param string $errstr The error message
	 * @param string $errfile The error file path
	 * @param string $errline Error line number
	 */
	public function __construct($level, $errno, $errstr = '', $errfile = '', $errline = '') {
		$this->errlevel = $level;
		$this->errno = $errno;
		$this->errstr = $errstr;
		$this->errfile = $errfile;
		$this->errline = $errline;
	}

	/**
	 * magic method returning a string describing this error.
	 *
	 * @return string with the error message and other information
	 */
	public function __toString() {
		$levels = array(
			'ERROR' => self::ERROR,
			'WARNING' => self::WARNING,
			'NOTICE' => self::NOTICE,
			'DEBUG' => self::DEBUG
		);

		return sprintf('[%s] %s(%s): %s in the file %s on line %d',
			date('D M d H:i:s Y'), @$levels[$this->errlevel], $this->errno, $this->errstr, $this->errfile, $this->errline
		);
	}
}
