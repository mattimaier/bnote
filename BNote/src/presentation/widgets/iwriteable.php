<?php

/**
 * This interface marks all classes that are writeable, i.e. that print html
**/

interface iWriteable {
	
	/**
	 * Outputs the HTML code of the element
	 */
 	public function write();
 	
 	/**
 	 * @return String name of the field or writable, optionally NULL
 	 */
 	public function getName();
}

?>