<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Required extends Rule {
	public static function defaultMessage(): string {
		return ':attr is required';
	}
}
