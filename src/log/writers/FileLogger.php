<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the File logger
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\log\writers;

use holonet\common\log\Logline;

/**
 * The FileLogger class is supposed to write a log file on the disk
 *
 * @author  matthias.lantsch
 * @package holonet\common\log\writers
 */
class FileLogger implements LogWriter {

	/**
	 * logfile path as initialised during logger init sequence
	 *
	 * @access private
	 * @var    string $logfile Path to the configured log file
	 */
	private $logfile;

	/**
	 * constructor method to intialise the logger.
	 * will check if the configured file path is writable
	 *
	 * @access public
	 * @param  string $path Filename for the log to be written
	 * @return void
	 */
	public function __construct($path) {
		$this->logfile = $path;

		//create the log file if required
		if(!file_exists($this->logfile)) {
			@touch($this->logfile);
		}

		//check if the logfile is writable
		if(!is_writable($this->logfile)) {
			throw new \Exception("Error initialising file logger. Cannot write to '{$this->logfile}'", 100);
		}
	}

	/**
	 * function actually writing a log line.
	 * will fail silently!! the log is only being checked during the init sequence
	 *
	 * @access public
	 * @param  Logline $msg The message that should be logged
	 * @return void
	 */
	public function write(Logline $msg) {
		@file_put_contents($this->logfile, $msg.PHP_EOL, FILE_APPEND);
	}

}
