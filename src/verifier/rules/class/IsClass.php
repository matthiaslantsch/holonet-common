<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules\class;

use Attribute;
use holonet\common\verifier\rules\CheckValueRuleInterface;
use holonet\common\verifier\rules\Rule;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsClass extends Rule implements CheckValueRuleInterface {

	public static function defaultMessage(): string {
		return ':attr must be a valid class name string (:value given)';
	}

	public function pass(mixed $value): bool {
		return is_string($value) && class_exists($value);
	}
}
