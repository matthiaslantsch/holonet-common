<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use holonet\common\collection\ChangeAwareTrait;
use holonet\common\collection\ChangeAwareInterface;
use holonet\common\collection\ChangeAwareCollection;

#[CoversClass(ChangeAwareCollection::class)]
#[CoversClass(ChangeAwareTrait::class)]
class ChangeAwareCollectionTest extends TestCase {
	public function test_initial_values_are_not_marked_as_added(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first'));

		$this->assertFalse($collection->changed());
		$this->assertSame(array('one' => 'first'), $collection->getAll());
	}

	public function test_added_values_are_tracked(): void {
		$collection = new ChangeAwareCollection();
		$collection->set('one', 'first');

		$this->assertTrue($collection->changed());
		$this->assertSame(array('one' => 'first'), $collection->getAll('new'));
	}

	public function test_appending_a_duplicate_value_marks_the_new_key_as_added(): void {
		$collection = new ChangeAwareCollection(array('duplicate'));

		// appending a value that already exists in the collection must
		// mark the newly created key as added, not the pre-existing one
		$collection->add('duplicate', null);

		$this->assertSame(array(1 => 'duplicate'), $collection->getAll('new'));
		$this->assertSame(array('duplicate', 'duplicate'), $collection->getAll());
	}

	public function test_changed_values_are_tracked(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first'));
		$collection->set('one', 'changed');

		$this->assertTrue($collection->changed());
		$this->assertSame(array('one' => 'changed'), $collection->getAll('changed'));
		$this->assertSame(array(), $collection->getAll('new'));
	}

	public function test_setting_the_same_value_is_not_a_change(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first'));
		$collection->set('one', 'first');

		$this->assertFalse($collection->changed());
	}

	public function test_removed_values_are_hidden_but_tracked(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first', 'two' => 'second'));

		$this->assertTrue($collection->remove('one'));
		$this->assertFalse($collection->remove('not-there'));

		$this->assertNull($collection->get('one'));
		$this->assertSame(array('two' => 'second'), $collection->getAll());
		$this->assertSame(array('one' => 'first'), $collection->getAll('removed'));
		$this->assertSame(1, $collection->count());
	}

	public function test_setting_a_removed_key_revives_the_entry(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first'));
		$collection->remove('one');

		$this->assertNull($collection->get('one'));

		$collection->set('one', 'revived');

		$this->assertSame('revived', $collection->get('one'));
		$this->assertSame(array('one' => 'revived'), $collection->getAll());
		$this->assertSame(array(), $collection->getAll('removed'));
		$this->assertTrue($collection->changed());
	}

	public function test_replace_marks_old_entries_as_removed_and_keeps_readded_keys(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first', 'two' => 'second'));
		$collection->replace(array('one' => 'new first', 'three' => 'third'));

		$this->assertSame(array('one' => 'new first', 'three' => 'third'), $collection->getAll());
		$this->assertSame(array('two' => 'second'), $collection->getAll('removed'));
	}

	public function test_apply_commits_all_changes(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first', 'two' => 'second'));
		$collection->remove('one');
		$collection->set('three', 'third');

		$collection->apply();

		$this->assertFalse($collection->changed());
		$this->assertSame(array('two' => 'second', 'three' => 'third'), $collection->getAll());
		$this->assertSame(array(), $collection->getAll('removed'));
	}

	public function test_change_returns_reference_and_marks_entry(): void {
		$collection = new ChangeAwareCollection(array('one' => array('value')));

		$entry = &$collection->change('one');
		$entry[] = 'second value';

		$this->assertTrue($collection->changed());
		$this->assertSame(array('value', 'second value'), $collection->get('one'));
	}

	public function test_change_on_unknown_entry_throws(): void {
		$this->expectException(OutOfBoundsException::class);

		$collection = new ChangeAwareCollection();
		$collection->change('not-there');
	}

	public function test_has_works_with_keys_and_values(): void {
		$collection = new ChangeAwareCollection(array('one' => 'first'));

		$this->assertTrue($collection->has('one'));
		$this->assertTrue($collection->has('first'));
		$this->assertFalse($collection->has('second'));
	}

	public function test_array_access(): void {
		$collection = new ChangeAwareCollection();
		$collection['one'] = 'first';

		$this->assertTrue(isset($collection['one']));
		$this->assertSame('first', $collection['one']);

		unset($collection['one']);

		$this->assertFalse(isset($collection['one']));
	}

	public function test_change_aware_values_notify_the_collection(): void {
		$value = new class() implements ChangeAwareInterface {
			use ChangeAwareTrait;

			public string $data = 'initial';
		};

		$collection = new ChangeAwareCollection();
		$collection->add($value, 'tracked', false);

		$this->assertFalse($collection->changed());

		$value->data = 'updated';
		$value->notifyChange();

		$this->assertTrue($collection->changed());
		$this->assertSame(array('tracked' => $value), $collection->getAll('changed'));
	}

	public function test_first_and_empty(): void {
		$collection = new ChangeAwareCollection();

		$this->assertTrue($collection->empty());

		$collection->set('one', 'first');
		$collection->set('two', 'second');

		$this->assertFalse($collection->empty());
		$this->assertSame('first', $collection->first());
	}
}
