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

	public function __construct(
		?string $message = null, protected bool $host = false, protected bool $path = false,
		protected bool $query = false, bool $fragment = false
	) {
		parent::__construct($message);
	}

	public static function defaultMessage(): string {
		return ':attr must be a valid url';
	}

	public function pass(mixed $value): bool {
		$options = 0;
		if ($this->host) {
			$options |= FILTER_FLAG_HOSTNAME;
		}
		if ($this->path) {
			$options |= FILTER_FLAG_PATH_REQUIRED;
		}
		if ($this->query) {
			$options |= FILTER_FLAG_QUERY_REQUIRED;
		}

		return filter_var($value, FILTER_VALIDATE_URL, $options) !== false;
	}

}
