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
	public function testCheckInArray(): void {
		$test = new class('itsy bitsy') {
			public function __construct(
				#[InArray(array('test', 'given', 'values'))]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp must be one of [\'test\', \'given\', \'values\']');

		$test->testProp = 'given';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function testCheckInArrayStrict(): void {
		$test = new class('12.4') {
			public function __construct(
				#[InArray(array('1.10', 12.4, 1.13), strict: true)]
				public mixed $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp must be one of [\'1.10\', 12.4, 1.13]');

		$test->testProp = 12.4;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function testCheckNotInArray(): void {
		$test = new class('values') {
			public function __construct(
				#[InArray(array('test', 'given', 'values'), not: true)]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp must not be one of [\'test\', \'given\', \'values\']');

		$test->testProp = 'something else';
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function testCustomMessage(): void {
		$test = new class('itsy bitsy') {
			public function __construct(
				#[InArray(array('test', 'given', 'values'), message: ':attr must :not be one of them :values')]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp must be one of them [\'test\', \'given\', \'values\']');
	}
}
