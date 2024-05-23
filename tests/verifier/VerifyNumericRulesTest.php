<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\verifier;

use function holonet\common\verify;
use holonet\common\verifier\Verifier;
use holonet\common\verifier\rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\verifier\rules\numeric\Between;
use holonet\common\verifier\rules\numeric\Maximum;
use holonet\common\verifier\rules\numeric\Minimum;
use holonet\common\verifier\rules\numeric\Numeric;

#[CoversClass(Verifier::class)]
#[CoversClass(Rule::class)]
#[CoversClass(Between::class)]
#[CoversClass(Maximum::class)]
#[CoversClass(Minimum::class)]
#[CoversClass(Numeric::class)]
class VerifyNumericRulesTest extends BaseVerifyTest {
	public function test_check_between(): void {
		$test = new class(22) {
			public function __construct(
				#[Between(1, 6)]
				public int $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be between 1 and 6');

		$test->testProp = 3;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_maximum(): void {
		$test = new class(22) {
			public function __construct(
				#[Maximum(8)]
				public float $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be less or equal to 8');

		$test->testProp = 4;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_minimum(): void {
		$test = new class(1) {
			public function __construct(
				#[Minimum(8)]
				public float $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be greater or equal to 8');

		$test->testProp = 10;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_numeric(): void {
		$test = new class('values') {
			public function __construct(
				#[Numeric]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be numeric');

		$test->testProp = '24.15';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_custom_message(): void {
		$test = new class(5, 'cool') {
			public function __construct(
				#[Between(6, 10, message: 'no less than :low, no more than :high')]
				#[Maximum(4, message: 'no seriously :attr cannot be more than :max')]
				#[Minimum(8, message: ':attr must be more than :min')]
				public int $testProp,

				#[Numeric(message: ':attr NUMERIC pls')]
				public string $other
			) {
			}
		};

		$proof = verify($test);
		$this->assertFalse($proof->pass());

		$this->assertFalse($proof->passed('testProp'));
		$this->assertProofContainsError($proof, 'testProp', 'no less than 6, no more than 10');
		$this->assertProofContainsError($proof, 'testProp', 'no seriously testProp cannot be more than 4');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be more than 8');

		$this->assertFalse($proof->passed('other'));
		$this->assertProofContainsError($proof, 'other', 'other NUMERIC pls');
	}

	public function test_array_of_values(): void {
		$test = new class([3, 6, 1, 'test']) {
			public function __construct(
				#[Between(1, 6)]
				#[Maximum(5)]
				#[Minimum(2)]
				#[Numeric]
				public array $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofPassed($proof, 'testProp.0');
		$this->assertProofFailedForAttribute($proof, 'testProp.1');
		$this->assertProofFailedForAttribute($proof, 'testProp.2');
		$this->assertProofFailedForAttribute($proof, 'testProp.3');

		$this->assertProofContainsError($proof, 'testProp.1', 'testProp.1 must be less or equal to 5');
		$this->assertProofContainsError($proof, 'testProp', '[1]: testProp.1 must be less or equal to 5');
		$this->assertProofContainsError($proof, 'testProp.2', 'testProp.2 must be greater or equal to 2');
		$this->assertProofContainsError($proof, 'testProp', '[2]: testProp.2 must be greater or equal to 2');
		$this->assertProofContainsError($proof, 'testProp.3', 'testProp.3 must be numeric');
		$this->assertProofContainsError($proof, 'testProp', '[3]: testProp.3 must be numeric');
	}


}
