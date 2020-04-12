<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * Class file for the BitwiseFlag class
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

/**
 * BitwiseFlag wraps around an integer and allows for easy flag setting and checking
 * while not touching the other bits in the integer.
 */
abstract class BitwiseFlag {
	/**
	 * @var int $flags The integer mask that holds the flags enclosed
	 */
	public $flags;

	/**
	 * @param int $mask The flags mask we are working with
	 */
	public function __construct(int $mask) {
		$this->flags = $mask;
	}

	/**
	 * @param int $flag The flag that should be checked for
	 * @return bool true or false if the flag is set or not
	 */
	protected function isFlagSet(int $flag): bool {
		return ($this->flags & $flag) === $flag;
	}

	/**
	 * @param int $flag The flag to be set in our integer mask
	 * @param bool $value The boolean value to be set, if the flag should be true or not
	 */
	protected function setFlag(int $flag, bool $value): void {
		if ($value) {
			$this->flags |= $flag;
		} else {
			$this->flags &= ~$flag;
		}
	}
}
