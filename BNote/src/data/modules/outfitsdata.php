<?php
/**
 * Data Access Class for outfits.
* @author matti
*
*/
class OutfitsData extends AbstractData {
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("OutfitsData_construct.id"), FieldType::INTEGER),
				"name" => array(Lang::txt("OutfitsData_construct.name"), FieldType::CHAR),
				"description" => array(Lang::txt("OutfitsData_construct.description"), FieldType::TEXT)
		);
		
		$this->references = array(
		);
		
		$this->table = "outfit";
		$this->init($dir_prefix);
	}
	
}

?>