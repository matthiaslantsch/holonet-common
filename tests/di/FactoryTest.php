<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\di;

use holonet\common\collection\ConfigRegistry;
use holonet\common\di\Container;
use holonet\common\di\Factory;
use holonet\common\error\BadEnvironmentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\di\autowire\AutoWireException;
use holonet\common\di\DependencyNotFoundException;
use holonet\common\di\DependencyInjectionException;
use ReflectionObject;
use function holonet\common\get_class_short;

#[CoversClass(Factory::class)]
class FactoryTest extends TestCase {

	public function testCompilerDisabledFactory(): void {
		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => [Dependency::class]]);

		$factory = new Factory($registry);

		$container = $factory->make();
		$this->assertSame(Container::class, get_class($container));
	}

	public function testFactoryReturnsOldCompiledContainer(): void {
		$this->assertFileExists(dirname(__DIR__).'/data/container.php');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => [DiAnonDep::class]]);
		$registry->set('di.cache_path', dirname(__DIR__).'/data');

		$factory = new Factory($registry);

		$container = $factory->make();
		$this->assertTrue(str_contains(get_class($container), '@anonymous'));
		$reflection = new ReflectionObject($container);
		$this->assertTrue($reflection->hasMethod('make_service1'), 'Factory did not create the make_service1 method');
		$this->assertInstanceOf(Container::class, $container);
	}

	public function testCompileContainerToFile(): void {
		@unlink(dirname(__DIR__).'/data/container.php');

		$this->assertFileDoesNotExist(dirname(__DIR__).'/data/container.php');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => DiAnonDep::class]);
		$registry->set('di.cache_path', dirname(__DIR__).'/data');

		$factory = new Factory($registry);

		$container = $factory->make();
		$this->assertTrue(str_contains(get_class($container), '@anonymous'));
		$reflection = new ReflectionObject($container);
		$this->assertTrue($reflection->hasMethod('make_service1'), 'Factory did not create the make_service1 method');
		$this->assertInstanceOf(Container::class, $container);
		$this->assertFileExists(dirname(__DIR__).'/data/container.php');
	}

	public function testBadCachePathCausesAnException(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Container compile path \'/rubbish/path/does/not/exist\' is not a writable directory');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => DiAnonDep::class]);
		$registry->set('di.cache_path', '/rubbish/path/does/not/exist');

		$factory = new Factory($registry);
		$factory->make();
	}

}
