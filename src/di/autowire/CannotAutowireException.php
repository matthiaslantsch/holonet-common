<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\autowire;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionFunctionAbstract;
use holonet\common\di\DependencyInjectionException;

class CannotAutowireException extends AutoWireException {
}
