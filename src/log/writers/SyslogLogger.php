<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the syslog logger
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\log\writers;

use holonet\common\Logger;
use holonet\common\Registry;

/**
 * The SyslogLogger class is supposed to write log lines to the logging service of the computer
 *
 * @author  matthias.lantsch
 * @package holonet\common\log\writers
 */
class SyslogLogger implements LogWriter {

	/**
	 * logging ident as determined by initialization
	 * will be prepended to each message
	 *
	 * @access private
	 * @var    string $ident Ident string prepended to each message that is being logged
	 */
	private $ident;

	/**
	 * syslog facilites to log to as specified by configuration
	 *
	 * @access private
	 * @var    array $facilities Array with facility numbers to log to
	 */
	private $facilities;

	/**
	 * constructor method
	 * will check if the configured facilites are all writable
	 * defaults to using the app.name from the registry as ident if not given
	 *
	 * @access public
	 * @param  string $name Name to be prepended to each log message that is being sent to syslog
	 * @param  array $facilities Array with syslog facilities that are to be openened
	 * @return void
	 */
	public function __construct($name = "", $facilities = [LOG_USER]) {
		die("not yet implemented @todo");
		if($name === "") {
			if(($name = Registry::get("app.name")) === false) {
				$name = "hdev php log";
			}
		}

		$this->ident = $name;

		foreach ($this->facilities as $fac) {
			if(@openlog($this->ident, LOG_NDELAY | LOG_PID, $fac) === false) {
				throw new \Exception("Error initialising syslog logger. Cannot open syslog facility '{$fac}'", 100);
			}
		}
	}

	/**
	 * function actually writing a log line
	 *
	 * @access public
	 * @param  string $logline The log line to actually be written
	 * @param  integer $level An error level integer as defined in the Logger class
	 */
	public function write(Logline $msg) {
		//syslog has no LOG_TRACE level, so we'll set it to LOG_DEBUG
		if($level === Logger::TRACE) {
			$level = Logger::DEBUG;
		}

		foreach ($facilities as $fac) {
			@openlog($this->ident, LOG_PID, $fac);
			@syslog($level, $logline);
		}
	}

}
