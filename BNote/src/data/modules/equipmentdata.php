<?php

class EquipmentData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("EquipmentData_construct.id"), FieldType::INTEGER),
				"model" => array(Lang::txt("EquipmentData_construct.model"), FieldType::CHAR),
				"make" => array(Lang::txt("EquipmentData_construct.make"), FieldType::CHAR),
				"name" => array(Lang::txt("EquipmentData_construct.name"), FieldType::CHAR, true),
				"purchase_price" => array(Lang::txt("EquipmentData_construct.purchase_price"), FieldType::DECIMAL),
				"current_value" => array(Lang::txt("EquipmentData_construct.current_value"), FieldType::DECIMAL),
				"quantity" => array(Lang::txt("EquipmentData_construct.quantity"), FieldType::INTEGER),
				"notes" => array(Lang::txt("EquipmentData_construct.notes"), FieldType::TEXT, true)
		);
	
		$this->references = array();
	
		$this->table = "equipment";
		$this->init($dir_prefix);
	}
}

?>