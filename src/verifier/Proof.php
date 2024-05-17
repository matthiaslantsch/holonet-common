<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier;

/**
 * Dto for the results of a verification of a given object using its attribute rules.
 */
class Proof {
	/**
	 * Multilevel mapping from attribute name to error messages.
	 * If a given attribute does not show in this array it can be considered verified.
	 * @var array<string, string[]> $errors
	 */
	private array $errors = array();

	public function add(string $attr, string $error): void {
		$this->errors[$attr][] = $error;
	}

	public function all(): array {
		return $this->errors;
	}

	public function attr(string $attr): array {
		$filtered = array();
		foreach ($this->errors as $key => $errors) {
			$baseKey = strstr($key, '.', true) ?: $key;
			if ($baseKey === $attr || $key === $attr) {
				$filtered = array(...$errors, ...$filtered);
			}
		}

		return $filtered;
	}

	public function flat(): array {
		$all = array();
		array_walk_recursive($this->errors, function (string $error) use (&$all): void { $all[] = $error; });

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
		if (isset($this->errors[$attr])) {
			return false;
		}

		foreach ($this->errors as $key => $value) {
			if (strstr($key, '.', true) === $attr) {
				return false;
			}
		}

		return !isset($this->errors[$attr]);
	}
}
