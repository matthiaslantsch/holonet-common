<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\di;

use Countable;
use FilesystemIterator;
use holonet\common\config\ConfigRegistry;
use holonet\common\di\autowire\provider\ConfigAutoWireProvider;
use holonet\common\di\autowire\provider\ContainerAutoWireProvider;
use holonet\common\di\autowire\provider\ForwardAutoWireProvider;
use holonet\common\di\Compiler;
use holonet\common\di\Factory;
use holonet\common\Noun;
use Stringable;
use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\di\autowire\AutoWireException;
use holonet\common\di\DependencyNotFoundException;
use holonet\common\di\DependencyInjectionException;

#[CoversClass(Container::class)]
#[CoversClass(Compiler::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(ConfigAutoWireProvider::class)]
#[CoversClass(ContainerAutoWireProvider::class)]
#[CoversClass(ForwardAutoWireProvider::class)]
class CompilerTest extends TestCase
{
	public function testParameterForwardCompile(): void
	{
		$container = new Container();

		$params = array(
			'string' => 'gojsdgoisjdgio',
			'int' => 5,
			'float' => 10.5,
			'boolean' => true,
			'array' => array('value1', 'value2')
		);
		$container->wire(DependencyForwardAutoWire::class, $params);

		$compiler = new Compiler($container);

		$compiled = <<<'COMPILED'
		if (!isset($config) || !$config instanceof \holonet\common\config\ConfigRegistry) {
			throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\config\ConfigRegistry');
		}
		
		return new class($config) extends \holonet\common\di\Container {
			public function make(string $abstract, array $extraParams = array()): object {
				return match ($abstract) {
					holonet\common\tests\di\DependencyForwardAutoWire::class => $this->make_holonet_common_tests_di_DependencyForwardAutoWire(),
					default => parent::make($abstract, $extraParams)
				};
			}
			
			public function make_holonet_common_tests_di_DependencyForwardAutoWire(): holonet\common\tests\di\DependencyForwardAutoWire {
				return new holonet\common\tests\di\DependencyForwardAutoWire(string: 'gojsdgoisjdgio', int: 5, float: 10.5, boolean: true, array: array (
		 	  0 => 'value1',
		 	  1 => 'value2',
			));
			}
		};
		COMPILED;
		$actual = $compiler->compile();

		$this->assertEqualsIgnoringIndentation($compiled, $actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testClassWithoutConstructorProvidedParams(): void
	{
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\NoConstructorDependency\': Has no constructor, but 1 parameters were given');

		$container = new Container();

		$container->wire(NoConstructorDependency::class, array('test' => 'value'));

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testClassWithoutConstructorCompiles(): void
	{
		$container = new Container();

		$container->wire(NoConstructorDependency::class);

		$compiler = new Compiler($container);

		$expected = <<<'COMPILED'
		if (!isset($config) || !$config instanceof \holonet\common\config\ConfigRegistry) {
			throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\config\ConfigRegistry');
		}
		
		return new class($config) extends \holonet\common\di\Container {
			public function make(string $abstract, array $extraParams = array()): object {
				return match ($abstract) {
					holonet\common\tests\di\NoConstructorDependency::class => $this->make_holonet_common_tests_di_NoConstructorDependency(),
					default => parent::make($abstract, $extraParams)
				};
			}
		
			public function make_holonet_common_tests_di_NoConstructorDependency(): holonet\common\tests\di\NoConstructorDependency {
				return new holonet\common\tests\di\NoConstructorDependency();
			}
		};
		COMPILED;
		$actual = $compiler->compile();

		$this->assertEqualsIgnoringIndentation($expected, $actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testIntersectionTypesCannotBeCompiled(): void
	{
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\DependencyWithIntersectionType::__construct\': Parameter #0: param: Cannot auto-wire intersection types');

		$container = new Container();

		$container->wire(DependencyWithIntersectionType::class);

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testCannotAutowireUntypedParameter(): void
	{
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\UntypedParamsDependency::__construct\': Parameter #0: param1: Can only auto-wire typed parameters');

		$container = new Container();
		$container->wire(UntypedParamsDependency::class);

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testCanAutowireUntypedOptionalParameter(): void
	{
		$container = new Container();
		$container->wire(UntypedParamOptionalDependency::class);

		$compiler = new Compiler($container);
		$this->assertNotEmpty($compiler->compile());
	}

	public function testCompilesOptionalOrNullableParameter(): void
	{
		$container = new Container();
		$container->wire(OptionalAndNullableParamsDependency::class);

		$compiler = new Compiler($container);

		$expected = <<<'COMPILED'
		if (!isset($config) || !$config instanceof \holonet\common\config\ConfigRegistry) {
			throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\config\ConfigRegistry');
		}
		
		return new class($config) extends \holonet\common\di\Container {
			public function make(string $abstract, array $extraParams = array()): object {
				return match ($abstract) {
					holonet\common\tests\di\OptionalAndNullableParamsDependency::class => $this->make_holonet_common_tests_di_OptionalAndNullableParamsDependency(),
					default => parent::make($abstract, $extraParams)
				};
			}
		
			public function make_holonet_common_tests_di_OptionalAndNullableParamsDependency(): holonet\common\tests\di\OptionalAndNullableParamsDependency {
				return new holonet\common\tests\di\OptionalAndNullableParamsDependency(param1: null);
			}
		};
		COMPILED;
		$actual = $compiler->compile();

		$this->assertEqualsIgnoringIndentation($expected, $actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testUnionTypeCanBeCompiled(): void
	{
		$container = new Container();
		$container->wire(UnionTypeDependency::class);

		$compiler = new Compiler($container);

		$expected = <<<'COMPILED'
		if (!isset($config) || !$config instanceof \holonet\common\config\ConfigRegistry) {
		       throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\config\ConfigRegistry');
		}
		
		return new class($config) extends \holonet\common\di\Container {
		       public function make(string $abstract, array $extraParams = array()): object {
		               return match ($abstract) {
		                       holonet\common\tests\di\UnionTypeDependency::class => $this->make_holonet_common_tests_di_UnionTypeDependency(),
		                       default => parent::make($abstract, $extraParams)
		               };
		       }
		       
		       public function make_holonet_common_tests_di_UnionTypeDependency(): holonet\common\tests\di\UnionTypeDependency {
		               return new holonet\common\tests\di\UnionTypeDependency(param: $this->byType('holonet\common\Noun', 'param'));
		       }
		};
		COMPILED;
		$actual = $compiler->compile();

		$this->assertEqualsIgnoringIndentation($expected, $actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testCannotCompileNonWireableDependency(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\NonWireableDependency::__construct\': Parameter #0: param: Cannot auto-wire to type \'string\'');

		$container = new Container();
		$container->wire(NonWireableDependency::class);

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testUnionTypeNonWireable(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\DependencyTest::__construct\': Parameter #0: param: Cannot auto-wire to union type \'holonet\common\tests\di\NonWireableDependency|string\'');

		$container = new Container();
		$container->wire(DependencyTest::class);

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testCompileConfigParam(): void {
		$container = new Container();

		$value = array('test', 'cool');
		$container->registry->set('config.just_an_array_value', $value);
		$container->registry->set('service.other', array('stringValue' => 'test'));
		$container->registry->set('service.config', array('stringValue' => 'test'));

		$container->wire(ServiceWithArrayConfigValue::class);
		$container->wire(OtherDependency::class);
		$container->wire(Dependency::class, array('config' => 'service.config'));

		$compiler = new Compiler($container);

		$expected = <<<'COMPILED'
		if (!isset($config) || !$config instanceof \holonet\common\config\ConfigRegistry) {
			throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\config\ConfigRegistry');
		}
		
		return new class($config) extends \holonet\common\di\Container {
			public function make(string $abstract, array $extraParams = array()): object {
				return match ($abstract) {
					holonet\common\tests\di\ServiceWithArrayConfigValue::class => $this->make_holonet_common_tests_di_ServiceWithArrayConfigValue(),
					holonet\common\tests\di\OtherDependency::class => $this->make_holonet_common_tests_di_OtherDependency(),
					holonet\common\tests\di\Dependency::class => $this->make_holonet_common_tests_di_Dependency(),
					default => parent::make($abstract, $extraParams)
				};
			}
		
			public function make_holonet_common_tests_di_ServiceWithArrayConfigValue(): holonet\common\tests\di\ServiceWithArrayConfigValue {
				return new holonet\common\tests\di\ServiceWithArrayConfigValue(value: $this->registry->get('config.just_an_array_value'));
			}
		
			public function make_holonet_common_tests_di_OtherDependency(): holonet\common\tests\di\OtherDependency {
				return new holonet\common\tests\di\OtherDependency(config: $this->registry->verifiedDto('service.other', 'holonet\common\tests\di\Config'));
			}
		
			public function make_holonet_common_tests_di_Dependency(): holonet\common\tests\di\Dependency {
				return new holonet\common\tests\di\Dependency(config: $this->registry->asDto('service.config', 'holonet\common\tests\di\Config'));
			}
		};
		COMPILED;
		$actual = $compiler->compile();

		$this->assertEqualsIgnoringIndentation($expected, $actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testCompileNamedService(): void {
		$registry = new ConfigRegistry();

		$container = new Container($registry);
		$container->set('service1', DiAnonDep::class);
		$compiler = new Compiler($container);
		$expected = <<<'COMPILED'
		if (!isset($config) || !$config instanceof \holonet\common\config\ConfigRegistry) {
			throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\config\ConfigRegistry');
		}
		
		return new class($config) extends \holonet\common\di\Container {
			public function make(string $abstract, array $extraParams = array()): object {
				return match ($abstract) {
					'service1' => $this->make_service1(),
					default => parent::make($abstract, $extraParams)
				};
			}
			
			public function make_service1(): holonet\common\tests\di\DiAnonDep {
				return new holonet\common\tests\di\DiAnonDep();
			}
		};
		COMPILED;

		$actual = $compiler->compile();

		$this->assertEqualsIgnoringIndentation($expected, $actual);
		$this->assertValidCompiledContainer($actual, $registry);
	}

	protected function assertValidCompiledContainer(string $code, ConfigRegistry $config): void {
		$container = eval("{$code}");
		$this->assertTrue(str_contains(get_class($container), '@anonymous'));
		$this->assertInstanceOf(Container::class, $container);
	}

	protected function assertEqualsIgnoringIndentation(string $expect, string $actual, string $message = ''): void
	{
		$expect = preg_replace('/\s+/', ' ', $expect);
		$actual = preg_replace('/\s+/', ' ', $actual);
		$this->assertEquals($expect, $actual, $message);
	}
}

class DependencyTest
{
	public function __construct(NonWireableDependency|string $param)
	{
	}
}

class NonWireableDependency
{
	public function __construct(string $param)
	{
	}
}

class UnionTypeDependency
{
	public function __construct(string|Noun $param)
	{
	}
}

class OptionalAndNullableParamsDependency
{
	public function __construct(?string $param1, string $param2 = 'default')
	{
	}
}

class UntypedParamOptionalDependency
{
	public function __construct($param1 = null)
	{
	}
}

class UntypedParamsDependency
{
	public function __construct($param1, $param2 = null)
	{
	}
}

class NoConstructorDependency
{
}

class DependencyWithIntersectionType
{
	public function __construct(Stringable&Countable $param)
	{
	}
}
