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
				if (is_int($service)) {
					$service = $abstract;
				}

				if (is_string($abstract)) {
					$abstract = array($abstract);
				}

				$abstract[1] ??= array();
				if (!$this->validateAbstract($abstract)) {
					throw BadEnvironmentException::faultyConfig("di.services.{$service}", 'Abstract must be class name or array with class name and parameters');
				}

				list($class, $params) = $abstract;
				$container->set($service, $class, $params);
			}
		}

		if (is_array($classWirings = $container->registry->get('di.auto_wire'))) {
			foreach ($classWirings as $name => $abstract) {
				if (is_string($abstract)) {
					$abstract = array($abstract);
				}

				$abstract[1] ??= array();
				if (!$this->validateAbstract($abstract)) {
					throw BadEnvironmentException::faultyConfig("di.auto_wire.{$name}", 'Abstract must be class name or array with class name and parameters');
				}

				list($class, $params) = $abstract;

				if (!is_string($name)) {
					$name = null;
				}
				$container->wire($class, $params, $name);
			}
		}
	}

	private function validateAbstract(mixed $abstract): bool {
		// validate form and size of abstract array
		if(!is_array($abstract) || count($abstract) > 2 || !array_is_list($abstract)) {
			return false;
		}

		// validate class name in the abstract array
		if (!is_string($abstract[0]) && !is_object($abstract[0])) {
			return false;
		}

		// validate parameters in the abstract array
		if (!is_array($abstract[1])) {
			return false;
		}

		return true;
	}
}
