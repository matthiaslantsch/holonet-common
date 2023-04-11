<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\di;

use holonet\common\collection\Registry;
use holonet\common\config\ConfigRegistry;
use holonet\common\di\discovery\ConfigDependencyDiscovery;
use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\error\BadEnvironmentException;
use holonet\common\di\DependencyInjectionException;
use holonet\common\verifier\rules\string\MaxLength;
use holonet\common\di\autowire\attribute\ConfigItem;
use holonet\common\di\autowire\provider\ConfigAutoWireProvider;

#[CoversClass(ConfigDependencyDiscovery::class)]
class ConfigDependencyDiscoveryTest extends TestCase {
	public function testDiscover() {
		$registry = new ConfigRegistry();
		$registry->set('di', [
			'services' => [
				'service1' => SimpleDependency::class,
				'service2' => [DependencyWithParameter::class, ['mustBeSuppliedParameter' => 'value1']],
				'service3' => AutoWire::class,
			],
			'auto_wire' => [
				'name1' => SimpleDependency::class,
				'name2' => [DependencyWithParameter::class, ['mustBeSuppliedParameter' => 'value1']],
				'name3' => AutoWire::class,
			],
		]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);

		$this->assertInstanceOf(SimpleDependency::class, $container->get('service1'));
		$this->assertInstanceOf(DependencyWithParameter::class, $container->get('service2'));
		$this->assertInstanceOf(AutoWire::class, $container->get('service3'));


		$this->assertInstanceOf(SimpleDependency::class, $container->make('name1'));
		$this->assertInstanceOf(DependencyWithParameter::class, $container->make('name2'));
		$this->assertInstanceOf(AutoWire::class, $container->make('name3'));
	}

	public function testInvalidOrEmptyAbstractDefinitionServices(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.services.service1\': Abstract must be class name or array with class name and parameters');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => []]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);

		$this->expectExceptionMessage('Faulty config with key \'di.services.service1\': Abstract must be class name or array with class name and parameters');
		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}

	public function testInvalidOrEmptyAbstractDefinition(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.auto_wire.service1\': Abstract must be class name or array with class name and parameters');

		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', ['service1' => []]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);

		$this->expectExceptionMessage('Faulty config with key \'di.auto_wire.service1\': Abstract must be class name or array with class name and parameters');
		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', ['service1' => ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}

	public function testInvalidClassNameAbstract(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.auto_wire.service1\': Abstract must be class name or array with class name and parameters');


		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', ['service1' => [100500, ['param1' => 'cool']]]);
		$container = new Container($registry);
		$dependencyDiscovery = new ConfigDependencyDiscovery();
		$dependencyDiscovery->discover($container);
	}

	public function testNonStringWiringKeyIsIgnored(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('No idea how to make \'5251\'. Class does not exist and no wire directive was set');

		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', [5251 => AutoWire::class]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);

		$container->make(5251);
	}

	public function testInvalidParameters(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.services.service1\': Abstract must be class name or array with class name and parameters');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => [SimpleDependency::class, 'not an array']]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}
}

class SimpleDependency {

}
