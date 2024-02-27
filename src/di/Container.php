<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use holonet\common\collection\Registry;
use holonet\holofw\auth\Authoriser;
use ReflectionClass;
use Psr\Container\ContainerInterface;
use holonet\common\di\autowire\AutoWire;
use holonet\common\collection\ConfigRegistry;
use holonet\common\di\autowire\AutoWireException;
use ReflectionNamedType;
use function holonet\common\get_class_short;

/**
 * Dependency Injection container conforming with PSR-11.
 */
class Container implements ContainerInterface {
	/**
	 * @var array<string, string> $aliases key mapping with all available alternative names on the container
	 */
	protected array $aliases = array();

	/**
	 * @var string[] $services All alias keys that are services
	 */
	protected array $services = array();

	/**
	 * @var array<string, string> $providers Provider classes mapping from alias => class name
	 */
	protected array $providers = array();

	/**
	 * @var array<string, array<string, array{string, array}>> $callers Method calls with injection definitions
	 */
	protected array $callers = array();

	/**
	 * @var array<string, object> $instances a key value storage with dependency objects
	 */
	protected array $instances = array();

	/**
	 * @var array<string, array{string, array}> $wiring Wiring information on how to make certain types of objects.
	 * Mapped by name / type => class abstract (array with class name and parameters).
	 */
	protected array $wiring = array();

	/**
	 * @var string[] $recursionPath Array used keep track of injections (to prevent recursive dependencies)
	 */
	protected array $recursionPath = array();

	protected AutoWire $autoWiring;

	public function __construct(public ConfigRegistry $registry = new ConfigRegistry(), array $initialServices = array()) {
		$this->autoWiring = new AutoWire($this);

		$this->set('container', $this);
		$this->set('registry', $this->registry);

		foreach ($initialServices as $id => $service) {
			$this->set($id, $service);
		}
	}

	/**
	 * @template T
	 * @param class-string<T> $class
	 * @return T
	 * @psalm-suppress InvalidReturnStatement
	 */
	public function byType(string $class, ?string $alias = null): object {
		$foundAliases = $this->findAliasesForType($class);
		if (count($foundAliases) === 1) {
			return $this->make($foundAliases[0]);
		}

		if ($alias === null && count($foundAliases) > 1) {
			throw new DependencyInjectionException(sprintf('Ambiguous dependency of type \'%s\' requested: found %d dependencies of that type', $class, count($foundAliases)));
		}

		if (!in_array($alias, $foundAliases)) {
			// we don't have it, let's try to make it
			try {
				return $this->make($alias);
			} catch (DependencyInjectionException $e) {
				return $this->make($class);
			}
		}

		return $this->get($alias);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $id): object {
		if (in_array($id, $this->recursionPath)) {
			$this->recursionPath[] = $id;
			throw new DependencyInjectionException(sprintf('Recursive dependency definition detected: %s', implode(' => ', $this->recursionPath)));
		}

		if (!$this->has($id)) {
			throw new DependencyNotFoundException("Container has no named dependency called '{$id}'");
		}

		// if we have the dependency, just return it
		if (isset($this->instances[$id])) {
			return $this->instances[$id];
		}

		try {
			$this->recursionPath[] = $id;
			$this->instances[$id] = $this->instance($id);
		} finally {
			array_pop($this->recursionPath);
		}

		return $this->instances[$id];
	}

	/**
	 * {@inheritDoc}
	 */
	public function has($id): bool {
		return in_array($id, $this->services);
	}

	/**
	 * @template T
	 * @param class-string<T>|string $alias
	 * @return T
	 * @psalm-suppress InvalidReturnType
	 * @psalm-suppress InvalidReturnStatement
	 */
	public function make(string $alias, array $extraParams = array()): object {
		if ($this->has($alias)) {
			return $this->get($alias);
		}

		if (in_array($alias, $this->recursionPath)) {
			$this->recursionPath[] = $alias;
			throw new DependencyInjectionException(sprintf('Recursive dependency definition detected: %s', implode(' => ', $this->recursionPath)));
		}

		try {
			$this->recursionPath[] = $alias;
			return $this->instance($alias, $extraParams);
		} finally {
			array_pop($this->recursionPath);
		}
	}

	/**
	 * Method used to set a dependency in this class.
	 * If the given value is an object, be saved under the key
	 * If the given value is a string a class name is assumed and the class / argument combination will be saved for later instantiation.
	 */
	public function set(string $id, object|string $value, array $params = array()): void {
		// as the object has already been created, we must assume it has its dependencies
		if (is_object($value)) {
			if (is_a($value, Provider::class, true)) {
				$value = $value->make();
			}
			$this->aliases[$id] = get_class($value);
			$this->instances[$id] = $value;
			$this->services[] = $id;

			return;
		}

		if (class_exists($value)) {
			$this->wire($value, $params, $id);
			$this->services[] = $id;

			return;
		}

		throw new DependencyInjectionException("Could not set dependency '{$id}': value is not an object or class name");
	}

