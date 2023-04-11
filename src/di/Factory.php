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
use holonet\common\config\ConfigRegistry;

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

	public function make(): Container {
		if ($this->registry->has('di.cache_path')) {
			return $this->makeCompiledContainer();
		} else {
			return $this->makeContainer();
		}
	}

	private function makeCompiledContainer(): Container {
		$cacheFile = $this->cacheFilePath();
		$config = $this->registry;

		if (file_exists($cacheFile)) {
			return require $cacheFile;
		}

		$container = $this->makeContainer();
		$compiler = new Compiler($container);

		file_put_contents($cacheFile, "<?php\n\n{$compiler->compile()}");
		return require $cacheFile;
	}

	private function makeContainer(): Container {
		$container = new Container($this->registry);
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
