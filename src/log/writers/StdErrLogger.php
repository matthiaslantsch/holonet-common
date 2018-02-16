<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the StdErr logger
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\log\writers;

use holonet\common\log\Logline;

/**
 * The StdErrLogger class is supposed to output log lines into StdErr of the php process
 *
 * @author  matthias.lantsch
 * @package holonet\common\log\writers
 */
class StdErrLogger implements LogWriter {

	/**
	 * function actually writing a log line
	 *
	 * @access public
	 * @param  Logline $msg The message that should be logged
	 * @return void
	 */
	public function write(Logline $msg) {
		//log only the message with the context to the apache error log (no date)
		error_log($msg->formatLine(false));
		//log everything to stderr
		@file_put_contents('php://stderr', $msg.PHP_EOL);
	}

}
