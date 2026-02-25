<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\di\autowire\provider;

use holonet\common\di\autowire\attribute\ConfigItem;
use holonet\common\di\Container;
use holonet\common\di\error\AutoWireException;
use LogicException;
use ReflectionNamedType;
use ReflectionParameter;
use function holonet\common\reflection_get_attribute;

/**
 * Provider which will automatically inject a config dto object read from the registry.
 * This is achieved using a special marker attribute on the parameter.
 * The corresponding config key that will be used to collect the config data can be supplied using:
 *   - a given parameter (a string) which represents the config key
 *   - the property in the marker attribute.
 */
class ConfigAutoWireProvider implements ParamAutoWireProvider {
	/**
	 * {@inheritDoc}
	 */
	public function provide(Container $container, ReflectionParameter $param, ReflectionNamedType $type, mixed $givenParam): mixed {
		$expectedType = $type->getName();

		$attribute = reflection_get_attribute($param, ConfigItem::class);
		if ($attribute === null) {
			return null;
		}

		$configKey = ($givenParam ?? $attribute->key);

		if (!is_string($configKey)) {
			AutowireException::failParam($param, 'Cannot auto-wire to a config dto object without supplying a config key');
		}

		if (class_exists($expectedType)) {
			if ($attribute->verified) {
				return $container->registry->verifiedDto($configKey, $expectedType);
			}

			return $container->registry->asDto($configKey, $expectedType);
		}

		return $container->registry->get($configKey);
	}

	/**
	 * {@inheritDoc}
	 */
	public function compile(Container $container, ReflectionParameter $param, ReflectionNamedType $type, mixed $givenParam): string {
		$expectedType = $type->getName();

		// if we got called here, it means provide() has returned something, so we can assume the attribute exists
		$attribute = reflection_get_attribute($param, ConfigItem::class) ?? throw new LogicException();

		$configKey = ($givenParam ?? $attribute->key);

		if (class_exists($expectedType)) {
			if ($attribute->verified) {
				return "\$this->registry->verifiedDto('{$configKey}', '{$expectedType}')";
			}

			return "\$this->registry->asDto('{$configKey}', '{$expectedType}')";
		}

		return "\$this->registry->get('{$configKey}')";
	}
}
