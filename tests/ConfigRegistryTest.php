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
use holonet\common\config\ConfigRegistry;
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

	public function testEnvPlaceholderReplacement(): void {
		$registry = new ConfigRegistry();
		$registry->setAll(array(
			'test' => '%env(ENV_VALUE)%',
			'test2' => '%env(ENV_VALUE_2)%'
		));

		$this->assertSame('cool env config value', $registry->get('test'));
		$this->assertSame('test value', $registry->get('test2'));
	}

	public function testNormalPlaceholdersStillWork(): void {
		$registry = new ConfigRegistry();
		$registry->setAll(array(
			'test' => '%test_prop%',
			'test_prop' => 'cool'
		));

		$this->assertSame('cool', $registry->get('test'));
	}

	public function testVerifiedDto(): void {
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

	public function testVerifiedDtoTypeError(): void {
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

	public function testVerifiedDtoUsingClass(): void {
		$registry = new ConfigRegistry();
		$registry->set('test', 'amazing value');

		$dto = $registry->verifiedDto('test', TestDto::class);
		$this->assertSame('amazing value', $dto->value);
	}
}

class TestDto {
	public function __construct(
		#[MinLength(4)]
		public string $value
	) {
	}
}
