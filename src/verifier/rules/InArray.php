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
class InArray extends ValueRule {
	public function __construct(
		public array $values,
		public bool $not = false,
		public bool $strict = false,
		?string $message = null,
	) {
		parent::__construct($message);
	}

	public static function defaultMessage(): string {
		return ':attr must :not be one of :values';
	}

	public function pass(mixed $value): bool {
		return (!$this->not) === in_array($value, $this->values, $this->strict);
	}
}
