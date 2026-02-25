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
use holonet\common\di\autowire\AutoWire;
use holonet\common\di\Container;
use holonet\common\di\error\AutoWireException;
use holonet\common\di\error\DependencyInjectionException;
use holonet\common\di\error\DependencyNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stringable;

#[CoversClass(Container::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(DependencyInjectionException::class)]
class ContainerTest extends TestCase {

	public function test_abstract_resolution_variants_service(): void {
		// concrete mapped by an interface => that interface mapped by a service id
		$container = new Container();
		$obj = new holonet_common_tests_TestClass('test');
		$container->set('test', $obj);
		$container->contract(holonet_common_tests_MyInterface::class, holonet_common_tests_TestClass::class);
		$container->alias('test', holonet_common_tests_MyInterface::class);

		// get only works with the service id
		$this->assertSame($obj, $container->get('test'));

		// instance should work with all typehints / wirings
		$this->assertSame($obj, $container->instance(holonet_common_tests_MyInterface::class));
		$this->assertSame($obj, $container->instance('test'));
		$this->assertSame($obj, $container->instance(holonet_common_tests_TestClass::class));
	}

	public function test_injection_with_constructor(): void {
		$container = new Container();
		$container->set('anonDep', holonet_common_tests_DiAnonDep::class);
		$container->set('anonClassTwo', holonet_common_tests_DiAnonClassTwo::class);

		$this->assertSame('test', $container->get('anonClassTwo')->test);
	}

	public function test_intersection_types_cannot_be_autowired(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage(<<<'MSG'
		Failed to instantiate 'holonet\common\tests\di\holonet_common_tests_IntersectionTypes':
		Failed to auto-wire 'holonet\common\tests\di\holonet_common_tests_IntersectionTypes::__construct': Parameter #0: intersection: Cannot auto-wire intersection types
		MSG);

		$container = new Container();

		$container->instance(holonet_common_tests_IntersectionTypes::class);
	}

	public function test_lazy_load_returns_one_instance(): void {
		$container = new Container();
		$container->set('anonDep', holonet_common_tests_DiAnonDep::class);

		$one = $container->get('anonDep');
		$two = $container->get('anonDep');

		$this->assertSame($one, $two);
	}

	public function test_instance_called_with_just_an_interface(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('No idea how to make \'holonet\common\tests\di\holonet_common_tests_MyInterface\'. Class does not exist and no wire directive was set');

		$container = new Container();

		$container->instance(holonet_common_tests_MyInterface::class);
	}

	public function test_instance_returning_given_instances_if_available(): void {
		$container = new Container();

		$override = new holonet_common_tests_DiAnonDep();
		$container->set(holonet_common_tests_DiAnonDep::class, $override);

		$this->assertSame($override, $container->instance(holonet_common_tests_DiAnonDep::class));
	}

	public function test_instance_returns_new_instance_every_call(): void {
		$container = new Container();

		$one = $container->instance(holonet_common_tests_DiAnonDep::class);
		$two = $container->instance(holonet_common_tests_DiAnonDep::class);

		$this->assertNotSame($one, $two);
	}

	public function test_instance_throws_error_if_constructor_arguments_are_not_given(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_SomeService::__construct\': Parameter #0: parameter: Cannot auto-wire to type \'string\'');

		$container = new Container();

