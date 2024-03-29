<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the IChangeAware interface
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

/**
 * The IChangeAware interface is to be used togheter with the ChangeAwareTrait
 * classes implementing this can be added to the ChangeAwareCollection and notify
 * the collection themselves that they changed
 *
 * @author  matthias.lantsch
 * @package holonet\common\collection
 */
interface IChangeAware {
	public function belongsTo(ChangeAwareCollection $coll);
	public function notifyChange();
}
