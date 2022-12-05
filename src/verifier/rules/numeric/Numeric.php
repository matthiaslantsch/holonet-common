<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules\numeric;

use Attribute;
use holonet\common\verifier\rules\Rule;
use holonet\common\verifier\rules\CheckValueRuleInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Numeric extends Rule implements CheckValueRuleInterface {
	public function __construct(
		?string $message = null,
	) {
		parent::__construct($message);
	}

	public static function defaultMessage(): string {
		return ':attr must be numeric';
	}

	public function pass(mixed $value): bool {
		return is_numeric($value);
	}
}
