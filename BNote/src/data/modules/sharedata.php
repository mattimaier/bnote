<?php

/**
 * Data provider for share module.
 * @author matti
 *
 */
class ShareData extends AbstractData {
	
	/**
	 * Create a new data provider for the share module.
	 */
	function __construct() {
		$this->fields = array(
				"id" => array("Schlüssel", FieldType::INTEGER),
				"name" => array("Name", FieldType::CHAR, true),
				"is_active" => array("Aktuell", FieldType::BOOLEAN)
		);
		
		$this->table = "doctype";
		
		$this->init();
	}
	
}

?>