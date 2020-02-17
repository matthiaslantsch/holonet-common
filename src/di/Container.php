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
	 * @var array A key value storage with dependency objects
	 */
	private $dependencies = array();

	/**
	 * {@inheritdoc}
	 */
	public function get($id) {
		if (!$this->has($id)) {
			throw new DependencyNotFoundException("Dependency '{$id}' does not exist on Dependency Container");
		}

		return $this->dependencies[$id];
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
	 */
	public function inject(object $dependencyUser, bool $forceInjection = true): void {
		foreach ($dependencyUser as $propertyName => $propertyValue) {
			if (mb_strpos($propertyName, static::DI_PREFIX) === 0) {
				$depKey = str_replace(static::DI_PREFIX, '', $propertyName);
				if (!$this->has($depKey) && $forceInjection) {
					throw new DependencyNotFoundException("Dependency '{$depKey}' does not exist on Dependency Container");
				}
				$dependencyUser->{$propertyName} = $this->get($depKey);
			}
		}
	}

	/**
	 * Method used to set a dependency in this class.
	 * If the given value is an object, it will get injected and saved under the key
	 * If the given value is a string a class name is assumed and a new object will be created and automatically get injected.
	 * @param string $id The key to save the dependency under
	 * @param object|string $value The dependency to save
	 * @param mixed ...$constructorArgs Arguments for the class instantiation
	 */
	public function set(string $id, $value, ...$constructorArgs): void {
		if (is_string($value) && class_exists($value)) {
			try {
				$value = new $value(...$constructorArgs);
			} catch (TypeError $e) {
				throw new DependencyInjectionException(
					"Cannot create dependency '{$id}' on Dependency Container: '{$e->getMessage()}'",
					$e->getCode(), $e
				);
			}
		}

		if (!is_object($value)) {
			throw new DependencyInjectionException(
				"Cannot set dependency '{$id}' on Dependency Container, value must be object or class string"
			);
		}

		$this->inject($value);
		$this->dependencies[$id] = $value;
	}
}
