<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\discovery;

use holonet\common\di\Container;

interface DependencyDiscovery {

	/**
	 * Discover auto-wiring definitions for the Container.
	 */
	public function discover(Container $container): void;

}
