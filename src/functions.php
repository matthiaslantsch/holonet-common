<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * file for convenience functions
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use DirectoryIterator;

if(!function_exists(__NAMESPACE__."\\registry")) {
	/**
	 * function used as getter/setter shorthand function for the registry
	 * if called with 1 parameter it acts as a getter
	 * if called with 2 parameters it acts as a setter
	 *
	 * @param  string $key Specified key for the registry operation
	 * @param  mixed $value Value to be set in the registry (if called as a getter)
	 * @return void|mixed only a value of called as a getter (with 2 arguments)
	 */
	function registry() {
		$args = func_get_args();
		if(count($args) == 1) {
			return Registry::get($args[0]);
		} elseif(count($args) == 2) {
			Registry::set($args[0], $args[1]);
		}
	}
}

if(!function_exists(__NAMESPACE__."\\trigger_error_context")) {
	/**
	 * function using the php debug backtrace to trigger an error on the calling line
	 * probably easier to just use Exceptions
	 *
	 * @param  string $message The message to throw in the error
	 * @param  integer $level Error level integer, defaults to E_USER_ERROR
	 * @return void
	 */
	function trigger_error_context($message, $level = E_USER_ERROR) {
		$caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
		trigger_error("{$message} in file {$caller["file"]} on line {$caller["line"]}", $level);
	}
}

if(!function_exists(__NAMESPACE__."\\reldirpath")) {
	/**
	 * function used to create a system independent directory path relative to the calling file
	 *
	 * @param  variable number of path elements
	 * @return string relative directory path to the calling file
	 */
	function reldirpath() {
		$bt =  debug_backtrace();
		$pathelements = func_get_args();
		array_unshift($pathelements, dirname($bt[0]['file']));
		//append empty path element in the end
		array_push($pathelements, "");
		return call_user_func_array(__NAMESPACE__."\\filepath", $pathelements);
	}
}

if(!function_exists(__NAMESPACE__."\\dirpath")) {
	/**
	 * function used to create a system independent absolute directory path
	 *
	 * @param  variable number of path elements
	 * @return string relative file path to the calling file
	 */
	function dirpath() {
		$pathelements = func_get_args();
		//append empty path element in the end
		array_push($pathelements, "");
		return call_user_func_array(__NAMESPACE__."\\filepath", $pathelements);
	}
}

if(!function_exists(__NAMESPACE__."\\relfilepath")) {
	/**
	 * function used to create a system independent path relative to the calling file
	 *
	 * @param  variable number of path elements
	 * @return string relative path to the calling file
	 */
	function relfilepath() {
		$bt =  debug_backtrace();
		$pathelements = func_get_args();
		array_unshift($pathelements, dirname($bt[0]['file']));
		return call_user_func_array(__NAMESPACE__."\\filepath", $pathelements);
	}
}

if(!function_exists(__NAMESPACE__."\\filepath")) {
	/**
	 * function used to create a system independent absolute path using the given path parts
	 *
	 * @param  variable number of path elements
	 * @return string absolute path with the given path elements
	 */
	function filepath() {
		$ret = join(DIRECTORY_SEPARATOR, func_get_args());
		//prepend a / on linux
		if($ret[0] !== DIRECTORY_SEPARATOR && DIRECTORY_SEPARATOR == '/') {
			$ret = DIRECTORY_SEPARATOR.$ret;
		}
		//make sure there's no double separators
		$double = DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR;
		if(strpos($ret, $double) !== false) {
			$ret = str_replace($double, DIRECTORY_SEPARATOR, $ret);
		}
		return $ret;
	}
}

