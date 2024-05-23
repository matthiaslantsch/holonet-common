<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use InvalidArgumentException;
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
use function holonet\common\dot_key_set;
use function holonet\common\dot_key_get;
use function holonet\common\dot_key_array_merge;
use function holonet\common\dot_key_flatten;
use holonet\common\code\FileUseStatementParser;
use PHPUnit\Framework\Attributes\CoversFunction;

#[CoversClass(FileUseStatementParser::class)]
#[CoversClass(FilesystemUtils::class)]
#[CoversFunction('holonet\common\verify')]
#[CoversFunction('holonet\common\dot_key_set')]
#[CoversFunction('holonet\common\dot_key_get')]
#[CoversFunction('holonet\common\dot_key_array_merge')]
#[CoversFunction('holonet\common\dot_key_flatten')]
class FunctionsTest extends TestCase {
	protected function tearDown(): void {
		verify(new stdClass(), new Verifier());
	}

	public function test_dot_key_flatten(): void {
		$array = array('a' => array('b' => array('c' => 5, 'd' => 6), 'e' => 7));
		$expected = array('a.b.c' => 5, 'a.b.d' => 6, 'a.e' => 7);
		$this->assertSame($expected, dot_key_flatten($array));
		// test flattening a multi-level array
		$array = array('a' => array('b' => array('c' => 5, 'd' => 6)));
		$expected = array('a.b.c' => 5, 'a.b.d' => 6);
		$this->assertSame($expected, dot_key_flatten($array));
		// test flatting an object
		$obj = new stdClass();
		$obj->a = new stdClass();
		$obj->a->b = new stdClass();
		$obj->a->b->c = array('d' => 5);
		$expected = array('a.b.c.d' => 5);
		$this->assertSame($expected, dot_key_flatten($obj));
		// test flatting an array which has a list somewhere => list should not be flattened
		$array = array('a' => array('b' => array('d' => array(5, 6))));
		$expected = array('a.b.d' => array(5, 6));
		$this->assertSame($expected, dot_key_flatten($array));
	}

	public function test_dot_key_array_merge(): void {
		$array1 = array('a' => array('b' => array('c' => 5)));
		$expected = array('a' => array('b' => array('c' => 5, 'd' => 6)));
		dot_key_array_merge($array1, 'a.b', array('d' => 6));
		$this->assertSame($expected, $array1);
		// test merging with a null value => should remove the key
		$array1 = array('a' => array('b' => array('c' => 5)));
		$expected = array('a' => array());
		dot_key_array_merge($array1, 'a', array('b' => null));
		$this->assertSame($expected, $array1);
		// test merging with a null value in the first array
		$array1 = array('a' => array('b' => null));
		$expected = array('a' => array('b' => array('c' => 5)));
		dot_key_array_merge($array1, 'a.b', array('c' => 5));
		$this->assertSame($expected, $array1);
		// test merging recursively
		$array1 = array('a' => array('b' => array('d' => array('f' => 5))));
		$expected = array('a' => array('b' => array('d' => array('f' => 5, 'e' => 6))));
		dot_key_array_merge($array1, 'a.b', array('d' => array('e' => 6)));
		$this->assertSame($expected, $array1);
		// test merging with lists => should not override the numeric values
		$array1 = array('a' => array('b' => array(5, 6)));
		$expected = array('a' => array('b' => array(5, 6, 7)));
		dot_key_array_merge($array1, 'a.b', array(7));
		$this->assertSame($expected, $array1);
	}

	public function test_dot_key_array_merge_does_not_merge_over_non_arrays(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The key a is not an array cannot merge with another array');

		$array1 = array('a' => 'test');
		dot_key_array_merge($array1, 'a', array('b' => 5));
	}

	public function test_dot_key_set(): void {
		$array = array();
		// setting key in sub array
		dot_key_set($array, 'a.b.c', 5);
		$this->assertSame(array('a' => array('b' => array('c' => 5))), $array);
		// setting an entire array
		$array = array('a' => array('b' => 'test'));
		dot_key_set($array, 'a.b', array('c' => 5));
		$this->assertSame(array('a' => array('b' => array('c' => 5))), $array);
		// setting a single array key
		$array = array('a' => array('b' => 'test'));
		dot_key_set($array, 'a.b', 5);
		$this->assertSame(array('a' => array('b' => 5)), $array);
		// test unsetting a value by supplying null
		$array = array('a' => array('b' => 'test'));
		dot_key_set($array, 'a.b', null);
		$this->assertSame(array('a' => array()), $array);
		// test overwriting a partial path with a new path
		$array = array('a' => array('b' => 'test'));
		dot_key_set($array, 'a.b.c', 5);
		$this->assertSame(array('a' => array('b' => array('c' => 5))), $array);
	}

	public function test_dot_key_get(): void {
		$array = array('a' => array('b' => array('c' => 5)));
		$this->assertSame(5, dot_key_get($array, 'a.b.c'));
		$this->assertSame(array('c' => 5), dot_key_get($array, 'a.b'));
		$this->assertNull(dot_key_get($array, 'a.b.d'));
		$this->assertNull(dot_key_get($array, 'a.b.c.d'));
		// test getting a key from an object
		$obj = new stdClass();
		$obj->a = new stdClass();
		$obj->a->b = new stdClass();
		$obj->a->b->c = 5;
		$this->assertSame(5, dot_key_get($obj, 'a.b.c'));
	}

	public function test_absolute_paths(): void {
		$expected = implode(\DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).\DIRECTORY_SEPARATOR;
		$this->assertSame($expected, co\FilesystemUtils::dirpath(__DIR__, 'subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		$this->assertSame($expected, co\FilesystemUtils::filepath(__DIR__, 'subfolder', 'subsubfolder', 'test.txt'));
	}

	public function test_file_get_use_statements(): void {
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

	public function test_get_absolute_path(): void {
		$this->assertSame('this/a/test/is', get_absolute_path('this/is/../a/./test/./is'));
	}

	public function test_relative_paths(): void {
		$expected = implode(\DIRECTORY_SEPARATOR, array(__DIR__, 'subfolder', 'subsubfolder')).\DIRECTORY_SEPARATOR;
		$this->assertSame($expected, co\FilesystemUtils::reldirpath('subfolder', 'subsubfolder'));

		$expected .= 'test.txt';
		$this->assertSame($expected, co\FilesystemUtils::relfilepath('subfolder', 'subsubfolder', 'test.txt'));
	}

	public function test_verifier_can_be_injected_into_verify(): void {
		$test = new class() {
			#[Required]
			public mixed $testProp;
		};

		$proof = verify($test);
		$this->assertSame(array('testProp' => 'testProp is required'), $proof->flat());

		$verifier = new class() extends Verifier {
			public function verify(object $obj): Proof {
				$result = new Proof();
				$result->add('test', 'my message');

				return $result;
			}
		};

		// inject and verify
		$proof = verify($test, $verifier);
		$this->assertSame(array('test' => 'my message'), $proof->flat());

		// make sure the injected instance stays
		$proof = verify($test);
		$this->assertSame(array('test' => 'my message'), $proof->flat());
	}
}
