<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\verifier;

use holonet\common\verifier\rules\Url;
use function holonet\common\stringify;
use function holonet\common\verify;
use holonet\common\verifier\Verifier;
use holonet\common\verifier\rules\Rule;
use holonet\common\verifier\rules\InArray;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Verifier::class)]
#[CoversClass(Rule::class)]
#[CoversClass(Url::class)]
class VerifyUrlTest extends BaseVerifyTest {

	public function test_invalid_url_values(): void {
		$test = new class('rubbish', null, '') {
			public function __construct(
				#[Url]
				public string $testProp,
				// unset value without #[Required] should not fail
				#[Url]
				public ?string $testProp2,
				#[Url]
				public string $testProp3
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp must be a valid url');
		$this->assertTrue($proof->passed('testProp2'));
		$this->assertProofFailedWithError($proof, 'testProp3', 'testProp3 must be a valid url');
	}

	public function test_valid_url_values(): void {
		$test = new class('http://www.google.com') {
			public function __construct(
				#[Url]
				public string $testProp
			)
			{
			}
		};

		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_custom_message(): void {
		$test = new class('itsy bitsy') {
			public function __construct(
				#[Url(message: ':attr hasen to bean a riggidy real url')]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', 'testProp hasen to bean a riggidy real url');
	}

}