if(!function_exists(__NAMESPACE__."\\rrmdir")) {
	/**
	 * function used to recursively delete a directory and it's content
	 * throws exceptions if it didn't work if the second boolean flag is given
	 *
	 * @param  string $directory The path to recursively delete
	 * @param  boolean $throw Boolean flag determing wheter to throw an exception or not
	 * @return void
	 * @throws \Exception if a path could not be deleted and the throw flag is given
	 */
	function rrmdir($directory, $throw = false) {
		if(!file_exists($directory)) {
			return;
		}

		if(is_dir($directory)) {
			$objects = scandir($directory);
			foreach ($objects as $object) {
				if($object != "." && $object != "..") {
					if (is_dir($directory.DIRECTORY_SEPARATOR.$object)) {
						rrmdir($directory.DIRECTORY_SEPARATOR.$object);
					} else {
						rrmdir($directory.DIRECTORY_SEPARATOR.$object);
					}
				}
			}
			if(!@rmdir($directory) && $throw) {
				throw new \Exception("Could not rmdir '{$directory}'", 100);
			}
		} else {
			if(!@unlink($directory) && $throw) {
				throw new \Exception("Could not unlink '{$directory}'", 100);
			}
		}
	}
}

if(!function_exists(__NAMESPACE__."\\rmove")) {
	/**
	 * Recursively move files from one directory to another
	 *
	 * @param String $src Source of files being moved
	 * @param String $dest Destination of files being moved
	 */
	function rmove(string $src, string $dest) {
		// If source is not a directory just simply move it
		if(!is_dir($src)) {
			return rename($src, $dest);
		}

		// Open the source directory to read in files
		$i = new DirectoryIterator($src);
		foreach($i as $f) {
			if($f->isFile()) {
				dir_should_exist(dirname("$dest/" . $f->getFilename()));
				rename($f->getRealPath(), "$dest/" . $f->getFilename());
			} else if(!$f->isDot() && $f->isDir()) {
				rmove($f->getRealPath(), "$dest/$f");
			}
		}
		rmdir($src);
	}
}

if(!function_exists(__NAMESPACE__."\\dir_should_exist")) {
	/**
	 * function used to make sure a dir exists
	 * creates it if it doesn't
	 *
	 * @param  string $directory The path to check/create
	 * @return void
	 */
	function dir_should_exist($directory) {
		if(!file_exists($directory) || !is_dir($directory)) {
			mkdir($directory, 0755, true);
		}
	}
}

if(!function_exists(__NAMESPACE__."\\indentText")) {
	/**
	 * function used to indent a text with newlines in it
	 * used to indent multiline text evenly
	 *
	 * @param  string $text The text to indent
	 * @param  int $number The number of tabs to indent
	 * @return string the string with the applied indent
	 */
	function indentText(string $text, int $number = 1) {
		return str_replace("\n", "\n".str_repeat("\t", $number), $text);
	}
}

if(!function_exists(__NAMESPACE__."\\isAssoc")) {
	/**
	 * function used to check if an array is associative
	 *
	 * @param  array $arr The array to check
	 * @return true or false on is associative or not
	 */
	function isAssoc(array $arr) {
		if (array() === $arr) {
			return false;
		}
		ksort($arr);
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}


if(!function_exists(__NAMESPACE__."\\readableDurationString")) {
	/**
	 * function used to transform a duration into a human readable string
	 *
	 * @param  numeric $time The duration in seconds
	 * @return string with the duration in a human readable format
	 */
	function readableDurationString($time) {
		 if($time >= 86400) {
	 		if($time % 86400 == 0) {
	 			return $time / 86400 . "days";
	 		}
	 		return (int)($time / 86400) . 'days ' . (int)($time % 86400 / 3600) . 'h';
	 	} elseif($time >= 3600) {
	 		if($time % 3600 == 0) {
	 			return $time / 3600 . "h";
	 		}
	 		return (int)($time / 3600) . 'h ' . (int)($time % 3600 / 60) . 'min';
	 	} if($time >= 60) {
	 		if($time % 60 == 0) {
	 			return $time / 60 . "min";
	 		}
	 		return (int)($time / 60) . 'min ' . (int)($time % 60) . 's';
	 	} else {
	 		return $time . 's';
	 	}
	}
}
