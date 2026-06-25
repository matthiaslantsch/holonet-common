<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests\discovery;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use holonet\common\FilesystemUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\discovery\TokeniserClassDiscovery;

#[CoversClass(TokeniserClassDiscovery::class)]
class TokeniserClassDiscoveryTest extends TestCase {
	private string $tempDir;

	protected function setUp(): void {
		$this->tempDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('class_discovery_test_', true);
		FilesystemUtils::dirShouldExist($this->tempDir);
	}

	protected function tearDown(): void {
		FilesystemUtils::rrmdir($this->tempDir);
	}

	public function test_discovers_namespaced_class(): void {
		$file = $this->writeFile('SimpleClass.php', <<<'PHP'
		<?php

		namespace my\test\space;

		class SimpleClass {
		}
		PHP);

		$discovery = new TokeniserClassDiscovery();

		$this->assertSame('\my\test\space\SimpleClass', $discovery->fromFile($file));
	}

	public function test_class_constant_references_before_the_declaration_are_ignored(): void {
		$file = $this->writeFile('WithAttribute.php', <<<'PHP'
		<?php

		namespace my\test\space;

		use holonet\common\Noun;

		#[SomeAttribute(Noun::class)]
		class WithAttribute {
		}
		PHP);

		$discovery = new TokeniserClassDiscovery();

		$this->assertSame('\my\test\space\WithAttribute', $discovery->fromFile($file));
	}

	public function test_error_file_without_class(): void {
		$file = $this->writeFile('functions.php', <<<'PHP'
		<?php

		namespace my\test\space;

		function some_function(): void {
		}
		PHP);

		$discovery = new TokeniserClassDiscovery();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Could not find class token in file '{$file}'");

		$discovery->fromFile($file);
	}

	private function writeFile(string $name, string $contents): string {
		$path = $this->tempDir.\DIRECTORY_SEPARATOR.$name;
		file_put_contents($path, $contents);

		return $path;
	}
}
