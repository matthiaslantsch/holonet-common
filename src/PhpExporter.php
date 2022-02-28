<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

/**
 * Pretty print a php variable into parsable php code.
 */
class PhpExporter {
	public function export($data): string {
		$ret = var_export($data, true);

		// replace the space after the array keyword
		$ret = str_replace('array (', 'array(', $ret);

		// replace new lines after array keys
		$ret = preg_replace("/' =>[\\s]*array\\(/", '\' => array(', $ret);

		// replace class names with constants
		$data = (array)$data;
		array_walk_recursive($data, function ($val) use (&$ret): void {
			if (is_scalar($val) && class_exists((string)$val)) {
				$ret = str_replace(str_replace('\\', '\\\\', "'{$val}'"), "{$val}::class", $ret);
			}
		});

		return "return {$ret};";
	}

	public function exportFile($data): string {
		return "<?php\n\n{$this->export($data)}";
	}
}
