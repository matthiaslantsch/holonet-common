<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common\Enum;

/**
 * @method static TestEnum VALUE1()
 * @method static TestEnum VALUE2()
 * @method static TestEnum VALUE3()
 * @method static TestEnum VALUE4()
 * @psalm-immutable
 */
class TestEnum extends Enum {
	private const VALUE1 = array('value1', 'secondAttr1', 'thirdAttr1');

	private const VALUE2 = array('value2', 'secondAttr2');

	private const VALUE3 = array('value3');

	private const VALUE4 = 'value4';

	protected string $secondAttr = 'default';

	protected ?string $thirdAttr = null;

	protected function __construct($value, string $secondAttr = null, string $thirdAttr = null) {
		parent::__construct($value);
		if ($secondAttr !== null) {
			$this->secondAttr = $secondAttr;
		}
		$this->thirdAttr = $thirdAttr;
	}
}
