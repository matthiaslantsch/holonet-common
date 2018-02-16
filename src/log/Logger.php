<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the Logger class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\log;

/**
 * The Logger class keeps a number of log channels open and writes logs to them
 *
 * @author  matthias.lantsch
 * @package holonet\common\log
 */
abstract class Logger {

	/**
	 * log level constant for "EMERGENCY"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int EMERGENCY Log level constant
	 */
	const EMERGENCY = 0;

	/**
	 * log level constant for "ALERT"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int ALERT Log level constant
	 */
	const ALERT = 1;

	/**
	 * log level constant for "CRITICAL"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int CRITICAL Log level constant
	 */
	const CRITICAL = 2;

	/**
	 * log level constant for "ERROR"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int ERROR Log level constant
	 */
	const ERROR = 3;

	/**
	 * log level constant for "WARNING"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int WARN Log level constant
	 */
	const WARN = 4;

	/**
	 * log level constant for "NOTICE"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int NOTICE Log level constant
	 */
	const NOTICE = 5;

	/**
	 * log level constant for "INFORMATIONAL"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int INFO Log level constant
	 */
	const INFO = 6;

	/**
	 * log level constant for "DEBUG"
	 * Follows RFC 5424
	 *
	 * @access public
	 * @var    int DEBUG Log level constant
	 */
	const DEBUG = 7;

	/**
	 * array with logging levels, mapped from level to string name
	 *
	 * @access public
	 * @var    array $loglevels
	 */
	public static $loglevels = array(
		self::EMERGENCY => "EMERGENCY",
		self::ALERT => "ALERT",
		self::CRITICAL => "CRITICAL",
		self::ERROR => "ERROR",
		self::WARN => "WARN",
		self::NOTICE => "NOTICE",
		self::INFO => "INFO",
		self::DEBUG => "DEBUG"
	);

	/**
	 * array with log writers
	 *
	 * @access protected
	 * @var    array $writers Array with initialised log writers
	 */
	protected $writers = array();

	/**
	 * integer value used to determine wheter a message should be logged or not
	 * calculated from the configured log level
	 *
	 * @access protected
	 * @var    integer $logUpto Upper boundary logging error severity level
	 */
	protected $logUpto;

	/**
	 * static field holding the only instance of this class
	 *
	 * @access protected
	 * @var    object $instance Class instance of this class
	 */
	protected static $instance = null;

	/**
	 * blocked clone method --> Singleton class
	 *
	 * @access protected
	 * @return void
	 */
	protected function __clone(){}

	/**
	 * Instanciator method.
	 * method for creating and getting the only instance of this singleton class
	 *
	 * @access public
	 * @return object of the child class
	 */
	public static function init() {
		if(self::$instance === null) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Method used to recreate the Logger instance
	 *
	 * @access public
	 * @return void
	 */
	public static function reload() {
		$me = self::$instance;
		self::$instance = null;
		try {
			self::init();
		} catch (\Exception $e) {
			self::$instance = $me;
			if(self::$instance !== null) {
				self::$instance->log(
					static::ERROR, "Error reloading the logger: {$e->getMessage()}"
				);
			}
		}

	}

	/**
	 * add another log writer to this logger instance
	 *
	 * @access public
	 * @param  writers\LogWriter $writer The log writer to be added
	 * @return void
	 */
	public function addWriter(writers\LogWriter $writer) {
		$this->writers[] = $writer;
	}

	/**
	 * write a message to all writers this Logger has
	 *
	 * @access public
	 * @param  int $level The log message level, must be one of the levels in this class
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public function log(int $level, string $msg, $context = array()) {
		die("@TODO Cleanup base logger method");
	}

	/**
	 * write a "EMERGENCY" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function emergency(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::EMERGENCY, $msg, $context);
	}

	/**
	 * write a "ALERT" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function alert(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::ALERT, $msg, $context);
	}

	/**
	 * write a "CRITICAL" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function critical(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::CRITICAL, $msg, $context);
	}

	/**
	 * write a "ERROR" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function error(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::ERROR, $msg, $context);
	}

	/**
	 * write a "WARN" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function warn(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::WARN, $msg, $context);
	}

	/**
	 * write a "NOTICE" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function notice(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::NOTICE, $msg, $context);
	}

	/**
	 * write a "INFO" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function info(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::INFO, $msg, $context);
	}

	/**
	 * write a "DEBUG" level message to all writers this Logger has
	 *
	 * @access public
	 * @param  string $msg The message that is to be logged
	 * @param  mixed $context The context that is to be added
	 * @return void
	 */
	public static function debug(string $msg, $context = array()) {
		$me = self::init();
		$me->log(static::DEBUG, $msg, $context);
	}

}
