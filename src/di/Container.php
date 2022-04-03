<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use TypeError;
use ReflectionClass;
use ReflectionException;
use Psr\Container\ContainerInterface;

/**
 * Dependency Injection container conforming with PSR-11.
 */
class Container implements ContainerInterface {
	/**
	 * @var string DI_PREFIX Prefix value for the injected class properties
	 */
	public const DI_PREFIX = 'di_';

	/**
	 * @var array<string, object> $dependencies A key value storage with dependency objects
	 */
	private array $dependencies = array();

	/**
	 * @var array<string, array> $lazyLoadedDeps Lazily loaded dependency objects
	 */
	private array $lazyLoadedDeps = array();

	/**
	 * {@inheritDoc}
	 * @param string[] $getFor Array used keep track of injections (to prevent recursive dependencies)
	 */
	public function get($id, array $getFor = array()) {
		if (in_array($id, $getFor)) {
			throw new DependencyInjectionException('Recursive dependency definition detected: '.implode(' => ', $getFor));
		}

		if (isset($this->dependencies[$id])) {
			return $this->dependencies[$id];
		}
		if (isset($this->lazyLoadedDeps[$id])) {
			try {
				list('class' => $class, 'args' => $args) = $this->lazyLoadedDeps[$id];
				$rfc = new ReflectionClass($class);
				$value = $rfc->newInstanceWithoutConstructor();
				$getFor[] = $id;
				$this->inject($value, true, $getFor);
				if (method_exists($value, '__construct')) {
					$value->__construct(...$args);
				}
				if (method_exists($value, 'init')) {
					trigger_error('Relying on init() to initialise dependency objects after injecting is no longer required and deprecated', \E_USER_DEPRECATED);
				}
				$this->dependencies[$id] = $value;

				return $value;
			} catch (TypeError | ReflectionException $e) {
				throw new DependencyInjectionException("Cannot initialise dependency '{$id}' on Dependency Container: '{$e->getMessage()}'", (int)($e->getCode()), $e);
			}
		} else {
			throw new DependencyNotFoundException("Dependency '{$id}' does not exist on Dependency Container");
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function has($id) {
		return isset($this->dependencies[$id]) || isset($this->lazyLoadedDeps[$id]);
	}

	/**
	 * Method used to inject dependencies into an object, here called "the user of the dependencies".
	 * @param object $dependencyUser The object to be injected
	 * @param bool $forceInjection Whether to throw an exception if a dependency cannot be found
	 * @param array $injectFor Array used keep track of injections (to prevent recursive dependencies)
	 */
	public function inject(object $dependencyUser, bool $forceInjection = true, array $injectFor = array()): void {
		foreach (array_keys(get_class_vars(get_class($dependencyUser))) as $propertyName) {
			$propertyName = (string)$propertyName;
			if (mb_strpos($propertyName, self::DI_PREFIX) === 0) {
				$depKey = str_replace(self::DI_PREFIX, '', $propertyName);
				if (!$this->has($depKey) && $forceInjection) {
					try {
						$dependencyUser->{$propertyName} = null;
					} catch (TypeError $e) {
						throw new DependencyNotFoundException("Dependency '{$depKey}' does not exist on Dependency Container");
					}
				} else {
					$dependencyUser->{$propertyName} = $this->get($depKey, $injectFor);
				}
			}
		}
	}

	/**
	 * Method used to set a dependency in this class.
	 * If the given value is an object, it will get injected and saved under the key
	 * If the given value is a string a class name is assumed and the class / argument combination will be saved for later instantiation.
	 * @param string $id The key to save the dependency under
	 * @param mixed $value The dependency to save
	 * @param mixed ...$constructorArgs Arguments for the class instantiation
	 */
	public function set(string $id, $value, ...$constructorArgs): void {
		if (is_string($value) && class_exists($value)) {
			$this->lazyLoadedDeps[$id] = array('class' => $value, 'args' => $constructorArgs);
		} else {
			if (!is_object($value)) {
				throw new DependencyInjectionException("Cannot create dependency '{$id}' on Dependency Container");
			}

			$this->inject($value);
			$this->dependencies[$id] = $value;
		}
	}
}
