<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\di;

use holonet\common\di\error\DependencyInjectionException;
use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;
use holonet\common\di\autowire\AutoWire;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\error\BadEnvironmentException;
use holonet\common\verifier\rules\string\MaxLength;
use holonet\common\di\autowire\attribute\ConfigItem;
use holonet\common\di\autowire\provider\ConfigAutoWireProvider;

#[CoversClass(Container::class)]
#[CoversClass(ConfigAutoWireProvider::class)]
#[CoversClass(AutoWire::class)]
#[CoversClass(ConfigItem::class)]
class ConfigAutoWireProviderTest extends TestCase {
	public function test_config_item_injection_key_in_arguments(): void {
		$container = new Container();

		$container->registry->set('service.config', array('stringValue' => 'test'));

		$result = $container->instance(holonet_common_tests_Dependency::class, array('config' => 'service.config'));

		$this->assertSame('test', $result->config->stringValue);
	}

	public function test_config_item_injection_key_in_attribute(): void {
		$container = new Container();

		$container->registry->set('service.other', array('stringValue' => 'test'));

		$result = $container->instance(holonet_common_tests_OtherDependency::class);

		$this->assertSame('test', $result->config->stringValue);
	}

	public function test_config_item_is_verified(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'service.config.stringValue\': stringValue must be at most 10 characters long');

		$container = new Container();

		$container->registry->set('service.config', array('stringValue' => 'test_longer_than_10'));

		$container->instance(holonet_common_tests_OtherDependency::class, array('config' => 'service.config'));
	}

	public function test_inject_array_config_value(): void {
		$container = new Container();

		$value = array('test', 'cool');
		$container->registry->set('config.just_an_array_value', $value);

		$result = $container->instance(holonet_common_tests_ServiceWithArrayConfigValue::class);

		$this->assertSame($value, $result->value);
	}

	public function test_inject_string_config_value(): void {
		$container = new Container();

		$container->registry->set('config.just_a_string_value', 'configured method');

		$result = $container->instance(holonet_common_tests_ServiceWithStringConfigValue::class);

		$this->assertSame('configured method', $result->value);
	}

	public function test_property_without_attribute_is_ignored(): void {
		$container = new Container();

		$result = $container->instance(holonet_common_tests_ClassWithoutAttribute::class);

		$this->assertNotNull($result);
	}

	public function test_error_user_must_supply_config_key_for_injection(): void {
		$this->expectException(DependencyInjectionException::class);
		$this->expectExceptionMessage('Failed to auto-wire \'holonet\common\tests\di\holonet_common_tests_Dependency::__construct\': Parameter #0: config: Cannot auto-wire to a config dto object without supplying a config key');

		$container = new Container();

		$container->instance(holonet_common_tests_Dependency::class);
	}
}

class holonet_common_tests_ClassWithoutAttribute {
	public function __construct(holonet_common_tests_Simple $config) {
	}
}

class holonet_common_tests_Simple {
}

class holonet_common_tests_Dependency {
	public function __construct(
		#[ConfigItem(verified: false)]
		public holonet_common_tests_Config $config
	) {
	}
}

class holonet_common_tests_OtherDependency {
	public function __construct(
		#[ConfigItem(key: 'service.other')]
		public holonet_common_tests_Config $config
	) {
	}
}

class holonet_common_tests_ServiceWithStringConfigValue {
	public function __construct(
		#[ConfigItem(key: 'config.just_a_string_value')]
		public string $value
	) {
	}
}

class holonet_common_tests_ServiceWithArrayConfigValue {
	public function __construct(
		#[ConfigItem(key: 'config.just_an_array_value')]
		public array $value
	) {
	}
}

class holonet_common_tests_Config {
	public function __construct(
		#[MaxLength(10)]
		public string $stringValue,
		public array $array = array()) {
	}
}
