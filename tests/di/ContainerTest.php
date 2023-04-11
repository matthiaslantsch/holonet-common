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
use Stringable;
use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\di\autowire\AutoWireException;
use holonet\common\di\DependencyNotFoundException;
use holonet\common\di\DependencyInjectionException;

#[CoversClass(Container::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(DependencyInjectionException::class)]
class ContainerTest extends TestCase {
	public function testGetNonExistingDependency(): void {
		$this->expectException(DependencyNotFoundException::class);
		$this->expectExceptionMessage("Container has no named dependency called 'kaudermelsh'");

		$container = new Container();
		$container->get('kaudermelsh');
	}

	public function testInjectionWithConstructor(): void {
		$container = new Container();
		$container->set('anonDep', DiAnonDep::class);
		$container->set('anonClassTwo', DiAnonClassTwo::class);

		$this->assertSame('test', $container->get('anonClassTwo')->test);
	}

	public function testIntersectionTypesCannotBeAutoWired(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\IntersectionTypes::__construct\': Parameter #0: intersection: Cannot auto-wire intersection types');

		$container = new Container();

		$container->make(IntersectionTypes::class);
	}

	public function testLazyLoadReturnsOneInstance(): void {
		$container = new Container();
		$container->set('anonDep', DiAnonDep::class);

		$one = $container->get('anonDep');
		$two = $container->get('anonDep');

		$this->assertSame($one, $two);
	}

	public function testMakeCalledWithJustAnInterface(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('No idea how to make \'holonet\common\tests\di\MyInterface\'. Class does not exist and no wire directive was set');

		$container = new Container();

		$container->make(MyInterface::class);
	}

	public function testMakeReturningGivenInstancesIfAvailable(): void {
		$container = new Container();

		$override = new DiAnonDep();
		$container->set(DiAnonDep::class, $override);

		$this->assertSame($override, $container->make(DiAnonDep::class));
	}

	public function testMakeReturnsNewInstanceEveryCall(): void {
		$container = new Container();

		$one = $container->make(DiAnonDep::class);
		$two = $container->make(DiAnonDep::class);

		$this->assertNotSame($one, $two);
	}

	public function testMakeThrowsErrorIfConstructorArgumentsAreNotGiven(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\SomeService::__construct\': Parameter #0: parameter: Cannot auto-wire to type \'string\'');

		$container = new Container();

		$container->make(SomeService::class);
	}

	public function testMakeThrowsErrorIfParametersAreGivenForConstructorLessAbstract(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\DiAnonDep\': Has no constructor, but 1 parameters were given');

		$container = new Container();

		$container->make(DiAnonDep::class, array('cool'));
	}

	public function testMultipleInstancesOfServiceBothAvailable(): void {
		$one = new DiAnonDep();
		$two = new DiAnonDep();

		$this->assertNotSame($one, $two);

		$container = new Container();
		$container->set('config_one', $one);
		$container->set('config_two', $two);

		// both should be available by their ids
		$this->assertSame($one, $container->get('config_one'));
		$this->assertSame($two, $container->get('config_two'));

		// both should be available by their type when supplying a name hint
		$this->assertSame($one, $container->byType(DiAnonDep::class, 'config_one'));
		$this->assertNotSame($two, $container->byType(DiAnonDep::class, 'config_one'));
		$this->assertSame($one, $container->byType(DiAnonDep::class, 'config_one'));
		$this->assertNotSame($two, $container->byType(DiAnonDep::class, 'config_one'));

		// if accessing by type without supplying a name hint, an exception should be thrown
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Ambiguous dependency of type \'holonet\common\tests\di\DiAnonDep\' requested: found 2 dependencies of that type');
		$container->byType(DiAnonDep::class);
	}

	public function testOptionalTypelessParametersAreIgnored(): void {
		$container = new Container();

		$result = $container->make(TypelessClassOptional::class);

		$this->assertSame('test', $result->test);
	}

	public function testRecursionDetectionMake(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Recursive dependency definition detected: holonet\common\tests\di\RecursionA => holonet\common\tests\di\RecursionB => holonet\common\tests\di\RecursionC');

		$container = new Container();

		$container->make(RecursionA::class);
	}

	public function testRecursionDetectionServiceGet(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Recursive dependency definition detected: A => B => C');

		$container = new Container();

		$container->set('A', RecursionA::class);
		$container->set('B', RecursionB::class);
		$container->set('C', RecursionC::class);

		$container->get('A');
	}

	public function testTypelessParametersThrowAnException(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\TypelessClass::__construct\': Parameter #0: test: Can only auto-wire typed parameters');

		$container = new Container();

		$container->make(TypelessClass::class);
	}

	public function testUnionTypesFailedInjection(): void {
		$this->expectException(AutoWireException::class);
		$this->expectExceptionMessage(<<<'Message'
		Failed to auto-wire 'holonet\common\tests\di\UnionTypesMultipleFailures::__construct': Parameter #0: service: Cannot auto-wire to union type 'holonet\common\tests\di\SomeService|holonet\common\tests\di\SomeServiceTwo': 
		Failed to auto-wire 'holonet\common\tests\di\SomeService::__construct': Parameter #0: parameter: Cannot auto-wire to type 'string'
		Failed to auto-wire 'holonet\common\tests\di\SomeServiceTwo::__construct': Parameter #0: parameter: Cannot auto-wire to type 'string'
		Message
		);

		$container = new Container();

		$container->make(UnionTypesMultipleFailures::class);
	}

	public function testWireNonExistingClassThrowsError(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Could not auto-wire abstract \'\nonsense\class\TestClass\': class does not exist');

		$container = new Container();

		$container->wire('\\nonsense\\class\\TestClass');
	}

	public function testWireParametersGetAppliedWiring(): void {
		$container = new Container();

		$container->wire(SomeService::class, array('parameter' => 'test'));

		$one = $container->make(SomeService::class);
		$two = $container->make(SomeService::class);

		$this->assertNotSame($one, $two);
		$this->assertTrue($one->parameter === $two->parameter);
	}

	public function testWiringAnInterfaceToAnImplementation(): void {
		$container = new Container();

		$container->wire(TestClass::class, abstract: MyInterface::class);
		$container->wire(TestClass::class, abstract: AbstractBaseClass::class);

		$this->assertInstanceOf(TestClass::class, $container->make(MyInterface::class));
		$this->assertInstanceOf(TestClass::class, $container->make(AbstractBaseClass::class));
	}

	public function testSetNonsenseAsService(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Could not set dependency \'nonsense\': value is not an object or class name');

		$container = new Container();

		$container->set('nonsense', '\\nonsense\\class\\TestClass');
	}
}

class RecursionA {
	public function __construct(RecursionB $recursionB) {
	}
}

class RecursionB {
	public function __construct(RecursionC $recursionC) {
	}
}

class RecursionC {
	public function __construct(RecursionA $recursionA) {
	}
}

class UnionTypesMultipleFailures {
	public function __construct(SomeService|SomeServiceTwo $service) {
	}
}

class IntersectionTypes {
	public function __construct(Countable&Stringable $intersection) {
	}
}

class TypelessClass {
	public function __construct(public $test) {
	}
}

class TypelessClassOptional {
	public function __construct(public $test = 'test') {
	}
}

class DiAnonClassTwo {
	public string $test;

	public function __construct(public DiAnonDep $anonDep) {
		$this->test = $this->anonDep->test();
	}
}

class DiAnonDep {
	public function test(): string {
		return 'test';
	}
}

class SomeService {
	public function __construct(public string $parameter) {
	}
}

class SomeServiceTwo {
	public function __construct(public string $parameter) {
	}
}

abstract class AbstractBaseClass {
}

class TestClass extends AbstractBaseClass implements MyInterface {
}

interface MyInterface {
}
