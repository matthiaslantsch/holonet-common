<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch
 *
 * class file for the IComparable interface
 *
 * @package common
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

/**
 * The IComparable interface forces the implementing class to define a comparison
 *
 * @author  matthias.lantsch
 * @package holonet\common
 */
interface IComparable {

    function compareTo(IComparable $other);

}
