<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the BadEnvironmentException exception class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

/**
 * exception to be thrown to show errors with the users php setup
 *
 * @author  matthias.lantsch
 * @package holonet\common\error
 */
class BadEnvironmentException extends \RuntimeException {}
