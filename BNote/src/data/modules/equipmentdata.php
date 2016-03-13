<?php

class EquipmentData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"model" => array(lang::txt("equipment_model"), FieldType::CHAR),
				"make" => array(lang::txt("equipment_make"), FieldType::CHAR),
				"name" => array("Name", FieldType::CHAR),
				"purchase_price" => array(lang::txt("equipment_purchase_price"), FieldType::DECIMAL),
				"current_value" => array(lang::txt("equipment_current_value"), FieldType::DECIMAL),
				"quantity" => array(lang::txt("equipment_quantity"), FieldType::INTEGER),
				"notes" => array("Notizen", FieldType::TEXT)
		);
	
		$this->references = array();
	
		$this->table = "equipment";
		$this->init($dir_prefix);
	}
	
}

?>