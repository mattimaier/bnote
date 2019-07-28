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
				"id" => array(Lang::txt("ShareData_construct.id"), FieldType::INTEGER),
				"name" => array(Lang::txt("ShareData_construct.name"), FieldType::CHAR, true),
				"is_active" => array(Lang::txt("ShareData_construct.is_active"), FieldType::BOOLEAN)
		);
		
		$this->table = "doctype";
		
		$this->init();
	}
	
}

?>