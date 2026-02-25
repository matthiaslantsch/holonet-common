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
use holonet\common\verifier\rules\string\Pattern;
use holonet\common\verifier\rules\string\MaxLength;
use holonet\common\verifier\rules\string\MinLength;
use holonet\common\verifier\rules\string\ExactLength;
use holonet\common\verifier\rules\string\LengthBetween;

#[CoversClass(Verifier::class)]
#[CoversClass(Rule::class)]
#[CoversClass(MaxLength::class)]
#[CoversClass(MinLength::class)]
#[CoversClass(ExactLength::class)]
#[CoversClass(LengthBetween::class)]
#[CoversClass(Pattern::class)]
class VerifyStringRulesTest extends BaseVerifyTest {
	public function test_check_exact_length(): void {
		$test = new class('itsy bitsy') {
			public function __construct(
				#[ExactLength(11)]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be exactly 11 characters long');

		$test->testProp = 'abcdefghijk';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_length_between(): void {
		$test = new class('as') {
			public function __construct(
				#[LengthBetween(3, 7)]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'the length of testProp must be between 3 and 7 characters');

		$test->testProp = 'very long test string';
		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'the length of testProp must be between 3 and 7 characters');

		$test->testProp = 'etc';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_max_length(): void {
		$test = new class('values') {
			public function __construct(
				#[MaxLength(3)]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be at most 3 characters long');

		$test->testProp = 'etc';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_min_length(): void {
		$test = new class('gds') {
			public function __construct(
				#[MinLength(5)]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be at least 5 characters long');

		$test->testProp = 'five characters';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_pattern(): void {
		$test = new class('values') {
			public function __construct(
				#[Pattern('/\d+/')]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', "testProp must match pattern '/\\d+/'");

		$test->testProp = '1234567890';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_custom_message(): void {
		$test = new class('et', 'longer than 5!!') {
			public function __construct(
				#[LengthBetween(3, 7, message: 'length must be more than :low and less than :high')]
				#[MinLength(4, message: 'no seriously :attr must be longer than :min')]
				#[ExactLength(5, message: ':attr: actually exactly :len')]
				public string $testProp,

				#[MaxLength(5, message: ':attr max is :max')]
				#[Pattern('/^\w+$/', message: ":attr is not strictly word characters (':pattern')")]
				public string $other
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'length must be more than 3 and less than 7');
		$this->assertProofContainsError($proof, 'testProp', 'no seriously testProp must be longer than 4');
		$this->assertProofContainsError($proof, 'testProp', 'testProp: actually exactly 5');

		$this->assertProofFailedForAttribute($proof, 'other');
		$this->assertProofContainsError($proof, 'other', 'other max is 5');
		$this->assertProofContainsError($proof, 'other', "other is not strictly word characters ('/^\\w+$/')");
	}

	public function test_array_of_values(): void {
		$test = new class(['test', 'givens', '#']) {
			public function __construct(
				#[MaxLength(5)]
				#[MinLength(2)]
				#[ExactLength(4)]
				#[LengthBetween(2, 5)]
				#[Pattern('/^\w+$/', message: ":attr is not strictly word characters (':pattern')")]
				public array $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofPassed($proof, 'testProp.0');
		$this->assertProofFailedForAttribute($proof, 'testProp.1');
		$this->assertProofFailedForAttribute($proof, 'testProp.2');

		$this->assertProofContainsError($proof, 'testProp', '[1]: testProp.1 must be at most 5 characters long');
		$this->assertProofContainsError($proof, 'testProp.1', 'testProp.1 must be at most 5 characters long');
		$this->assertProofContainsError($proof, 'testProp', '[1]: testProp.1 must be exactly 4 characters long');
		$this->assertProofContainsError($proof, 'testProp.1', 'testProp.1 must be exactly 4 characters long');
		$this->assertProofContainsError($proof, 'testProp', '[1]: the length of testProp.1 must be between 2 and 5 characters');
		$this->assertProofContainsError($proof, 'testProp.1', 'the length of testProp.1 must be between 2 and 5 characters');
		$this->assertProofContainsError($proof, 'testProp', '[2]: testProp.2 must be at least 2 characters long');
		$this->assertProofContainsError($proof, 'testProp.2', 'testProp.2 must be at least 2 characters long');
		$this->assertProofContainsError($proof, 'testProp', '[2]: testProp.2 must be exactly 4 characters long');
		$this->assertProofContainsError($proof, 'testProp.2', 'testProp.2 must be exactly 4 characters long');
		$this->assertProofContainsError($proof, 'testProp', '[2]: the length of testProp.2 must be between 2 and 5 characters');
		$this->assertProofContainsError($proof, 'testProp.2', 'the length of testProp.2 must be between 2 and 5 characters');
		$this->assertProofContainsError($proof, 'testProp', "[2]: testProp.2 is not strictly word characters ('/^\\w+$/')");
		$this->assertProofContainsError($proof, 'testProp.2', "testProp.2 is not strictly word characters ('/^\\w+$/')");
	}
}
