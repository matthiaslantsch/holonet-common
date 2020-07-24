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
 * The ChangeAwareInterface interface is to be used together with the ChangeAwareTrait
 * classes implementing this can be added to the ChangeAwareCollection and notify
 * the collection themselves that they changed.
 */
interface ChangeAwareInterface {
	public function belongsTo(ChangeAwareCollection $coll): void;

	public function notifyChange(): void;
}
