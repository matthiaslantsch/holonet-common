<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

trait ChangeAwareTrait {
	/**
	 * holds a reference to the ChangeAwareCollection that is keeping track of this object.
	 */
	private ChangeAwareCollection $partOfCollection;

	/**
	 * @param ChangeAwareCollection $coll A reference to the collection this object is part of
	 */
	public function belongsTo(ChangeAwareCollection $coll): void {
		$this->partOfCollection = $coll;
	}

	public function notifyChange(): void {
		if (isset($this->partOfCollection)) {
			$this->partOfCollection->change($this);
		}
	}
}
