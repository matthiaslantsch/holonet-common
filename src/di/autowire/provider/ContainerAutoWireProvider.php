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
use holonet\common\di\DependencyInjectionException;

class ContainerAutoWireProvider implements ParamAutoWireProvider {
	/**
	 * {@inheritDoc}
	 */
	public function provide(Container $container, ReflectionParameter $param, ReflectionNamedType $type, mixed $givenParam): mixed {
		if (class_exists($type->getName())) {
			try {
				return $container->byType($type->getName(), $param->getName());
			} catch (DependencyInjectionException $e) {
				if (!$type->allowsNull() && !$param->isOptional()) {
					throw $e;
				}
			}
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function compile(ReflectionParameter $param, ReflectionNamedType $type, mixed $givenParam): string {
		return "\$this->byType({$type->getName()}::class, '{$param->getName()}')";
	}
}
