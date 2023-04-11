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
use holonet\common\error\BadEnvironmentException;
use holonet\common\di\DependencyInjectionException;
use holonet\common\verifier\rules\string\MaxLength;
use holonet\common\di\autowire\attribute\ConfigItem;
use holonet\common\di\autowire\provider\ConfigAutoWireProvider;

#[CoversClass(Container::class)]
#[CoversClass(ConfigAutoWireProvider::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(ConfigItem::class)]
class ConfigAutoWireProviderTest extends TestCase {
	public function testConfigItemInjectionKeyInArguments(): void {
		$container = new Container();

		$container->registry->set('service.config', array('stringValue' => 'test'));

		$result = $container->make(Dependency::class, array('config' => 'service.config'));

		$this->assertSame('test', $result->config->stringValue);
	}

	public function testConfigItemInjectionKeyInAttribute(): void {
		$container = new Container();

		$container->registry->set('service.other', array('stringValue' => 'test'));

		$result = $container->make(OtherDependency::class);

		$this->assertSame('test', $result->config->stringValue);
	}

	public function testConfigItemIsVerified(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'service.config.stringValue\': stringValue must be at most 10 characters long');

		$container = new Container();

		$container->registry->set('service.config', array('stringValue' => 'test_longer_than_10'));

		$container->make(OtherDependency::class, array('config' => 'service.config'));
	}

	public function testInjectArrayConfigValue(): void {
		$container = new Container();

		$value = array('test', 'cool');
		$container->registry->set('config.just_an_array_value', $value);

		$result = $container->make(ServiceWithArrayConfigValue::class);

		$this->assertSame($value, $result->value);
	}

	public function testInjectStringConfigValue(): void {
		$container = new Container();

		$container->registry->set('config.just_a_string_value', 'configured method');

		$result = $container->make(ServiceWithStringConfigValue::class);

		$this->assertSame('configured method', $result->value);
	}

	public function testPropertyWithoutAttributeIsIgnored(): void {
		$container = new Container();

		$result = $container->make(ClassWithoutAttribute::class);

		$this->assertNotNull($result);
	}

	public function testUserMustSupplyConfigKeyForInjection(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\Dependency::__construct\': Parameter #0: config: Cannot auto-wire to a config dto object without supplying a config key');

		$container = new Container();

		$container->make(Dependency::class);
	}
}

class ClassWithoutAttribute {
	public function __construct(Simple $config) {
	}
}

class Simple {
}

class Dependency {
	public function __construct(
		#[ConfigItem(verified: false)]
		public Config $config
	) {
	}
}

class OtherDependency {
	public function __construct(
		#[ConfigItem(key: 'service.other')]
		public Config $config
	) {
	}
}

class ServiceWithStringConfigValue {
	public function __construct(
		#[ConfigItem(key: 'config.just_a_string_value')]
		public string $value
	) {
	}
}

class ServiceWithArrayConfigValue {
	public function __construct(
		#[ConfigItem(key: 'config.just_an_array_value')]
		public array $value
	) {
	}
}

class Config {
	public function __construct(
		#[MaxLength(10)]
		public string $stringValue,
		public array $array = array()) {
	}
}
