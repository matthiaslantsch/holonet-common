<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di;

use holonet\common\di\discovery\ConfigDependencyDiscovery;
use holonet\common\di\discovery\DependencyDiscovery;
use holonet\common\error\BadEnvironmentException;
use holonet\common\collection\ConfigRegistry;
use Throwable;

/**
 * Factory class that is supposed to initialise a container based on a configuration.
 */
class Factory {

	/**
	 * @var DependencyDiscovery[] $discoverers
	 */
	protected array $discoverers = array();

	public function __construct(protected ConfigRegistry $registry = new ConfigRegistry()) {
		$this->discoverers[] = new ConfigDependencyDiscovery();
	}

	public function discover(DependencyDiscovery $discovery): void {
		$this->discoverers[] = $discovery;
	}

	public function make(array $initialServices = array()): Container {
		$warnAboutInefficientInstantiation = $this->registry->get('di.warn_on_inefficient_instantiation', false);
		$this->registry->set('di.warn_on_inefficient_instantiation', false);
		try {
			if ($this->registry->has('di.cache_path')) {
				return $this->makeCompiledContainer($initialServices);
			}

			return $this->makeContainer($initialServices);
		} finally {
			$this->registry->set('di.warn_on_inefficient_instantiation', $warnAboutInefficientInstantiation);
		}
	}

	private function makeCompiledContainer(array $initialServices = array()): Container {
		$cacheFile = $this->cacheFilePath();
		$config = $this->registry;

		if (file_exists($cacheFile)) {
			$container = require $cacheFile;
			foreach ($initialServices as $name => $service) {
				$container->set($name, $service);
			}
			return $container;
		}

		$container = $this->makeContainer($initialServices);
		$compiler = new Compiler($container);

		// LOCK_EX so two processes bootstrapping concurrently cannot interleave
		// their writes and corrupt the cache file
		file_put_contents($cacheFile, "<?php\n\n{$compiler->compile()}", LOCK_EX);
		return $this->makeCompiledContainer($initialServices);
	}

	private function makeContainer(array $initialServices = array()): Container {
		$container = new Container($this->registry);
		foreach ($initialServices as $service => $instance) {
			$container->set($service, $instance);
		}
		foreach ($this->discoverers as $discoverer) {
			$discoverer->discover($container);
		}
		return $container;
	}

	private function cacheFilePath(): string {
		$dir = $this->registry->get('di.cache_path');

		if (!is_dir($dir) || !is_writable($dir)) {
			throw new BadEnvironmentException("Container compile path '{$dir}' is not a writable directory");
		}

		return "{$dir}/container.php";
	}

}
