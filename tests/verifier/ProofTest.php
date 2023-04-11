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
	public function testProofErrorBag(): void {
		$proof = new Proof();

		$this->assertTrue($proof->pass());

		$proof->add('test', 'error message');

		$this->assertFalse($proof->pass());
		$this->assertFalse($proof->passed('test'));
		$this->assertSame(array('error message'), $proof->flat());
		$this->assertSame(array('error message'), $proof->attr('test'));
		$this->assertSame(array('test' => array('error message')), $proof->all());
	}
}
