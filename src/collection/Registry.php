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
	private array $data = array();

	/**
	 * @var string $separator Multilevel separator string
	 */
	private string $separator;

	public function __construct(string $separator = '.') {
		$this->separator = $separator;
	}

	public function clear(): void {
		$this->data = array();
	}

	/**
	 * @see self::offsetGet()
	 * @param mixed|null $default
	 */
	public function get(string $key, $default = null) {
		return $this->offsetGet($key) ?? $default;
	}

	public function getAll(bool $replace = true): array {
		return $replace ? $this->replacePlaceholder($this->data) : $this->data;
	}

	/**
	 * @see self::offsetExists()
	 */
	public function has(string $key): bool {
		return $this->offsetExists($key);
	}

	/**
	 * {@inheritDoc}
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
	 * {@inheritDoc}
	 */
	public function offsetGet($offset) {
		$parts = explode($this->separator, $offset);
		$position = $this->data;

		foreach ($parts as $sublevel) {
			if (!isset($position[$sublevel])) {
				return;
			}
			$position = $position[$sublevel];
		}

		return $this->replacePlaceholder($position);
	}

	/**
	 * {@inheritDoc}
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
	 * {@inheritDoc}
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
	 * @see self::offsetSet()
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
	 * @see self::offsetUnset()
	 */
	public function unset(string $key): void {
		$this->offsetUnset($key);
	}

	/**
	 * replaces place holders in values of the registry
	 * recursively calls this function if we are talking an array.
	 * @param mixed $position The value to be searched for placeholders
	 * @return mixed the updated value
	 */
	protected function replacePlaceholder($position) {
		if (is_string($position) && mb_strpos($position, '%') !== false) {
			$matches = array();
			preg_match_all('/%([^%]+)%/', $position, $matches, \PREG_SET_ORDER);
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
