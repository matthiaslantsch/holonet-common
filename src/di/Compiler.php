<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use holonet\common\di\autowire\provider\ConfigAutoWireProvider;
use holonet\common\di\autowire\provider\ContainerAutoWireProvider;
use holonet\common\di\autowire\provider\ForwardAutoWireProvider;
use holonet\common\di\autowire\provider\ParamAutoWireProvider;
use holonet\common\di\Container;
use ReflectionClass;
use Psr\Container\ContainerInterface;
use holonet\common\di\autowire\AutoWire;
use holonet\common\config\ConfigRegistry;
use holonet\common\di\autowire\AutoWireException;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * Compile a static php anonymous class which can create all the current definitions in the container without reflection.
 */
class Compiler {

	/**
	 * @var array<string, string> $aliases key mapping with all available services on the container
	 */
	protected array $aliases = array();

	/**
	 * @var ParamAutoWireProvider[]
	 */
	protected array $paramProviders;

	/**
	 * @var array<string, array{string, array}> $wiring Wiring information on how to make certain types of objects.
	 * Mapped by name / type => class abstract (array with class name and parameters).
	 */
	protected array $wiring = array();

	public function __construct(protected Container $container) {
		$reflection = new ReflectionObject($this->container);

		$this->aliases = $reflection->getProperty('aliases')->getValue($container);
		$this->wiring = $reflection->getProperty('wiring')->getValue($container);

		$this->paramProviders = array(
			new ForwardAutoWireProvider(),
			new ConfigAutoWireProvider(),
			new ContainerAutoWireProvider(),
		);
	}

	public function compile(): string {
		$methods[] = $this->compileMakeMethod();

		foreach ($this->wiring as $alias => $constructor) {
			$methods[] = $this->compileWiringMakeMethod($alias);
		}

		$methods = implode("\n\t", explode("\n", implode("\n\n", $methods)));

		return <<<PHP
		return new class(\holonet\common\config\ConfigRegistry \$config) extends \holonet\common\di\Container {
			{$methods}
		}
		PHP;
	}

	/**
	 * Compile a static version of the make() method of a container
	 */
	private function compileMakeMethod(): string {
		$matchStatements = array();

		foreach ($this->wiring as $abstract => $constructor) {
			$matchStatements[] = "\t\t{$abstract} => \$this->{$this->serviceMakeMethodName($abstract)}()";
		}

		$matchStatements[] = "\t\tdefault => parent::make(\$class, \$params, \$abstract)";
		$matchStatements = implode(",\n", $matchStatements);
		return <<<PHP
		public function make(string \$class, array \$params = array(), ?string \$abstract = null): object {
			return match (\$abstract) {
		$matchStatements
			};
		}
		PHP;
	}

	private function serviceMakeMethodName(string $alias): string {
		return sprintf('make_%s', str_replace('\\', '_', $alias));
	}

	private function compileWiringMakeMethod(string $alias): string {
		list($class, $params) = $this->wiring[$alias];

		return <<<PHP
		public function {$this->serviceMakeMethodName($alias)}(): {$class} {
			return {$this->compileNewStatement($class, $params)};
		}
		PHP;
	}

	private function compileNewStatement(string $class, array $params): string {
		$reflection = new ReflectionClass($class);
		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			if (!empty($params)) {
				AutoWireException::failNoConstructor($reflection, $params);
			}

			return "new $class()";
		}

		$params = $this->compileAutoWiring($constructor, $params);

		return sprintf('new %s(%s)', $class, implode($params));
	}

	protected function compileAutoWiring(ReflectionFunctionAbstract $method, array $givenParams): array {
		$parameters = $method->getParameters();
		$compiled = array();
		foreach ($parameters as $param) {
			$compiledValue = $this->compileParameter($param, $givenParams[$param->getName()] ?? null);
			if ($compiledValue !== null) {
				$compiled[] = "{$param->getName()}: {$compiledValue}";
			}
		}

		return $compiled;
	}

	private function compileParameter(ReflectionParameter $param, mixed $paramValue): ?string {
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
			return $this->compileUnionType($param, $paramType, $paramValue);
		}

		return $this->compileNamedType($param, $paramType, $paramValue);
	}

	private function compileNamedType(ReflectionParameter $param, ReflectionNamedType $type, mixed $paramValue): ?string {
		foreach ($this->paramProviders as $provider) {
			$wiredValue = $provider->provide($this->container, $param, $type, $paramValue);

			if ($wiredValue !== null) {
				return $provider->compile($param, $type, $paramValue);
			}
		}

		if ($param->isOptional()) {
			return null;
		}

		if ($param->allowsNull()) {
			return 'null';
		}

		AutoWireException::failParam($param, "Cannot auto-wire to type '{$type->getName()}'");
	}

	private function compileUnionType(ReflectionParameter $param, ReflectionUnionType $type, mixed $paramValue): ?string {
		$types = $type->getTypes();
		$errors = array();

		foreach ($this->paramProviders as $provider) {
			foreach ($types as $type) {
				try {
					$wiredValue = $provider->provide($this->container, $param, $type, $paramValue);

					if ($wiredValue !== null) {
						return $provider->compile($param, $type, $paramValue);
					}
				} catch (DependencyInjectionException $e) {
					$errors[$type->getName()] = $e->getMessage();
				}
			}
		}

		$unionType = implode('|', array_keys($errors));
		$errors = implode("\n", $errors);
		AutoWireException::failParam($param, "Cannot auto-wire to union type '{$unionType}': \n{$errors}");
	}

}
