<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\concerns;

use holonet\common\di\error\DependencyNotFoundException;
use holonet\common\di\Provider;

/**
 * @internal
 */
trait HasServicesConcern {

	use MakesObjectConcern;
	use TracksRecursionConcern;

	/**
	 * @var array<string, object|null>
	 */
	protected array $instances = array();

	/**
	 * @var string[]
	 */
	protected array $services = array();

	/**
	 * Method from the interface. Only concerns services.
	 */
	public function get(string $id) {
		$concrete = $this->resolve($id);

		$this->recursionCheck($id);

		if (!$this->has($id)) {
			throw new DependencyNotFoundException("Container has no service called '{$id}'");
		}

		// if we have the dependency, just return it
		if (isset($this->instances[$id])) {
			return $this->instances[$id];
		}

		try {
			$this->recursionPush($id);
			$object = $this->instantiate($concrete);
			if ($object instanceof Provider) {
				$object = $object->make();
			}
			$this->instances[$id] = $object;
		} finally {
			$this->recursionPop();
		}

		return $this->instances[$id];
	}

	/**
	 * Method from the interface. Only concerns services.
	 */
	public function has(string $id): bool {
		return in_array($id, $this->services);
	}

	/**
	 * Method used to set a dependency in this class.
	 * If the given value is an object, be saved under the key
	 * If the given value is a string a class name is assumed and the class / argument combination will be saved for later instantiation.
	 */
	public function set(string $id, object|string $value, array $params = array()): void {
		$this->services[] = $id;

		if (is_object($value)) {
			if ($value instanceof Provider) {
				$value = $value->make();
			}

			$concrete = get_class($value);
			$this->alias($id, $concrete);
			$this->instances[$id] = $value;
			return;
		}

		$this->wire($value, $params, $id);
	}

}
