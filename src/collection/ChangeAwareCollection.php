<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the ChangeAwareCollection class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

use ArrayAccess;
use Countable;
use ArrayIterator;
use IteratorAggregate;
use holonet\common\IComparable;

/**
 * The ChangeAwareCollection is used as a wrapper around an array
 * it keeps track internally on how it's data changed
 *
 * @author  matthias.lantsch
 * @package holonet\common\collection
 */
class ChangeAwareCollection implements ArrayAccess, Countable, IteratorAggregate, IComparable {

	/**
	 * holds all the data entries, not only the current/deleted/changed
	 *
	 * @access protected
	 * @var    array $all An array containing all entries
	 */
	protected $all = array();

	/**
	 * holds an array with keys to the $all property array
	 * used to mark data entries as changed
	 *
	 * @access protected
	 * @var    array $changed An array containing the keys of changed entries
	 */
	protected $changed = array();

	/**
	 * holds an array with keys to the $all property array
	 * used to mark data entries as newly added
	 *
	 * @access protected
	 * @var    array $added An array containing the keys of newly added entries
	 */
	protected $added = array();

	/**
	 * holds an array with keys to the $all property array
	 * used to mark data entries as removed
	 *
	 * @access protected
	 * @var    array $removed An array containing the keys of removed entries
	 */
	protected $removed = array();

	/**
	 * constructor for the collection, allowing the user to give a set of
	 * initial entries
	 *
	 * @access public
	 * @param  array $initial Array with initial data entries
	 * @return void
	 */
	public function __construct(array $initial = array()) {
		$this->addAll($initial, false);
	}

	/**
	 * adder function for an entry to the data array
	 * allows for specifying the key yourself as well as flagging the entry as not new
	 *
	 * @access public
	 * @param  mixed $value The data entry to be saved
	 * @param  string|int $key The key to save the entry under
	 * @param  bool $new Flag marking this entry as not new (not to be saved into $this->added)
	 * @return void
	 */
	public function add($val, $key = null, bool $new = true) {
		if(is_object($val) && is_subclass_of($val, IChangeAware::class)) {
			$val->belongsTo($this);
			//not every change aware object can know about a unique key
			if(method_exists($val, "uniqKey") && $key !== null) {
				$key = $val->uniqKey();
			}
		}

		if($key === null) {
			$this->all[] = $val;
			$key = array_search($val, $this->all);
		} else {
			$this->all[$key] = $val;
		}

		//if the override flag wasn't given, mark the entry as newly added
		if($new) {
			$this->added[] = $key;
		}
	}

	/**
	 * adder function for multiple entries to the data array
	 * allows for flagging the entries as not new
	 *
	 * @access public
	 * @param  array $values The data entries to be saved
	 * @param  bool $new Flag marking these entries as not new (not to be saved into $this->added)
	 * @return void
	 */
	public function addAll(array $values, bool $new = true) {
		array_walk($values, array($this, "add"), $new);
	}

	/**
	 * change function to change an entry (add to $this->changed)
	 *
	 * @access public
	 * @param  mixed $entry Either the key or the value that changes
	 * @return reference to the value or null if it doesn't exist
	 */
	public function change($entry) {
		$key = $this->findKeyForKeyOrEntry($entry);
		if($key !== null) {
			$this->changed[] = $key;
			$ret = &$this->all[$key];
		} else {
			return null;
		}
	}

