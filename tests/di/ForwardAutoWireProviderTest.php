<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\di\autowire\AutoWireException;
use holonet\common\di\DependencyInjectionException;
use holonet\common\di\autowire\provider\ForwardAutoWireProvider;

#[CoversClass(Container::class)]
#[CoversClass(ForwardAutoWireProvider::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(AutoWireException::class)]
#[CoversClass(DependencyInjectionException::class)]
class ForwardAutoWireProviderTest extends TestCase {
	public function testBasicScalarParameterForwarding(): void {
		$container = new Container();

		$params = array(
			'string' => 'gojsdgoisjdgio',
			'int' => 5,
			'float' => 10.5,
			'boolean' => true,
			'array' => array('value1', 'value2')
		);

		$result = $container->make(DependencyForwardAutoWire::class, $params);

		$this->assertSame($params, get_object_vars($result));
	}

	public function testObjectTypesForwarding(): void {
		$container = new Container();

		$apples = new Apples();
		$result = $container->make(DependencyWithObjectParam::class, array('apples' => $apples));

		$this->assertSame($apples, $result->apples);
	}

	public function testObjectTypesForwardingUnionTypes(): void {
		$container = new Container();

		$apples = new Apples();
		$other = new DependencyWithUnionTypeHints();

		$one = $container->make(DependencyWithUnionTypeHintsObjects::class, array('other' => $apples));
		$two = $container->make(DependencyWithUnionTypeHintsObjects::class, array('other' => $other));

		$this->assertSame($apples, $one->other);
		$this->assertSame($other, $two->other);
	}

	public function testScalarUnionTypesForwarding(): void {
		$container = new Container();

		$one = $container->make(DependencyWithUnionTypeHints::class, array('testUnion' => 'string_value'));
		$two = $container->make(DependencyWithUnionTypeHints::class, array('testUnion' => 5.4));

		$this->assertSame('string_value', $one->testUnion);
		$this->assertSame(5.4, $two->testUnion);
	}
}

class DependencyForwardAutoWire {
	public function __construct(public string $string, public int $int, public float $float, public bool $boolean, public array $array) {
	}
}

class DependencyWithObjectParam {
	public function __construct(public Apples $apples) {
	}
}

class Apples {
}

class DependencyWithUnionTypeHints {
	public function __construct(public string|float $testUnion = 5.0) {
	}
}

class DependencyWithUnionTypeHintsObjects {
	public function __construct(public Apples|DependencyWithUnionTypeHints $other) {
	}
}
