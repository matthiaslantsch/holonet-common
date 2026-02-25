<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\code;

use PhpToken;
use function holonet\common\get_class_short;
use function holonet\common\read_file_contents;

/**
 * Uses a tokeniser approach to reading and parsing all use statement in a php source code file.
 */
class FileUseStatementParser {
	/**
	 * Array with mappings of token types that should change the use type of the current statement.
	 */
	protected const CHANGES_USE_TYPE = array(
		\T_USE => 'class',
		\T_FUNCTION => 'function',
		\T_CONST => 'constant',
	);

	protected const CLOSES_GROUP = '}';

	protected const ENDS_USE_STATEMENT = array(';', ',', '}');

	protected const OPENS_GROUP = '{';

	protected ?string $aliasedName = null;

	protected array $allUses = array();

	protected ?string $beingUsed = null;

	protected ?string $groupedPrefix = null;

	protected array $tokens;

	protected string $useType = 'class';

	public function __construct(string $file) {
		preg_match_all('/^use[^\n]+$/m', read_file_contents($file), $useStatements);

		list($useStatements) = $useStatements;
		$this->tokens = PhpToken::tokenize(sprintf('<?php %s', implode('', $useStatements)));
	}

	public function parse(): array {
		foreach ($this->tokens as $current) {
			if (isset(static::CHANGES_USE_TYPE[$current->id])) {
				$this->useType = static::CHANGES_USE_TYPE[$current->id];

				continue;
			}

			if ($current->is(\T_NAME_QUALIFIED)) {
				$this->beingUsed = $current->text;

				continue;
			}

			if ($current->is(\T_STRING)) {
				if ($this->beingUsed === null) {
					$this->beingUsed = $current->text;
				} else {
					$this->aliasedName = $current->text;
				}

				continue;
			}

			if (in_array($current->text, static::ENDS_USE_STATEMENT) && $this->beingUsed !== null) {
				$this->saveCurrent();
			}

			if ($current->text === static::OPENS_GROUP) {
				$this->groupedPrefix = $this->beingUsed;
				$this->beingUsed = null;
			} elseif ($current->text === static::CLOSES_GROUP) {
				$this->groupedPrefix = null;
			}
		}

		return $this->allUses;
	}

	protected function saveCurrent(): void {
		if ($this->groupedPrefix !== null) {
			$this->beingUsed = "{$this->groupedPrefix}\\{$this->beingUsed}";
		}

		if ($this->aliasedName === null) {
			$this->aliasedName = get_class_short($this->beingUsed);
		}

		$this->allUses[$this->useType][$this->aliasedName] = $this->beingUsed;
		$this->beingUsed = null;
		$this->aliasedName = null;
	}
}
