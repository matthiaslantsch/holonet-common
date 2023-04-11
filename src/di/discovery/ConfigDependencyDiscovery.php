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
use holonet\common\error\BadEnvironmentException;

/**
 * Read auto-wiring definitions from the config registry.
 */
class ConfigDependencyDiscovery implements DependencyDiscovery {

	public function discover(Container $container): void {
		if (is_array($services = $container->registry->get('di.services'))) {
			foreach ($services as $service => $abstract) {
				if (!is_array($abstract) && is_string($abstract)) {
					$abstract = array($abstract);
				}

				$this->validateAbstract("di.services.{$service}", $abstract);
				$abstract[1] ??= array();
				list($class, $params) = $abstract;
				$container->set($service, $class, $params);
			}
		}

		if (is_array($classWirings = $container->registry->get('di.auto_wire'))) {
			foreach ($classWirings as $name => $abstract) {
				if (!is_array($abstract) && is_string($abstract)) {
					$abstract = array($abstract);
				}

				$this->validateAbstract("di.auto_wire.{$name}", $abstract);

				$abstract[1] ??= array();
				list($class, $params) = $abstract;

				if (!is_string($name)) {
					$name = null;
				}
				$container->wire($class, $params, $name);
			}
		}
	}

	private function validateAbstract(string $configKey, mixed $abstract) {
		if (!is_array($abstract) || count($abstract) > 2 || !array_is_list($abstract) || (isset($abstract[1]) && !is_array($abstract[1]))) {
			throw BadEnvironmentException::faultyConfig($configKey, 'Definition must be an array with class and parameters as values');
		}
	}
}
