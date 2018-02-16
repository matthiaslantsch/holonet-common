<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the Collection class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * The Collection is used as a wrapper around a data array
 * it allows for the data to be accessed like an array or object
 *
 * @author  matthias.lantsch
 * @package holonet\common\collection
 */
class Collection implements IteratorAggregate, ArrayAccess, Countable {

	/**
	 * holds all the data entries
	 *
	 * @access protected
	 * @var	array $data An array containing all data entries
	 */
	protected $data = array();

	/**
	 * constructor for the collection, allowing the user to give a set of
	 * initial entries
	 *
	 * @access public
	 * @param  array $initial Array with initial data entries
	 * @return void
	 */
	public function __construct(array $initial = array()) {
		$this->data = $initial;
	}

	/**
	 * getter function used to return entries from the internal data storage
	 * can be used with a mask in order to return an array of values
	 *
	 * @access public
	 * @param  array $which Array with keys that are requested
	 * @return an array with the values from the keys in $which or all values
	 */
	public function getAll(array $which = null) {
		if($which !== null) {
			return array_intersect_key($this->data, array_flip($which));
		} else {
			return $this->data;
		}
	}

	/**
	 * getter function used to return a single entry by it's key
	 * returns null if a value doesn't exist
	 *
	 * @access public
	 * @param  string $key The key for the value that is requested
	 * @param  mixed $default The default that should be returned if the key doesn't exist
	 * @return the value that was saved under the given key or the default/null if not found
	 */
	public function get($key, $default = null) {
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}

	/**
	 * Set a data entry
	 *
	 * @access public
	 * @param  string $key The key of the parameter to set
	 * @param  mixed $value The value of the parameter to set
	 * @return void
	 */
	public function set($key, $value) {
		if($key === null) {
			$this->data[] = $value;
		} else {
			$this->data[$key] = $value;
		}
	}

	/**
	 * Replace our data array
	 *
	 * @access public
	 * @param  array $data The data array to replace the collection's with
	 * @return void
	 */
	public function replace(array $data = array()) {
		$this->data = $data;
	}

	/**
	 * Merge data entries with the collection's data entries
	 *
	 * @access public
	 * @param array $data The data to be mergeg into the collection
	 * @return void
	 */
	public function merge(array $data = array()) {
		//don't merge an empty array
		if(!empty($data)) {
			$this->data = array_merge($this->data, $data);
		}
	}

	/**
	 * See if a key exists in the data array
	 *
	 * @access public
	 * @param  string $key The key that should be checked for
	 * @return boolean if key exists or not
	 */
	public function has($key) {
		// Don't use "isset", since it returns false for null values
		return array_key_exists($key, $this->data);
	}

	/**
	 * Remove an attribute from the collection
	 *
	 * @access public
	 * @param  string $key The key to unset
	 * @return void
	 */
	public function remove($key) {
		unset($this->data[$key]);
	}

	/**
	 * Clear the collection's contents
	 *
	 * @access public
	 * @return void
	 */
	public function clear() {
		$this->data = array();
	}

	/**
	 * Check if the collection is empty
	 *
	 * @access public
	 * @return boolean if our array is empty or not
	 */
	public function empty() {
		return empty($this->data);
	}

	/**
	 * Get a data by key
	 * return a reference so we can change e.g. sub arrays from this call
	 *  => cannot use our own get() method
	 *
	 * @access public
	 * @param  string $key The key data to retrieve
	 * @return mixed reference to the value for the key
	 */
	public function &__get($key) {
		if(is_object($this->data[$key])) {
			//only variable variables should be returned by reference
			return $this->data[$key];
		} else {
			return @$this->data[$key];
		}
	}

	/**
	 * Assigns a value to the specified data
	 * uses our internal set() method
	 *
	 * @access public
	 * @param  string $key The data key to assign the value to
	 * @param  mixed $value The value to set
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Whether or not a data entry exists by key
	 * don't use our internal has() method in order to return false for null values
	 *
	 * @access public
	 * @param  string $key A data key to check for
	 * @return boolean if isset or not
	 */
	public function __isset($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Unsets an data by key
	 * uses our internal remove() method
	 *
	 * @access public
	 * @param  string $key The key to unset
	 * @return void
	 */
	public function __unset($key) {
		$this->remove($key);
	}

	/**
	 * Get the aggregate iterator
	 * IteratorAggregate interface required method
	 *
	 * @access public
	 * @return ArrayIterator to iterate over our internal data
	 */
	public function getIterator() {
		return new ArrayIterator($this->data);
	}

	/**
	 * Assigns a value to the specified offset
	 *
	 * @access public
	 * @param  string $offset The key to assign the value to
	 * @param  mixed $value The value to set
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	/**
	 * Check wheter or not an offset exists
	 *
	 * @access public
	 * @param  string $offset A offset to check for
	 * @return boolean is isset or not
	 */
	public function offsetExists($offset) {
		//use the isset method in order to return false for null value (the behaviour of isset())
		return $this->__isset($offset);
	}

	/**
	 * Unsets an offset
	 * uses our internal remove() method
	 *
	 * @access public
	 * @param  string $offset The offset to unset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->remove($offset);
	}

	/**
	 * Returns the value at specified offset
	 *
	 * @access public
	 * @param  string $offset The offset to retrieve
	 * @return mixed the value that was saved at the offset or null if not set
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
		return count($this->data);
	}

}
