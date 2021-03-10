<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

use Throwable;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * ErrorHandler class with methods to be registered by php as error handling functions.
 * Automatically logs if a logger is given during initialisation.
 */
class ErrorHandler {
	/**
	 * @psalm-var array<int, array{level: string, name: string}>
	 */
	public const ERROR_LEVEL_LOOKUP = array(
		\E_ERROR => array('level' => LogLevel::CRITICAL, 'name' => 'E_ERROR'),
		\E_WARNING => array('level' => LogLevel::WARNING, 'name' => 'E_WARNING'),
		\E_PARSE => array('level' => LogLevel::ALERT, 'name' => 'E_PARSE'),
		\E_NOTICE => array('level' => LogLevel::NOTICE, 'name' => 'E_NOTICE'),
		\E_CORE_ERROR => array('level' => LogLevel::CRITICAL, 'name' => 'E_CORE_ERROR'),
		\E_CORE_WARNING => array('level' => LogLevel::WARNING, 'name' => 'E_CORE_WARNING'),
		\E_COMPILE_ERROR => array('level' => LogLevel::ALERT, 'name' => 'E_COMPILE_ERROR'),
		\E_COMPILE_WARNING => array('level' => LogLevel::WARNING, 'name' => 'E_COMPILE_WARNING'),
		\E_USER_ERROR => array('level' => LogLevel::ERROR, 'name' => 'E_USER_ERROR'),
		\E_USER_WARNING => array('level' => LogLevel::WARNING, 'name' => 'E_USER_WARNING'),
		\E_USER_NOTICE => array('level' => LogLevel::NOTICE, 'name' => 'E_USER_NOTICE'),
		\E_STRICT => array('level' => LogLevel::NOTICE, 'name' => 'E_STRICT'),
		\E_RECOVERABLE_ERROR => array('level' => LogLevel::ERROR, 'name' => 'E_RECOVERABLE_ERROR'),
		\E_DEPRECATED => array('level' => LogLevel::WARNING, 'name' => 'E_DEPRECATED'),
		\E_USER_DEPRECATED => array('level' => LogLevel::WARNING, 'name' => 'E_USER_DEPRECATED'),
	);

	protected ?Throwable $lastException = null;

	protected ?LoggerInterface $logger;

	public function __construct(?LoggerInterface $logger = null) {
		$this->logger = $logger;
	}

	/**
	 * handles errors coming over the error_handler
	 * maps php error levels to psr-3 error levels.
	 * @param int $errno The error number of the thrown error
	 * @param string $msg The error message
	 * @param string $file The file the error was caused in
	 * @param int $line The line the error was caused on
	 * @return bool|null To advise the spl to continue error handling or not
	 */
	public function handleError(int $errno, string $msg = '', string $file = '', ?int $line = null): ?bool {
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting
			return null;
		}

		list('level' => $type, 'name' => $name) = (self::ERROR_LEVEL_LOOKUP[$errno] ?? self::ERROR_LEVEL_LOOKUP[\E_ERROR]);

		if ($this->logger !== null) {
			$this->logger->log(
				$type,
				"{$name}: {$msg}",
				array(
					'code' => $errno,
					'file' => $file,
					'line' => $line,
				)
			);
		}

		return true;
	}

	/**
	 * handler method called by the spl when an exception is thrown that isn't caught
	 * if an exception gets here, it's a server side error so we exit execution after.
	 * @param Throwable $exception Uncaught exception
	 */
	public function handleException(Throwable $exception): void {
		$message = sprintf(
			'Uncaught Exception %s: "%s" at %s line %s',
			get_class($exception),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine()
		);

		if ($this->logger !== null) {
			$this->logger->log(LogLevel::ERROR, $message, array('exception' => $exception));
		}

		$this->lastException = $exception;
	}

	/**
	 * Shutdown function
	 * should be changed in extending classes to add more functionality to it.
	 */
	public function handleShutdown(): void {
		if (($error = $this->getLastError()) !== null) {
			echo $error;
			exit(255);
		}
	}

	/**
	 * Return the last error message if there was one.
	 * Can be used in fatal shutdown handlers to help.
	 */
	protected function getLastError(): ?string {
		if ($this->lastException !== null) {
			$class = get_class($this->lastException);

			return "Unwanted crash due to {$class}: {$this->lastException->getMessage()}";
		}
		if (($lasterror = error_get_last()) !== null) {
			return "Unwanted crash due to: {$lasterror['message']} in file {$lasterror['file']} on line {$lasterror['line']}";
		}

		return null;
	}
}
