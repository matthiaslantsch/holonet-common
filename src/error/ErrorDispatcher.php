<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
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
	 * @var callable[] $errorHandlers
	 */
	private array $errorHandlers = array();

	/**
	 * @var callable[] $exceptionHandlers
	 */
	private array $exceptionHandlers = array();

	/**
	 * @var ErrorHandler $finalHandler Last error handler to be called
	 */
	private ?ErrorHandler $finalHandler = null;

	/**
	 * @var callable[] $shutdownHandlers
	 */
	private array $shutdownHandlers = array();

	public function addErrorHandler(callable $handler): void {
		$this->errorHandlers[] = $handler;
	}

	public function addExceptionHandler(callable $handler): void {
		$this->exceptionHandlers[] = $handler;
	}

	public function addShutdownHandler(callable $handler): void {
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

			if ($this->finalHandler !== null) {
				$this->finalHandler->handleError(...$args);
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

			if ($this->finalHandler !== null) {
				$this->finalHandler->handleException(...$args);
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

			if ($this->finalHandler !== null) {
				$this->finalHandler->handleShutdown(...$args);
			}
		});
	}

	public function setFinalHandler(ErrorHandler $finalHandler): void {
		$this->finalHandler = $finalHandler;
	}
}
