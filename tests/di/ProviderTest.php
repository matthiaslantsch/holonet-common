<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use Countable;
use holonet\common\di\Provider;
use Stringable;
use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\di\autowire\AutoWireException;
use holonet\common\di\DependencyNotFoundException;
use holonet\common\di\DependencyInjectionException;

#[CoversClass(Container::class)]
#[CoversClass(Provider::class)]
class ProviderTest extends TestCase {
	// test provider for service
	// test provider for instance
	// test provider for type byType
	// test provider calling make() with the actual class

	public function testProviderSetForService(): void {
		$container = new Container();

		$container->set('test_dep', TestProvider::class);

		$result = $container->get('test_dep');

		$this->assertInstanceOf(ProvidedDependency::class, $result);
		$this->assertTrue($result === $container->get('test_dep'));
	}

	public function testProviderForWireMake(): void {
		$container = new Container();

		$container->wire(TestProvider::class, array(), 'dep');

		$result = $container->make('dep');

		$this->assertInstanceOf(ProvidedDependency::class, $result);
		$this->assertFalse($result === $container->make('dep'));
	}

	public function testProviderServiceByType(): void {
		$container = new Container();

		$container->set('test_dep', new TestProvider($container));

		$result = $container->byType(ProvidedDependency::class);

		$this->assertInstanceOf(ProvidedDependency::class, $result);
		$this->assertTrue($result === $container->byType(ProvidedDependency::class));
	}

	public function testProviderByProvidedClass(): void {
		$container = new Container();

		$container->wire(TestProvider::class);

		$result = $container->make(ProvidedDependency::class);

		$this->assertInstanceOf(ProvidedDependency::class, $result);
		$this->assertFalse($result === $container->make(ProvidedDependency::class));
	}
}

class TestProvider extends Provider {

	public function make(): ProvidedDependency {
		return new ProvidedDependency();
	}

}

class ProvidedDependency {

}