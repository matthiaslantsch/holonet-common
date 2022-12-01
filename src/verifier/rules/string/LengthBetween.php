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
class LengthBetween extends ValueRule {
	public function __construct(
		public int $low,
		public int $high,
		?string $message = null,
	) {
		parent::__construct($message);
	}

	public static function defaultMessage(): string {
		return 'the length of :attr must be between :low and :high characters';
	}

	public function pass(mixed $value): bool {
		$len = mb_strlen($value);

		return $len >= $this->low && $len <= $this->high;
	}
}
