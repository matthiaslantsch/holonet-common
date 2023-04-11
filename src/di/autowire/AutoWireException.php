<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\autowire;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionFunctionAbstract;
use holonet\common\di\DependencyInjectionException;

class AutoWireException extends DependencyInjectionException {
	private function __construct(ReflectionFunctionAbstract|ReflectionClass $reflection, string $message) {
		$identifier = $reflection->getName();
		if ($reflection instanceof ReflectionMethod) {
			$identifier = sprintf('%s::%s', $reflection->getDeclaringClass()->getName(), $reflection->getName());
		}
		parent::__construct("Failed to auto-wire '{$identifier}': {$message}");
	}

	public static function failNoConstructor(ReflectionClass $reflection, array $params): void {
		throw new static($reflection, sprintf('Has no constructor, but %d parameters were given', count($params)));
	}

	public static function failParam(ReflectionParameter $param, string $message): never {
		throw new static($param->getDeclaringFunction(), "Parameter #{$param->getPosition()}: {$param->getName()}: {$message}");
	}
}
