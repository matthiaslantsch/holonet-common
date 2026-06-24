<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\discovery;

use RuntimeException;

/**
 * Uses the php token parser system to discover class and namespace by tokenising the source code of a file.
 */
class TokeniserClassDiscovery extends ClassDiscovery {
	/**
	 * {@inheritDoc}
	 * @see https://stackoverflow.com/a/7153391 Courtesy of stackoverflow
	 */
	public function fromFile(string $filename): string {
		$fp = fopen($filename, 'rb');
		$class = $namespace = $buffer = '';
		$i = 0;
		if ($fp === false) {
			throw new RuntimeException("Could not open file '{$filename}'");
		}

		try {
			$class = $namespace = $buffer = '';
			$i = 0;
			while (!$class) {
				if (feof($fp)) {
					throw new RuntimeException("Could not find class token in file '{$filename}'");
				}

				$buffer .= fread($fp, 512);
				$tokens = token_get_all($buffer);

				if (mb_strpos($buffer, '{') === false) {
					continue;
				}

				for (; $i < count($tokens); $i++) {
					if ($tokens[$i][0] === \T_NAMESPACE) {
						for ($j = $i + 1; $j < count($tokens); $j++) {
							if ($tokens[$j][0] === \T_STRING || $tokens[$j][0] === \T_NAME_QUALIFIED) {
								$namespace .= '\\'.$tokens[$j][1];
							} elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
								break;
							}
						}
					}

					// a T_CLASS token preceded by :: is a ClassName::class constant, not a declaration
					if ($tokens[$i][0] === \T_CLASS && ($i === 0 || $tokens[$i - 1][0] !== \T_DOUBLE_COLON)) {
						for ($j = $i + 1; $j < count($tokens); $j++) {
							if ($tokens[$j] === '{') {
								// anonymous classes ("new class {") have no T_STRING name token
								$name = $tokens[$i + 2] ?? null;
								if (is_array($name) && $name[0] === \T_STRING) {
									$class = $name[1];
								}

								break;
							}
						}

						if ($class !== '') {
							break;
						}
					}
				}
			}
			return "{$namespace}\\{$class}";
		} finally {
			fclose($fp);
		}
	}
}
