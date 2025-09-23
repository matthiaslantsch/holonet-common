<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use holonet\common\collection\ConfigRegistry;
use holonet\common\di\autowire\AutoWire;
use holonet\common\di\concerns\HasServicesConcern;
use holonet\common\di\concerns\MakesObjectConcern;
use holonet\common\di\concerns\TracksAbstractsConcern;
use holonet\common\di\concerns\TracksCallablesConcern;
use holonet\common\di\concerns\TracksRecursionConcern;
use holonet\common\di\concerns\TracksWiringConcern;
use holonet\holofw\auth\Authoriser;
use Psr\Container\ContainerInterface;

/**
 * Dependency Injection container conforming with PSR-11.
 *
 * The container implements the following concerns:
 * - Services: shared object instances (named dependencies), created once and reused whenever requested.
 * - Track abstracts: an abstract in this case could be:
 * 	- an interface name (e.g. `MyInterface`) that has a concrete class mapped to it
 * 	- an abstract base class name (e.g. `MyBaseClass`) that has a concrete class mapped to it
 * 	- an arbitrary name (e.g. `that_class`) that has a concrete class mapped to it
 * - Object creation: instantiate new objects and inject them with dependencies.
 */
class Container implements ContainerInterface {

	use HasServicesConcern;
	use TracksAbstractsConcern;
	use MakesObjectConcern;
	use TracksWiringConcern;
	use TracksRecursionConcern;

	protected AutoWire $autoWiring;

	public function __construct(public ConfigRegistry $registry = new ConfigRegistry()) {
		$this->autoWiring = new AutoWire($this);

		$this->set('container', $this);
		$this->set('registry', $this->registry);
	}

	protected function byType(string $type, string $hint): object {
		// first we assume the parameter name is an actual hint as to which service is wanted
		if ($this->has($hint)) {
			$containerType = $this->resolve($hint);
			if (is_a($containerType, $hint, true)) {
				return $this->get($hint);
			}
		}

		return $this->instance($hint);
	}

}
