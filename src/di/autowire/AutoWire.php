<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\autowire;

use holonet\common\di\autowire\provider\ConfigAutoWireProvider;
use holonet\common\di\autowire\provider\ContainerAutoWireProvider;
use holonet\common\di\autowire\provider\ForwardAutoWireProvider;
use holonet\common\di\autowire\provider\ParamAutoWireProvider;
use holonet\common\di\Container;
use holonet\common\di\error\DependencyInjectionException;
use holonet\common\di\error\AutoWireException;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * Small helper class which uses reflection to try and auto-wire parameters for a function / method.
 * Uses a given set of provider classes which can try to find a parameter based on it's name, user supplied value and type.
 */
class AutoWire {
	/**
	 * @var ParamAutoWireProvider[]
	 */
	protected array $paramProviders;

	public function __construct(protected Container $container) {
		$this->paramProviders = array(
			new ForwardAutoWireProvider(),
			new ConfigAutoWireProvider(),
			new ContainerAutoWireProvider(),
		);
	}

	public function autoWire(ReflectionFunctionAbstract $method, array $givenParams = array()): array {
		$parameters = $method->getParameters();
		$mapped = array();
		foreach ($parameters as $param) {
			$autoWiredValue = $this->autoWireParameter($param, $givenParams[$param->getName()] ?? null);
			// if we are here and the auto-wired value is null, it must be because the parameter
			// has a default or null works as a value for the parameter.
			if ($autoWiredValue !== null || !$param->isDefaultValueAvailable()) {
				$mapped[$param->getName()] = $autoWiredValue;
			}
		}

		return $mapped;
	}

	private function autoWireNamedType(ReflectionParameter $param, ReflectionNamedType $type, mixed $paramValue): mixed {
		foreach ($this->paramProviders as $provider) {
			$wiredValue = $provider->provide($this->container, $param, $type, $paramValue);

			if ($wiredValue !== null) {
				return $wiredValue;
			}
		}

		if ($param->allowsNull() || $param->isOptional()) {
			return null;
		}

		AutoWireException::failParam($param, "Cannot auto-wire to type '{$type->getName()}'");
	}

	private function autoWireParameter(ReflectionParameter $param, mixed $paramValue): mixed {
		$paramType = $param->getType();

		if ($paramType instanceof ReflectionIntersectionType) {
			AutoWireException::failParam($param, 'Cannot auto-wire intersection types');
		}

		if ($paramType === null) {
			if ($param->isOptional()) {
				return null;
			}

			AutoWireException::failParam($param, 'Can only auto-wire typed parameters');
		}

		if ($paramType instanceof ReflectionUnionType) {
			return $this->autoWireUnionType($param, $paramType, $paramValue);
		}

		return $this->autoWireNamedType($param, $paramType, $paramValue);
	}

	private function autoWireUnionType(ReflectionParameter $param, ReflectionUnionType $type, mixed $paramValue): mixed {
		$types = $type->getTypes();
		$errors = array();

		foreach ($this->paramProviders as $provider) {
			foreach ($types as $type) {
				try {
					$wiredValue = $provider->provide($this->container, $param, $type, $paramValue);

					if ($wiredValue !== null) {
						return $wiredValue;
					}
				} catch (DependencyInjectionException $e) {
					$errors[$type->getName()] = $e->getMessage();
				}
			}
		}

		$unionType = implode('|', array_keys($errors));
		$errors = implode("\n", $errors);
		AutoWireException::failParam($param, "Cannot auto-wire to union type '{$unionType}':\n{$errors}");
	}
}
