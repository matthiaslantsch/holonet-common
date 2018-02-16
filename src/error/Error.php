<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the Error base class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

/**
 * The Error class wraps around an error that was thrown
 *
 * @author  matthias.lantsch
 * @package holonet\common\error
 */
class Error {

	/**
	 * error level constant for "ERROR"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int ERROR Error level constant
	 */
	const ERROR = 3;

	/**
	 * error level constant for "WARNING"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int WARNING Error level constant
	 */
	const WARNING = 4;

	/**
	 * error level constant for "NOTICE"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int NOTICE Error level constant
	 */
	const NOTICE = 5;

	/**
	 * error level constant for "DEBUG"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int DEBUG Error level constant
	 */
	const DEBUG = 7;

	/**
	 * field contains the error level integer
	 * is one of the class constants in here
	 *
	 * @access public
	 * @var    int $errlevel The error level integer
	 */
	public $errlevel;

	/**
	 * field contains an error message describing the error
	 *
	 * @access public
	 * @var    string $errstr The error message
	 */
	public $errstr;

	/**
	 * field contains an error code used to identify the error
	 *
	 * @access public
	 * @var    int $errno The error code
	 */
	public $errno;

	/**
	 * field contains the path to the file where the error was triggered
	 *
	 * @access public
	 * @var    string $errfile The error file path
	 */
	public $errfile;

	/**
	 * field contains the line number inside the file where the error was triggered
	 *
	 * @access public
	 * @var    int $errline Error line number
	 */
	public $errline;

	/**
	 * constructor method
	 *
	 * @access public
	 * @param  int $errlevel The error level integer
	 * @param  int $errno The error code
	 * @param  string $errstr The error message
	 * @param  string $errfile The error file path
	 * @param  int $errline Error line number
	 * @return void
	 */
	public function __construct($level, $errno, $errstr = '', $errfile = '', $errline = '') {
		$this->errlevel = $level;
		$this->errno = $errno;
		$this->errstr = $errstr;
		$this->errfile = $errfile;
		$this->errline = $errline;
	}

	/**
	 * magic method returning a string describing this error
	 *
	 * @access public
	 * @return string with the error message and other information
	 */
	public function __toString() {
		$levels = array(
			"ERROR" => self::ERROR,
			"WARNING" => self::WARNING,
			"NOTICE" => self::NOTICE,
			"DEBUG" => self::DEBUG
		);

		return sprintf("[%s] %s(%s): %s in the file %s on line %d",
			date("D M d H:i:s Y"), @$levels[$this->errlevel], $this->errno, $this->errstr, $this->errfile, $this->errline
		);
	}

}
