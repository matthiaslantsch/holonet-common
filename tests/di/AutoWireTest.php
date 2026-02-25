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

#[CoversClass(Container::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(DependencyInjectionException::class)]
class AutoWireTest extends TestCase {
	public function test_error_typeless_parameters_throw_an_exception(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage(<<<'MSG'
			Failed to instantiate 'holonet\common\tests\di\holonet_common_tests_TypelessClass':
			Failed to auto-wire 'holonet\common\tests\di\holonet_common_tests_TypelessClass::__construct': Parameter #0: test: Can only auto-wire typed parameters
			MSG
		);

		$container = new Container();

		$container->instance(holonet_common_tests_TypelessClass::class);
	}

	public function test_error_union_types_failed_injection(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage(<<<'MSG'
			Failed to instantiate 'holonet\common\tests\di\holonet_common_tests_UnionTypesMultipleFailures':
			Failed to auto-wire 'holonet\common\tests\di\holonet_common_tests_UnionTypesMultipleFailures::__construct': Parameter #0: service: Cannot auto-wire to union type 'holonet\common\tests\di\holonet_common_tests_SomeService|holonet\common\tests\di\holonet_common_tests_SomeServiceTwo':
			Failed to instantiate 'holonet\common\tests\di\holonet_common_tests_SomeService':
			Failed to auto-wire 'holonet\common\tests\di\holonet_common_tests_SomeService::__construct': Parameter #0: parameter: Cannot auto-wire to type 'string'
			Failed to instantiate 'holonet\common\tests\di\holonet_common_tests_SomeServiceTwo':
			Failed to auto-wire 'holonet\common\tests\di\holonet_common_tests_SomeServiceTwo::__construct': Parameter #0: parameter: Cannot auto-wire to type 'string'
			MSG
		);

		$container = new Container();

		$container->instance(holonet_common_tests_UnionTypesMultipleFailures::class);
	}
}
