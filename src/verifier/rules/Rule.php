<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier\rules;

use function holonet\common\stringify;

abstract class Rule {
	public function __construct(public ?string $message = null) {
		$this->message ??= static::defaultMessage();
	}

	public static function defaultMessage(): string {
		return ':attr is invalid';
	}

	public function message(string $attr): string {
		$message = str_replace(':attr', $attr, $this->message);

		$words = explode(' ', $message);
		foreach ($words as &$word) {
			if (!str_contains($word, ':')) {
				continue;
			}

			// allow for punctuation symbols around placeholders
			$prop = trim($word, ':,;)({}[]."\'');
			$word = $this->replacePlaceholder($word, $prop);
		}

		return implode(' ', array_filter($words));
	}

	protected function replacePlaceholder(string $subject, string $prop): string {
		if (!property_exists($this, $prop)) {
			return $subject;
		}

		if ($prop === 'not') {
			$replace = ($this->not ?? false) ? 'not' : '';
		} else {
			$replace = stringify($this->{$prop});
		}

		return str_replace(":{$prop}", $replace, $subject);
	}
}
