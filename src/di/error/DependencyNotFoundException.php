<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\error;

use RuntimeException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Dependency Injection not found exception conforming with PSR-11.
 */
class DependencyNotFoundException extends DependencyInjectionException implements NotFoundExceptionInterface {
}
