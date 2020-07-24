<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use RuntimeException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Dependency Injection not found exception conforming with PSR-11.
 */
class DependencyNotFoundException extends RuntimeException implements NotFoundExceptionInterface {
}
