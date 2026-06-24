<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use Exception;
use RuntimeException;
use DirectoryIterator;

/**
 * FilesystemUtils utility class making interacting with the file system easier.
 */
class FilesystemUtils {
	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent absolute directory path with a trailing separator
	 */
	public static function dirpath(string ...$parts): string {
		return static::filepath(...$parts).\DIRECTORY_SEPARATOR;
	}

	/**
	 * check if a directory exist and create it if it doesn't.
	 */
	public static function dirShouldExist(string $directory): void {
		// re-check is_dir() after a failed mkdir() in case of a race with another process
		if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
			throw new RuntimeException(sprintf("Could not create directory '%s': %s", $directory, error_get_last()['message'] ?? 'unknown error'));
		}
	}

	/**
	 * Read in the contents of a file and check whether a given string is contained within.
	 */
	public static function fileContains(string $path, string $search): bool {
		return str_contains(static::readFileContents($path), $search);
	}

	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent absolute path using the given path parts
	 */
	public static function filepath(string ...$parts): string {
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
	 * Wrapper around file_get_contents() which throws an exception if the underlying call failed.
	 */
	public static function readFileContents(string $path): string {
		$contents = @file_get_contents($path);
		if ($contents === false) {
			throw new RuntimeException(sprintf("Could not file_get_contents() path '%s': last error on the stack: %s", $path, error_get_last()['message']));
		}

		return $contents;
	}

	/**
	 * Wrapper around file() which throws an exception if the underlying call failed.
	 */
	public static function readFileLines(string $path): array {
		$contents = @file($path);
		if ($contents === false) {
			throw new RuntimeException(sprintf("Could not file() path '%s': %s", $path, error_get_last()['message']));
		}

		return $contents;
	}

	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent directory path relative to the calling file with a trailing separator
	 */
	public static function reldirpath(string ...$parts): string {
		$bt = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		array_unshift($parts, dirname($bt[0]['file']));

		return static::dirpath(...$parts);
	}

	/**
	 * @param string ...$parts variable number of path elements
	 * @return string system independent file path relative to the calling file
	 */
	public static function relfilepath(string ...$parts): string {
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
			static::dirShouldExist(dirname($dest));
			if (!@rename($src, $dest)) {
				$error = error_get_last();
				throw new RuntimeException("Could not move {$src} to {$dest}: {$error['message']}");
			}
			return;
		}

		// Open the source directory to read in files
		$i = new DirectoryIterator($src);
		$moved = 0;
		foreach ($i as $f) {
			if ($f->isDot()) {
				continue;
			}

			if ($f->isFile()) {
				static::dirShouldExist(dirname("{$dest}/".$f->getFilename()));
				if (!@rename($f->getRealPath(), "{$dest}/".$f->getFilename())) {
					$error = error_get_last();
					throw new RuntimeException("Could not move {$f->getRealPath()} to {$dest}/{$f->getFilename()}: {$error['message']}");
				}
			} elseif (!$f->isDot() && $f->isDir()) {
				static::rmove($f->getRealPath(), "{$dest}/{$f}");
			}
			$moved++;
		}
		if ($moved === 0) {
			// empty directory, just move it
			if (!@rename($src, $dest)) {
				$error = error_get_last();
				throw new RuntimeException("Could not move {$src} to {$dest}: {$error['message']}");
			}
		} else {
			// new directory was already created, remove the old directory
			if (!@rmdir($src)) {
				$error = error_get_last();
				throw new RuntimeException("Could not remove directory {$src}: {$error['message']}");
			}
		}
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
			foreach (scandir($directory) as $object) {
				if ($object !== '.' && $object !== '..') {
					static::rrmdir($directory.\DIRECTORY_SEPARATOR.$object, $throw);
				}
			}
			if (!@rmdir($directory) && $throw) {
				$msg = error_get_last()['message'];

				throw new Exception("Could not rmdir '{$directory}': {$msg}");
			}
		} elseif (!@unlink($directory) && $throw) {
			$msg = error_get_last()['message'];

			throw new Exception("Could not unlink '{$directory}': {$msg}");
		}
	}
}
