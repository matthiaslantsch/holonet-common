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
use holonet\common\di\error\AutoWireException;
use ReflectionClass;

/**
 * @internal
 * @property ConfigRegistry $registry
 * @property AutoWire $autoWiring
 */
trait MakesObjectConcern {
	use TracksWiringConcern;

	/**
	 * @template T
	 * @param class-string<T> $concrete
	 * @return T
	 */
	protected function instantiate(string $concrete, array $params = array()): object {
		// concrete has wired a custom provider factory to make it
		if (isset($this->providers[$concrete])) {
			$provider = $this->providers[$concrete];

			return $this->instantiate($provider, $params)->make();
		}

		if ($this->registry->get('di.warn_on_inefficient_instantiation')) {
			// emit a warning using the php error system
			trigger_error("Inefficient instantiation of '{$concrete}' using the dependency injection container", E_USER_WARNING);
		}

		// collect extra parameters that were specified in the wiring
		if (isset($this->wiring[$concrete])) {
			$params = array_merge($this->wiring[$concrete], $params);
		}

		$reflection = new ReflectionClass($concrete);
		if ($reflection->isAbstract()) {
			throw new DependencyInjectionException("Cannot instantiate abstract class '{$concrete}'");
		}

		try {
			$constructor = $reflection->getConstructor();
			if ($constructor === null) {
				if (!empty($params)) {
					AutoWireException::failNoConstructor($reflection, $params);
				}

				return new $concrete();
			} else {
				$params = $this->autoWiring->autoWire($constructor, $params);

				return new $concrete(...$params);
			}
		} catch (AutoWireException $e) {
			throw new DependencyInjectionException("Failed to instantiate '{$concrete}':\n{$e->getMessage()}", previous: $e);
		}

	}

	/**
	 * @template T
	 * @param class-string<T>|string $abstract
	 * @return T
	 */
	public function instance(string $abstract, array $params = array()): object {
		try {
			$concrete = $this->resolve($abstract);
		} catch (DependencyInjectionException $e) {
			throw new DependencyInjectionException("No idea how to make '{$abstract}'. Class does not exist and no wire directive was set", previous: $e);
		}

		// if it's a service, and we have it, return it
		$id = $this->reverseResolve($abstract);
		if (isset($this->instances[$id])) {
			return $this->instances[$id];
		}

		$this->recursionCheck($id);
		try {
			$this->recursionPush($id);
			return $this->instantiate($concrete, $params);
		} finally {
			$this->recursionPop();
		}
	}
}
