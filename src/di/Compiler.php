<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use holonet\common\collection\Registry;
use holonet\common\di\autowire\provider\ConfigAutoWireProvider;
use holonet\common\di\autowire\provider\ContainerAutoWireProvider;
use holonet\common\di\autowire\provider\ForwardAutoWireProvider;
use holonet\common\di\autowire\provider\ParamAutoWireProvider;
use holonet\common\di\error\AutoWireException;
use holonet\common\di\error\CannotAutowireException;
use holonet\common\di\error\DependencyInjectionException;
use holonet\holofw\tasks\cache\CacheRefreshCommand;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;
use function holonet\common\indentText;

/**
 * Compile a static php anonymous class which can create all the current definitions in the container without reflection.
 */
class Compiler {

	/**
	 * @var ParamAutoWireProvider[]
	 */
	protected array $paramProviders;

	/**
	 * Arbitrary class name aliases to abstract classes or interfaces.
	 * Alias => abstract
	 * @var array<string, string>
	 */
	private array $aliases = array();

	/**
	 * Mapping from abstract class or interface names to concrete class names.
	 * Abstract class / interface => concrete class
	 * @var array<string, string>
	 */
	private array $contracts = array();

	/**
	 * @var string[] $services All alias keys that are services
	 */
	protected array $services = array();

	/**
	 * @var array<string, array> $wiring Wiring information on how to make certain types of objects.
	 * Mapped by name / type => class abstract (array with class name and parameters).
	 */
	private array $wiring = array();

	/**
	 * @var array <string,string> $providers Mapping from abstracts to whatever provider class makes them.
	 */
	private array $providers = array();


	public function __construct(protected Container $container) {
		$reflection = new ReflectionObject($this->container);

		$this->aliases = $reflection->getProperty('aliases')->getValue($container);
		$this->wiring = $reflection->getProperty('wiring')->getValue($container);
		$this->providers = $reflection->getProperty('providers')->getValue($container);
		$this->services = $reflection->getProperty('services')->getValue($container);
		$this->contracts = $reflection->getProperty('contracts')->getValue($container);

		$this->paramProviders = array(
			new ForwardAutoWireProvider(),
			new ConfigAutoWireProvider(),
			new ContainerAutoWireProvider(),
		);
	}

	public function compile(): string {
		foreach ($this->wiring as $class => $params) {
			$reflection = new ReflectionClass($class);
			if ($reflection->isAbstract()) {
				continue;
			}

			try {
				$methods[$class] = $this->compileWiringInstantiateMethod($class, $params, $reflection);
			} catch (Throwable $e) {
				if ($e instanceof AutoWireException) {
					throw $e;
				}

				// ignore the exception. The dependency will be lazily created and either work then or throw then
				// we don't want to throw it now since this is the bootstrapping stage
				$userWarning = sprintf('Error when compiling static make method for %s: %s', $class, str_replace('\'', '\\\'', $e->getMessage()));
				$methods[$class] = <<<PHP
				protected function {$this->serviceInstantiateMethodName($class)}(array \$params): {$class} {
					if (\$this->registry->get('di.warn_on_inefficient_instantiation')) {
						trigger_error('{$userWarning}', E_USER_WARNING);
					}
					return parent::instantiate($class::class, \$params);
				}
				PHP;

				continue;
			}
		}

		array_unshift($methods, $this->compileInstantiateMethod($methods));

		$methods = implode("\n\t", explode("\n", implode("\n\n", $methods)));

		$aliases = $this->compileArrayProperty('$aliases', $this->aliases);
		$services = $this->compileArrayProperty('$services', $this->services);
		$contracts = $this->compileArrayProperty('$contracts', $this->contracts);
		$providers = $this->compileArrayProperty('$providers', $this->providers);

		return <<<PHP
		if (!isset(\$config) || !\$config instanceof \holonet\common\collection\ConfigRegistry) {
			throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\collection\ConfigRegistry');
		}
		
		\$initialServices ??= array();
		
		return new class(\$config, \$initialServices) extends \holonet\common\di\Container {
			{$aliases}
			
			{$services}
			
			{$contracts}
			
			{$providers}
			
			{$methods}
		};
		PHP;
	}

	private function compileArrayProperty(string $name, array $values): string {
		return sprintf('protected array %s = %s;',
			$name, $this->exportArray($values)
		);
	}

	public static function exportArray(array $values): string	{
		$val = str_replace("array(\n\t)", 'array()', // empty array reduce to one line
			str_replace(array('  ', 'array ('), array("\t", 'array('), // fix annoying syntax / indent
				indentText(var_export($values, true))
			)
		);

		if (array_is_list($values)) {
			// remove indexes from simple lists
			$val = preg_replace("/\d => /", '', $val);
		}

		return $val;
	}