	/**
	 * Set up a wiring from an abstract to an actual implementation.
	 * This can be used to choose strategy pattern possibilities based on config.
	 * The wired object will also get additional params autowired.
	 */
	public function wire(string $wiredTo, array $params = array(), string $alias = null): void {
		// allow for wire() to be used as a convenient shorthand for provide()
		// resolve any wiring that might be made using a provider
		if (is_a($wiredTo, Provider::class, true)) {
			$reflection = new ReflectionClass($wiredTo);
			$factoryMethod = $reflection->getMethod('make');

			$returnType = $factoryMethod->getReturnType();
			if (!$returnType instanceof ReflectionNamedType || $returnType->getName() === 'object') {
				throw new DependencyInjectionException("Provider factory method {$reflection->getName()}::make() has no return type");
			}

			// default alias to the type that the provider returns
			$alias ??= $returnType->getName();

			// set up a provider mapping from the alias to the provider class
			$this->providers[$alias] = $wiredTo;

			// set up the wiring to make the provider
			$this->wiring[$wiredTo] = array($wiredTo, $params);
			return;
		}

		// default alias to the class that is being wired to
		$alias ??= $wiredTo;
		if ($alias !== $wiredTo) {
			// check if the given alias name already exists and set up a alias to an alias if it does
			if (isset($this->aliases[$alias])) {
				$this->aliases[$this->aliases[$alias]] = $wiredTo;
			} else {
				$this->aliases[$alias] = $wiredTo;
			}
		}

		// if it is a class, remember how to make it (the wiring)
		if (class_exists($wiredTo)) {
			$this->wiring[$alias] = array($wiredTo, $params);
			return;
		}

		if (!interface_exists($wiredTo)) {
			throw new DependencyInjectionException("Could not auto-wire to '{$wiredTo}': not a class or interface");
		}
	}

	protected function instance(string $alias, array $params = array()): object {
		// it could be something we have a mapped provider for
		if (isset($this->providers[$alias])) {
			$provider = $this->providers[$alias];
			list($class, $wiredParams) = $this->wiring[$provider];

			$provider = $this->instance($class, array_merge($wiredParams, $params));
			return $provider->make();
		}

		// it could be something we have a wiring directive for
		if (isset($this->wiring[$alias])) {
			list($class, $wiredParams) = $this->wiring[$alias];
			$params = array_merge($wiredParams, $params);
		} else {
			// it could be a type that was wired to by some other type
			$foundAliases = $this->findAliasesForType($alias);
			if(count($foundAliases) === 1) {
				return $this->instance($foundAliases[0], $params);
			} elseif (count($foundAliases) > 1) {
				throw new DependencyInjectionException("Multiple aliases found for '{$alias}': " . implode(', ', $foundAliases));
			}

			if(isset($this->aliases[$alias])) {
				return $this->instance($this->aliases[$alias], $params);
			}
		}

		// assume the given alias is a class the user wants to have made
		$class ??= $alias;
		if (!class_exists($class)) {
			throw new DependencyInjectionException("No idea how to make '{$alias}'. Class does not exist and no wire directive was set");
		}

		if ($this->registry->get('di.warn_on_inefficient_instantiation')) {
			// emit a warning using the php error system
			trigger_error("Inefficient instantiation of '{$class}' using the dependency injection container", E_USER_WARNING);
		}

		$reflection = new ReflectionClass($class);
		if ($reflection->isAbstract()) {
			// it could be a type that was wired to by some other type
			if(isset($this->aliases[$class])) {
				return $this->instance($this->aliases[$alias], $params);
			}
			throw new DependencyInjectionException("Cannot instantiate abstract class '{$class}'");
		}

		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			if (!empty($params)) {
				AutoWireException::failNoConstructor($reflection, $params);
			}

			return new $class();
		} else {
			$params = $this->autoWiring->autoWire($constructor, $params);

			return new $class(...$params);
		}
	}

	private function findAliasesForType(string $type): array {
		if (!empty($found = array_keys($this->aliases, $type))) {
			return $found;
		}

		return array_keys(array_filter($this->aliases, function (string $aliasedType) use($type): bool {
			return is_a($type, $aliasedType, true) || is_a($aliasedType, $type, true);
		}));
	}
}
