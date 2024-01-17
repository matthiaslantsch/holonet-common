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
class Directory extends PathRule {

	public function __construct(public bool $create = false, public  bool $recursive = false, string $message = null) {
		parent::__construct($message);
	}

	public static function defaultMessage(): string {
		return "':value' is not a directory";
	}

	public function pass(mixed $value): bool {
		if ($this->create && !is_dir($value)) {
			if (!mkdir($value, 0777, $this->recursive)) {
				return false;
			}
		}

		return is_dir($value);
	}
}
