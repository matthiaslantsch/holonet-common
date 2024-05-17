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
use holonet\common\verifier\rules\filesystem\PathRule;
use holonet\common\verifier\rules\filesystem\Readable;
use holonet\common\verifier\rules\filesystem\Writable;
use holonet\common\verifier\rules\filesystem\Directory;
use holonet\common\verifier\rules\filesystem\ValidPath;

#[CoversClass(Verifier::class)]
#[CoversClass(Rule::class)]
#[CoversClass(ValidPath::class)]
#[CoversClass(PathRule::class)]
#[CoversClass(Readable::class)]
#[CoversClass(Writable::class)]
#[CoversClass(Directory::class)]
class VerifyFilesystemRulesTest extends BaseVerifyTest {
	public function test_check_path_is_directory(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[Directory]
				public string $path
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'path');
		$this->assertProofContainsError($proof, 'path', "'/path/surely/doesnt/exist' is not a directory");

		$test->path = __DIR__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'path');
	}

	public function test_check_path_is_readable(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[Readable]
				public string $path
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'path');
		$this->assertProofContainsError($proof, 'path', "'/path/surely/doesnt/exist' is not readable");

		$test->path = __FILE__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'path');
	}

	public function test_check_path_is_writable(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[Writable]
				public string $path
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'path');
		$this->assertProofContainsError($proof, 'path', "'/path/surely/doesnt/exist' is not writable");

		$test->path = __FILE__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'path');
	}

	public function test_check_valid_path(): void {
		$test = new class('/path/surely/doesnt/exist') {
			public function __construct(
				#[ValidPath]
				public string $testProp
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'testProp');
		$this->assertProofContainsError($proof, 'testProp', "'/path/surely/doesnt/exist' is not a valid path");

		$test->testProp = __FILE__;
		$proof = verify($test);
		$this->assertProofPassed($proof, 'testProp');
	}

	public function test_custom_message(): void {
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

		$this->assertProofFailedForAttribute($proof, 'path');
		$this->assertProofContainsError($proof, 'path', 'dir pls not: /doesnt/exist');
		$this->assertProofContainsError($proof, 'path', 'readable pls not: /doesnt/exist');
		$this->assertProofContainsError($proof, 'path', 'valid pls not: /doesnt/exist');
		$this->assertProofContainsError($proof, 'path', 'writable pls not: /doesnt/exist');
	}

	public function test_relative_path_is_transformed(): void {
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

	public function test_verify_array_of_paths(): void {
		$test = new class(array(
			__DIR__,
			'/path/surely/doesnt/exist',
			__FILE__,
		)) {
			public function __construct(
				#[Readable]
				#[Writable]
				#[ValidPath]
				public array $paths
			) {
			}
		};

		$proof = verify($test);

		$this->assertProofFailedForAttribute($proof, 'paths');
		$this->assertProofPassed($proof, 'paths.0');
		$this->assertProofFailedForAttribute($proof, 'paths.1');
		$this->assertProofPassed($proof, 'paths.2');

		$this->assertProofContainsError($proof, 'paths', "'/path/surely/doesnt/exist' is not readable");
		$this->assertProofContainsError($proof, 'paths.1', "'/path/surely/doesnt/exist' is not readable");
		$this->assertProofContainsError($proof, 'paths', "'/path/surely/doesnt/exist' is not writable");
		$this->assertProofContainsError($proof, 'paths.1', "'/path/surely/doesnt/exist' is not writable");
		$this->assertProofContainsError($proof, 'paths', "'/path/surely/doesnt/exist' is not a valid path");
		$this->assertProofContainsError($proof, 'paths.1', "'/path/surely/doesnt/exist' is not a valid path");
	}

}
