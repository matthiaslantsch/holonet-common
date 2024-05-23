<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier;

use function holonet\common\dot_key_array_merge;
use function holonet\common\dot_key_flatten;
use function holonet\common\dot_key_get;
use function holonet\common\dot_key_set;

/**
 * Dto for the results of a verification of a given object using its attribute rules.
 */
class Proof {
	/**
	 * Multilevel mapping from attribute name to error messages.
	 * If a given attribute does not show in this array it can be considered verified.
	 * This is an array with paths to sub errors.
	 */
	private array $errors = array();

	public function add(string $attr, string $error): void {
		$error = array($error);
		dot_key_array_merge($this->errors, $attr, $error);
	}

	/**
	 * @return array<string, string[]> The errors array
	 */
	public function all(): array {
		// return a flat array of all errors mapped to their properties
		// sub errors will be added to the parent AND their own key
		$all = dot_key_flatten($this->errors);
		foreach ($all as $key => $value) {
			$parentKey = strstr($key, '.', true);
			if ($parentKey !== false) {
				if (!isset($all[$parentKey])) {
					$all[$parentKey] = array();
				}
				$subKey = substr($key, strlen($parentKey) + 1);
				foreach ($value as $error) {
					$all[$parentKey][] = "[{$subKey}]: {$error}";
				}
			}
		}

		return $all;
	}

	public function attr(string $attr): array {
		return $this->all()[$attr] ?? array();
	}

	public function flat(): array {
		$all = array();
		foreach ($this->all() as $attr => $errors) {
			$all[$attr] = implode(', ', $errors);
		}
		return $all;
	}

	/**
	 * Determine whether all attributes passed.
	 */
	public function pass(): bool {
		return empty($this->errors);
	}

	/**
	 * Determine whether a single attribute passed.
	 */
	public function passed(string $attr): bool {
		return empty(dot_key_get($this->errors, $attr, array()));
	}
}
