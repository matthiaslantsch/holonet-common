<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

use TypeError;
use function holonet\common\verify;
use holonet\common\collection\Registry;
use function holonet\common\set_object_vars;
use holonet\common\error\BadEnvironmentException;

/**
 * ConfigRegistry adds $_ENV placeholder functionality to the standard Registry.
 */
class ConfigRegistry extends Registry {
	/**
	 * @template T
	 * Get an instance of a config dto class supplied by the user.
	 * @psalm-param class-string<T>|T $cfgDto
	 * @return T
	 */
	public function asDto(string $configKey, string|object $cfgDto): object {
		if (!$this->has($configKey)) {
			throw BadEnvironmentException::faultyConfig($configKey, 'Config item doesn\'t exist');
		}

		$value = $this->get($configKey);
		if (!is_array($value)) {
			$value = array($value);
		}

		try {
			if (is_string($cfgDto)) {
				$cfgDto = new $cfgDto(...$value);
			} else {
				set_object_vars($cfgDto, $value);
			}
		} catch (TypeError $e) {
			throw BadEnvironmentException::faultyConfig($configKey, "TypeError: {$e->getMessage()}");
		}

		return $cfgDto;
	}

	/**
	 * @template T
	 * Get a verified instance of a config dto class supplied by the user.
	 * @psalm-param class-string<T>|T $cfgDto
	 * @return T
	 */
	public function verifiedDto(string $configKey, string|object $cfgDto): object {
		$cfgDto = $this->asDto($configKey, $cfgDto);

		$proof = verify($cfgDto);
		if ($proof->pass()) {
			return $cfgDto;
		}

		throw BadEnvironmentException::faultyConfigFromProof($configKey, $proof);
	}

	/**
	 * Try to resolve placeholder from the env variables of the application process.
	 */
	protected function resolvePlaceHolder(string $placeholder): ?string {
		if (str_starts_with($placeholder, 'env(')) {
			$envKey = str_replace(array('env(', ')'), '', $placeholder);
			if (($envVal = $_ENV[$envKey] ?? getenv($envKey)) !== false) {
				return $envVal;
			}

			return '';
		}

		return parent::resolvePlaceHolder($placeholder);
	}
}
