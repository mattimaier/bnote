<?php

class EquipmentData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"model" => array(Lang::txt("equipment_model"), FieldType::CHAR),
				"make" => array(Lang::txt("equipment_make"), FieldType::CHAR),
				"name" => array("Name", FieldType::CHAR, true),
				"purchase_price" => array(Lang::txt("equipment_purchase_price"), FieldType::DECIMAL),
				"current_value" => array(Lang::txt("equipment_current_value"), FieldType::DECIMAL),
				"quantity" => array(Lang::txt("equipment_quantity"), FieldType::INTEGER),
				"notes" => array("Notizen", FieldType::TEXT, true)
		);
	
		$this->references = array();
	
		$this->table = "equipment";
		$this->init($dir_prefix);
	}
}

?>