<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

use RuntimeException;
use holonet\common\verifier\Proof;
use function holonet\common\stringify;

/**
 * exception to be thrown to show errors with the users php setup.
 */
class BadEnvironmentException extends RuntimeException {
	public static function faultyConfig(string $key, string $errors): static {
		return new static("Faulty config with key {$key}: {$errors}");
	}

	public static function faultyConfigFromProof(string $key, Proof $proof): static {
		$errors = $proof->all();

		// reduce errors for only one subkey to a specific error about that key
		if (count($errors) === 1) {
			$subKey = array_key_first($errors);
			$errors = array_shift($errors);
			$key = "{$key}.{$subKey}";
		}

		// in case of only a single error for the given config key simply return only the error
		if (count($errors) === 1) {
			$errors = array_shift($errors);
		}

		return static::faultyConfig($key, stringify($errors, true));
	}
}
