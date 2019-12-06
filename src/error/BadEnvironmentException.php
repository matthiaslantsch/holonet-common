<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the BadEnvironmentException exception class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

use RuntimeException;

/**
 * exception to be thrown to show errors with the users php setup.
 */
class BadEnvironmentException extends RuntimeException {
}
