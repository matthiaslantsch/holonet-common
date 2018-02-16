<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the ChangeAwareTrait trait
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\collection;

/**
 * The ChangeAwareTrait trait is to be used togheter with the ChangeAwareTrait
 * classes using this trait can be added to the ChangeAwareCollection and notify
 * the collection themselves that they changed
 *
 * @author  matthias.lantsch
 * @package holonet\common\collection
 */
trait ChangeAwareTrait {

	/**
	 * holds a reference to the ChangeAwareCollection that is keeping track of this object
	 *
	 * @access private
	 * @var    ChangeAwareCollection $belongsTo Reference to a ChangeAwareCollection object
	 */
	private $partOfCollection;

	/**
	 * setter function to make this object aware to what collection it belongs to
	 *
	 * @access public
	 * @param  ChangeAwareCollection $coll A reference to the collection this object is part of
	 * @return void
	 */
	public function belongsTo(ChangeAwareCollection $coll) {
		$this->partOfCollection = $coll;
	}

	/**
	 * function used to mark this object as changed in the collection object
	 *
	 * @access public
	 * @return void
	 */
	public function notifyChange() {
		if($this->partOfCollection !== null) {
			$this->partOfCollection->change($this);
		}
	}

}
