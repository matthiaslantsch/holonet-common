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
