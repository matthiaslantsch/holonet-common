<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use ReflectionClass;
use ReflectionProperty;
use holonet\common\verifier\Proof;
use holonet\common\verifier\Verifier;
use function Webmozart\Assert\Tests\StaticAnalysis\string;

if (!function_exists(__NAMESPACE__.'\\dot_key_set')) {
	function dot_key_set(object|array &$position, string $key, mixed $value = null, string $separator = '.'): void {
		$parts = explode($separator, $key);

		$targetKey = array_pop($parts);

		foreach ($parts as $subLevel) {
			if (!isset($position[$subLevel])) {
				$position[$subLevel] = array();
			}
			$position = &$position[$subLevel];
		}

		if (!is_array($position)) {
			$position = array();
		}

		if ($value === null) {
			unset($position[$targetKey]);

			return;
		}
		$position[$targetKey] = $value;
	}
}

if (!function_exists(__NAMESPACE__.'\\dot_key_get')) {
	function dot_key_get(object|array $position, string $key, mixed $default = null, string $separator = '.'): mixed {
		$parts = explode($separator, $key);

		foreach ($parts as $subLevel) {
			if (!isset($position[$subLevel])) {
				return $default;
			}
			$position = $position[$subLevel];
		}

		return $position;
	}
}

if (!function_exists(__NAMESPACE__.'\\set_object_vars')) {
	function set_object_vars(object $object, array $vars): void {
		foreach ($vars as $name => $value) {
			$object->{$name} = $value;
		}
	}
}

if (!function_exists(__NAMESPACE__.'\\verify')) {
	/**
	 * Run a verifier on a given php object (most likely a dto) and return a proof of its verified state.
	 * Can be used with the second parameter to save a different version of the Verifier.
	 */
	function verify(object $obj, ?Verifier $verifier = null): Proof {
		static $_verifier;

		if ($verifier !== null) {
			$_verifier = $verifier;
		}
		$_verifier ??= new Verifier();

		return $_verifier->verify($obj);
	}
}

if (!function_exists(__NAMESPACE__.'\\reflection_get_attribute')) {
	/**
	 * @template T
	 * Get a single attribute from a reflection object.
	 * @param class-string<T> $class
	 * @return ?T
	 */
	function reflection_get_attribute(ReflectionClass|ReflectionProperty $reflection, string $class): ?object {
		$attrs = $reflection->getAttributes($class);

		return reset($attrs) ? reset($attrs)->newInstance() : null;
	}
}

if (!function_exists(__NAMESPACE__.'\\stringify')) {
	/**
	 * Return a best guess string representation of the given value.
	 */
	function stringify(mixed $value, bool $prettyPrint = false): string {
		if (is_array($value)) {
			if (empty($value)) {
				return '[]';
			}

			foreach ($value as &$sub) {
				if (is_string($sub)) {
					$sub = sprintf("'%s'", stringify($sub, $prettyPrint));
				} else {
					$sub = stringify($sub, $prettyPrint);
				}
			}

			if ($prettyPrint) {
				return sprintf("[\n\t%s\n]", implode(",\n\t", $value));
			}

			return sprintf('[%s]', implode(', ', $value));
		}

		return (string)$value;
	}
}

if (!function_exists(__NAMESPACE__.'\\trigger_error_context')) {
	/**
	 * function using the php debug backtrace to trigger an error on the calling line.
	 * @param string $message The message to throw in the error
	 * @param int $level Error level integer, defaults to E_USER_ERROR
	 */
	function trigger_error_context(string $message, int $level = \E_USER_ERROR): void {
		$caller = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
		trigger_error("{$message} in file {$caller['file']} on line {$caller['line']}", $level);
	}
}

if (!function_exists(__NAMESPACE__.'\\indentText')) {
	/**
	 * function used to indent a text with newlines in it
	 * used to indent multiline text evenly.
	 * @param string $text The text to indent
	 * @param int $number The number of tabs to indent
	 * @return string the string with the applied indent
	 */
	function indentText(string $text, int $number = 1): string {
		return str_replace("\n", "\n".str_repeat("\t", $number), $text);
	}
}

if (!function_exists(__NAMESPACE__.'\\isAssoc')) {
	/**
	 * function used to check if an array is associative.
	 * @param array $arr The array to check
	 * @return bool true or false on is associative or not
	 */
	function isAssoc(array $arr): bool {
		if ($arr === array()) {
			return false;
		}
		ksort($arr);

		/** @psalm-suppress DocblockTypeContradiction */
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}

if (!function_exists(__NAMESPACE__.'\\readableDurationString')) {
	/**
	 * function used to transform a duration into a human readable string.
	 * @param int $time The duration in seconds
	 * @return string with the duration in a human readable format
	 */
	function readableDurationString(int $time): string {
		if ($time >= 86400) {
			if ($time % 86400 === 0) {
				return $time / 86400 .'days';
			}

			return (int)($time / 86400).'days '.(int)($time % 86400 / 3600).'h';
		}
		if ($time >= 3600) {
			if ($time % 3600 === 0) {
				return $time / 3600 .'h';
			}

			return (int)($time / 3600).'h '.(int)($time % 3600 / 60).'min';
		}
		if ($time >= 60) {
			if ($time % 60 === 0) {
				return $time / 60 .'min';
			}

			return (int)($time / 60).'min '.(int)($time % 60).'s';
		}

		return $time.'s';
	}
}

if (!function_exists(__NAMESPACE__.'\\get_absolute_path')) {
	/**
	 * Get the absolute path for a given path by resolving any relative .. references
	 * and normalising separator. Courtesy of:.
	 * @see https://www.php.net/manual/en/function.realpath.php#84012
	 */
	function get_absolute_path(string $path): string {
		if ($path[0] === '.') {
			$cwd = getcwd();
			if ($cwd !== false) {
				$path = "{$cwd}/{$path}";
			}
		}

		$path = str_replace(array('/', '\\'), \DIRECTORY_SEPARATOR, $path);
		$parts = explode(\DIRECTORY_SEPARATOR, $path);
		$absolutes = array();
		foreach ($parts as $part) {
			if ($part === '.') {
				continue;
			}
			if ($part === '..') {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}

		return implode(\DIRECTORY_SEPARATOR, $absolutes);
	}
}
