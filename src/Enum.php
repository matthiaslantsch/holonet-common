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

/**
 * Wrapper class around the standard enum to make all value classes singleton
 * Goal was to get closer to the Java implementation of enums including multiple data values for each enum value.
 * @psalm-immutable
 */
abstract class Enum extends \MyCLabs\Enum\Enum {
	protected static array $instances = array();

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
		$name = array_search($value, static::toArray(), true);
		if ($name === false) {
			/** @psalm-suppress InvalidCast */
			throw new \UnexpectedValueException("Value '{$value}' is not part of the enum ".static::class);
		}

		return static::valueOf($name);
	}

	/**
	 * Try to get the enum implementation closer to a useful one
	 * By ensuring all enum objects are singletons, we can just use reference comparison in
	 * userland code.
	 */
	public static function valueOf(string $name): self {
		$array = static::toArray();
		if (isset($array[$name]) || \array_key_exists($name, $array)) {
			if (!isset(static::$instances[$name])) {
				if (is_array($array[$name])) {
					$params = $array[$name];
				} else {
					$params = array($array[$name]);
				}

				static::$instances[$name] = new static(...$params);
			}

			return static::$instances[$name];
		}

		throw new BadMethodCallException("No static method or enum constant '{$name}' in class ".static::class);
	}
}
