<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\di\autowire\AutoWireException;
use holonet\common\di\DependencyInjectionException;
use holonet\common\di\autowire\provider\ContainerAutoWireProvider;

#[CoversClass(Container::class)]
#[CoversClass(ContainerAutoWireProvider::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(DependencyInjectionException::class)]
class ContainerAutoWireProviderTest extends TestCase {
	public function testInjectionFailedThrowsException(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\DependencyWithParameter::__construct\': Parameter #0: mustBeSuppliedParameter: Cannot auto-wire to type \'string\'');

		$container = new Container();

		$container->make(ServiceMultipleVersionDependencies::class);
	}

	public function testInjectionIgnoresMissingOptionalParams(): void {
		$container = new Container();

		$result = $container->make(ServiceOptionalDep::class);

		$this->assertNull($result->dependency);
		$this->assertNotNull($result->optional);
	}

	public function testInjectionOfMultipleVersionsOfService(): void {
		$container = new Container();

		$serviceOne = new DependencyWithParameter('string_one');
		$serviceTwo = new DependencyWithParameter('string_two');

		$container->set('serviceOne', $serviceOne);
		$container->set('serviceTwo', $serviceTwo);

		$result = $container->make(ServiceMultipleVersionDependencies::class);

		$this->assertSame($serviceOne, $result->serviceOne);
		$this->assertSame($serviceTwo, $result->serviceTwo);
	}

	public function testInjectionUsingMake(): void {
		$container = new Container();

		$container->wire(DependencyWithParameter::class, array('mustBeSuppliedParameter' => 'test_string'));

		$one = $container->make(ServiceWithDependencyInjectUsingMake::class);
		$two = $container->make(ServiceWithDependencyInjectUsingMake::class);

		// because it's injected using make, they should be two different instances
		$this->assertTrue($one->dep !== $two->dep);
		$this->assertSame('test_string', $one->dep->mustBeSuppliedParameter);
		$this->assertSame('test_string', $two->dep->mustBeSuppliedParameter);
	}

	public function testServiceInjection(): void {
		$container = new Container();

		$dependency = new DependencyContainerAutoWire();
		$container->set('dependency', $dependency);

		$result = $container->make(Service::class);

		$this->assertSame($dependency, $result->dependency);
	}
}

class ServiceMultipleVersionDependencies {
	public function __construct(public DependencyWithParameter $serviceOne, public DependencyWithParameter $serviceTwo) {
	}
}

class ServiceOptionalDep {
	public function __construct(public ?DependencyWithParameter $dependency = null, public DependencyWithParameter $optional = new DependencyWithParameter('test')) {
	}
}

class Service {
	public function __construct(public DependencyContainerAutoWire $dependency) {
	}
}

class ServiceWithDependencyInjectUsingMake {
	public function __construct(public DependencyWithParameter $dep) {
	}
}

class DependencyContainerAutoWire {
	public function __construct() {
	}
}

class DependencyWithParameter {
	public function __construct(public string $mustBeSuppliedParameter) {
	}
}
