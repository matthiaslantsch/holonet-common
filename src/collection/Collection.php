<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the Collection class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * The Collection is used as a wrapper around a data array
 * it allows for the data to be accessed like an array or object.
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate {
	/**
	 * holds all the data entries.
	 *
	 * @var array An array containing all data entries
	 */
	protected $data = array();

	/**
	 * constructor for the collection, allowing the user to give a set of
	 * initial entries.
	 *
	 * @param array $initial Array with initial data entries
	 */
	public function __construct(array $initial = array()) {
		$this->data = $initial;
	}

	/**
	 * Whether or not a data entry exists by key
	 * don't use our internal has() method in order to return false for null values.
	 * @param string $key Key to check for
	 * @return bool if isset or not
	 */
	public function __isset($key): bool {
		return isset($this->data[$key]);
	}

	/**
	 * Assigns a value to the specified key
	 * uses our internal set() method.
	 * @param string $key The data key to assign the value to
	 * @param mixed $value The value to set
	 */
	public function __set($key, $value): void {
		$this->set($key, $value);
	}

	/**
	 * Unsets a data entry by key
	 * uses our internal remove() method.
	 * @param string $key The key to unset
	 */
	public function __unset($key): void {
		$this->remove($key);
	}

	/**
	 * Get a data by key
	 * return a reference so we can change e.g. sub arrays from this call
	 *  => cannot use our own get() method.
	 * @param string $key The key data to retrieve
	 * @return mixed reference to the value for the key
	 */
	public function &__get($key) {
		if (is_object($this->data[$key]) || null === $this->data[$key]) {
			//only variable variables should be returned by reference
			return $this->data[$key];
		}

		return @$this->data[$key];
	}

	public function clear(): void {
		$this->data = array();
	}

	/**
	 * @return int number of items in the internal data array
	 */
	public function count(): int {
		return count($this->data);
	}

	/**
	 * @return bool if our array is empty or not
	 */
	public function empty(): bool {
		return empty($this->data);
	}

	/**
	 * getter function used to return a single entry by it's key
	 * returns null if a value doesn't exist.
	 *
	 * @param string $key The key for the value that is requested
	 * @param mixed $default The default that should be returned if the key doesn't exist
	 * @return mixed value that was saved under the given key or the default/null if not found
	 */
	public function get($key, $default = null) {
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}

	/**
	 * getter function used to return entries from the internal data storage
	 * can be used with a mask in order to return an array of values.
	 *
	 * @param array $which Array with keys that are requested
	 * @return array with the values from the keys in $which or all values
	 */
	public function getAll(array $which = null): array {
		if ($which !== null) {
			return array_intersect_key($this->data, array_flip($which));
		}

		return $this->data;
	}

	/**
	 * @return ArrayIterator to iterate over our internal data
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator($this->data);
	}

	/**
	 * @param string $key The key that should be checked for
	 * @return bool if key exists or not
	 */
	public function has($key): bool {
		// Don't use "isset", since it returns false for null values
		return array_key_exists($key, $this->data);
	}

	/**
	 * @param array $data The data to be merged into the collection
	 */
	public function merge(array $data = array()): void {
		//don't merge an empty array
		if (!empty($data)) {
			$this->data = array_merge($this->data, $data);
		}
	}

	/**
	 * Check wheter or not an offset exists.
	 * @param string $offset A offset to check for
	 * @return bool is isset or not
	 */
	public function offsetExists($offset): bool {
		//use the isset method in order to return false for null value (the behaviour of isset())
		return $this->__isset($offset);
	}

	/**
	 * Returns the value at specified offset.
	 * @param string $offset The offset to retrieve
	 * @return mixed the value that was saved at the offset or null if not set
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * Sets a data entry by key
	 * uses our internal set() method.
	 * @param string $offset The key to assign the value to
	 * @param mixed $value The value to set
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * Unsets an offset
	 * uses our internal remove() method.
	 * @param string $offset The offset to unset
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	/**
	 * @param string $key The key to unset
	 */
	public function remove($key): void {
		unset($this->data[$key]);
	}

	/**
	 * @param array $data The data array to replace the collection's with
	 */
	public function replace(array $data = array()): void {
		$this->data = $data;
	}

	/**
	 * @param string|null $key The key of the parameter to set
	 * @param mixed|null $value The value of the parameter to set
	 */
	public function set($key, $value): void {
		if ($key === null) {
			$this->data[] = $value;
		} else {
			$this->data[$key] = $value;
		}
	}
}
