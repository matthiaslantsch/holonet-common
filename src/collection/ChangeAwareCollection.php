<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use OutOfBoundsException;

/**
 * The ChangeAwareCollection is used as a wrapper around an array
 * it keeps track internally on how it's data changed.
 * @psalm-suppress MissingTemplateParam
 */
class ChangeAwareCollection implements Countable, ArrayAccess, IteratorAggregate {
	/**
	 * Array with $all keys of newly added entries.
	 */
	protected array $added = array();

	/**
	 * @var array<string, mixed> $all An array containing all entries
	 */
	protected array $all = array();

	/**
	 * Array containing the $all keys of changed entries.
	 */
	protected array $changed = array();

	/**
	 * Array containing the $all keys of removed entries.
	 */
	protected array $removed = array();

	public function __construct(array $initial = array()) {
		$this->addAll($initial, false);
	}

	/**
	 * change function to change an entry (add to $this->changed).
	 * @param mixed $entry Either the key or the value that changes
	 * @return mixed reference to the value or null if it doesn't exist
	 */
	public function &change(mixed $entry): mixed {
		$key = $this->findKeyForKeyOrEntry($entry);
		if ($key !== null) {
			$this->changed[] = $key;

			return $this->all[$key];
		}

		throw new OutOfBoundsException("Entry not found");
	}

	/**
	 * @param mixed $val The data entry to be saved
	 * @param string $key The key to save the entry under
	 * @param bool $new Flag marking this entry as not new (not to be saved into $this->added)
	 */
	public function add(mixed $val, string $key, bool $new = true): void {
		if (is_object($val) && is_subclass_of($val, ChangeAwareInterface::class)) {
			$val->belongsTo($this);
		}

		$this->all[$key] = $val;

		//if the override flag wasn't given, mark the entry as newly added
		if ($new) {
			$this->added[] = $key;
		}
	}

	/**
	 * @param array $values The data entries to be saved
	 * @param bool $new Flag marking these entries as not new (not to be saved into $this->added)
	 */
	public function addAll(array $values, bool $new = true): void {
		foreach ($values as $key => $val) {
			$this->add($val, $key, $new);
		}
	}

	/**
	 * function used to "reset" the internal change arrays and removing all the removed entries.
	 */
	public function apply(): void {
		$this->all = $this->getAll('current');
		$this->removed = array();
		$this->changed = array();
		$this->added = array();
	}

	public function changed(): bool {
		return !empty($this->added) || !empty($this->removed) || !empty($this->changed);
	}

	/**
	 * Only counts the "current" entries
	 * {@inheritDoc}
	 */
	public function count(): int {
		return count($this->getAll('current'));
	}

	public function empty(): bool {
		return empty($this->all);
	}

	/**
	 * Does not return "removed" items.
	 * @param string $key The key for the value
	 * @return mixed the value from the $this->all array or null if not found
	 */
	public function get(string $key): mixed {
		if (isset($this->all[$key]) && !in_array($key, $this->removed)) {
			return $this->all[$key];
		}

		return null;
	}

	/**
	 * Does not return "removed" items, except the "removed" key is given.
	 * @param string $what Determines what set of data should be returned
	 * @return array with all the values that match the specification
	 */
	public function getAll(string $what = 'current'): array {
		if ($what === 'current') {
			return array_diff_key(
				$this->all, //all our values
				array_intersect_key($this->all, array_flip($this->removed)) //the removed values
			);
		}
		if ($what === 'new') {
			return array_intersect_key($this->all, array_flip($this->added));
		}
		if ($what === 'removed') {
			return array_intersect_key($this->all, array_flip($this->removed));
		}
		if ($what === 'changed') {
			return array_intersect_key($this->all, array_merge(
				array_flip($this->changed), //the changed values
				array_flip($this->added) //the new values
			));
		}
		if ($what === 'updated') {
			return array_intersect_key($this->all, array_merge(
				array_flip($this->changed), //the changed values
			));
		}
		if ($what === 'unchanged') {
			return array_diff_key(
				$this->all, //all our values
				array_intersect_key($this->all, array_merge(
					array_flip($this->removed), //the removed values
					array_flip($this->changed), //the changed values
					array_flip($this->added) //the new values
				))
			);
		}

		return $this->all;
	}

	public function has(mixed $value): bool {
		return $this->findKeyForKeyOrEntry($value) !== null;
	}

	/**
	 * change function to remove an entry (add to $this->removed).
	 * @param mixed $entry Either the key or the value that changes
	 * @return bool true or false on success or not
	 */
	public function remove(mixed $entry): bool {
		$key = $this->findKeyForKeyOrEntry($entry);
		if ($key !== null) {
			$this->removed[] = $key;

			return true;
		}

		return false;
	}

	/**
	 * function that can be used to replace all internal values with a new set.
	 * @param array $values An array with new values
	 */
	public function replace(array $values): void {
		$this->apply();
		$this->removed = array_keys($this->all);
		$this->addAll($values);
	}

	/**
	 * setter function to set a value by its key
	 * either calls the add function or set the value.
	 * @param string $key The key for the value
	 * @param mixed $value The value that is to be set
	 */
	public function set(string $key, mixed $value): void {
		if (!array_key_exists($key, $this->all)) {
			$this->add($value, $key);
		} else {
			if ($this->all[$key] !== $value) {
				$this->changed[] = $key;
			}

			if (is_object($value) && is_subclass_of($value, ChangeAwareInterface::class)) {
				$value->belongsTo($this);
			}

			$this->all[$key] = $value;
		}
	}

	/**
	 * helper function to figure out what an argument is
	 * first assumes the argument is a key
	 * then assumes the argument is a value
	 * and return the key for the value or null if not found.
	 * @param mixed $entry Either the key or the value
	 * @return mixed|null the key of the value/the key if it's a key
	 */
	private function findKeyForKeyOrEntry(mixed $entry): ?string {
		//check if $entry is an array key (allow null, so no isset)
		if (is_string($entry) && array_key_exists($entry, $this->all)) {
			return $entry;
		}

		if (($key = array_search($entry, $this->all, true)) !== false) {
			return $key;
		}

		return null;
	}

	public function offsetExists(mixed $offset): bool {
		// specifically use isset() in order to return false on null values
		return isset($this->all[$offset]) && !in_array($offset, $this->removed);
	}

	/**
	 * @see self::get()
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->get($offset);
	}

	/**
	 * @see self::set()
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @see self::remove()
	 */
	public function offsetUnset(mixed $offset): void {
		$this->remove($offset);
	}

	public function getIterator(): Iterator {
		return new ArrayIterator($this->getAll());
	}
}