	/**
	 * Compile a static version of the instantiate() method of a container
	 */
	private function compileInstantiateMethod(array $makeMethods): string {
		$matchStatements = array();

		foreach ($this->wiring as $abstract => $constructor) {
			if (!isset($makeMethods[$abstract])) {
				continue;
			}
			$makeStatement = sprintf('$this->%s($params)', $this->serviceInstantiateMethodName($abstract));
			$matchStatements[$makeStatement][] = $abstract;
		}

		foreach ($this->providers as $alias => $provider) {
			$makeStatement = sprintf('$this->%s($params)->make()', $this->serviceInstantiateMethodName($provider));
			$matchStatements[$makeStatement][] = $alias;
		}

		foreach ($this->aliases as $alias => $aliased) {
			$makeStatement = $this->serviceInstantiateMethodName($aliased);
			if (isset($matchStatements[$makeStatement])) {
				$matchStatements[$makeStatement][] = $alias;
			}
		}

		$compiledMatchStatements = array();
		foreach ($matchStatements as $methodCall => $matches) {
			$matches = array_map(function ($match) {
				if (class_exists($match)) {
					return "{$match}::class";
				} else {
					return "'{$match}'";
				}
			}, $matches);
			$compiledMatchStatements[] = sprintf("\t\t%s => %s",
				implode(', ', $matches),
				$methodCall
			);
		}

		$compiledMatchStatements[] = "\t\tdefault => parent::instantiate(\$class, \$params)";

		$compiledMatchStatements = implode(",\n", $compiledMatchStatements);
		return <<<PHP
		protected function instantiate(string \$class, array \$params = array()): object {
			return match (\$class) {
		$compiledMatchStatements
			};
		}
		PHP;
	}

	private function serviceInstantiateMethodName(string $alias): string {
		return sprintf('instantiate_%s', str_replace('\\', '_', $alias));
	}

	private function compileWiringInstantiateMethod(string $class, array $params, ReflectionClass $reflection): string {
		$wiringMakeMethodBodyStatements = $this->compileWiringMakeMethodBody($class, $params, $reflection);

		$wiringMakeMethodBodyStatements = implode(";\n\t", $wiringMakeMethodBodyStatements);
		return <<<PHP
		protected function {$this->serviceInstantiateMethodName($class)}(array \$params): {$class} {
			{$wiringMakeMethodBodyStatements};
		}
		PHP;
	}

	private function compileWiringMakeMethodBody(string $class, array $params, ReflectionClass $reflection): array {
		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			if (!empty($params)) {
				AutoWireException::failNoConstructor($reflection, $params);
			}

			return ["return new $class()"];
		}

		$parameterAssignments = $this->compileAutoWiring($constructor, $params, $class);
		$parameterAssignments[] = sprintf('return new %s(...$params)', $class);

		return $parameterAssignments;
	}

	protected function compileAutoWiring(ReflectionFunctionAbstract $method, array $givenParams, string $class): array {
		$parameters = $method->getParameters();
		$compiled = array();
		foreach ($parameters as $param) {
			try {
				$compiledValue = $this->compileParameter($param, $givenParams[$param->getName()] ?? null);
			} catch (DependencyInjectionException $e) {
				$compiledValue = null;
				if ($e instanceof CannotAutowireException) {
					throw $e;
				}
			}
			if ($compiledValue !== null) {
				$compiled[] = "\$params['{$param->getName()}'] ??= {$compiledValue}";
			} elseif (!$param->isOptional()) {
				$compiled[] = "\$params['{$param->getName()}'] ?? throw new \InvalidArgumentException('Cannot instantiate \'{$class}\': Missing parameter \'{$param->getName()}\' of type \'{$param->getType()})\'')";
			}
		}

		return $compiled;
	}

	private function compileParameter(ReflectionParameter $param, mixed $paramValue): ?string {
		$paramType = $param->getType();

		if ($paramType instanceof ReflectionIntersectionType) {
			CannotAutowireException::failParam($param, 'Cannot auto-wire intersection types');
		}

		if ($paramType === null) {
			if ($param->isOptional()) {
				return null;
			}

			CannotAutowireException::failParam($param, 'Can only auto-wire typed parameters');
		}

		if ($paramType instanceof ReflectionUnionType) {
			return $this->compileUnionType($param, $paramType, $paramValue);
		}

		return $this->compileNamedType($param, $paramType, $paramValue);
	}

	private function compileNamedType(ReflectionParameter $param, ReflectionNamedType $type, mixed $paramValue): ?string {
		if (is_a($type->getName(), Container::class, true)) {
			return "\$this";
		}

		if (is_a($type->getName(), Registry::class, true)) {
			return "\$this->registry";
		}

		if (($alias = array_search($type->getName(), $this->aliases, true)) !== false) {
			return "\$this->get('{$alias}')";
		}

		foreach ($this->paramProviders as $provider) {
			try {
				$wiredValue = $provider->provide($this->container, $param, $type, $paramValue);
			} catch (AutoWireException $e) {
				continue;
			}

			if ($wiredValue !== null) {
				return $provider->compile($param, $type, $paramValue);
			}
		}

		if ($param->allowsNull()) {
			return 'null';
		}

		// hail mary pass: let's hope we have the dependency at runtime
		if (class_exists($type->getName()) || interface_exists($type->getName())) {
			return "\$this->byType('{$type->getName()}', '{$param->getName()}')";
		}

		return null;
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

		$unionType = implode('|', array_map(fn ($type) => $type->getName(), $types));
		CannotAutowireException::failParam($param, "Cannot auto-wire to union type '{$unionType}'");
	}

}
