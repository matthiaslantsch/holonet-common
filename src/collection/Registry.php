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
use function holonet\common\dot_key_get;
use function holonet\common\dot_key_set;

/**
 * Registry is a key value storage collection class that allows for multilevel keys and placeholders
 *  e.g. toplevel.lowerlevel => $data['toplevel']['lowerlevel']
 *  e.g. "%app.name%-test" => $data['app']['name'] . "-test".
 */
class Registry {
	/**
	 * @var array<string, mixed> $data Multilevel array with key=value pairs
	 */
	private array $data = array();

	public function __construct(public string $separator = '.') {
	}

	public function all(bool $replace = true): array {
		return $replace ? $this->replacePlaceholder($this->data) : $this->data;
	}

	public function clear(): void {
		$this->data = array();
	}

	public function get(string $key, mixed $default = null): mixed {
		$value = dot_key_get($this->data, $key, separator: $this->separator);

		return $this->replacePlaceholder($value) ?? $default;
	}

	public function has(string $key): bool {
		$parts = explode($this->separator, $key);
		$position = $this->data;
		foreach ($parts as $subLevel) {
			if (!isset($position[$subLevel])) {
				return false;
			}
			$position = $position[$subLevel];
		}

		return true;
	}

	public function set(string $key, $value): void {
		dot_key_set($this->data, $key, $value, $this->separator);
	}

	public function setAll(array $data): void {
		$this->data = array_replace_recursive($this->data, $data);
	}

	public function unset(string $key): void {
		dot_key_set($this->data, $key, null, $this->separator);
	}

	/**
	 * replaces placeholders in values of the registry
	 * recursively calls this function if we are talking an array.
	 * @param mixed $position The value to be searched for placeholders
	 * @return mixed the updated value
	 */
	protected function replacePlaceholder(mixed $position): mixed {
		if (is_string($position) && mb_strpos($position, '%') !== false) {
			$matches = array();
			preg_match_all('/%([^%]+)%/', $position, $matches, \PREG_SET_ORDER);
			foreach ($matches as $placeholderPair) {
				//if the placeholder is a value in the registry, replace it, otherwise leave it with the % signs
				if (($resolved = $this->resolvePlaceHolder($placeholderPair[1])) !== null) {
					$position = str_replace($placeholderPair[0], $resolved, $position);
				}
			}
		} elseif (is_array($position)) {
			foreach ($position as $key => $val) {
				$position[$key] = $this->replacePlaceholder($val);
			}
		}

		return $position;
	}

	/**
	 * Try to resolve a placeholder from the registry.
	 * Treat it as a key for a given value in here.
	 */
	protected function resolvePlaceHolder(string $placeholder): ?string {
		return $this->get($placeholder);
	}
}
