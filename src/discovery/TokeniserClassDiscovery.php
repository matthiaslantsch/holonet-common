<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\discovery;

use RuntimeException;

/**
 * Class discovery utility class.
 * Uses the php token parser system to discover class and namespace.
 */
class TokeniserClassDiscovery extends ClassDiscovery {
	/**
	 * @psalm-suppress LessSpecificReturnStatement
	 * @psalm-suppress MoreSpecificReturnType
	 * {@inheritdoc}
	 * @see https://stackoverflow.com/a/7153391 Courtesy of stackoverflow
	 */
	public function fromFile(string $filename): string {
		$fp = fopen($filename, 'rb');
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
				if ($tokens[$i][0] === T_NAMESPACE) {
					for ($j = $i + 1; $j < count($tokens); $j++) {
						if ($tokens[$j][0] === T_STRING) {
							$namespace .= '\\'.$tokens[$j][1];
						} elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
							break;
						}
					}
				}

				if ($tokens[$i][0] === T_CLASS) {
					for ($j = $i + 1; $j < count($tokens); $j++) {
						if ($tokens[$j] === '{') {
							$class = $tokens[$i + 2][1];
						}
					}
				}
			}
		}

		return "{$namespace}\\{$class}";
	}
}
