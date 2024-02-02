<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Url extends Rule implements CheckValueRuleInterface {

	public static function defaultMessage(): string {
		return ':attr must be a valid url';
	}

	public function pass(mixed $value): bool {
		return filter_var($value, FILTER_VALIDATE_URL) !== false;
	}

}
