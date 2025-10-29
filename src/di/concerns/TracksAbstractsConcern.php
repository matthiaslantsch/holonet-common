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
use holonet\common\tests\di\ProvidedDependency;

/**
 * @internal
 */
trait TracksAbstractsConcern {

	/**
	 * Arbitrary class name aliases to abstract classes or interfaces.
	 * Alias => abstract
	 * @var array<string, string>
	 */
	protected array $aliases = array();

	/**
	 * Mapping from abstract class or interface names to concrete class names.
	 * Abstract class / interface => concrete class
	 * @var array<string, string>
	 */
	protected array $contracts = array();

	public function contract(string $contract, string $concrete): void {
		if (!is_subclass_of($concrete, $contract)) {
			throw new DependencyInjectionException("Concrete '{$concrete}' does not implement/extend '{$contract}'.");
		}
		$this->contracts[$contract] = $concrete;
	}

	/**
	 * Attempt to resolve an "abstract" to figure out what the user wants to make / get.
	 */
	public function resolve(string $abstract): string {
		// arbitrary alias name (could map to any abstract => resolve again)
		if (isset($this->aliases[$abstract])) {
			return $this->resolve($this->aliases[$abstract]);
		}

		// explicitly mapped class or interface
		if (isset($this->contracts[$abstract])) {
			return $this->contracts[$abstract];
		}

		// just a class name => we can make it with reflection
		if (class_exists($abstract)) {
			return $abstract;
		}

		// interface => no contract given but there could be a provider that makes it
		if (isset($this->providers[$abstract])) {
			return $abstract;
		}

		throw new DependencyInjectionException("'{$abstract}' is not a valid abstract (neither alias nor implementation contract)");
	}

	public function alias(string $alias, string $abstract): void {
		if ($alias !== $abstract) {
			$this->aliases[$alias] = $abstract;
		}
	}

	protected function hasAlias(string $abstract): bool {
		return isset($this->aliases[$abstract]);
	}

	protected function reverseResolve(string $concrete): string {
		// check if it's a service id
		if (isset($this->aliases[$concrete])) {
			return $concrete;
		}

		// check if it's an implementation contract
		if (isset($this->contracts[$concrete])) {
			if (($match = $this->typeSearch($concrete, $this->aliases)) !== null) {
				return $match;
			}
		}

		// check if it's a concrete that has a contract mapped to it
		if (($match = $this->typeSearch($concrete, $this->contracts)) !== null) {
			return $this->reverseResolve($match);
		}

		// check if it's a concrete that has an alias pointing to it
		if (($match = $this->typeSearch($concrete, $this->aliases)) !== null) {
			return $match;
		}

		return $concrete;
	}

	private function typeSearch(string $abstract, array $mapping): ?string {
		if (in_array($abstract, $mapping, true)) {
			$matches = array_keys(array_filter($mapping, function ($mappedTo) use ($abstract) {
				return $mappedTo === $abstract;
			}));

			if (count($matches) === 1) {
				return array_pop($matches);
			}

			$matches = implode("', '", $matches);
			throw new DependencyInjectionException("Failed getting instance of type '{$abstract}'. Ambiguous dependencies: '{$matches}'.");
		}

		return null;
	}

}
