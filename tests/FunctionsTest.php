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
use holonet\common\FilesystemUtils;
use holonet\common\verifier\Verifier;
use holonet\common\verifier\rules\Required;
use PHPUnit\Framework\Attributes\CoversClass;
use function holonet\common\get_absolute_path;
use holonet\common\code\FileUseStatementParser;
use PHPUnit\Framework\Attributes\CoversFunction;

#[CoversClass(FileUseStatementParser::class)]
#[CoversClass(FilesystemUtils::class)]
#[CoversFunction('holonet\common\verify')]
class FunctionsTest extends TestCase {
	protected function tearDown(): void {
		verify(new stdClass(), new Verifier());
	}

	public function testAbsolutePaths(): void {
		$expected = implode(\DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).\DIRECTORY_SEPARATOR;
		$this->assertSame($expected, co\FilesystemUtils::dirpath(__DIR__, 'subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		$this->assertSame($expected, co\FilesystemUtils::filepath(__DIR__, 'subfolder', 'subsubfolder', 'test.txt'));
	}

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

	public function testGetAbsolutePath(): void {
		$this->assertSame('this/a/test/is', get_absolute_path('this/is/../a/./test/./is'));
	}

	public function testRelativePaths(): void {
		$expected = implode(\DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).\DIRECTORY_SEPARATOR;
		$this->assertSame($expected, co\FilesystemUtils::reldirpath('subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		$this->assertSame($expected, co\FilesystemUtils::relfilepath('subfolder', 'subsubfolder', 'test.txt'));
	}

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
