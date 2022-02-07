<?php

class EquipmentData extends AbstractData {
	
	public static $CUSTOM_DATA_OTYPE = 'e';
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("EquipmentData_construct.id"), FieldType::INTEGER),
				"model" => array(Lang::txt("EquipmentData_construct.model"), FieldType::CHAR),
				"make" => array(Lang::txt("EquipmentData_construct.make"), FieldType::CHAR),
				"name" => array(Lang::txt("EquipmentData_construct.name"), FieldType::CHAR, true),
				"purchase_price" => array(Lang::txt("EquipmentData_construct.purchase_price"), FieldType::CURRENCY),
				"current_value" => array(Lang::txt("EquipmentData_construct.current_value"), FieldType::CURRENCY),
				"quantity" => array(Lang::txt("EquipmentData_construct.quantity"), FieldType::INTEGER),
				"notes" => array(Lang::txt("EquipmentData_construct.notes"), FieldType::TEXT, true)
		);
	
		$this->references = array();
	
		$this->table = "equipment";
		$this->init($dir_prefix);
	}
	
	function findEquipmentById($id) {
		$eq = parent::findByIdNoRef($id);
		$cust = $this->getCustomFieldData(EquipmentData::$CUSTOM_DATA_OTYPE, $id);
		return array_merge($eq, $cust);
	}
	
	function findAllEquipment() {
		$query = "SELECT name, quantity, make, model, purchase_price, current_value, id, notes FROM equipment ORDER BY name";
		$selection = $this->database->getSelection($query);
		return $this->appendCustomDataToSelection(EquipmentData::$CUSTOM_DATA_OTYPE, $selection);
	}
	
	function create($values) {
		$id = parent::create($values);
		$this->createCustomFieldData(EquipmentData::$CUSTOM_DATA_OTYPE, $id, $values);
	}
	
	function update($id, $values) {
		parent::update($id, $values);
		$this->updateCustomFieldData(EquipmentData::$CUSTOM_DATA_OTYPE, $id, $values);
	}
	
	function delete($id) {
		$this->deleteCustomFieldData(EquipmentData::$CUSTOM_DATA_OTYPE, $id);
		parent::delete($id);
	}
}