<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\verifier;

use Attribute;
use PHPUnit\Framework\TestCase;
use holonet\common\verifier\Proof;
use function holonet\common\verify;
use function holonet\common\stringify;
use holonet\common\verifier\rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\verifier\rules\CheckValueRuleInterface;
use holonet\common\verifier\rules\TransformValueRuleInterface;

#[CoversClass(Rule::class)]
#[CoversClass(CheckValueRuleInterface::class)]
#[CoversClass(TransformValueRuleInterface::class)]
class BaseVerifyTest extends TestCase {
	public function assertProofContainsError(Proof $actual, string $attr, string $error): void {
		$this->assertContains($error, $actual->flat(), sprintf('Instead contains: %s', stringify($actual->flat())));
		$this->assertContains($error, $actual->attr($attr), sprintf('Instead contains: %s', stringify($actual->attr($attr))));
	}

	public function assertProofFailedForAttribute(Proof $actual, string $attr): void {
		$this->assertFalse($actual->passed($attr), "Failed asserting that verification for '{$attr}' didn't pass");
	}

	public function assertProofPassed(Proof $actual, string $attr): void {
		$failMessage = "Failed asserting that verification for '%s' passed; Got errors: %s";
		$this->assertTrue($actual->passed($attr), sprintf($failMessage, $attr, stringify($actual->attr($attr))));
	}

	public function test_base_default_message(): void {
		$test = new class() {
			public function __construct(
				#[holonet_common_tests_verifier_BaseVerifyTest_Invalid]
				public string $testProp = 'default'
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp is invalid');
	}

	public function test_non_rule_attribute_is_ignored(): void {
		$test = new class() {
			public function __construct(
				#[holonet_common_tests_verifier_BaseVerifyTest_TestAttribute]
				public string $testProp = 'default'
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_transforms_rule(): void {
		$test = new class() {
			public function __construct(
				#[holonet_common_tests_verifier_BaseVerifyTest_SlugifyAttribute]
				public string $testProp = 'This Is A normal Sentence'
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
		$this->assertSame('this-is-a-normal-sentence', $test->testProp);
	}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class holonet_common_tests_verifier_BaseVerifyTest_Invalid extends Rule implements CheckValueRuleInterface {
	public function pass(mixed $value): bool {
		return false;
	}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class holonet_common_tests_verifier_BaseVerifyTest_SlugifyAttribute extends Rule implements TransformValueRuleInterface {
	public function transform(mixed $value): mixed {
		return mb_strtolower(str_replace(' ', '-', $value));
	}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class holonet_common_tests_verifier_BaseVerifyTest_TestAttribute {
}
