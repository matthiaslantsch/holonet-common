<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the Registry class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

use ArrayAccess;
use RuntimeException;

/**
 * Registry is a key value storage collection class that allows for multilevel keys and placeholders
 *  e.g. toplevel.lowerlevel => $data['toplevel']['lowerlevel']
 *  e.g. "%app.name%-test" => $data['app']['name'] . "-test".
 */
class Registry implements ArrayAccess {
	/**
	 * @var array $data Multilevel array with key=value pairs
	 */
	private $data = array();

	/**
	 * @var string $separator Multilevel separator string
	 */
	private $separator;

	/**
	 * @param string $separator Allow the user to override the default level separator
	 */
	public function __construct(string $separator = '.') {
		$this->separator = $separator;
	}

	/**
	 * Clear all values from the registry.
	 */
	public function clear(): void {
		$this->data = array();
	}

	/**
	 * Return the value for a certain key or a default.
	 * @param string $key The to get the value for
	 * @param mixed|null $default Default to return of the key cannot be found
	 * @return mixed value
	 */
	public function get(string $key, $default = null) {
		return $this->offsetGet($key) ?? $default;
	}

	/**
	 * @param bool $replace if given and false, no place holders will be replaced
	 * @return array with the data
	 */
	public function getAll(bool $replace = true): array {
		return $replace ? $this->replacePlaceholder($this->data) : $this->data;
	}

	/**
	 * Check if the registry has a certain key.
	 * @param string $key The string key offset to check for
	 */
	public function has(string $key): bool {
		return $this->offsetExists($key);
	}

	/**
	 * Whether an offset exists.
	 * @see http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param string $offset
	 * @return bool true on success or false on failure
	 */
	public function offsetExists($offset): bool {
		$parts = explode($this->separator, $offset);
		$position = $this->data;
		foreach ($parts as $sublevel) {
			if (!isset($position[$sublevel])) {
				return false;
			}
			$position = $position[$sublevel];
		}

		return true;
	}

	/**
	 * Offset to retrieve.
	 * @see http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param string $offset
	 * @return mixed|null can return all value types or null if not found
	 */
	public function offsetGet($offset) {
		$parts = explode($this->separator, $offset);
		$position = $this->data;

		foreach ($parts as $sublevel) {
			if (!isset($position[$sublevel])) {
				return null;
			}
			$position = $position[$sublevel];
		}

		return $this->replacePlaceholder($position);
	}

	/**
	 * Offset to set.
	 * @see http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param string $offset
	 */
	public function offsetSet($offset, $value): void {
		$parts = explode($this->separator, $offset);
		$position = &$this->data;
		foreach ($parts as $key => $sublevel) {
			if ($key === array_key_last($parts)) {
				$position[$sublevel] = $value;
			} else {
				if (!isset($position[$sublevel])) {
					$position[$sublevel] = array();
				}
				$position = &$position[$sublevel];
			}
		}
	}

	/**
	 * Offset to unset.
	 * @see http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param string $offset
	 */
	public function offsetUnset($offset): void {
		$parts = explode($this->separator, $offset);
		$position = &$this->data;
		foreach ($parts as $key => $sublevel) {
			if (!isset($position[$sublevel])) {
				return;
			}

			if ($key === array_key_last($parts)) {
				unset($position[$sublevel]);
			} else {
				$position = &$position[$sublevel];
			}
		}
	}

	/**
	 * Set the value for a certain key.
	 * @param string $key The key to set the value for
	 * @param mixed $value The value to be set
	 */
	public function set(string $key, $value): void {
		$this->offsetSet($key, $value);
	}

	public function setAll(array $data): void {
		$replace = array_replace_recursive($this->data, $data);
		if ($replace === null) {
			throw new RuntimeException('Failed to array_replace_recursive() data array in Registry');
		}
		$this->data = $replace;
	}

	/**
	 * Clear the value for a certain key.
	 * @param string $key The string key to unset
	 */
	public function unset(string $key): void {
		$this->offsetUnset($key);
	}

	/**
	 * sreplaces place holders in values of the registry
	 * recursively calls this function if we are talking an array.
	 * @param mixed $position The value to be searched for placeholders
	 * @return mixed the updated value
	 */
	private function replacePlaceholder($position) {
		if (is_string($position) && mb_strpos($position, '%') !== false) {
			$matches = array();
			preg_match_all('/%([^%]+)%/', $position, $matches, PREG_SET_ORDER);
			foreach ($matches as $placeholderPair) {
				//if the placeholder is a value in the registry, replace it, otherwise leave it with the % signs
				$position = str_replace($placeholderPair[0], $this->get($placeholderPair[1], $placeholderPair[0]), $position);
			}
		} elseif (is_array($position)) {
			foreach ($position as $key => $val) {
				$position[$key] = $this->replacePlaceholder($val);
			}
		}

		return $position;
	}
}
