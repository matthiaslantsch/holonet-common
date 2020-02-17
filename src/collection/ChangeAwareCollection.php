<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the ChangeAwareCollection class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use holonet\common\ComparableInterface;

/**
 * The ChangeAwareCollection is used as a wrapper around an array
 * it keeps track internally on how it's data changed.
 */
class ChangeAwareCollection implements ArrayAccess, ComparableInterface, Countable, IteratorAggregate {
	/**
	 * holds an array with keys to the $all property array
	 * used to mark data entries as newly added.
	 * @var array $added An array containing the keys of newly added entries
	 */
	protected $added = array();

	/**
	 * holds all the data entries, not only the current/deleted/changed.
	 * @var array $all An array containing all entries
	 */
	protected $all = array();

	/**
	 * holds an array with keys to the $all property array
	 * used to mark data entries as changed.
	 * @var array $changed An array containing the keys of changed entries
	 */
	protected $changed = array();

	/**
	 * holds an array with keys to the $all property array
	 * used to mark data entries as removed.
	 * @var array $removed An array containing the keys of removed entries
	 */
	protected $removed = array();

	/**
	 * constructor for the collection, allowing the user to give a set of
	 * initial entries.
	 * @param array $initial Array with initial data entries
	 */
	public function __construct(array $initial = array()) {
		$this->addAll($initial, false);
	}

	/**
	 * change function to change an entry (add to $this->changed).
	 * @param mixed $entry Either the key or the value that changes
	 * @return mixed reference to the value or null if it doesn't exist
	 */
	public function &change($entry) {
		$key = $this->findKeyForKeyOrEntry($entry);
		if ($key !== null) {
			$this->changed[] = $key;

			return $this->all[$key];
		}
	}

	/**
	 * adder function for an entry to the data array
	 * allows for specifying the key yourself as well as flagging the entry as not new.
	 * @param mixed $val The data entry to be saved
	 * @param int|string|null $key The key to save the entry under
	 * @param bool $new Flag marking this entry as not new (not to be saved into $this->added)
	 */
	public function add($val, $key = null, bool $new = true): void {
		if (is_object($val) && is_subclass_of($val, ChangeAwareInterface::class)) {
			$val->belongsTo($this);
			//not every change aware object can know about a unique key
			if (method_exists($val, 'uniqKey') && $key === null) {
				$key = $val->uniqKey();
			}
		}

		if ($key === null) {
			$this->all[] = $val;
			$key = array_search($val, $this->all, true);
		} else {
			$this->all[$key] = $val;
		}

		//if the override flag wasn't given, mark the entry as newly added
		if ($new) {
			$this->added[] = $key;
		}
	}

