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

/**
 * Tests the functionality of the extended enum class.
 *
 * @internal
 *
 * @small
 * @covers \holonet\common\Enum
 */
class EnumTest extends TestCase {
	public function testEnumExtraFeatures(): void {
		// we should be able to instantiate all 4 enum values even though they are defined differently in the constant
		static::assertNotNull(TestEnum::VALUE1());
		static::assertNotNull(TestEnum::VALUE2());
		static::assertNotNull(TestEnum::VALUE3());
		static::assertNotNull(TestEnum::VALUE4());

		$value1 = TestEnum::VALUE1();
		// no matter how it's accessed (static method or from dynamic value) it should always be the same instance
		static::assertSame($value1, TestEnum::fromValue('value1'));
		static::assertSame($value1, TestEnum::valueOf('VALUE1'));
	}

	/**
	 * @covers \holonet\common\Enum::toArray()
	 */
	public function testToArrayMethod(): void {
		$array = TestEnum::toArray();
		static::assertSame(array(
			'VALUE1' => 'value1',
			'VALUE2' => 'value2',
			'VALUE3' => 'value3',
			'VALUE4' => 'value4'
		), $array);
	}

	/**
	 * @covers \holonet\common\Enum::values()
	 */
	public function testValuesMethod(): void {
		$values = array(
			'VALUE1' => TestEnum::VALUE1(),
			'VALUE2' => TestEnum::VALUE2(),
			'VALUE3' => TestEnum::VALUE3(),
			'VALUE4' => TestEnum::VALUE4(),
		);

		static::assertSame($values, TestEnum::values());
	}
}
