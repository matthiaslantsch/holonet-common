<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\di;

use holonet\common\di\autowire\AutoWire;
use holonet\common\di\autowire\provider\ForwardAutoWireProvider;
use holonet\common\di\Container;
use holonet\common\di\error\AutoWireException;
use holonet\common\di\error\DependencyInjectionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(ForwardAutoWireProvider::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(DependencyInjectionException::class)]
class ForwardAutoWireProviderTest extends TestCase {
	public function test_basic_scalar_parameter_forwarding(): void {
		$container = new Container();

		$params = array(
			'string' => 'gojsdgoisjdgio',
			'int' => 5,
			'float' => 10.5,
			'boolean' => true,
			'array' => array('value1', 'value2')
		);

		$result = $container->instance(holonet_common_tests_DependencyForwardAutoWire::class, $params);

		$this->assertSame($params, get_object_vars($result));
	}

	public function test_object_types_forwarding(): void {
		$container = new Container();

		$apples = new holonet_common_tests_Apples();
		$result = $container->instance(holonet_common_tests_DependencyWithObjectParam::class, array('apples' => $apples));

		$this->assertSame($apples, $result->apples);
	}

	public function test_object_types_forwarding_union_types(): void {
		$container = new Container();

		$apples = new holonet_common_tests_Apples();
		$other = new holonet_common_tests_DependencyWithUnionTypeHints();

		$one = $container->instance(holonet_common_tests_DependencyWithUnionTypeHintsObjects::class, array('other' => $apples));
		$two = $container->instance(holonet_common_tests_DependencyWithUnionTypeHintsObjects::class, array('other' => $other));

		$this->assertSame($apples, $one->other);
		$this->assertSame($other, $two->other);
	}

	public function test_scalar_union_types_forwarding(): void {
		$container = new Container();

		$one = $container->instance(holonet_common_tests_DependencyWithUnionTypeHints::class, array('testUnion' => 'string_value'));
		$two = $container->instance(holonet_common_tests_DependencyWithUnionTypeHints::class, array('testUnion' => 5.4));

		$this->assertSame('string_value', $one->testUnion);
		$this->assertSame(5.4, $two->testUnion);
	}
}

class holonet_common_tests_DependencyForwardAutoWire {
	public function __construct(public string $string, public int $int, public float $float, public bool $boolean, public array $array) {
	}
}

class holonet_common_tests_DependencyWithObjectParam {
	public function __construct(public holonet_common_tests_Apples $apples) {
	}
}

class holonet_common_tests_Apples {
}

class holonet_common_tests_DependencyWithUnionTypeHints {
	public function __construct(public string|float $testUnion = 5.0) {
	}
}

class holonet_common_tests_DependencyWithUnionTypeHintsObjects {
	public function __construct(public holonet_common_tests_Apples|holonet_common_tests_DependencyWithUnionTypeHints $other) {
	}
}
