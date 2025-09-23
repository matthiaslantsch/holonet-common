<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\di;

use holonet\common\di\Container;
use holonet\common\di\error\DependencyInjectionException;
use holonet\common\di\Provider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(Provider::class)]
class ProviderTest extends TestCase {

	public function test_provider_set_for_service(): void {
		$container = new Container();

		$container->set('test_dep', holonet_common_tests_TestProvider::class);

		$result = $container->get('test_dep');

		$this->assertInstanceOf(holonet_common_tests_ProvidedDependency::class, $result);
		$this->assertTrue($result === $container->get('test_dep'));

		$result = $container->instance(holonet_common_tests_ProvidedDependency::class);

		$this->assertInstanceOf(holonet_common_tests_ProvidedDependency::class, $result);
		$this->assertTrue($result === $container->instance(holonet_common_tests_ProvidedDependency::class));
	}

	public function test_provider_for_wire_instance(): void {
		$container = new Container();

		$container->wire(holonet_common_tests_TestProvider::class, array(), 'dep');

		$result = $container->instance('dep');

		$this->assertInstanceOf(holonet_common_tests_ProvidedDependency::class, $result);
		$this->assertFalse($result === $container->instance('dep'));

		$result = $container->instance(holonet_common_tests_ProvidedDependency::class);

		$this->assertInstanceOf(holonet_common_tests_ProvidedDependency::class, $result);
		$this->assertFalse($result === $container->instance(holonet_common_tests_ProvidedDependency::class));
	}

	public function test_provider_service_by_type(): void {
		$container = new Container();

		$container->set('test_dep', new holonet_common_tests_TestProvider($container));

		$result = $container->instance(holonet_common_tests_ProvidedDependency::class);

		$this->assertInstanceOf(holonet_common_tests_ProvidedDependency::class, $result);
		$this->assertTrue($result === $container->instance(holonet_common_tests_ProvidedDependency::class));
	}

	public function test_error_instance_with_bad_provider_without_typehint_causes_exception(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('\'holonet\common\tests\di\holonet_common_tests_BadProviderWithoutTypehint::make()\' has an invalid return type: \'object\'.');

		$container = new Container();

		$container->wire(holonet_common_tests_BadProviderWithoutTypehint::class);
	}

	public function test_error_set_with_bad_provider_without_typehint_causes_exception(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('\'holonet\common\tests\di\holonet_common_tests_BadProviderWithoutTypehint::make()\' has an invalid return type: \'object\'.');

		$container = new Container();

		$container->set('test', holonet_common_tests_BadProviderWithoutTypehint::class);
	}
}

class holonet_common_tests_TestProvider extends Provider {

	public function make(): holonet_common_tests_ProvidedDependency {
		return new holonet_common_tests_ProvidedDependency();
	}

}

class holonet_common_tests_BadProviderWithoutTypehint extends Provider {

	public function make(): object {
		return new holonet_common_tests_ProvidedDependency();
	}

}

class holonet_common_tests_ProvidedDependency {

}
