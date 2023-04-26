<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

/**
 * Base class for a static factory class which can use detailed logic to build dependency objects.
 */
abstract class Provider {

	public function __construct(protected Container $container) {
	}

	/**
	 * Build the dependency in the concrete implementation of the child class.
	 */
	public abstract function make(): object;

}
