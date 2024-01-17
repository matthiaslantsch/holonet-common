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
use ArrayIterator;
use IteratorAggregate;

/**
 * The Collection is used as a wrapper around a data array
 * it allows for the data to be accessed like an array or object.
 * @template TKey of array-key
 * @template T
 *
 * @implements IteratorAggregate<array-key, T>
 */
class Collection implements Countable, IteratorAggregate {
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
	 * return a reference, so we can change e.g. sub arrays from this call
	 *  => cannot use our own get() method.
	 */
	public function &__get(string $key): mixed {
		if (!$this->has($key)) {
			$null = null;
			return $null;
		}

		return $this->data[$key];
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
	public function all(?array $which = null): array {
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
