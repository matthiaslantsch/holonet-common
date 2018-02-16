<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the Singleton Registry class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

/**
 * The Registry is used to save data in a globally reachable place
 *
 * SINGLETON
 *
 * @author  matthias.lantsch
 * @package holonet\common
 */
final class Registry {

	/**
	 * static field holding the only instance of this class
	 *
	 * @access protected
	 * @var    object $instance Class instance of the Registry
	 */
	protected static $instance = null;

	/**
	 * blocked constructor --> Singleton class
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct(){}

	/**
	 * blocked clone method --> Singleton class
	 *
	 * @access protected
	 * @return void
	 */
	protected function __clone(){}

	/**
	 * Instanciator method.
	 * method for creating and getting the only instance of this singleton class
	 *
	 * @access public
	 * @return object of the child class
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * contains all data of the registry
	 *
	 * @access private
	 * @var    array $data Multilevel array with key=value pairs
	 */
	private $data = [];

	/**
	 * saves a value in the registry
	 * iterates over the internal data array in order to find the multilevel place to save the value
	 * the key key.sublevel would mean $data["key"]["sublevel"] in the registry array
	 *
	 * @access public
	 * @param  string $key Key for which to save the value (can be multilevel with ".")
	 * @param  mixed $value The value to be saved for the respective key
	 * @return void
	 */
	public function setVal(string $key, $value) {
		$position = &$this->data;
		$parts = explode(".", $key);
		foreach ($parts as $sublevel) {
			//check if the current cursor position has the next sub index
			if(isset($position[$sublevel])) {
				$position = &$position[$sublevel];
			} else {
				$position[$sublevel] = array();
				$position = &$position[$sublevel];
			}
		}
		if(is_array($position) && is_array($value)) {
			$position = array_merge($value, $position);
		} else {
			$position = $value;
		}
	}

	/**
	 * return a value from the internal array using a key
	 * iterates over the internal data array in order to find the multilevel place to return
	 * return false if the key cannot be found
	 *
	 * @access public
	 * @param  string $key Key for which to save the value (can be multilevel with ".")
	 * @param  mixed $default The default to return if the key doesn't exist
	 * @return mixed value for the given key or false if it couldn't be found
	 */
	public function getVal(string $key, $default = false) {
		$position = $this->data;
		$parts = explode(".", $key);
		foreach ($parts as $sublevel) {
			//check if the current cursor position has the next sub index
			if(isset($position[$sublevel])) {
				$position = $position[$sublevel];
			} else {
				return $default;
			}
		}

		//resolve placeholders in the variable
		if(is_string($position) && strpos($position, "%") !== false) {
			$matches = array();
			preg_match_all("/%([^%]+)%/", $position, $matches, PREG_SET_ORDER);
			foreach ($matches as $placeholderPair) {
				//if the placeholder is a value in the registry, replace it, otherwise leave it with the % signs
				$position = str_replace($placeholderPair[0], $this->getVal($placeholderPair[1], $placeholderPair[0]), $position);
			}
		}

		return $position;
	}

	/**
	 * saves a value in the registry
	 *
	 * @access public
	 * @param  string $key Key for which to save the value (can be multilevel with ".")
	 * @param  mixed $value The value to be saved for the respective key
	 * @return void
	 */
	public static function set(string $key, $value) {
		$reg = self::getInstance();
		$reg->setVal($key, $value);
	}

	/**
	 * return a value from the internal array using a key
	 *
	 * @access public
	 * @param  string $key Key for which to get the value (can be multilevel with ".")
	 * @param  mixed $default The default to return if the key doesn't exist
	 * @return mixed the data for the given key or null if no data can be found
	 */
	public static function get(string $key, $default = false) {
		$reg = self::getInstance();
		return $reg->getVal($key, $default);
	}

	/**
	 * returns the entire registry content
	 *
	 * @access public
	 * @return array with all the registry data
	 */
	public static function getAll() {
		$reg = self::getInstance();
		return $reg->data;
	}

	/**
	 * sets the entire registry data
	 *
	 * @access public
	 * @param  array $data New data to be saved instead of the current
	 * @return void
	 */
	public static function setAll($data) {
		$reg = self::getInstance();
		$reg->data = $data;
	}

	/**
	 * clears the registry by unsetting the singleton reference
	 *
	 * @access public
	 * @return void
	 */
	public static function clear() {
		self::$instance = null;
	}

}
