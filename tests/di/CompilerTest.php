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
use holonet\common\collection\ConfigRegistry;
use holonet\common\di\autowire\CannotAutowireException;
use holonet\common\di\autowire\provider\ConfigAutoWireProvider;
use holonet\common\di\autowire\provider\ContainerAutoWireProvider;
use holonet\common\di\autowire\provider\ForwardAutoWireProvider;
use holonet\common\di\Compiler;
use holonet\common\di\Factory;
use holonet\common\Noun;
use InvalidArgumentException;
use ReflectionClass;
use Spatie\Snapshots\MatchesSnapshots;
use Stringable;
use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\di\autowire\AutoWireException;
use holonet\common\di\DependencyNotFoundException;
use holonet\common\di\DependencyInjectionException;
use function holonet\common\dir_path;

#[CoversClass(Container::class)]
#[CoversClass(Compiler::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(ConfigAutoWireProvider::class)]
#[CoversClass(ContainerAutoWireProvider::class)]
#[CoversClass(ForwardAutoWireProvider::class)]
class CompilerTest extends TestCase {
	use MatchesSnapshots;

	public function test_parameter_required_compile(): void {
		$container = new Container();

		$container->wire(holonet_common_tests_CompilerTest_ForwardParamDependency::class, array('testParamTwo' => 'testParamTwoValue'));

		$compiler = new Compiler($container);

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$container = $this->assertValidCompiledContainer($actual, $container->registry);

		// assert we can make it if we supply the required parameter
		$this->assertInstanceOf(holonet_common_tests_CompilerTest_ForwardParamDependency::class, $container->make(holonet_common_tests_CompilerTest_ForwardParamDependency::class, array('testParam' => 'testParamValue')));
		// assert we can't make it without supplying the required parameter
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot instantiate \'holonet\common\tests\di\holonet_common_tests_CompilerTest_ForwardParamDependency\': Missing parameter \'testParam\' of type \'string)\'');
		$container->make(holonet_common_tests_CompilerTest_ForwardParamDependency::class);
	}

	public function testParameterForwardCompile(): void {
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

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testClassWithoutConstructorProvidedParams(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_CompilerTest_NoConstructorDependency\': Has no constructor, but 1 parameters were given');

		$container = new Container();

		$container->wire(holonet_common_tests_CompilerTest_NoConstructorDependency::class, array('test' => 'value'));

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testClassWithoutConstructorCompiles(): void {
		$container = new Container();

		$container->wire(holonet_common_tests_CompilerTest_NoConstructorDependency::class);

		$compiler = new Compiler($container);

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testIntersectionTypesCannotBeCompiled(): void {
		$this->expectException(CannotAutowireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_CompilerTest_DependencyWithIntersectionType::__construct\': Parameter #0: param: Cannot auto-wire intersection types');

		$container = new Container();

		$container->wire(holonet_common_tests_CompilerTest_DependencyWithIntersectionType::class);

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testCannotAutowireUntypedParameter(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_CompilerTest_UntypedParamsDependency::__construct\': Parameter #0: param1: Can only auto-wire typed parameters');

		$container = new Container();
		$container->wire(holonet_common_tests_CompilerTest_UntypedParamsDependency::class);

		$compiler = new Compiler($container);
		$compiler->compile();
	}

	public function testCanAutowireUntypedOptionalParameter(): void {
		$container = new Container();
		$container->wire(holonet_common_tests_CompilerTest_UntypedParamOptionalDependency::class);

		$compiler = new Compiler($container);
		$this->assertNotEmpty($compiler->compile());
	}

	public function testCompilesOptionalOrNullableParameter(): void {
		$container = new Container();
		$container->wire(holonet_common_tests_CompilerTest_OptionalAndNullableParamsDependency::class);

		$compiler = new Compiler($container);

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testUnionTypeCanBeCompiled(): void
	{
		$container = new Container();
		$container->wire(holonet_common_tests_CompilerTest_UnionTypeDependency::class);

		$compiler = new Compiler($container);

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testCannotCompileNonWireableDependency(): void {
		$container = new Container();
		$container->wire(holonet_common_tests_CompilerTest_NonWireableDependency::class);

		$compiler = new Compiler($container);

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testUnionTypeNonWireable(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_CompilerTest_DependencyTest::__construct\': Parameter #0: param: Cannot auto-wire to union type \'holonet\common\tests\di\holonet_common_tests_CompilerTest_NonWireableDependency|string\'');

		$container = new Container();
		$container->wire(holonet_common_tests_CompilerTest_DependencyTest::class);

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

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$this->assertValidCompiledContainer($actual, $container->registry);
	}

	public function testCompileNamedService(): void {
		$registry = new ConfigRegistry();

		$container = new Container($registry);
		$container->set('service1', DiAnonDep::class);
		$compiler = new Compiler($container);

		$actual = $compiler->compile();

		$this->assertMatchesTextSnapshot($actual);
		$this->assertValidCompiledContainer($actual, $registry);
	}

	protected function assertValidCompiledContainer(string $code, ConfigRegistry $config): Container {
		$container = eval("{$code}");
		$this->assertTrue(str_contains(get_class($container), '@anonymous'));
		$this->assertInstanceOf(Container::class, $container);

		return $container;
	}

	protected function getSnapshotDirectory(): string {
		return dir_path(dirname(__FILE__, 2), '__snapshots__');
	}
}

class holonet_common_tests_CompilerTest_DependencyTest
{
	public function __construct(holonet_common_tests_CompilerTest_NonWireableDependency|string $param)
	{
	}
}

class holonet_common_tests_CompilerTest_NonWireableDependency
{
	public function __construct(string $param)
	{
	}
}

class holonet_common_tests_CompilerTest_UnionTypeDependency
{
	public function __construct(string|Noun $param)
	{
	}
}

class holonet_common_tests_CompilerTest_OptionalAndNullableParamsDependency
{
	public function __construct(?string $param1, string $param2 = 'default')
	{
	}
}

class holonet_common_tests_CompilerTest_UntypedParamOptionalDependency
{
	public function __construct($param1 = null)
	{
	}
}

class holonet_common_tests_CompilerTest_UntypedParamsDependency
{
	public function __construct($param1, $param2 = null)
	{
	}
}

class holonet_common_tests_CompilerTest_NoConstructorDependency
{
}

class holonet_common_tests_CompilerTest_DependencyWithIntersectionType
{
	public function __construct(Stringable&Countable $param)
	{
	}
}

class holonet_common_tests_CompilerTest_ForwardParamDependency
{
	public function __construct(holonet_common_tests_CompilerTest_NoConstructorDependency $dependency, string $testParam, string $testParamTwo, int $value = 5)
	{
	}
}
