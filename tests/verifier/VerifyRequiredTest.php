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
use holonet\common\verifier\rules\Required;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Verifier::class)]
#[CoversClass(Required::class)]
class VerifyRequiredTest extends BaseVerifyTest {
	public function test_check_for_required_after_unset(): void {
		$test = new class('test') {
			public function __construct(
				#[Required]
				public string $testProp
			) {
			}
		};

		unset($test->testProp);

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp is required');
	}

	public function test_check_for_required_nullable(): void {
		$test = new class() {
			#[Required]
			public ?string $testProp = null;
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp is required');
	}

	public function test_check_for_required_of_uninitialised(): void {
		$test = new class() {
			#[Required]
			public string $testProp;
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', 'testProp is required');
	}

	public function test_custom_message(): void {
		$test = new class() {
			#[Required(message: "you better make sure :attr is set. It's the law!")]
			public string $testProp;
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', "you better make sure testProp is set. It's the law!");
	}

	public function test_non_required_unitialised_pass(): void {
		$test = new class() {
			public string $testProp;
		};

		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}
}
