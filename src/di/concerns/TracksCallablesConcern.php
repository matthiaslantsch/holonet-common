<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\concerns;

use holonet\common\collection\ConfigRegistry;
use holonet\common\di\autowire\AutoWire;
use holonet\common\di\error\DependencyInjectionException;
use ReflectionFunction;

/**
 * @internal
 * @property ConfigRegistry $registry
 * @property AutoWire $autoWiring
 * @todo
 */
trait TracksCallablesConcern {

	private array $callables = array();

	private function callable(string $routine, callable $callable, array $params = array()): void {
		$this->callables[$routine] = [$callable, $params];
	}

	private function call(string|callable $callable, array $params = array()): mixed {
		if (is_string($callable)) {
			if (isset($this->callables[$callable])) {
				list($callable, $callableParams) = $this->callables[$callable];
				$params = array_merge($callableParams, $params);
			} elseif(!is_callable($callable)) {
				throw new DependencyInjectionException("'{$callable}' is not a callable");
			}
		}

		$reflection = new ReflectionFunction($callable);
		if ($this->registry->get('di.warn_on_inefficient_instantiation')) {
			// emit a warning using the php error system
			trigger_error("Inefficient callable call for '{$reflection->getName()}' using the dependency injection container", E_USER_WARNING);
		}

		$params = $this->autoWiring->autoWire($reflection, $params);

		return $callable($params);
	}

}
