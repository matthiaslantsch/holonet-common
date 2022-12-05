<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\error;

use PHPUnit\Framework\TestCase;
use holonet\common\verifier\Proof;
use function holonet\common\stringify;
use holonet\common\error\BadEnvironmentException;

/**
 * @covers \holonet\common\error\BadEnvironmentException
 */
class BadEnvironmentExceptionTest extends TestCase {
	public function testFaultyConfigFactoryMethod(): void {
		$proof = new Proof();
		$proof->add('guard_enabled', 'guard_enabled is required');

		$ex = BadEnvironmentException::faultyConfigFromProof('app.auth', $proof);
		$this->assertSame('Faulty config with key app.auth.guard_enabled: guard_enabled is required', $ex->getMessage());

		$proof->add('guard_enabled', 'guard_enabled is invalid');
		$ex = BadEnvironmentException::faultyConfigFromProof('app.auth', $proof);
		$this->assertSame(sprintf('Faulty config with key app.auth.guard_enabled: %s', stringify($proof->attr('guard_enabled'), true)), $ex->getMessage());

		$proof->add('handler', 'handler must be a subclass of Handler');
		$ex = BadEnvironmentException::faultyConfigFromProof('app.auth', $proof);
		$this->assertSame(sprintf('Faulty config with key app.auth: %s', stringify($proof->all(), true)), $ex->getMessage());
	}
}
