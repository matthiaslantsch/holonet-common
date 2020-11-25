<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use BadMethodCallException;
use UnexpectedValueException;
use function array_key_exists;

/**
 * Wrapper class around the standard enum to make all value classes singleton instances
 * Goal was to get closer to the Java implementation of enums including multiple data values for each enum value.
 * @psalm-immutable
 */
abstract class Enum extends \MyCLabs\Enum\Enum {
	protected static $instances = array();

	/**
	 * {@inheritdoc}
	 */
	protected function __construct($value) {
		$this->value = $value;
	}

	/**
	 * {@inheritdoc}
	 * @return static
	 * @psalm-suppress MissingImmutableAnnotation
	 */
	public static function __callStatic($name, $arguments): self {
		return static::valueOf($name);
	}

	public static function fromValue($value): self {
		$name = static::search($value);
		if ($name === null) {
			/** @psalm-suppress InvalidCast */
			throw new UnexpectedValueException("Value '{$value}' is not part of the enum ".static::class);
		}

		return static::valueOf($name);
	}

	/**
	 * {@inheritdoc}
	 * @psalm-suppress MissingImmutableAnnotation
	 */
	public static function isValid($value): bool {
		return static::search($value) !== null;
	}

	/**
	 * {@inheritdoc}
	 * @psalm-suppress MissingImmutableAnnotation
	 */
	public static function search($value): ?string {
		$toArray = static::toArray();
		foreach ($toArray as $name => $constValue) {
			if (is_array($constValue)) {
				$constValue = array_shift($constValue);
			}
			if ((string)$constValue === (string)$value) {
				return $name;
			}
		}

		return null;
	}

	/**
	 * Try to get the enum implementation closer to a useful one
	 * By ensuring all enum objects are singletons, we can just use reference comparison in
	 * userland code.
	 */
	public static function valueOf(string $name): self {
		$array = static::toArray();
		$class = get_called_class();
		if (isset($array[$name]) || array_key_exists($name, $array)) {
			if (!isset(static::$instances[$class][$name])) {
				if (is_array($array[$name])) {
					$params = $array[$name];
				} else {
					$params = array($array[$name]);
				}

				static::$instances[$class][$name] = new static(...$params);
			}

			return static::$instances[$class][$name];
		}

		throw new BadMethodCallException("No static method or enum constant '{$name}' in class ".static::class);
	}

	/**
	 * {@inheritdoc}
	 * @psalm-suppress MissingImmutableAnnotation
	 */
	public static function values(): array {
		// just make sure all instances where created
		foreach (static::keys() as $key) {
			static::valueOf($key);
		}

		return static::$instances[get_called_class()];
	}
}
