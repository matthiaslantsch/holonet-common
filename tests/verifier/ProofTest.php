<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\verifier;

use PHPUnit\Framework\TestCase;
use holonet\common\verifier\Proof;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Proof::class)]
class ProofTest extends TestCase {
	public function test_proof_error_bag(): void {
		$proof = new Proof();

		$this->assertTrue($proof->pass());

		$proof->add('test', 'error message');

		$this->assertFalse($proof->pass());
		$this->assertFalse($proof->passed('test'));
		$this->assertSame(array('error message'), $proof->flat());
		$this->assertSame(array('error message'), $proof->attr('test'));
		$this->assertSame(array('test' => array('error message')), $proof->all());
	}

	public function test_sub_errors_from_array_are_returned_correctly(): void {
		$proof = new Proof();

		$proof->add('array.0', 'error message 0.0');
		$proof->add('array.0', 'error message 0.1');
		$proof->add('array.1', 'error message 1');

		$this->assertCount(3, $proof->flat());
		$this->assertCount(3, $proof->attr('array'));
		$this->assertCount(2, $proof->attr('array.0'));
		$this->assertCount(1, $proof->attr('array.1'));

		$this->assertFalse($proof->passed('array'));
		$this->assertFalse($proof->passed('array.0'));
		$this->assertFalse($proof->passed('array.1'));

		$this->assertTrue($proof->passed('testProp'));
	}

}
