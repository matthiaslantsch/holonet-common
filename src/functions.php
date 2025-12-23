<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use holonet\common\error\BadEnvironmentException;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use ReflectionProperty;
use ReflectionParameter;
use InvalidArgumentException;
use holonet\common\verifier\Proof;
use holonet\common\verifier\Verifier;
use holonet\common\code\FileUseStatementParser;
use function Symfony\Component\String\b;
use function Webmozart\Assert\Tests\StaticAnalysis\string;

if (!function_exists(__NAMESPACE__.'\\is_abstract')) {
	/**
	 * Horribly inefficient method using reflection to check whether a class is abstract and
	 * then throwing it away again. To be used during container compilation.
	 */
	function is_abstract(string $class): bool {
		$reflection = new ReflectionClass($class);

		return $reflection->isAbstract();
	}
}


if (!function_exists(__NAMESPACE__.'\\read_php_config_file')) {
	/**
	 * @param string|null $expectedVariable The config file can be expected to set a variable instead of returning its content.
	 * If this parameter is given, the reader will expect the required file to set a variable with the same name and
	 * then return it.
	 */
	function read_php_config_file(string $file, ?string $expectedVariable = null): mixed {
		if (!is_readable($file) || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
			throw new BadEnvironmentException("Config file '{$file}' is not a valid php config file");
		}

		$return = require $file;

		if (!empty($expectedVariable) && isset(${$expectedVariable})) {
			return ${$expectedVariable};
		}

		if (isset($return)) {
			return $return;
		}

		if ($expectedVariable === null) {
			throw new BadEnvironmentException("Error reading php config '{$file}': File was expected to return a value");
		}
		throw new BadEnvironmentException("Error reading php config '{$file}': File was expected to either return a value or set a variable named '\${$expectedVariable}'");
	}
}

if (!function_exists(__NAMESPACE__.'\\array_head')) {
	function array_head(array $arr): mixed {
		return reset($arr) ?: null;
	}
}

if (!function_exists(__NAMESPACE__.'\\array_head_keys')) {
	function array_head_keys(array $arr): ?array {
		$head = array_head($arr);

		if ($head === null) {
			return null;
		}

		if (!is_array($head)) {
			throw new InvalidArgumentException('Can only use array_head_keys on array of arrays');
		}

		return array_keys($head);
	}
}


if (!function_exists(__NAMESPACE__.'\\dot_key_flatten')) {
	function dot_key_flatten(object|array $position, string $separator = '.', string $keyPrefix = ''): array {
		$flat = array();

		if (is_object($position)) {
			$position = (array)$position;
		}

		foreach ($position as $key => $value) {
			$key = "{$keyPrefix}{$key}";
			if (is_object($value) || (is_array($value) && !array_is_list($value))) {
				$flat = array_merge($flat, dot_key_flatten($value, $separator, "{$key}{$separator}"));
			} else {
				$flat[$key] = $value;
			}
		}

		return $flat;
	}
}


if (!function_exists(__NAMESPACE__.'\\dot_key_array_merge')) {
	function dot_key_array_merge(object|array &$position, string $key, array $value = array(), string $separator = '.'): void {
		$current = dot_key_get($position, $key, null, $separator);
		if ($current === null) {
			dot_key_set($position, $key, $value, $separator);

			return;
		}

		if (!is_array($current)) {
			throw new InvalidArgumentException("The key {$key} is not an array cannot merge with another array");
		}

		if (array_is_list($current) && array_is_list($value)) {
			dot_key_set($position, $key, array_merge($current, $value), $separator);
			return;
		}

		foreach ($value as $subKey => $subValue) {
			if (is_array($subValue) && isset($current[$subKey]) && is_array($current[$subKey])) {
				dot_key_array_merge($position, "{$key}{$separator}{$subKey}", $subValue, $separator);
			} else {
				dot_key_set($position, "{$key}{$separator}{$subKey}", $subValue, $separator);
			}
		}
	}
}

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
		if (is_object($position)) {
			$position = (array)$position;
		}

		$parts = explode($separator, $key);

		foreach ($parts as $subLevel) {
			if (!isset($position[$subLevel])) {
				return $default;
			}
			$position = $position[$subLevel];
			if (is_object($position)) {
				$position = (array)$position;
			}
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

if (!function_exists(__NAMESPACE__.'\\reflection_get_attributes')) {
	/**
	 * @template T
	 * Get all attributes from a reflection object.
	 * @psalm-param class-string<T> $class
	 * @return array<T>
	 * @psalm-suppress InvalidReturnType
	 * @psalm-suppress InvalidReturnStatement
	 */
	function reflection_get_attributes(ReflectionClass|ReflectionProperty|ReflectionParameter|ReflectionMethod $reflection, string $class): array {
		$attrs = $reflection->getAttributes();

		$attrs = array_filter($attrs, fn($attr) => is_a($attr->getName(), $class, true));

		// instantiate the attributes
		return array_map(fn($attr) => $attr->newInstance(), $attrs);
	}
}


if (!function_exists(__NAMESPACE__.'\\reflection_get_attribute')) {
	/**
	 * @template T
	 * Get a single attribute from a reflection object.
	 * @psalm-param class-string<T> $class
	 * @return ?T
	 */
	function reflection_get_attribute(ReflectionClass|ReflectionProperty|ReflectionParameter|ReflectionMethod $reflection, string $class): ?object {
		$attrs = reflection_get_attributes($reflection, $class);

		if (count($attrs) > 1) {
			throw new RuntimeException(sprintf('Multiple attributes of type %s found on %s', $class, $reflection->getName()));
		}

		return array_head($attrs);
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

if (!function_exists(__NAMESPACE__.'\\readableDurationString')) {
	/**
	 * function used to transform a duration into a human readable string.
	 * @param int $time The duration in seconds
	 * @return string with the duration in a human readable format
	 * @psalm-suppress InvalidOperand
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

			return (int)($time / 60).'min '. $time % 60 .'s';
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
		if (empty($path)) {
			return $path;
		}

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

if (!function_exists(__NAMESPACE__.'\\read_file_contents')) {
	function read_file_contents(string $path): string {
		return FilesystemUtils::readFileContents($path);
	}
}

if (!function_exists(__NAMESPACE__.'\\read_file_lines')) {
	function read_file_lines(string $path): array {
		return FilesystemUtils::readFileLines($path);
	}
}

if (!function_exists(__NAMESPACE__.'\\file_contains')) {
	function file_contains(string $path, string $search): bool {
		return FilesystemUtils::fileContains($path, $search);
	}
}

if (!function_exists(__NAMESPACE__.'\\dir_path')) {
	function dir_path(string ...$parts): string {
		return FilesystemUtils::dirpath(...$parts);
	}
}

if (!function_exists(__NAMESPACE__.'\\str_lreplace')) {
	/**
	 * Replace the last occurrence of a string inside the subject.
	 * Courtesy of https://stackoverflow.com/a/3835653.
	 */
	function str_lreplace(string $search, string $replace, string $subject): string {
		$pos = mb_strrpos($subject, $search);

		if ($pos !== false) {
			$subject = substr_replace($subject, $replace, $pos, mb_strlen($search));
		}

		return $subject;
	}
}

if (!function_exists(__NAMESPACE__.'\\get_class_short')) {
	function get_class_short(string $class): string {
		return basename(str_replace('\\', '/', $class));
	}
}

if (!function_exists(__NAMESPACE__.'\\file_get_use_statements')) {
	/**
	 * Read in and parse all use statements for a given php code file.
	 */
	function file_get_use_statements(string $file): array {
		return (new FileUseStatementParser($file))->parse();
	}
}
