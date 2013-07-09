<?php

/**
 * Class to represent simple textfields.
 * @author matti
 *
 */
class TextWriteable implements iWriteable {
	
	private $value;
	
	function __construct($v) {
		$this->value = $v;
	}
	
	function write() {
		return $this->value;
	} 
}