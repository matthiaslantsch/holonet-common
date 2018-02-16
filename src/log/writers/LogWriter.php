<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * interface file for the abstract LogWriter interface
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\log\writers;

use holonet\common\log\Logline;

/**
 * LogWriter interface as an interface to all log writing classes
 *
 * @author  matthias.lantsch
 * @package holonet\common\log\writers
 */
interface LogWriter {

	/**
	 * force the child class to implement a write method to write out the message to whatever medium it targets
	 *
	 * @access public
	 * @param  Logline $msg The message that should be logged
	 * @return void
	 */
	public function write(Logline $msg);

}
