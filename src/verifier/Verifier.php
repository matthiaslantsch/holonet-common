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
use holonet\common\verifier\rules\Rule;
use holonet\common\verifier\rules\Required;
use function holonet\common\reflection_get_attribute;
use holonet\common\verifier\rules\CheckValueRuleInterface;
use holonet\common\verifier\rules\TransformValueRuleInterface;
use function holonet\common\reflection_get_attributes;

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

		if ($obj instanceof VerifiesState) {
			$obj->verify($proof);
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
		foreach ($this->getRulesForProperty($property) as $rule) {
			if (is_array($value)) {
				foreach ($value as $i => $val) {
					if ($rule instanceof TransformValueRuleInterface) {
						$value[$i] = $this->transformValue($rule, $val);
					}
					if ($rule instanceof CheckValueRuleInterface) {
						$this->checkValue($rule, $value[$i], $proof,"{$property->getName()}.$i");
					}
				}
			} else {
				if ($rule instanceof TransformValueRuleInterface) {
					$value = $this->transformValue($rule, $value);
				}
				if ($rule instanceof CheckValueRuleInterface) {
					$this->checkValue($rule, $value, $proof, $property->getName());
				}
			}
			if ($rule instanceof TransformValueRuleInterface) {
				$property->setValue($obj, $value);
			}
		}
	}

	/**
	 * @return Rule[]
	 */
	protected function getRulesForProperty(ReflectionProperty $property): array	{
		return reflection_get_attributes($property, Rule::class);
	}

	protected function transformValue(TransformValueRuleInterface&Rule $rule, mixed $value): mixed {
		return $rule->transform($value);
	}

	protected function checkValue(CheckValueRuleInterface&Rule $rule, mixed $value, Proof $proof, string $property): void {
		if (!$rule->pass($value)) {
			$proof->add($property, $rule->message($property, $value));
		}
	}

}
