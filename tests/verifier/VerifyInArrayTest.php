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
use holonet\common\verifier\rules\InArray;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Verifier::class)]
#[CoversClass(Rule::class)]
#[CoversClass(InArray::class)]
class VerifyInArrayTest extends BaseVerifyTest {
	public function test_check_in_array(): void {
		$test = new class('itsy bitsy') {
			public function __construct(
				#[InArray(array('test', 'given', 'values'))]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be one of [\'test\', \'given\', \'values\']');

		$test->testProp = 'given';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_in_array_strict(): void {
		$test = new class('12.4') {
			public function __construct(
				#[InArray(array('1.10', 12.4, 1.13), strict: true)]
				public mixed $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be one of [\'1.10\', 12.4, 1.13]');

		$test->testProp = 12.4;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_check_not_in_array(): void {
		$test = new class('values') {
			public function __construct(
				#[InArray(array('test', 'given', 'values'), not: true)]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must not be one of [\'test\', \'given\', \'values\']');

		$test->testProp = 'something else';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_custom_message(): void {
		$test = new class('itsy bitsy') {
			public function __construct(
				#[InArray(array('test', 'given', 'values'), message: ':attr must :not be one of them :values')]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp must be one of them [\'test\', \'given\', \'values\']');
	}

	public function test_array_of_values(): void {
		$test = new class(['f' => 'test', 's' => 'test', 't' => 'not in there']) {
			public function __construct(
				#[InArray(array('test', 'given', 'values'))]
				public array $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofPassed($proof, 'testProp.f');
		$this->assertProofPassed($proof, 'testProp.s');
		$this->assertProofFailedForAttribute($proof, 'testProp.t');

		$this->assertProofContainsError($proof, 'testProp', '[t]: testProp.t must be one of [\'test\', \'given\', \'values\']');
		$this->assertProofContainsError($proof, 'testProp.t', 'testProp.t must be one of [\'test\', \'given\', \'values\']');
	}

}
