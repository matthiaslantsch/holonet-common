<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the ErrorDispatcher class
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\error;

/**
 * The ErrorDispatcher class registers as error/exception/shutdown handler
 * and can be used to add as many callables to handle errors.
 */
class ErrorDispatcher {
	/**
	 * @var callable[]
	 */
	private $errorHandlers = array();

	/**
	 * @var callable[]
	 */
	private $exceptionHandlers = array();

	/**
	 * @var callable[]
	 */
	private $shutdownHandlers = array();

	public function addErrorHandler(callable $handler): void {
		$this->errorHandlers[] = $handler;
	}

	public function addExceptionHandler(callable $handler): void {
		$this->exceptionHandlers[] = $handler;
	}

	public function addShutdownHandlers(callable $handler): void {
		$this->shutdownHandlers[] = $handler;
	}

	/**
	 * registers the our callbacks as error handlers/exception handlers/shutdown function with the SPL.
	 */
	public function register(): void {
		/**
		 * @psalm-suppress InvalidArgument
		 * @psalm-suppress MissingClosureParamType
		 */
		set_error_handler(function (...$args): void {
			foreach ($this->errorHandlers as $handler) {
				$handler(...$args);
			}
		});

		/**
		 * @psalm-suppress InvalidArgument
		 * @psalm-suppress MissingClosureParamType
		 */
		set_exception_handler(function (...$args): void {
			foreach ($this->exceptionHandlers as $handler) {
				$handler(...$args);
			}
		});

		/**
		 * @psalm-suppress InvalidArgument
		 * @psalm-suppress MissingClosureParamType
		 */
		register_shutdown_function(function (...$args): void {
			foreach ($this->shutdownHandlers as $handler) {
				$handler(...$args);
			}
		});
	}
}
