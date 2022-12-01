<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

/**
 * ConfigRegistry adds $_ENV placeholder functionality to the standard Registry.
 */
class ConfigRegistry extends Registry {
	/**
	 * Extend base placeholder logic to replace env variables in config values
	 * {@inheritDoc}
	 */
	protected function replacePlaceholder($position): mixed {
		if (is_string($position) && mb_strpos($position, '%') !== false) {
			$matches = array();
			preg_match_all('/%(?:env\(|)([^%]+?)(?:\)|)%/', $position, $matches, \PREG_SET_ORDER);
			foreach ($matches as $placeholderPair) {
				//check if it is a $_ENV placeholder
				if (mb_strpos($placeholderPair[0], '%env(') === 0) {
					//if the placeholder is an offset in the $_ENV, replace it, otherwise return null
					if (($envval = $_ENV[$placeholderPair[1]] ?? getenv($placeholderPair[1])) !== false) {
						$position = str_replace($placeholderPair[0], $envval, $position);
					} else {
						return $position;
					}
				} else {
					//if the placeholder is a value in the registry, replace it, otherwise leave it with the % signs
					$position = str_replace($placeholderPair[0], $this->get($placeholderPair[1], $placeholderPair[0]), $position);
				}
			}
		} elseif (is_array($position)) {
			foreach ($position as $key => $val) {
				$position[$key] = $this->replacePlaceholder($val);
			}
		}

		return $position;
	}
}
