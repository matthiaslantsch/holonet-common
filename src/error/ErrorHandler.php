<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the ErrorHandler base class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

/**
 * The ErrorHandler should be extended by an application that needs it's errors handled.
 *
 * @author  matthias.lantsch
 */
abstract class ErrorHandler {
	/**
	 * handles errors coming over the error_handler
	 * maps php error levels to our internal error levels.
	 *
	 * @param int $errno The error number of the thrown error
	 * @param string $msg The error message
	 * @param string $file The file the error was caused in
	 * @param string $line The line the error was caused on
	 */
	public static function handleError($errno, $msg = '', $file = '', $line = ''): void {
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting
			return;
		}

		$levelLookup = array(
			E_ERROR => Error::ERROR,
			E_WARNING => Error::WARNING,
			E_PARSE => Error::ERROR,
			E_NOTICE => Error::NOTICE,
			E_CORE_ERROR => Error::ERROR,
			E_CORE_WARNING => Error::WARNING,
			E_COMPILE_ERROR => Error::ERROR,
			E_COMPILE_WARNING => Error::WARNING,
			E_USER_ERROR => Error::ERROR,
			E_USER_WARNING => Error::WARNING,
			E_USER_NOTICE => Error::NOTICE,
			E_STRICT => Error::DEBUG,
			E_RECOVERABLE_ERROR => Error::ERROR,
			E_DEPRECATED => Error::DEBUG,
			E_USER_DEPRECATED => Error::DEBUG,
		);

		if (!isset($levelLookup[$errno])) {
			$level = Error::ERROR;
		} else {
			$level = $levelLookup[$errno];
		}

		self::onError(new Error($level, $errno, $msg, $file, $line));
	}

	/**
	 * static method called by the spl when an exception is thrown that isn't caught
	 * now if an exeception gets here, it's a server side error.
	 *
	 * @param \Exception $exception Exception that was thrown but wasn't caught
	 */
	public static function handleException($exception): void {
		$error = new Error(
			Error::ERROR, $exception->getCode(),
			$exception->getMessage(), $exception->getFile(),
			$exception->getLine()
		);

		self::onError($error);
	}

	/**
	 * registers the static methods as error_handler/exception_handler with the SPL.
	 */
	public static function register(): void {
		set_error_handler(array(static::class, 'handleError'));
		set_exception_handler(array(static::class, 'handleException'));
	}

	/**
	 * force the child class to implement a method in which it reacts to an error.
	 *
	 * @param Error $error The error that must be handled
	 */
	abstract protected static function processError(Error $error): void;

	/**
	 * decide wheter to log the error or not, call child class processError method.
	 *
	 * @param Error $error The error that must be handled
	 */
	private static function onError(Error $error): void {
		//call the processError() method of the implementing class
		static::processError($error);

		exit(1);
	}
}
