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
use holonet\common\verifier\rules\ValueRule;

/**
 * @covers \holonet\common\verifier\rules\Rule
 * @covers \holonet\common\verifier\rules\ValueRule
 */
abstract class BaseVerifyTest extends TestCase {
	public function assertProofContainsError(Proof $actual, string $attr, string $error): void {
		$this->assertContains($error, $actual->flat());
		$this->assertArrayHasKey($attr, $actual->all());
	}

	public function assertProofFailedWithError(Proof $actual, string $attr, string $error): void {
		$this->assertFalse($actual->passed($attr), "Failed asserting that verification for '{$attr}' didn't pass");
		$this->assertFalse($actual->pass(), "Failed asserting that verification didn't pass");
		$this->assertProofContainsError($actual, $attr, $error);
	}

	public function assertProofPassed(Proof $actual, string $attr): void {
		$failMessage = "Failed asserting that verification for '%s' passed; Got errors: %s";
		$this->assertTrue($actual->passed($attr), sprintf($failMessage, $attr, stringify($actual->attr($attr))));
		$this->assertTrue($actual->pass(), 'Failed asserting that verification passed');
		$this->assertEmpty($actual->all());
	}

	public function testBaseDefaultMessage(): void {
		$test = new class() {
			public function __construct(
				#[Invalid]
				public string $testProp = 'default'
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp is invalid');
	}

	public function testNonRuleAttributeIsIgnored(): void {
		$test = new class() {
			public function __construct(
				#[TestAttribute]
				public string $testProp = 'default'
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Invalid extends ValueRule {
	public function pass(mixed $value): bool {
		return false;
	}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class TestAttribute {
}
