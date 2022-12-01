<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules\string;

use Attribute;
use holonet\common\verifier\rules\ValueRule;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength extends ValueRule {
	public function __construct(
		public int $max,
		?string $message = null,
	) {
		parent::__construct($message);
	}

	public static function defaultMessage(): string {
		return ':attr must be at most :max characters long';
	}

	public function pass(mixed $value): bool {
		return mb_strlen($value) <= $this->max;
	}
}
