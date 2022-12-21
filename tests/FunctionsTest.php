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
use function holonet\common\get_absolute_path;

/**
 * @coversNothing
 */
class FunctionsTest extends TestCase {
	protected function tearDown(): void {
		verify(new stdClass(), new Verifier());
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
	 * @covers \holonet\common\file_get_use_statements()
	 * @covers \holonet\common\code\FileUseStatementParser
	 */
	public function testFileGetUseStatements(): void {
		$this->assertSame(array(
			'class' => array(
				'Another' => 'My\Full\Classname',
				'NSname' => 'My\Full\NSname',
				'ArrayObject' => 'ArrayObject',
				'AnotherTest' => 'My\Full\Classname',
				'NSnameNamed' => 'My\Full\NSnameNamed',
				'ClassA' => 'some\namespace\ClassA',
				'ClassB' => 'some\namespace\ClassB',
				'C' => 'some\namespace\ClassC',
			),
			'function' => array(
				'functionName' => 'My\Full\functionName',
				'func' => 'My\Full\functionName',
				'fn_a' => 'some\namespace\fn_a',
				'fn_b' => 'some\namespace\fn_b',
				'fc' => 'some\namespace\fn_c',
			),
			'constant' => array(
				'CONSTANT' => 'My\Full\CONSTANT',
				'ConstA' => 'some\namespace\ConstA',
				'ConstB' => 'some\namespace\ConstB',
				'cnstc' => 'some\namespace\ConstC'
			)
		), co\file_get_use_statements(__DIR__.'/data/use_statements.txt'));
	}

	/**
	 * @covers \holonet\common\get_absolute_path()
	 */
	public function testGetAbsolutePath(): void {
		$this->assertSame('this/a/test/is', get_absolute_path('this/is/../a/./test/./is'));
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
			$line = (__LINE__) + 1;
			co\trigger_error_context('oh nos');
		} catch (\PHPUnit\Exception $e) {
			$msg = $e->getMessage();
		}

		$expected = 'oh nos in file '.__FILE__." on line {$line}";
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
