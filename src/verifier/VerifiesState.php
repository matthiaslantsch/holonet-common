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
 * This interface will allow an implementing dto object to define it's own arbitrary verification rules.
 */
interface VerifiesState {
	public function verify(Proof $proof): void;
}
