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
use holonet\common\verifier\rules\filesystem\Readable;
use holonet\common\verifier\rules\filesystem\Writable;
use holonet\common\verifier\rules\filesystem\Directory;
use holonet\common\verifier\rules\filesystem\ValidPath;

/**
 * @covers \holonet\common\verifier\Verifier
 * @covers \holonet\common\verifier\rules\Rule
 * @covers \holonet\common\verifier\rules\filesystem\ValidPath
 * @covers \holonet\common\verifier\rules\filesystem\PathRule
 * @covers \holonet\common\verifier\rules\filesystem\Readable
 * @covers \holonet\common\verifier\rules\filesystem\Writable
 * @covers \holonet\common\verifier\rules\filesystem\Directory
 */
class VerifyFilesystemRulesTest extends BaseVerifyTest {
	public function testCheckPathIsDirectory(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[Directory]
				public string $path
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'path', "'/path/surely/doesnt/exist' is not a directory");

		$test->path = __DIR__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'path');
	}

	public function testCheckPathIsReadable(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[Readable]
				public string $path
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'path', "'/path/surely/doesnt/exist' is not readable");

		$test->path = __FILE__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'path');
	}

	public function testCheckPathIsWritable(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[Writable]
				public string $path
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'path', "'/path/surely/doesnt/exist' is not writable");

		$test->path = __FILE__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'path');
	}

	public function testCheckValidPath(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[ValidPath]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofFailedWithError($proof, 'testProp', "'/path/surely/doesnt/exist' is not a valid path");

		$test->testProp = __FILE__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function testCustomMessage(): void {
		$test = new class('/path/sure/../../doesnt/exist') {
			public function __construct(
				#[Directory(message: 'dir pls not: :value')]
				#[Readable(message: 'readable pls not: :value')]
				#[ValidPath(message: 'valid pls not: :value')]
				#[Writable(message: 'writable pls not: :value')]
				public string $path,
			) {
			}
		};

		$proof = verify($test);
		$this->assertFalse($proof->pass());

		$this->assertFalse($proof->passed('path'));
		$this->assertProofContainsError($proof, 'path', 'dir pls not: /doesnt/exist');
		$this->assertProofContainsError($proof, 'path', 'readable pls not: /doesnt/exist');
		$this->assertProofContainsError($proof, 'path', 'valid pls not: /doesnt/exist');
		$this->assertProofContainsError($proof, 'path', 'writable pls not: /doesnt/exist');
	}

	public function testRelativePathIsTransformed(): void {
		$test = new class('./src/../tests/verifier/'.basename(__FILE__)) {
			public function __construct(
				#[Readable]
				public string $path
			) {
			}
		};

		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
		$this->assertSame(__FILE__, $test->path);
	}
}
