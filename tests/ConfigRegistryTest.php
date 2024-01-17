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
use holonet\common\collection\Registry;
use holonet\common\collection\ConfigRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use holonet\common\error\BadEnvironmentException;
use holonet\common\verifier\rules\string\MinLength;
use holonet\common\verifier\rules\string\ExactLength;

#[CoversClass(ConfigRegistry::class)]
#[CoversClass(Registry::class)]
#[CoversFunction('holonet\common\dot_key_get')]
#[CoversFunction('holonet\common\dot_key_set')]
class ConfigRegistryTest extends TestCase {
	protected function setUp(): void {
		$_ENV['ENV_VALUE'] = 'cool env config value';
		putenv('ENV_VALUE_2=test value');
	}

	protected function tearDown(): void {
		unset($_ENV['ENV_VALUE']);
		putenv('ENV_VALUE_2=');
	}

	public function test_env_placeholder_gets_replaced(): void {
		$registry = new ConfigRegistry();
		$registry->setAll(array(
			'test' => '%env(ENV_VALUE)%',
			'test2' => '%env(ENV_VALUE_2)%'
		));

		$this->assertSame('cool env config value', $registry->get('test'));
		$this->assertSame('test value', $registry->get('test2'));
	}

	public function test_normal_placeholders_still_work(): void {
		$registry = new ConfigRegistry();
		$registry->setAll(array(
			'test' => '%test_prop%',
			'test_prop' => 'cool'
		));

		$this->assertSame('cool', $registry->get('test'));
	}

	public function test_config_data_can_be_fetched_as_a_verified_dto_object(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'test.testProp\': testProp must be exactly 11 characters long');

		$dto = new class() {
			public function __construct(
				#[ExactLength(11)]
				public string $testProp = ''
			) {
			}
		};

		$registry = new ConfigRegistry();
		$registry->set('test', array('testProp' => 'cool val'));

		$registry->verifiedDto('test', $dto);
	}

	public function test_faulty_config_data_fetch_with_dto_object_throws_type_error(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'test\': TypeError: Cannot assign array to property class@anonymous::$testProp of type string');

		$dto = new class() {
			public function __construct(
				#[ExactLength(11)]
				public string $testProp = ''
			) {
			}
		};

		$registry = new ConfigRegistry();
		$registry->set('test', array('testProp' => array('cool')));

		$registry->verifiedDto('test', $dto);
	}

	public function test_config_data_can_be_fetched_using_an_actual_dto_object_class(): void {
		$registry = new ConfigRegistry();
		$registry->set('test', 'amazing value');

		$dto = $registry->verifiedDto('test', holonet_common_tests_ConfigRegistryTest_TestDto::class);
		$this->assertSame('amazing value', $dto->value);
	}

	public function test_accessing_a_non_existing_key_will_throw_a_helpful_exception(): void {
		$this->expectException(BadEnvironmentException::class);
		$this->expectExceptionMessage('Faulty config with key \'test\': Config item doesn\'t exist');

		$registry = new ConfigRegistry();
		$registry->asDto('test', holonet_common_tests_ConfigRegistryTest_TestDto::class);
	}

	public function test_having_an_unset_environment_placeholder_in_a_value_defaults_to_empty_string(): void {
		$registry = new ConfigRegistry();
		$registry->setAll(array(
			'test' => '%env(ENV_VALUE_3)%',
		));

		$this->assertSame('', $registry->get('test'));
	}

}

class holonet_common_tests_ConfigRegistryTest_TestDto {
	public function __construct(
		#[MinLength(4)]
		public string $value
	) {
	}
}
