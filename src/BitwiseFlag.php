<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch
 *
 * Class file for the BitwiseFlag class
 *
 * @package holonet common code library
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use holonet\common as co;

/**
 * BitwiseFlag wraps around an integer and allows for easy flag setting and checking
 * while not touching the other bits in the integer
 *
 * @author  matthias.lantsch
 * @package holonet\common
 */
abstract class BitwiseFlag {

	/**
	 * Property holding the actual flags values in an integer
	 *
	 * @access public
	 * @var    int $flags The integer mask that holds the flags enclosed
	 */
	public $flags;

	/**
	 * constructor method taking the integer mask as an argument
	 *
	 * @access public
	 * @param  integer $mask The flags mask we are working with
	 * @return void
	 */
	public function __construct($mask) {
		$this->flags = intval($mask);
	}

	/**
	 * small helper method that can be used to selectively check a single bit in our integer
	 *
	 * @access protected
	 * @param  int $flag The flag that should be checked for
	 * @return boolean true or false if the flag is set or not
	 */
	protected function isFlagSet(int $flag) {
      return (($this->flags & $flag) == $flag);
    }

	/**
	 * small helper method that can be used to selectively set a single bit in our integer
	 *
	 * @access protected
	 * @param  int $flag The flag to be set in our integer mask
	 * @param  boolean $value The boolean value to be set, if the flag should be true or not
	 * @return void
	 */
    protected function setFlag(int $flag, bool $value) {
    	if($value) {
        	$this->flags |= $flag;
    	} else {
        	$this->flags &= ~$flag;
    	}
    }

}
