<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
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
