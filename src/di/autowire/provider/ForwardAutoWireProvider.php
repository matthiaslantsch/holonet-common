<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\autowire\provider;

use ReflectionNamedType;
use ReflectionParameter;
use holonet\common\di\Container;

/**
 * Special provider to check the types of a given parameter and just forward it if the types are the same.
 * This is so a user can just provide an actual scalar value in a configuration array.
 */
class ForwardAutoWireProvider implements ParamAutoWireProvider {
	/**
	 * {@inheritDoc}
	 */
	public function provide(Container $container, ReflectionParameter $param, ReflectionNamedType $type, mixed $givenParam): mixed {
		$givenType = gettype($givenParam);
		$expectedType = $type->getName();
		$givenType = match ($givenType) {
			'integer' => 'int',
			'double' => 'float',
			'boolean' => 'bool',
			default => $givenType
		};

		if (in_array($givenType, explode('|', $expectedType)) || $givenParam instanceof $expectedType) {
			return $givenParam;
		}

		return null;
	}
}