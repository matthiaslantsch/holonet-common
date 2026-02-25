<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules\filesystem;

use holonet\common\verifier\rules\Rule;
use function holonet\common\get_absolute_path;
use holonet\common\verifier\rules\CheckValueRuleInterface;
use holonet\common\verifier\rules\TransformValueRuleInterface;

abstract class PathRule extends Rule implements CheckValueRuleInterface, TransformValueRuleInterface {
	public function transform(mixed $value): mixed {
		return get_absolute_path($value);
	}
}
