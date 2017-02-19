<?php
/**
 * Data Access Class for outfits.
* @author matti
*
*/
class OutfitsData extends AbstractData {
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"name" => array("Name", FieldType::CHAR),
				"description" => array("Beschreibung", FieldType::TEXT)
		);
		
		$this->references = array(
		);
		
		$this->table = "outfit";
		$this->init($dir_prefix);
	}
	
}

?>