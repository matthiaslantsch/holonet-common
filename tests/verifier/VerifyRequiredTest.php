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
use holonet\common\verifier\rules\Required;

/**
 * @covers \holonet\common\verifier\Verifier
 * @covers \holonet\common\verifier\rules\Required
 */
class VerifyRequiredTest extends BaseVerifyTest {
	public function testCheckForRequiredAfterUnset(): void {
		$test = new class('test') {
			public function __construct(
				#[Required]
				public string $testProp
			) {
			}
		};

		unset($test->testProp);

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp is required');
	}

	public function testCheckForRequiredNullable(): void {
		$test = new class() {
			#[Required]
			public ?string $testProp = null;
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp is required');
	}

	public function testCheckForRequiredOfUninitialised(): void {
		$test = new class() {
			#[Required]
			public string $testProp;
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp is required');
	}

	public function testCustomMessage(): void {
		$test = new class() {
			#[Required(message: "you better make sure :attr is set. It's the law!")]
			public string $testProp;
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', "you better make sure testProp is set. It's the law!");
	}

	public function testNonRequiredUnitialisedPass(): void {
		$test = new class() {
			public string $testProp;
		};

		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}
}
