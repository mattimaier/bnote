<?php

class EquipmentData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"model" => array(Lang::txt("equipment_model"), FieldType::CHAR, true),
				"make" => array(Lang::txt("equipment_make"), FieldType::CHAR, true),
				"name" => array("Name", FieldType::CHAR),
				"purchase_price" => array(Lang::txt("equipment_purchase_price"), FieldType::DECIMAL, true),
				"current_value" => array(Lang::txt("equipment_current_value"), FieldType::DECIMAL, true),
				"quantity" => array(Lang::txt("equipment_quantity"), FieldType::INTEGER, true),
				"notes" => array("Notizen", FieldType::TEXT)
		);
	
		$this->references = array();
	
		$this->table = "equipment";
		$this->init($dir_prefix);
	}
	
	function validate($input) {
		foreach($input as $k => $v) {
			// optional attributes
			if($k == "name" && $v == "") continue;
			
			$this->validate_pair($k, $v);
		}
	}

}

?>