		$container->instance(holonet_common_tests_SomeService::class);
	}

	public function test_instance_throws_error_if_parameters_are_given_for_abstract_without_constructor(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_DiAnonDep\': Has no constructor, but 1 parameters were given');

		$container = new Container();

		$container->instance(holonet_common_tests_DiAnonDep::class, array('cool'));
	}

	public function test_optional_typeless_parameters_are_ignored(): void {
		$container = new Container();

		$result = $container->instance(holonet_common_tests_TypelessClassOptional::class);

		$this->assertSame('test', $result->test);
	}

	public function test_wire_parameters_get_applied_wiring(): void {
		$container = new Container();

		$container->wire(holonet_common_tests_SomeService::class, array('parameter' => 'test'));

		$one = $container->instance(holonet_common_tests_SomeService::class);
		$two = $container->instance(holonet_common_tests_SomeService::class);

		$this->assertNotSame($one, $two);
		$this->assertTrue($one->parameter === $two->parameter);
	}

	public function test_wiring_an_interface_to_an_implementation(): void {
		$container = new Container();

		// first add an alias for the interface
		$container->alias('test_interface', holonet_common_tests_MyInterface::class);

		// then add an implementation contract while wiring said object
		$container->wire(holonet_common_tests_TestClass::class, array('value' => 'cool'), holonet_common_tests_MyInterface::class);

		$this->assertInstanceOf(holonet_common_tests_TestClass::class, $container->instance(holonet_common_tests_MyInterface::class));
		$this->assertInstanceOf(holonet_common_tests_TestClass::class, $container->instance('test_interface'));
		$this->assertInstanceOf(holonet_common_tests_TestClass::class, $container->instance(holonet_common_tests_TestClass::class));
	}

	public function test_wiring_an_abstract_class_to_an_implementation(): void {
		$container = new Container();

		// first add an alias for the abstract class
		$container->alias('test_abstract', holonet_common_tests_AbstractBaseClass::class);

		// then add an implementation contract while wiring said object
		$container->wire(holonet_common_tests_TestClass::class, array('value' => 'cool'), holonet_common_tests_AbstractBaseClass::class);

		$this->assertInstanceOf(holonet_common_tests_TestClass::class, $container->instance(holonet_common_tests_AbstractBaseClass::class));
		$this->assertInstanceOf(holonet_common_tests_TestClass::class, $container->instance('test_abstract'));
		$this->assertInstanceOf(holonet_common_tests_TestClass::class, $container->instance(holonet_common_tests_TestClass::class));
	}

	public function test_multiple_instances_of_same_service(): void {
		$one = new holonet_common_tests_DiAnonDep();
		$two = new holonet_common_tests_DiAnonDep();

		$this->assertNotSame($one, $two);

		$container = new Container();
		$container->set('config_one', $one);
		$container->set('config_two', $two);

		// both should be available by their ids
		$this->assertSame($one, $container->get('config_one'));
		$this->assertSame($two, $container->get('config_two'));

		// if accessing by type without supplying a name hint, an exception should be thrown
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed getting instance of type \'holonet\common\tests\di\holonet_common_tests_DiAnonDep\'. Ambiguous dependencies: \'config_one\', \'config_two\'.');
		$container->instance(holonet_common_tests_DiAnonDep::class);
	}

	public function test_error_wire_non_existing_class_throws_error(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Abstract \'\nonsense\class\TestClass\' is not a class or configured alias.');

		$container = new Container();

		$container->wire('\\nonsense\\class\\TestClass');
	}

	public function test_error_set_nonsense_as_service(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Abstract \'\nonsense\class\TestClass\' is not a class or configured alias.');

		$container = new Container();

		$container->set('nonsense', '\\nonsense\\class\\TestClass');
	}

	public function test_error_get_non_existing_dependency(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage("'kaudermelsh' is not a valid abstract (neither alias nor implementation contract)");

		$container = new Container();
		$container->get('kaudermelsh');
	}

	public function test_error_recursion_detection_instance_call(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Recursive dependency definition detected: holonet\common\tests\di\holonet_common_tests_RecursionA => holonet\common\tests\di\holonet_common_tests_RecursionB => holonet\common\tests\di\holonet_common_tests_RecursionC');

		$container = new Container();

		$container->instance(holonet_common_tests_RecursionA::class);
	}

	public function test_error_recursion_detection_service_get(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Recursive dependency definition detected: A => B => C => A');

		$container = new Container();

		$container->set('A', holonet_common_tests_RecursionA::class);
		$container->set('B', holonet_common_tests_RecursionB::class);
		$container->set('C', holonet_common_tests_RecursionC::class);

		$container->get('A');
	}

}

class holonet_common_tests_RecursionA {
	public function __construct(holonet_common_tests_RecursionB $recursionB) {
	}
}

class holonet_common_tests_RecursionB {
	public function __construct(holonet_common_tests_RecursionC $recursionC) {
	}
}

class holonet_common_tests_RecursionC {
	public function __construct(holonet_common_tests_RecursionA $recursionA) {
	}
}

class holonet_common_tests_UnionTypesMultipleFailures {
	public function __construct(holonet_common_tests_SomeService|holonet_common_tests_SomeServiceTwo $service) {
	}
}

class holonet_common_tests_IntersectionTypes {
	public function __construct(Countable&Stringable $intersection) {
	}
}

class holonet_common_tests_TypelessClass {
	public function __construct(public $test) {
	}
}

class holonet_common_tests_TypelessClassOptional {
	public function __construct(public $test = 'test') {
	}
}

class holonet_common_tests_DiAnonClassTwo {
	public string $test;

	public function __construct(public holonet_common_tests_DiAnonDep $anonDep) {
		$this->test = $this->anonDep->test();
	}
}

class holonet_common_tests_DiAnonDep {
	public function test(): string {
		return 'test';
	}
}

class holonet_common_tests_SomeService {
	public function __construct(public string $parameter) {
	}
}

class holonet_common_tests_SomeServiceTwo {
	public function __construct(public string $parameter) {
	}
}

abstract class holonet_common_tests_AbstractBaseClass {
}

class holonet_common_tests_TestClass extends holonet_common_tests_AbstractBaseClass implements holonet_common_tests_MyInterface {
	public function __construct(public string $value) {
	}
}

interface holonet_common_tests_MyInterface {
}
