<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
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
	protected array $data = array();

	public function __construct(array $initial = array()) {
		$this->data = $initial;
	}

	/**
	 * Doesn't use our internal has() method in order to return false for null values.
	 */
	public function __isset(string $key): bool {
		return isset($this->data[$key]);
	}

	/**
	 * @see self::set()
	 */
	public function __set(string $key, $value): void {
		$this->set($key, $value);
	}

	/**
	 * @see self::remove()
	 */
	public function __unset(string $key): void {
		$this->remove($key);
	}

	/**
	 * return a reference so we can change e.g. sub arrays from this call
	 *  => cannot use our own get() method.
	 */
	public function &__get(string $key) {
		if (is_object($this->data[$key]) || $this->data[$key] === null) {
			//only actual variables should be returned by reference
			return $this->data[$key];
		}

		return $this->data[$key] ?? null;
	}

	public function clear(): void {
		$this->data = array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function count(): int {
		return count($this->data);
	}

	public function empty(): bool {
		return empty($this->data);
	}

	public function get(string $key, $default = null) {
		return $this->data[$key] ?? $default;
	}

	/**
	 * @param string[] $which Array with keys that are requested
	 */
	public function getAll(?array $which = null): array {
		if ($which !== null) {
			return array_intersect_key($this->data, array_flip($which));
		}

		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator($this->data);
	}

	public function has(string $key): bool {
		// Don't use "isset", since it returns false for null values
		return array_key_exists($key, $this->data);
	}

	public function merge(array $data = array()): void {
		//don't merge an empty array
		if (!empty($data)) {
			$this->data = array_merge($this->data, $data);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see self::__isset()
	 */
	public function offsetExists($offset): bool {
		//use the isset method in order to return false for null value (the behaviour of isset())
		return $this->__isset($offset);
	}

	/**
	 * {@inheritDoc}
	 * @see self::get()
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * {@inheritDoc}
	 * @see self::set()
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * {@inheritDoc}
	 * @see self::remove()
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	public function remove(string $key): void {
		unset($this->data[$key]);
	}

	public function replace(array $data = array()): void {
		$this->data = $data;
	}

	public function set(?string $key, $value): void {
		if ($key === null) {
			$this->data[] = $value;
		} else {
			$this->data[$key] = $value;
		}
	}
}
