<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * class file for the ConfigRegistry class
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
	 * Extend base placeholder logic to replace $_ENV variables in config values
	 * {@inheritdoc}
	 */
	protected function replacePlaceholder($position) {
		if (is_string($position) && mb_strpos($position, '%') !== false) {
			$matches = array();
			preg_match_all('/%(?:env\(|)([^%]+?)(?:\)|)%/', $position, $matches, PREG_SET_ORDER);
			foreach ($matches as $placeholderPair) {
				//check if it is a $_ENV placeholder
				if (mb_strpos($placeholderPair[0], '%env(') === 0) {
					//if the placeholder is an offset in the $_ENV, replace it, otherwise use NULL
					$position = str_replace($placeholderPair[0], $_ENV[$placeholderPair[1]] ?? 'NULL', $position);
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
