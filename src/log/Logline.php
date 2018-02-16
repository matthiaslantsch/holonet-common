<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the Logline abstraction class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\log;

/**
 * The Logline class represents a line in a log file, encapsulated so it can be
 * read in different forms
 *
 * @author  matthias.lantsch
 * @package holonet\common\log
 */
class Logline {

	/**
	 * string with the channel name for this log line
	 *
	 * @access public
	 * @var    string $channel The channel name for this log message
	 */
	public $channel;

	/**
	 * integer with the log level for this log line
	 *
	 * @access public
	 * @var    int $severity The log level number, one of the constants in the Logger class
	 */
	public $severity;

	/**
	 * the actual message that is to be logged
	 *
	 * @access public
	 * @var    string $msg The log message to be logged
	 */
	public $msg;

	/**
	 * the datetime format for this log line
	 *
	 * @access public
	 * @var    string $dateformat The date format string
	 */
	public $dateformat;

	/**
	 * context for the log line that is to be added to the message
	 *
	 * @access public
	 * @var    mixed $context The context for the log message
	 */
	public $context;

	/**
	 * constructor method for a log line object
	 *
	 * @access public
	 * @param  string $channel The channel name for this log message
	 * @param  string $msg The log message that is to be logged
	 * @param  int $severity The log level integer
	 * @param  string $dformat Time format string for the date() function
	 * @param  mixed $context Context that is to be added to this log line
	 * @return void
	 */
	public function __construct(string $channel, string $msg, int $severity, string $dformat = "Y-m-d H:i:s", $context = array()) {
		$this->channel = $channel;
		$this->msg = $msg;
		$this->severity = $severity;
		$this->dateformat = $dformat;
		$this->context = $context;
	}

	/**
	 * method returning a string with the log line
	 * parameter flags can be used to influence the output
	 *
	 * @access public
	 * @param  bool $date Flag wheter to prepend date or not
	 * @param  bool $channel Flag wheter to include channel name/severity or not
	 * @param  bool $context Flag wheter to include context or not
	 * @return string with the formatted log message according to the flags the user set
	 */
	public function formatLine(bool $date = true, bool $channel = true, bool $context = true) {
		$ret = "";

		if($date) {
			$ret .= "[".date($this->dateformat)."] ";
		}

		if($channel) {
			$ret .= "{$this->channel}.".@Logger::$loglevels[$this->severity].": ";
		}

		$ret .= "{$this->msg} ";

		if($context) {
			$ctxt = json_encode($this->context, JSON_FORCE_OBJECT);
			if(json_last_error() !== JSON_ERROR_NONE) {
				$ctxt = "INVALID_CONTEXT";
			}
			$ret .= "{$ctxt}";
		}
		return $ret;
	}

	/**
	 * magic __toString() method
	 * returns a formatted string with everything
	 *
	 * @access public
	 * @return string with the formatted log message with all the flags set
	 */
	public function __toString() {
		return $this->formatLine();
	}

}
