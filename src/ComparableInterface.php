<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

/**
 * The ComparableInterface interface forces the implementing class to define a comparison method.
 */
interface ComparableInterface {
	public function compareTo(self $other): bool;
}
