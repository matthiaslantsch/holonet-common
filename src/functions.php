<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

if (!function_exists(__NAMESPACE__.'\\trigger_error_context')) {
	/**
	 * function using the php debug backtrace to trigger an error on the calling line.
	 * @param string $message The message to throw in the error
	 * @param int $level Error level integer, defaults to E_USER_ERROR
	 */
	function trigger_error_context(string $message, int $level = E_USER_ERROR): void {
		$caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
		trigger_error("{$message} in file {$caller['file']} on line {$caller['line']}", $level);
	}
}

if (!function_exists(__NAMESPACE__.'\\indentText')) {
	/**
	 * function used to indent a text with newlines in it
	 * used to indent multiline text evenly.
	 * @param string $text The text to indent
	 * @param int $number The number of tabs to indent
	 * @return string the string with the applied indent
	 */
	function indentText(string $text, int $number = 1): string {
		return str_replace("\n", "\n".str_repeat("\t", $number), $text);
	}
}

if (!function_exists(__NAMESPACE__.'\\isAssoc')) {
	/**
	 * function used to check if an array is associative.
	 * @param array $arr The array to check
	 * @return bool true or false on is associative or not
	 */
	function isAssoc(array $arr): bool {
		if (array() === $arr) {
			return false;
		}
		ksort($arr);

		/** @psalm-suppress DocblockTypeContradiction */
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}

if (!function_exists(__NAMESPACE__.'\\readableDurationString')) {
	/**
	 * function used to transform a duration into a human readable string.
	 * @param int $time The duration in seconds
	 * @return string with the duration in a human readable format
	 */
	function readableDurationString(int $time): string {
		if ($time >= 86400) {
			if ($time % 86400 === 0) {
				return $time / 86400 .'days';
			}

			return (int)($time / 86400).'days '.(int)($time % 86400 / 3600).'h';
		}
		if ($time >= 3600) {
			if ($time % 3600 === 0) {
				return $time / 3600 .'h';
			}

			return (int)($time / 3600).'h '.(int)($time % 3600 / 60).'min';
		}
		if ($time >= 60) {
			if ($time % 60 === 0) {
				return $time / 60 .'min';
			}

			return (int)($time / 60).'min '.(int)($time % 60).'s';
		}

		return $time.'s';
	}
}
