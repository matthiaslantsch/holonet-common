<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\verifier;

use ReflectionObject;
use ReflectionProperty;
use holonet\common\verifier\rules\Required;
use holonet\common\verifier\rules\ValueRule;
use function holonet\common\reflection_get_attribute;

/**
 * Base verifier simply reads the rule attributes on the given object and checks against them.
 */
class Verifier {
	public function verify(object $obj): Proof {
		$proof = new Proof();

		$reflection = new ReflectionObject($obj);
		foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			$this->verifyAttribute($obj, $proof, $property);
		}

		return $proof;
	}

	protected function verifyAttribute(object $obj, Proof $proof, ReflectionProperty $property): void {
		if (!$property->isInitialized($obj) || $property->getValue($obj) === null) {
			$required = reflection_get_attribute($property, Required::class);
			if ($required === null) {
				return;
			}

			$proof->add($property->getName(), $required->message($property->getName()));

			return;
		}

		$value = $property->getValue($obj);
		foreach ($property->getAttributes() as $rule) {
			$rule = $rule->newInstance();
			if (!$rule instanceof ValueRule) {
				continue;
			}

			if (!$rule->pass($value)) {
				$proof->add($property->getName(), $rule->message($property->getName()));
			}
		}
	}
}
