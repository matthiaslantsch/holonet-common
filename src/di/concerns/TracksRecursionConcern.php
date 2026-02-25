<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\concerns;

use holonet\common\di\error\DependencyInjectionException;

/**
 * @internal
 */
trait TracksRecursionConcern {

	/**
	 * @var string[]
	 */
	private array $recursionStack = array();

	protected function recursionCheck(string $id): void {
		if (in_array($id, $this->recursionStack)) {
			$this->recursionStack[] = $id;
			throw new DependencyInjectionException(sprintf('Recursive dependency definition detected: %s', implode(' => ', $this->recursionStack)));
		}
	}

	protected function recursionPush(string $id): void {
		$this->recursionStack[] = $id;
	}

	protected function recursionPop(): void	{
		array_pop($this->recursionStack);
	}

}
