<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the ConfigReader class
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

/**
 * The ConfigReader is used to read various config file formats into the registry
 * Currently supported formats include:
 *  - ini
 *  - php (must define $config variable)
 *  - json
 *
 * @author  matthias.lantsch
 * @package holonet\common
 */
class ConfigReader {

	/**
	 * static method used to read a config file without specifying the type
	 * this method will probably be the one used most
	 *
	 * @access private
	 * @param  string $filename The path to the config file that should be read
	 * @param  boolean $return Boolean flag determing wheter to write the values to the registry or return the,
	 * @return void|array returns array if that was requested by the second parameter (boolean flag)
	 */
	public static function read($filename, $return = false) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		return static::__callStatic($ext, [$filename, $return]);
	}

	/**
	 * static magic method used to dispatch a config parse call
	 * verifies the config file type and file location
	 * either returns the data or saves it to the Registry
	 * e.g. ConfigReader::ini($file) to get it into the registry, ConfigReader::ini($file, true) to return the values
	 *
	 * @access private
	 * @param  string $name The name of the magic method called (config file type in this case)
	 * @param  array $args Arguments that were passed to the static method call (filename, return flag boolean)
	 * @return void|array returns array if that was requested by the second parameter (boolean flag)
	 * @throws Exception if filetype is unknown, no file path was given or if the file doesn't exist/is unreadable
	 */
	public static function __callStatic($name, $args) {
		$parsermethod = "read".strtoupper($name);
		if(!method_exists(__CLASS__, $parsermethod)) {
			throw new \Exception("Unknown config file type '{$name}'", 1000);
		}

		if(!isset($args[0])) {
			throw new \Exception("No config file path was specified", 1001);
		}

		$filename = array_shift($args);
		if(!file_exists($filename) || !is_readable($filename)) {
			throw new \Exception("Config file '{$filename}' doesn't exist or cannot be read", 1002);
		}

		$data = forward_static_call_array(array(__CLASS__, $parsermethod), array($filename));

		if($data === false) {
			throw new \Exception("Error reading config file '{$filename}'", 1003);
		}

		//check if the user wants the data returned or saved to the registry
		if(isset($args[0]) && $args[0] === true) {
			return $data;
		} else {
			foreach ($data as $key => $value) {
				Registry::set($key, $value);
			}
		}
	}

	/**
	 * actual parsing method used to read ini config files
	 *
	 * @access private
	 * @param  string $path The path to the config file that should be read
	 * @return false|array returns array with the parsed data or false if an error occured
	 */
	private static function readINI($path) {
		return parse_ini_file($path);
	}

	/**
	 * actual parsing method used to read php config files
	 * reads the file into this context and looks for
	 * a defined $config variable that will be returned
	 *
	 * @access private
	 * @param  string $path The path to the config file that should be read
	 * @return false|array returns array with the parsed data or false if an error occured
	 */
	private static function readPHP($path) {
		require $path;
		if(!isset($config)) {
			return false;
		} else {
			return $config;
		}
	}

	/**
	 * actual parsing method used to read json config files
	 * reads the file into this context and looks for
	 * a defined $config variable that will be returned
	 *
	 * @access private
	 * @param  string $path The path to the config file that should be read
	 * @return false|array returns array with the parsed data or false if an error occured
	 */
	private static function readJSON($path) {
		$data = json_decode(file_get_contents($path), true);
		if($data === null) {
			return false;
		} else {
			return $data;
		}
	}

}
