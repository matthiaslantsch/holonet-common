<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\autowire\provider;

use ReflectionNamedType;
use ReflectionParameter;
use holonet\common\di\Container;

/**
 * Interface for parameter auto-wire behaviours.
 * An implementation is supposed to provide a way to auto-wire a given type of parameter.
 */
interface ParamAutoWireProvider {
	/**
	 * @param ReflectionParameter $param reflection object for the parameter that should be auto-wired
	 * @param ReflectionNamedType $type reflection type for the given parameter
	 * @param mixed $givenParam Parameter provided by the user
	 *                          The given parameter could come from:
	 *                          - an array that the user supplied to a get() or wire() call on the container
	 *                          - a config array in the container configuration on the registry
	 *
	 * This method should return a mapped parameter value. If it is not appropriate for this provider to supply
	 * such a value, it should return null
	 * If it is appropriate to return a value but it can't it should throw an exception instead.
	 */
	public function provide(Container $container, ReflectionParameter $param, ReflectionNamedType $type, mixed $givenParam): mixed;
}