	/**
	 * change function to remove an entry (add to $this->removed)
	 *
	 * @access public
	 * @param  mixed $entry Either the key or the value that changes
	 * @return true or false on success or not
	 */
	public function remove($entry) {
		$key = $this->findKeyForKeyOrEntry($entry);
		if($key !== null) {
			$this->removed[] = $key;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * helper function to figure out what an argument is
	 * first assumes the argument is a key
	 * then assumes the argument is a value
	 * and return the key for the value or null if not found
	 *
	 * @access public
	 * @param  mixed $entry Either the key or the value
	 * @return string|int the key of the value/the key if it's a key
	 */
	private function findKeyForKeyOrEntry($entry) {
		//check if $entry is an array key (allow null, so no isset)
		if(is_scalar($entry) && array_key_exists($entry, $this->all)) {
			return $entry;
		} elseif(is_object($entry) && $entry instanceof IComparable) {
			foreach ($this->all as $key => $value) {
				if($value instanceof IComparable && $entry->compareTo($value)) {
					return $key;
				}
			}
		} elseif(($key = array_search($entry, $this->all)) !== false) {
			return $key;
		} else {
			return null;
		}
	}

	/**
	 * getter function to return a value by its key
	 * does not return "removed" items
	 *
	 * @access public
	 * @param  string|int $key The key for the value
	 * @return the value from the $this->all array or null if not found
	 */
	public function get($key) {
		if(isset($this->all[$key]) && !in_array($key, $this->removed)) {
			return $this->all[$key];
		} else {
			return null;
		}
	}

	/**
	 * setter function to set a value by its key
	 * either calls the add function or set the value
	 *
	 * @access public
	 * @param  string|int $key The key for the value
	 * @param  mixed $value The value that is to be set
	 * @return the value from the $this->all array or null if not found
	 */
	public function set($key, $value) {
		if($key === null || !array_key_exists($key, $this->all)) {
			$this->add($value, $key);
		} else {
			if($this->all[$key] !== $value) {
				$this->change($key);
			}

			if(is_object($value) && is_subclass_of($value, IChangeAware::class)) {
				$value->belongsTo($this);
			}

			$this->all[$key] = $value;
		}
	}

	/**
	 * function that can be used to replace all internal values with a new set
	 *
	 * @access public
	 * @param  array $values An array with new values
	 * @return void
	 */
	public function replace(array $values) {
		$this->apply();
		$this->removed = array_keys($this->all);
		$this->addAll($values);
	}

	/**
	 * getter function to return all the values that match a specification
	 * does not return "removed" items, except the "removed" key is given
	 *
	 * @access public
	 * @param  string $what Determinges what set of data should be returned
	 * @return array with all the values that match the specification
	 */
	public function getAll($what = "current") {
		if($what === "current") {
			return array_diff_key(
				$this->all, //all our values
				array_intersect_key($this->all, array_flip($this->removed)) //the removed values
			);
		} elseif($what === "new") {
			return array_intersect_key($this->all, array_flip($this->added));
		} elseif($what === "removed") {
			return array_intersect_key($this->all, array_flip($this->removed));
		} elseif($what === "changed") {
			return array_intersect_key($this->all, array_flip($this->changed));
		} elseif($what === "unchanged") {
			return array_diff_key(
				$this->all, //all our values
				array_intersect_key($this->all, array_merge(
					array_flip($this->removed), //the removed values
					array_flip($this->changed), //the changed values
					array_flip($this->added) //the new values
				))
			);
		} elseif($what === "all") {
			return $this->all;
		}
	}

	/**
	 * function used to "reset" the internal change arrays and removing all the removed entries
	 *
	 * @access public
	 * @return void
	 */
	public function apply() {
		$this->all = $this->getAll("current");
		$this->removed = array();
		$this->changed = array();
		$this->added = array();
	}

	/**
	 * function used to check if the collection has any changes recorded
	 *
	 * @access public
	 * @return boolean true on changed or not
	 */
	public function changed() {
		return !empty($this->added) || !empty($this->removed) || !empty($this->changed);
	}

	/**
	 * function used to set an entry via the array syntax
	 * marks the entry as either added or not
	 *
	 * @access public
	 * @param  mixed $offset The key to save the value under
	 * @param  mixed $value The value to be saved
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	/**
	 * function used to check if an entry exists via isset()
	 * does not return true for "removed" entries
	 *
	 * @access public
	 * @param  mixed $offset The key to check if it exists
	 * @return true or false on exists or not
	 */
	public function offsetExists($offset) {
		return isset($this->all[$offset]) && !in_array($offset, $this->removed);
	}

	/**
	 * function used to unset an entry via unset()
	 * does only add the entry to "removed"
	 *
	 * @access public
	 * @param  mixed $offset The key to unset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->remove($offset);
	}

	/**
	 * function used to get an entry via the array syntax
	 * does only return values that aren't "removed"
	 *
	 * @access public
	 * @param  mixed $offset The key to get the value for
	 * @return void
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * Count the attributes via a simple "count" call
	 *
	 * @access public
	 * @return number of items in the internal data array
	 */
	public function count() {
		return count($this->all);
	}

	/**
	 * Get the aggregate iterator
	 * IteratorAggregate interface required method
	 *
	 * @access public
	 * @return ArrayIterator to iterate over our internal data
	 */
	public function getIterator() {
		return new ArrayIterator($this->getAll());
	}

	/**
	 * compare this attribute set to another attribute set
	 *
	 * @access public
	 * @param  IComparable $other The other object to compare this one to
	 * @return boolean if this object should be considered the same attribute set as the other one
	 */
	public function compareTo(IComparable $other) {
		return get_called_class() == get_class($other) //make sure it's the same class
			&& $this->all == $other->all && $this->changed == $other->changed
			&& $this->added == $other->added && $this->removed == $other->removed;
	}

}
