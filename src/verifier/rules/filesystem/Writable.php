<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules\filesystem;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Writable extends PathRule {
	public static function defaultMessage(): string {
		return "':value' is not writable";
	}

	public function pass(mixed $value): bool {
		return is_readable($value) && is_writable($value);
	}
}
