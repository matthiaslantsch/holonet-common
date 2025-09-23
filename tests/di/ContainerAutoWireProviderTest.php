<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\di;

use holonet\common\di\autowire\AutoWire;
use holonet\common\di\autowire\provider\ContainerAutoWireProvider;
use holonet\common\di\Container;
use holonet\common\di\error\AutoWireException;
use holonet\common\di\error\DependencyInjectionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(ContainerAutoWireProvider::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(DependencyInjectionException::class)]
class ContainerAutoWireProviderTest extends TestCase {
	public function test_injection_failed_throws_exception(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_DependencyWithParameter::__construct\': Parameter #0: mustBeSuppliedParameter: Cannot auto-wire to type \'string\'');

		$container = new Container();

		$container->instance(holonet_common_tests_ServiceMultipleVersionDependencies::class);
	}

	public function test_injection_ignores_missing_optional_params(): void {
		$container = new Container();

		$result = $container->instance(holonet_common_tests_ServiceOptionalDep::class);

		$this->assertNull($result->dependency);
		$this->assertNotNull($result->optional);
	}

	public function test_injection_of_multiple_versions_of_service(): void {
		$container = new Container();

		$serviceOne = new holonet_common_tests_DependencyWithParameter('string_one');
		$serviceTwo = new holonet_common_tests_DependencyWithParameter('string_two');

		$container->set('serviceOne', $serviceOne);
		$container->set('serviceTwo', $serviceTwo);

		$result = $container->instance(holonet_common_tests_ServiceMultipleVersionDependencies::class);

		$this->assertSame($serviceOne, $result->serviceOne);
		$this->assertSame($serviceTwo, $result->serviceTwo);
	}

	public function test_injection_using_instance(): void {
		$container = new Container();

		$container->wire(holonet_common_tests_DependencyWithParameter::class, array('mustBeSuppliedParameter' => 'test_string'));

		$one = $container->instance(holonet_common_tests_ServiceWithDependencyInjectUsingMake::class);
		$two = $container->instance(holonet_common_tests_ServiceWithDependencyInjectUsingMake::class);

		// because it's injected using make, they should be two different instances
		$this->assertTrue($one->dep !== $two->dep);
		$this->assertSame('test_string', $one->dep->mustBeSuppliedParameter);
		$this->assertSame('test_string', $two->dep->mustBeSuppliedParameter);
	}

	public function test_service_injection(): void {
		$container = new Container();

		$dependency = new holonet_common_tests_DependencyContainerAutoWire();
		$container->set('dependency', $dependency);

		$result = $container->instance(holonet_common_tests_Service::class);

		$this->assertSame($dependency, $result->dependency);
	}
}

class holonet_common_tests_ServiceMultipleVersionDependencies {
	public function __construct(public holonet_common_tests_DependencyWithParameter $serviceOne, public holonet_common_tests_DependencyWithParameter $serviceTwo) {
	}
}

class holonet_common_tests_ServiceOptionalDep {
	public function __construct(public ?holonet_common_tests_DependencyWithParameter $dependency = null, public holonet_common_tests_DependencyWithParameter $optional = new holonet_common_tests_DependencyWithParameter('test')) {
	}
}

class holonet_common_tests_Service {
	public function __construct(public holonet_common_tests_DependencyContainerAutoWire $dependency) {
	}
}

class holonet_common_tests_ServiceWithDependencyInjectUsingMake {
	public function __construct(public holonet_common_tests_DependencyWithParameter $dep) {
	}
}

class holonet_common_tests_DependencyContainerAutoWire {
	public function __construct() {
	}
}

class holonet_common_tests_DependencyWithParameter {
	public function __construct(public string $mustBeSuppliedParameter) {
	}
}
