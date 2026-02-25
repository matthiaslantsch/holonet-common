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
use holonet\common\di\DependencyNotFoundException;
use holonet\common\di\Provider;
use ReflectionClass;
use ReflectionNamedType;
use function holonet\common\is_abstract;

/**
 * @internal
 */
trait TracksWiringConcern {

	use TracksAbstractsConcern;

	/**
	 * @var array<string, array> $wiring Wiring information on how to make certain types of objects.
	 * Mapped by name / type => class abstract (array with class name and parameters).
	 */
	protected array $wiring = array();

	/**
	 * @var array <string,string> $providers Mapping from abstracts to whatever provider class makes them.
	 */
	protected array $providers = array();

	/**
	 * Central wiring function to teach the container how to make an object.
	 * @param ?string $name Abstract to wire from (the key)
	 * @param string $abstract Abstract to wire to
	 * Examples:
	 * 	wire(alias, args) => tell the container how to make an object that was referred to by its alias
	 * 	wire(concrete, args) => just wire a simple object construction
	 *  wire(concrete, args, contract / alias) => wire an object construction to an alias or contract
	 *  wire(provider, args) => wire a custom factory provider to make an object
	 *  wire(provider, args, contract / alias) => wire a custom factory provider to make an object and supply
	 */
	public function wire(string $abstract, array $args = array(), ?string $name = null): void {
		if ($this->hasAlias($abstract)) {
			$this->wire($this->resolve($abstract), $args, $name);
			return;
		}

		if (!class_exists($abstract)) {
			throw new DependencyInjectionException("Abstract '{$abstract}' is not a class or configured alias.");
		}
		$aliasTo = $abstract;

		if (is_subclass_of($abstract, Provider::class)) {
			$reflection = new ReflectionClass($abstract);
			$type = $reflection->getMethod('make')->getReturnType();
			if (!$type instanceof ReflectionNamedType || (!class_exists($type->getName())) && !interface_exists($type->getName())) {
				throw new DependencyInjectionException("Provider '{$abstract}::make()' has an invalid return type: '{$type->getName()}'.");
			}
			$aliasTo = $type->getName();
			$this->providers[$type->getName()] = $abstract;
		}

		if (isset($name) && $name !== $aliasTo) {
			if (interface_exists($name) || class_exists($name)) {
				$this->contract($name, $aliasTo);
			} else {
				$this->alias($name, $aliasTo);
			}
		}

		$this->wiring[$abstract] = $args;
	}

}
