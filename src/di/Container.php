<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use ReflectionClass;
use Psr\Container\ContainerInterface;
use holonet\common\di\autowire\AutoWire;
use holonet\common\config\ConfigRegistry;
use holonet\common\di\autowire\AutoWireException;

/**
 * Dependency Injection container conforming with PSR-11.
 */
class Container implements ContainerInterface {
	/**
	 * @var array<string, string> $aliases key mapping with all available services on the container
	 */
	protected array $aliases = array();

	protected AutoWire $autoWiring;

	/**
	 * @var array<string, array<string, array{string, array}>> $callers Method calls with injection definitions
	 */
	protected array $callers = array();

	/**
	 * @var array<string, object> $instances a key value storage with dependency objects
	 */
	protected array $instances = array();

	/**
	 * @var string[] $recursionPath Array used keep track of injections (to prevent recursive dependencies)
	 */
	protected array $recursionPath = array();

	/**
	 * @var array<string, array{string, array<string, array>}> $wiring Wiring information on how to make certain types of objects.
	 * Mapped by name / type => class abstract (array with class name and parameters).
	 */
	protected array $wiring = array();

	public function __construct(public ConfigRegistry $registry = new ConfigRegistry()) {
		$this->autoWiring = new AutoWire($this);
	}

	/**
	 * @template T
	 * @param class-string<T> $class
	 * @return T
	 */
	public function byType(string $class, ?string $id = null): object {
		$keys = array_keys($this->aliases, $class);
		if (count($keys) === 1) {
			return $this->get(reset($keys));
		}

		if ($id === null) {
			throw new DependencyInjectionException(sprintf('Ambiguous dependency of type \'%s\' requested: found %d dependencies of that type', $class, count($keys)));
		}

		if (!in_array($id, $keys)) {
			// we don't have it, let's try to make it
			return $this->make($class);
		}

		return $this->get($id);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $id): object {
		if (in_array($id, $this->recursionPath)) {
			throw new DependencyInjectionException(sprintf('Recursive dependency definition detected: %s', implode(' => ', $this->recursionPath)));
		}

		if (!$this->has($id)) {
			throw new DependencyNotFoundException("Container has no named dependency called '{$id}'");
		}

		// if we have the dependency, just return it
		if (isset($this->instances[$id])) {
			return $this->instances[$id];
		}

		list($class, $params) = $this->wiring[$id];

		$this->recursionPath[] = $id;
		$this->instances[$id] = $this->instance($class, $params);
		array_pop($this->recursionPath);

		return $this->instances[$id];
	}

	/**
	 * {@inheritDoc}
	 */
	public function has($id): bool {
		return isset($this->aliases[$id]);
	}

	/**
	 * @template T
	 * @param class-string<T>|string $abstract
	 * @return T
	 */
	public function make(string $abstract, array $extraParams = array()): object {
		if ($this->has($abstract)) {
			return $this->get($abstract);
		}

		if (in_array($abstract, $this->recursionPath)) {
			throw new DependencyInjectionException(sprintf('Recursive dependency definition detected: %s', implode(' => ', $this->recursionPath)));
		}

		$this->recursionPath[] = $abstract;
		if (isset($this->wiring[$abstract])) {
			list($class, $params) = $this->wiring[$abstract];
			$instance = $this->instance($class, array_merge($params, $extraParams));
		} else {
			if (!class_exists($abstract)) {
				throw new DependencyInjectionException("No idea how to make '{$abstract}'. Class does not exist and no wire directive was set");
			}

			$instance = $this->instance($abstract, $extraParams);
		}
		array_pop($this->recursionPath);

		return $instance;
	}

	/**
	 * Method used to set a dependency in this class.
	 * If the given value is an object, be saved under the key
	 * If the given value is a string a class name is assumed and the class / argument combination will be saved for later instantiation.
	 */
	public function set(string $id, object|string $value, array $params = array()): void {
		// as the object has already been created, we must assume it has its dependencies
		if (is_object($value)) {
			$this->aliases[$id] = get_class($value);
			$this->instances[$id] = $value;

			return;
		}

		if (class_exists($value)) {
			$this->aliases[$id] = $value;
			$this->wire($value, $params, $id);
		}
	}

	/**
	 * Set up a wiring from an abstract to an actual implementation.
	 * This can be used to choose strategy pattern possibilities based on config.
	 * The wired object will also get additional params autowired.
	 */
	public function wire(string $class, array $params = array(), ?string $abstract = null): void {
		if (!class_exists($class)) {
			throw new DependencyInjectionException("Could not auto-wire abstract '{$class}': class does not exist");
		}

		$abstract ??= $class;

		$this->wiring[$abstract] = array($class, $params);
	}

	protected function instance(string $class, array $params): object {
		$reflection = new ReflectionClass($class);
		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			if (!empty($params)) {
				AutoWireException::failNoConstructor($reflection, $params);
			}

			return new $class();
		}

		$params = $this->autoWiring->autoWire($constructor, $params);

		return new $class(...$params);
	}
}
