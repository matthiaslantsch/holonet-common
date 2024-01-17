<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\discovery;

use InvalidArgumentException;
use holonet\common\FilesystemUtils;

/**
 * Class discovery utility class.
 * Uses the php token parser system to discover class and namespace.
 */
class Psr4ClassDiscovery extends ClassDiscovery {
	/**
	 * Namespace basis for all namespaces below the psr4 directory structure.
	 */
	protected ?string $baseNamespace;

	/**
	 * The directory at which the root of the psr4 namespace starts.
	 */
	private string $srcDirectory;

	public function __construct(string $srcDirectory, ?string $baseNamespace = null) {
		if (!is_dir($srcDirectory) || !is_readable($srcDirectory)) {
			throw new InvalidArgumentException("Could not find / read directory '{$srcDirectory}'");
		}
		$this->srcDirectory = FilesystemUtils::dirpath($srcDirectory);
		$this->baseNamespace = $baseNamespace;
	}

	/**
	 * {@inheritDoc}
	 */
	public function fromFile(string $filename): string {
		if (mb_strpos($filename, $this->srcDirectory) !== 0) {
			throw new InvalidArgumentException("Path '{$filename}' is not under the psr4 root directory '{$this->srcDirectory}'");
		}

		$className = str_replace(array($this->srcDirectory, ".{$this->scannedExtension}"), '', $filename);
		$className = str_replace('/', '\\', $className);

		if ($this->baseNamespace !== null) {
			$className = "{$this->baseNamespace}\\{$className}";
		}

		return $className;
	}
}
