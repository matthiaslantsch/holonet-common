<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use Exception;
use PHPUnit\Framework\TestCase;

use RuntimeException;
use function holonet\common\kvm_match;
use function holonet\common\kvm_parse;
use function holonet\common\kvm_append;
use function holonet\common\kvm_parse_query;
use function holonet\common\kvm_serialise;

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * @internal
 */
#[CoversFunction('holonet\common\kvm_match')]
#[CoversFunction('holonet\common\kvm_parse')]
#[CoversFunction('holonet\common\kvm_append')]
#[CoversFunction('holonet\common\kvm_serialise')]
#[CoversFunction('holonet\common\kvm_match')]
#[CoversFunction('holonet\common\kvm_sanitise_value')]
#[CoversFunction('holonet\common\kvm_parse_query')]
class KVMTest extends TestCase {
	public function test_error_invalid_condition_empty(): void {
		$this->expectException(RuntimeException::class);
		kvm_match('', array());
	}

	public function test_kvm_match_boolean_condition_empty_value_evaluates_to_false(): void {
		$data = array(
			'string' => '',
			'number' => 0,
		);

		$this->assertFalse(kvm_match('string', $data));
		$this->assertFalse(kvm_match('number', $data));
		$this->assertTrue(kvm_match('!string', $data));
		$this->assertTrue(kvm_match('!number', $data));
	}

	public function test_kvm_match_boolean_conditions(): void {
		$data = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$this->assertTrue(kvm_match('foo', $data));
		$this->assertFalse(kvm_match('!foo', $data));
		$this->assertTrue(kvm_match('baz', $data));
		$this->assertFalse(kvm_match('!baz', $data));
		$this->assertFalse(kvm_match('nonexistant', $data));
	}

	public function test_kvm_match_comparison_conditions(): void {
		$data = array(
			'count' => 5,
			'value' => 10,
		);

		$this->assertTrue(kvm_match('count=5', $data));
		$this->assertFalse(kvm_match('count=10', $data));
		$this->assertTrue(kvm_match('value>5', $data));
		$this->assertFalse(kvm_match('value<5', $data));
		$this->assertFalse(kvm_match('nonexistant<5', $data));
	}

	public function test_kvm_match_comparison_multiple_values(): void {
		$data = array(
			'foo' => array('bar', 'baz'),
		);

		$this->assertTrue(kvm_match('foo', $data));
		$this->assertTrue(kvm_match('foo=bar', $data));
		$this->assertTrue(kvm_match('foo=baz', $data));
		$this->assertFalse(kvm_match('foo=qux', $data));
	}

	public function test_kvm_match_parse_query(): void {
		$query = 'foo=bar/score>10/checked<100/tagged';

		$query = kvm_parse_query($query);

		$this->assertSame(array(
			array('foo', '=', 'bar'),
			array('score', '>', '10'),
			array('checked', '<', '100'),
			array('tagged'),
		), $query);
	}

	public function test_multiline_value_and_list_separator_being_sanitised_properly(): void {
		$value = <<<'VALUE'
		test
		
		gdsgsdg	test				test

		
		gsdhfdhdfhdh
		
		
		
		test
		
		
		
		
		test
		VALUE;

		$meta = array('foo' => $value);
		$serialised = kvm_serialise($meta);
		$this->assertSame(<<<'META'
		foo
		test
		gdsgsdg test    test
		gsdhfdhdfhdh
		test
		test
		META, $serialised);
	}

	public function test_multiple_conditions(): void {
		$data = array(
			'foo' => 'bar',
			'count' => 5,
		);

		$this->assertTrue(kvm_match('foo/count=5', $data));
		$this->assertFalse(kvm_match('foo/count=10', $data));
	}

	public function test_multiple_values(): void {
		$kvm = array();

		kvm_append('foo', 'bar', $kvm);
		kvm_append('foo', 'baz', $kvm);

		$this->assertkvm($kvm, <<<'META'
		foo
		bar	baz
		META);

		kvm_append('foo', 'qux', $kvm);
		kvm_append('foo', 'pal', $kvm);
		$this->assertkvm($kvm, <<<'META'
		foo
		bar	baz	qux	pal
		META);

		$kvm['foo'] = 'qux';
		$this->assertkvm($kvm, <<<'META'
		foo
		qux
		META);
	}

	public function test_singular_values(): void {
		$kvm = array();

		kvm_append('foo', 'bar', $kvm);

		$this->assertkvm($kvm, <<<'META'
		foo
		bar
		META);

		$kvm['foo'] = 'baz';
		$this->assertkvm($kvm, <<<'META'
		foo
		baz
		META);
	}

	private function assertkvm(array $kvm, string $expectedSerialised): void {
		$actualSerialised = kvm_serialise($kvm);
		$this->assertSame($expectedSerialised, $actualSerialised);
		$this->assertSame($kvm, kvm_parse($actualSerialised));
	}
}
