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
use holonet\common\collection\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;

#[CoversClass(Registry::class)]
#[CoversFunction('holonet\common\dot_key_get')]
#[CoversFunction('holonet\common\dot_key_set')]
class RegistryTest extends TestCase {
	public function test_get_multilevel(): void {
		$registry = new Registry();

		$data = array(
			'array' => array(
				'lowerval' => 'lower',
				'arrayarray' => array(
					'lowestval' => 'lowest'
				)
			)
		);
		$registry->setAll($data);

		$this->assertFalse($registry->has('array.notexisting'));
		$this->assertSame('lower', $registry->get('array.lowerval'));
		$this->assertNull($registry->get('array.notexisting'));
		$this->assertSame('lowest', $registry->get('array.arrayarray.lowestval'));
		$this->assertNull($registry->get('array.lowerval.stillnotexisting'));
	}

	public function test_placeholders(): void {
		$registry = new Registry();

		$registry->set('app.name', 'coolapp');
		$registry->set('app.environment', '%app.name%-test');
		$registry->set('app.testing', 'inside-%not-existing-placeholder%-testing');

		$this->assertSame('coolapp-test', $registry->get('app.environment'));
		$this->assertSame('inside-%not-existing-placeholder%-testing', $registry->get('app.testing'));
		$this->assertSame(array('app' => array('name' => 'coolapp', 'environment' => 'coolapp-test', 'testing' => 'inside-%not-existing-placeholder%-testing')), $registry->all());
	}

	public function test_reset(): void {
		$registry = new Registry();

		$data = array(
			'array' => array(
				'lowerval' => 'lower',
				'arrayarray' => array(
					'lowestval' => 'lowest'
				)
			)
		);
		$registry->setAll($data);

		$this->assertNotEmpty($registry->all());
		$registry->clear();
		$this->assertEmpty($registry->all());
	}

	public function test_set_multilevel(): void {
		$registry = new Registry();

		//test multi level set
		$registry->set('test.subone.sub2', 'subvalue');
		$expected['test']['subone']['sub2'] = 'subvalue';
		$this->assertSame($expected, $registry->all());

		//test multi level set with overwrite
		$registry->set('test.subone', 'overwrite');
		$expected['test']['subone'] = 'overwrite';
		$this->assertSame($expected, $registry->all());

		//test same level 2 values set
		$registry->set('test.subonebrother', 'nexttoit');
		$expected['test'] = array('subone' => 'overwrite', 'subonebrother' => 'nexttoit');
		$this->assertSame($expected, $registry->all());
	}

	public function test_set_sublevel_key_without_overwrite(): void {
		$registry = new Registry();
		$registry->set('app', array(
			'db' => array('host' => '127.0.0.1', 'port' => '224')
		));
		$newConfigfile = array(
			'app' => array(
				'db' => array('host' => 'localhost')
			)
		);
		$registry->setAll($newConfigfile);

		$this->assertSame(array('host' => 'localhost', 'port' => '224'), $registry->get('app.db'));
	}

	public function test_simple_pair(): void {
		$registry = new Registry();
		$registry->set('test', 'value');

		$this->assertSame('value', $registry->get('test'));
		$this->assertTrue($registry->has('test'));
		$this->assertNull($registry->get('notexisting'));
		$this->assertSame(array('test' => 'value'), $registry->all());
		$registry->unset('test');
		$this->assertNull($registry->get('test'));
	}
}
