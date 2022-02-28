<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common\Noun;
use holonet\common\PhpExporter;
use PHPUnit\Framework\TestCase;

/**
 * @covers  \holonet\common\PhpExporter
 *
 * @internal
 *
 * @small
 */
class PhpExporterTest extends TestCase {
	public function testEval(): void {
		$exporter = new PhpExporter();

		$data = array(
			'toplevel' => array(
				'sublevel' => array(
					'class' => \holonet\common\Noun::class
				)
			)
		);
		$exported = $exporter->export($data);
		$this->assertSame($data, eval($exported));
	}

	public function testExportClassname(): void {
		$exporter = new PhpExporter();

		$val = Noun::class;

		$exported = $exporter->export($val);

		$this->assertFalse(str_contains($exported, "'"));
	}
}