	/**
	 * adder function for multiple entries to the data array
	 * allows for flagging the entries as not new.
	 * @param array $values The data entries to be saved
	 * @param bool $new Flag marking these entries as not new (not to be saved into $this->added)
	 */
	public function addAll(array $values, bool $new = true): void {
		array_walk($values, array($this, 'add'), $new);
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

	/**
	 * function used to check if the collection has any changes recorded.
	 * @return bool true on changed or not
	 */
	public function changed(): bool {
		return !empty($this->added) || !empty($this->removed) || !empty($this->changed);
	}

	/**
	 * compare this attribute set to another attribute set.
	 * @param ComparableInterface $other The other object to compare this one to
	 * @return bool if this object should be considered the same attribute set as the other one
	 */
	public function compareTo(ComparableInterface $other): bool {
		if (!$other instanceof self) {
			return false;
		}

		return $this->compareToCollection($other);
	}

	/**
	 * Count the attributes via a simple "count" call.
	 * @return int number of items in the internal data array
	 */
	public function count(): int {
		return count($this->all);
	}

	/**
	 * Small method returning true if this collection contains no data.
	 * @return bool true or false if the internal array is empty or not
	 */
	public function empty(): bool {
		return empty($this->all);
	}

	/**
	 * getter function to return a value by its key
	 * does not return "removed" items.
	 * @param int|string $key The key for the value
	 * @return mixed the value from the $this->all array or null if not found
	 */
	public function get($key) {
		if (isset($this->all[$key]) && !in_array($key, $this->removed)) {
			return $this->all[$key];
		}
	}

	/**
	 * getter function to return all the values that match a specification
	 * does not return "removed" items, except the "removed" key is given.
	 * @param string $what Determines what set of data should be returned
	 * @return array with all the values that match the specification
	 */
	public function getAll($what = 'current'): array {
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
			return array_intersect_key($this->all, array_flip($this->changed));
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

	/**
	 * Get the aggregate iterator.
	 * @return ArrayIterator to iterate over our internal data
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator($this->getAll());
	}

	/**
	 * function that can be used to check if a given value exists
	 * in the internal data array.
	 * @param mixed $value The value to be checked for
	 * @return bool true or false if the given value is contained or not
	 */
	public function has($value): bool {
		//if the given value is comparable, we can use that to find it
		if (is_object($value) && $value instanceof ComparableInterface) {
			foreach ($this->getAll('current') as $entry) {
				if (is_object($entry) && $entry instanceof ComparableInterface && $entry->compareTo($value)) {
					return true;
				}
			}
		} else {
			return in_array($value, $this->getAll('current'), true);
		}

		return false;
	}

	/**
	 * function used to check if an entry exists via isset()
	 * does not return true for "removed" entries.
	 * @param mixed $offset The key to check if it exists
	 * @return bool true or false on exists or not
	 */
	public function offsetExists($offset): bool {
		return isset($this->all[$offset]) && !in_array($offset, $this->removed);
	}

	/**
	 * function used to get an entry via the array syntax
	 * does only return values that aren't "removed".
	 * @param mixed $offset The key to get the value for
	 * @return mixed value from our internal array
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * function used to set an entry via the array syntax
	 * marks the entry as either added or not.
	 * @param mixed $offset The key to save the value under
	 * @param mixed $value The value to be saved
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * function used to unset an entry via unset()
	 * does only add the entry to "removed".
	 * @param mixed $offset The key to unset
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	/**
	 * change function to remove an entry (add to $this->removed).
	 * @param mixed $entry Either the key or the value that changes
	 * @return bool true or false on success or not
	 */
	public function remove($entry): bool {
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
	 * @param int|string|null $key The key for the value
	 * @param mixed $value The value that is to be set
	 */
	public function set($key, $value): void {
		if ($key === null || !array_key_exists($key, $this->all)) {
			$this->add($value, $key);
		} else {
			if ($this->all[$key] !== $value) {
				$this->change($key);
			}

			if (is_object($value) && is_subclass_of($value, ChangeAwareInterface::class)) {
				$value->belongsTo($this);
			}

			$this->all[$key] = $value;
		}
	}

	/**
	 * compare this attribute set to another attribute set.
	 * @param ChangeAwareCollection $other The other object to compare this one to
	 * @return bool if this object should be considered the same attribute set as the other one
	 */
	private function compareToCollection(self $other) {
		return $this->all === $other->all && $this->changed === $other->changed
			&& $this->added === $other->added && $this->removed === $other->removed;
	}

	/**
	 * helper function to figure out what an argument is
	 * first assumes the argument is a key
	 * then assumes the argument is a value
	 * and return the key for the value or null if not found.
	 * @param mixed $entry Either the key or the value
	 * @return mixed|null the key of the value/the key if it's a key
	 */
	private function findKeyForKeyOrEntry($entry) {
		//check if $entry is an array key (allow null, so no isset)
		if ((is_string($entry) || is_int($entry)) && array_key_exists($entry, $this->all)) {
			return $entry;
		}
		if (is_object($entry) && $entry instanceof ComparableInterface) {
			foreach ($this->all as $key => $value) {
				if ($value instanceof ComparableInterface && $entry->compareTo($value)) {
					return $key;
				}
			}
		} elseif (($key = array_search($entry, $this->all)) !== false) {
			return $key;
		} else {
			return null;
		}
	}
}
