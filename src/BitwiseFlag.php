<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
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
	 * The integer mask that holds the flags enclosed.
	 */
	public int $flags;

	public function __construct(int $mask) {
		$this->flags = $mask;
	}

	protected function isFlagSet(int $flag): bool {
		return ($this->flags & $flag) === $flag;
	}

	protected function setFlag(int $flag, bool $value): void {
		if ($value) {
			$this->flags |= $flag;
		} else {
			$this->flags &= ~$flag;
		}
	}
}
