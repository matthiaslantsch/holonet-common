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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

#[CoversClass(Factory::class)]
class FactoryTest extends TestCase {

	public function test_compiler_disabled_factory(): void {
		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => [holonet_common_tests_Dependency::class]]);

		$factory = new Factory($registry);

		$container = $factory->make();
		$this->assertSame(Container::class, get_class($container));
	}

	public function test_factory_returns_old_compiled_container(): void {
		$this->assertFileExists(dirname(__DIR__).'/data/container.php');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => [holonet_common_tests_DiAnonDep::class]]);
		$registry->set('di.cache_path', dirname(__DIR__).'/data');

		$factory = new Factory($registry);

		$container = $factory->make();
		$this->assertTrue(str_contains(get_class($container), '@anonymous'));
		$reflection = new ReflectionObject($container);
		$this->assertTrue($reflection->hasMethod('instantiate_holonet_common_tests_di_holonet_common_tests_DiAnonDep'), 'Factory did not create the instantiate_holonet_common_tests_di_holonet_common_tests_DiAnonDep method');
		$this->assertInstanceOf(Container::class, $container);
	}

	public function test_compile_container_to_file(): void {
		@unlink(dirname(__DIR__).'/data/container.php');

		$this->assertFileDoesNotExist(dirname(__DIR__).'/data/container.php');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => holonet_common_tests_DiAnonDep::class]);
		$registry->set('di.cache_path', dirname(__DIR__).'/data');

		$factory = new Factory($registry);

		$container = $factory->make();
		$this->assertTrue(str_contains(get_class($container), '@anonymous'));
		$reflection = new ReflectionObject($container);
		$this->assertTrue($reflection->hasMethod('instantiate_holonet_common_tests_di_holonet_common_tests_DiAnonDep'), 'Factory did not create the instantiate_holonet_common_tests_di_holonet_common_tests_DiAnonDep method');
		$this->assertInstanceOf(Container::class, $container);
		$this->assertFileExists(dirname(__DIR__).'/data/container.php');
	}

	public function test_error_bad_cache_path_causes_an_exception(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Container compile path \'/rubbish/path/does/not/exist\' is not a writable directory');

		$registry = new ConfigRegistry();
		$registry->set('di.services', ['service1' => holonet_common_tests_DiAnonDep::class]);
		$registry->set('di.cache_path', '/rubbish/path/does/not/exist');

		$factory = new Factory($registry);
		$factory->make();
	}

}
