<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\container;

use holonet\common\di\DependencyInjectionException;
use holonet\common\di\Provider;
use ReflectionClass;
use Psr\Container\ContainerInterface;
use holonet\common\di\autowire\AutoWire;
use holonet\common\config\ConfigRegistry;
use holonet\common\di\autowire\AutoWireException;

/**
 * Special trait containing the logic for auto-wiring objects at creation time.
 */
trait AutoWiringTrait {
	/**
	 * @var array<string, array{string, array}> $wiring Wiring information on how to make certain types of objects.
	 * Mapped by name / type => class abstract (array with class name and parameters).
	 */
	protected array $wiring = array();

	protected AutoWire $autoWiring;

	/**
	 * Set up a wiring from an abstract to an actual implementation.
	 * This can be used to choose strategy pattern possibilities based on config.
	 * The wired object will also get additional params autowired.
	 */
	public function wire(string $class, array $params = array(), ?string $abstract = null): void {
		if (!class_exists($class)) {
			throw new DependencyInjectionException("Could not auto-wire abstract '{$class}': class does not exist");
		}

		if (is_a($class, Provider::class, true) && $abstract === null) {
			$reflection = new ReflectionClass($class);
			$factoryMethod = $reflection->getMethod('make');

			$returnType = $factoryMethod->getReturnType();
			if (!$returnType instanceof \ReflectionNamedType || $returnType->getName() === 'object') {
				throw new DependencyInjectionException("Provider factory method {$class}::make() has no return type");
			}
			$abstract = $returnType->getName();
		}

		$abstract ??= $class;

		$this->wiring[$abstract] = array($class, $params);
	}

	protected function instance(string $class, array $params): object {
		$reflection = new ReflectionClass($class);
		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			if (!empty($params)) {
				AutoWireException::failNoConstructor($reflection, $params);
			}

			$result = new $class();
		} else {
			$params = $this->autoWiring->autoWire($constructor, $params);

			$result = new $class(...$params);
		}

		if ($result instanceof Provider) {
			return $result->make();
		}

		return $result;
	}
}
