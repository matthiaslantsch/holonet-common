<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use Exception;
use DirectoryIterator;

/**
 * FilesystemUtils utility class making interacting with the file system easier.
 */
class FilesystemUtils {
	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent absolute directory path with a trailing separator
	 */
	public static function dirpath(...$parts): string {
		return static::filepath(...$parts).\DIRECTORY_SEPARATOR;
	}

	/**
	 * check if a directory exist and create it if it doesn't.
	 */
	public static function dirShouldExist(string $directory): void {
		if (!file_exists($directory) || !is_dir($directory)) {
			mkdir($directory, 0755, true);
		}
	}

	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent absolute path using the given path parts
	 */
	public static function filepath(...$parts): string {
		$ret = implode(\DIRECTORY_SEPARATOR, $parts);
		//prepend a / on linux
		if ($ret[0] !== \DIRECTORY_SEPARATOR && \DIRECTORY_SEPARATOR === '/') {
			$ret = \DIRECTORY_SEPARATOR.$ret;
		}
		//make sure there's no double separators
		$double = \DIRECTORY_SEPARATOR.\DIRECTORY_SEPARATOR;
		if (mb_strpos($ret, $double) !== false) {
			$ret = str_replace($double, \DIRECTORY_SEPARATOR, $ret);
		}

		return $ret;
	}

	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent directory path relative to the calling file with a trailing separator
	 */
	public static function reldirpath(...$parts): string {
		$bt = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		array_unshift($parts, dirname($bt[0]['file']));

		return static::dirpath(...$parts);
	}

	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent file path relative to the calling file
	 */
	public static function relfilepath(...$parts): string {
		$bt = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		array_unshift($parts, dirname($bt[0]['file']));

		return static::filepath(...$parts);
	}

	/**
	 * Recursively move files from one directory to another.
	 * @param string $src Source of files being moved
	 * @param string $dest Destination of files being moved
	 */
	public static function rmove(string $src, string $dest): void {
		// If source is not a directory just simply move it
		if (!is_dir($src)) {
			rename($src, $dest);

			return;
		}

		// Open the source directory to read in files
		$i = new DirectoryIterator($src);
		foreach ($i as $f) {
			if ($f->isFile()) {
				static::dirShouldExist(dirname("{$dest}/".$f->getFilename()));
				rename($f->getRealPath(), "{$dest}/".$f->getFilename());
			} elseif (!$f->isDot() && $f->isDir()) {
				static::rmove($f->getRealPath(), "{$dest}/{$f}");
			}
		}
		rmdir($src);
	}

	/**
	 * @param string $directory The path to recursively delete
	 * @param bool $throw Boolean flag marking whether to throw an exception or not
	 * @throws Exception if a path could not be deleted and the throw flag is given
	 */
	public static function rrmdir(string $directory, bool $throw = false): void {
		if (!file_exists($directory)) {
			return;
		}

		if (is_dir($directory)) {
			$objects = scandir($directory);
			foreach ($objects as $object) {
				if ($object !== '.' && $object !== '..') {
					if (is_dir($directory.\DIRECTORY_SEPARATOR.$object)) {
						static::rrmdir($directory.\DIRECTORY_SEPARATOR.$object);
					} else {
						static::rrmdir($directory.\DIRECTORY_SEPARATOR.$object);
					}
				}
			}
			if (!@rmdir($directory) && $throw) {
				$err = error_get_last();
				$msg = ($err !== null ? $err['message'] : 'No Error');

				throw new Exception("Could not rmdir '{$directory}': {$msg}", 100);
			}
		} else {
			if ((!@unlink($directory) && $throw) || file_exists($directory)) {
				$err = error_get_last();
				$msg = ($err !== null ? $err['message'] : 'No Error');

				throw new Exception("Could not unlink '{$directory}': {$msg}", 100);
			}
		}
	}
}
