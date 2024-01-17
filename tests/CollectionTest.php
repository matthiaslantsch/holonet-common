<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common\collection\Collection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Collection::class)]
class CollectionTest extends TestCase {
	public function test_constructor_with_initial_data(): void {
		$initialData = ['foo' => 'bar', 'baz' => 123];
		$collection = new Collection($initialData);

		$this->assertSame($initialData, $collection->all());
	}

	public function test_constructor_with_empty_initial_data(): void {
		$collection = new Collection();

		$this->assertTrue($collection->empty());
	}

	public function test_set_and_get(): void {
		$collection = new Collection();
		$collection->set('key1', 'value1');
		$collection->key2 = 'value2';

		$this->assertEquals('value1', $collection->key1);
		$this->assertEquals('value2', $collection->get('key2'));
		$this->assertNull($collection->NonsensicalKey);
	}

	public function test_set_with_null_key(): void {
		$collection = new Collection();
		$collection->set(null, 'value');

		$this->assertEquals('value', $collection->get(0)); // 0 is the index when the key is null
	}

	public function test_set_with_null_key_adds_to_the_end(): void {
		$collection = new Collection(['existingValue']);
		$collection->set(null, 'newValue');

		$this->assertEquals(['existingValue', 'newValue'], $collection->all());
	}

	public function test_get_with_default(): void {
		$collection = new Collection(['existingKey' => 'value']);

		$this->assertEquals('value', $collection->get('existingKey'));
		$this->assertEquals('default', $collection->get('nonExistingKey', 'default'));
	}

	public function test_changes_to_magic_method_reads_work(): void {
		$collection = new Collection(['null' => null, 'object' => new \stdClass(), 'array' => ['key' => 'value']]);

		// make sure the reference to null points nowhere
		$reference = $collection->null;
		$reference = 'test';
		$this->assertNull($collection->get('null'));

		// make sure the reference to the object works as expected
		$reference = $collection->object;
		$reference->test = 'test';
		$this->assertEquals('test', $collection->get('object')->test);
		$collection->object->test = 'new value';
		$this->assertEquals('new value', $collection->get('object')->test);

		// make sure the reference to the array works as expected
		$collection->array['key'] = 'new value';
		$this->assertEquals(array('key' => 'new value'), $collection->get('array'));
		$collection->array[] = 'appended';
		$this->assertEquals(array('key' => 'new value', 'appended'), $collection->get('array'));

		// changes to a subarray
		$collection = new Collection(['key' => ['subkey' => 'value']]);
		$collection->key['subkey'] = 'new value';

		$this->assertEquals(['key' => ['subkey' => 'new value']], $collection->all());
	}

	public function test_has(): void {
		$collection = new Collection(['existingKey' => 'value']);

		$this->assertTrue($collection->has('existingKey'));
		$this->assertTrue(isset($collection->existingKey));
		$this->assertFalse($collection->has('nonExistingKey'));
		$this->assertFalse(isset($collection->nonExistingKey));

		$collection->set('nullKey', null);

		// behave differently when the value is null
		// => the key is set but the offset definitely is not "isset"
		$this->assertTrue($collection->has('nullKey'));
		$this->assertFalse(isset($collection->nullKey));
	}

	public function test_remove(): void {
		$collection = new Collection(['key1' => 'value1', 'key2' => 'value2']);
		$collection->remove('key1');

		$this->assertEquals(['key2' => 'value2'], $collection->all());

		unset($collection->key2);

		$this->assertEquals([], $collection->all());
	}

	public function test_clear(): void {
		$collection = new Collection(['key' => 'value']);
		$collection->clear();

		$this->assertTrue($collection->empty());
	}

	public function test_count(): void {
		$collection = new Collection(['key1' => 'value1', 'key2' => 'value2']);

		$this->assertEquals(2, $collection->count());
	}

	public function test_empty(): void {
		$collection = new Collection();

		$this->assertTrue($collection->empty());

		$collection->set('key', 'value');

		$this->assertFalse($collection->empty());
	}

	public function test_iterator(): void {
		$collection = new Collection(['key1' => 'value1', 'key2' => 'value2']);
		$iterator = $collection->getIterator();

		$this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], iterator_to_array($iterator));
	}

	public function test_merge(): void {
		$collection = new Collection(['key1' => 'value1', 'key2' => 'value2']);
		$collection->merge(['key2' => 'newValue2', 'key3' => 'value3']);

		$this->assertEquals(['key1' => 'value1', 'key2' => 'newValue2', 'key3' => 'value3'], $collection->all());
	}

	public function test_replace(): void {
		$collection = new Collection(['key1' => 'value1', 'key2' => 'value2']);
		$collection->replace(['key3' => 'value3']);

		$this->assertEquals(['key3' => 'value3'], $collection->all());
	}

	public function test_all(): void {
		$collection = new Collection(['key' => 'value']);

		$this->assertEquals(['key' => 'value'], $collection->all());
	}

	public function test_filter_param_can_be_used_to_filter_keys(): void {
		$collection = new Collection(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3']);

		$this->assertEquals(['key1' => 'value1', 'key3' => 'value3'], $collection->all(['key1', 'key3']));
	}

}
