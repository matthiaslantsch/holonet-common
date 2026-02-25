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
use holonet\common\collection\ConfigRegistry;
use holonet\common\di\discovery\ConfigDependencyDiscovery;
use holonet\common\di\error\DependencyInjectionException;
use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\error\BadEnvironmentException;
use holonet\common\verifier\rules\string\MaxLength;
use holonet\common\di\autowire\attribute\ConfigItem;
use holonet\common\di\autowire\provider\ConfigAutoWireProvider;

#[CoversClass(ConfigDependencyDiscovery::class)]
class ConfigDependencyDiscoveryTest extends TestCase {
	public function test_discover() {
		$registry = new ConfigRegistry();
		$registry->set('di', [
			'services' => [
				'service1' => holonet_common_tests_SimpleDependency::class,
				'service2' => [holonet_common_tests_DependencyWithParameter::class, ['mustBeSuppliedParameter' => 'value1']],
				'service3' => AutoWire::class,
			],
			'auto_wire' => [
				'name1' => holonet_common_tests_SimpleDependency::class,
				'name2' => [holonet_common_tests_DependencyWithParameter::class, ['mustBeSuppliedParameter' => 'value1']],
				'name3' => AutoWire::class,
			],
		]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);

		$this->assertInstanceOf(holonet_common_tests_SimpleDependency::class, $container->get('service1'));
		$this->assertInstanceOf(holonet_common_tests_DependencyWithParameter::class, $container->get('service2'));
		$this->assertInstanceOf(AutoWire::class, $container->get('service3'));


		$this->assertInstanceOf(holonet_common_tests_SimpleDependency::class, $container->instance('name1'));
		$this->assertInstanceOf(holonet_common_tests_DependencyWithParameter::class, $container->instance('name2'));
		$this->assertInstanceOf(AutoWire::class, $container->instance('name3'));
	}

	public function test_error_invalid_abstract_definition_services(): void {
		$this->expectExceptionMessage('Faulty config with key \'di.services.service1\': Abstract must be class name or array with class name and parameters');
		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}

	public function test_error_empty_abstract_definition_services(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.services.service1\': Abstract must be class name or array with class name and parameters');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => []]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}

	public function test_error_invalid_abstract_definition(): void {
		$this->expectExceptionMessage('Faulty config with key \'di.auto_wire.service1\': Abstract must be class name or array with class name and parameters');
		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', ['service1' => ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}

	public function test_error_empty_abstract_definition(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.auto_wire.service1\': Abstract must be class name or array with class name and parameters');

		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', ['service1' => []]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}

	public function test_error_invalid_class_name_abstract(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.auto_wire.service1\': Abstract must be class name or array with class name and parameters');

		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', ['service1' => [100500, ['param1' => 'cool']]]);
		$container = new Container($registry);
		$dependencyDiscovery = new ConfigDependencyDiscovery();
		$dependencyDiscovery->discover($container);
	}

	public function test_error_non_string_wiring_key_is_ignored(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('No idea how to make \'5251\'. Class does not exist and no wire directive was set');

		$registry = new ConfigRegistry();
		$registry->set('di.auto_wire', [5251 => AutoWire::class]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);

		$container->instance(5251);
	}

	public function test_error_invalid_parameters(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'di.services.service1\': Abstract must be class name or array with class name and parameters');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => [holonet_common_tests_SimpleDependency::class, 'not an array']]);
		$container = new Container($registry);

		$dependencyDiscovery = new ConfigDependencyDiscovery();

		$dependencyDiscovery->discover($container);
	}
}

class holonet_common_tests_SimpleDependency {

}
