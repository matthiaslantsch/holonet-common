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

namespace holonet\common;

use Psr\Container\ContainerInterface;

/**
 * Dependency Injection container conforming with PSR-11.
 */
class Containers implements ContainerInterface {
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
}
