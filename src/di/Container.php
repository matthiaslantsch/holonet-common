<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * Class file for the Dependency Injection Container class
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use TypeError;
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
	 * @var array $dependencies A key value storage with dependency objects
	 */
	private $dependencies = array();

	/**
	 * {@inheritdoc}
	 * @param array $getFor Array used keep track of injections (to prevent recursive dependencies)
	 */
	public function get($id, array $getFor = array()) {
		if (!$this->has($id)) {
			throw new DependencyNotFoundException("Dependency '{$id}' does not exist on Dependency Container");
		}

		if (in_array($id, $getFor)) {
			throw new DependencyInjectionException('Recursive dependency definition detected: '.implode(' => ', $getFor));
		}

		$value = $this->dependencies[$id];
		if (is_object($value)) {
			return $value;
		}

		try {
			list($class, $args) = $value;
			$value = new $class(...$args);
			$getFor[] = $id;
			$this->inject($value, true, $getFor);
			if (method_exists($value, 'init')) {
				$value->init();
			}
			$this->dependencies[$id] = $value;
		} catch (TypeError $e) {
			throw new DependencyInjectionException(
				"Cannot initialise dependency '{$id}' on Dependency Container: '{$e->getMessage()}'",
				(int)($e->getCode()), $e
			);
		}

		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($id) {
		return isset($this->dependencies[$id]);
	}

	/**
	 * Method used to inject dependencies into an object, here called "the user of the dependencies".
	 * @param object $dependencyUser The object to be injected
	 * @param bool $forceInjection Whether to throw an exception if a dependency cannot be found
	 * @param array $injectFor Array used keep track of injections (to prevent recursive dependencies)
	 */
	public function inject(object $dependencyUser, bool $forceInjection = true, array $injectFor = array()): void {
		foreach ($dependencyUser as $propertyName => $propertyValue) {
			if (mb_strpos($propertyName, static::DI_PREFIX) === 0) {
				$depKey = str_replace(static::DI_PREFIX, '', $propertyName);
				if (!$this->has($depKey) && $forceInjection) {
					throw new DependencyNotFoundException("Dependency '{$depKey}' does not exist on Dependency Container");
				}
				$dependencyUser->{$propertyName} = $this->get($depKey, $injectFor);
			}
		}
	}

	/**
	 * Method used to set a dependency in this class.
	 * If the given value is an object, it will get injected and saved under the key
	 * If the given value is a string a class name is assumed and the class / argument combination will be saved for later instantiation.
	 * @param string $id The key to save the dependency under
	 * @param object|string $value The dependency to save
	 * @param mixed ...$constructorArgs Arguments for the class instantiation
	 */
	public function set(string $id, $value, ...$constructorArgs): void {
		if (is_string($value) && class_exists($value)) {
			$value = array($value, $constructorArgs);
		} else {
			if (!is_object($value)) {
				throw new DependencyInjectionException(
					"Cannot create dependency '{$id}' on Dependency Container"
				);
			}

			$this->inject($value);
		}

		$this->dependencies[$id] = $value;
	}
}
