<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use stdClass;
use holonet\common as co;
use PHPUnit\Framework\TestCase;
use holonet\common\verifier\Proof;
use function holonet\common\verify;
use holonet\common\verifier\Verifier;
use holonet\common\verifier\rules\Required;

/**
 * @coversNothing
 */
class FunctionsTest extends TestCase {
	protected function tearDown(): void {
		verify(new stdClass(), reset: true);
	}

	/**
	 * @covers \holonet\common\FilesystemUtils::dirpath()
	 * @covers \holonet\common\FilesystemUtils::filepath()
	 */
	public function testAbsolutePaths(): void {
		$expected = implode(\DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).\DIRECTORY_SEPARATOR;
		$this->assertSame($expected, co\FilesystemUtils::dirpath(__DIR__, 'subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		$this->assertSame($expected, co\FilesystemUtils::filepath(__DIR__, 'subfolder', 'subsubfolder', 'test.txt'));
	}

	/**
	 * @covers \holonet\common\FilesystemUtils::dirpath()
	 * @covers \holonet\common\FilesystemUtils::filepath()
	 * @covers \holonet\common\FilesystemUtils::reldirpath()
	 * @covers \holonet\common\FilesystemUtils::relfilepath()
	 */
	public function testRelativePaths(): void {
		$expected = implode(\DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).\DIRECTORY_SEPARATOR;
		$this->assertSame($expected, co\FilesystemUtils::reldirpath('subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		$this->assertSame($expected, co\FilesystemUtils::relfilepath('subfolder', 'subsubfolder', 'test.txt'));
	}

	/**
	 * @covers \holonet\common\trigger_error_context()
	 */
	public function testTriggerErrorContext(): void {
		$msg = '';

		try {
			co\trigger_error_context('oh nos');
		} catch (\PHPUnit\Exception $e) {
			$msg = $e->getMessage();
		}

		$expected = 'oh nos in file '.__FILE__.' on line 61';
		$this->assertSame($expected, $msg);
	}

	/**
	 * @covers \holonet\common\verify()
	 */
	public function testVerifierCanBeInjectedIntoVerify(): void {
		$test = new class() {
			#[Required]
			public mixed $testProp;
		};

		$proof = verify($test);
		$this->assertSame(array('testProp is required'), $proof->flat());

		$verifier = new class() extends Verifier {
			public function verify(object $obj): Proof {
				$result = new Proof();
				$result->add('test', 'my message');

				return $result;
			}
		};

		// inject and verify
		$proof = verify($test, $verifier);
		$this->assertSame(array('my message'), $proof->flat());

		// make sure the injected instance stays
		$proof = verify($test);
		$this->assertSame(array('my message'), $proof->flat());
	}
}
