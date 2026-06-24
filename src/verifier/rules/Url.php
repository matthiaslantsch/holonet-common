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
		protected bool $query = false, protected bool $fragment = false
	) {
		parent::__construct($message);
	}

	public static function defaultMessage(): string {
		return ':attr must be a valid url';
	}

	public function pass(mixed $value): bool {
		$options = 0;
		if ($this->path) {
			$options |= FILTER_FLAG_PATH_REQUIRED;
		}
		if ($this->query) {
			$options |= FILTER_FLAG_QUERY_REQUIRED;
		}

		if (filter_var($value, FILTER_VALIDATE_URL, $options) === false) {
			return false;
		}

		// filter_var() offers no flags for these two requirements, check the url components manually
		if ($this->host && empty(parse_url($value, \PHP_URL_HOST))) {
			return false;
		}

		return !($this->fragment && empty(parse_url($value, \PHP_URL_FRAGMENT)));
	}

}
