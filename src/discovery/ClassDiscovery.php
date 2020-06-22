<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\discovery;

use RuntimeException;
use DirectoryIterator;
use InvalidArgumentException;

/**
 * Class discovery utility class.
 * Different extending classes can be used to detect php classes from files.
 */
abstract class ClassDiscovery {
	/**
	 * @var bool $ensureClassExists Boolean flag that can be used to check if the discovered class truly exists
	 */
	public $ensureClassExists = true;

	/**
	 * @var string $scannedExtension File extension to limit what files to scan for classes
	 */
	public $scannedExtension = 'php';

	/**
	 * Loop through all files in a directory and attempt to discover classes from it.
	 * @param string $directory The path to the directory to scan
	 * @param bool $throwOnFailure Boolean flag whether to throw an exception if a file does not contain a valid class
	 * @return array<class-string>
	 */
	public function fromDirectory(string $directory, bool $throwOnFailure = false): array {
		if (!is_dir($directory) || !is_readable($directory)) {
			throw new InvalidArgumentException("Target directory {$directory} does not exist");
		}

		$dir = new DirectoryIterator($directory);
		$classes = array();
		foreach ($dir as $fileinfo) {
			if (!$fileinfo->isDot() && $fileinfo->getExtension() === $this->scannedExtension) {
				if (($class = $this->fromFile($fileinfo->getPathname())) !== null) {
					if ($this->ensureClassExists && !class_exists($class)) {
						throw new RuntimeException("Discovered class '{$class}' from file '{$fileinfo->getPathname()}', but class does not exist (Autoloading problem?)");
					}
					$classes[] = $class;
				} elseif ($throwOnFailure) {
					throw new RuntimeException("Could not discover any classes from file '{$fileinfo->getPathname()}'");
				}
			}
		}

		return $classes;
	}

	/**
	 * Autodiscover a fully qualified class name from the given php code file.
	 * @param string $filename The path to the php file
	 * @psalm-return class-string
	 */
	abstract public function fromFile(string $filename): string;
}
