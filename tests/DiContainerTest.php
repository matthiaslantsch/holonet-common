<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use PHPUnit\Framework\TestCase;
use holonet\common\di\Container;

/**
 * Tests the functionality of utility functions in functions.php.
 *
 * @internal
 *
 * @covers \holonet\common\di\Container
 *
 * @small
 */
class DiContainerTest extends TestCase {
	public function testInjectionBeforeConstructor(): void {
		$container = new Container();
		$container->set('anonDep', DiAnonDep::class);
		$container->set('anonClassTwo', DiAnonClassTwo::class);

		$this->assertSame('test', $container->get('anonClassTwo')->test);
	}
}

class DiAnonClassOne {
	public DiAnonClassTwo $di_anonClassTwo;
}

class DiAnonClassTwo {
	public DiAnonDep $di_anonDep;

	public string $test;

	public function __construct() {
		$this->test = $this->di_anonDep->test();
	}
}

class DiAnonDep {
	public function test(): string {
		return 'test';
	}
}
