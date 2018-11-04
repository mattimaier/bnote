<?php 

/**
 * DAO for appointments (calendar sub-module).
 * @author matti
 *
 */
class AppointmentData extends AbstractData {
	
	public static $colExchange = array(
			"contact" => array("name", "surname"),
			"location" => array("name")
	);
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("id"), FieldType::INTEGER),
			"begin" => array(Lang::txt("begin"), FieldType::DATETIME),
			"end" => array(Lang::txt("end"), FieldType::DATETIME),
			"name" => array(Lang::txt("name"), FieldType::CHAR),
			"location" => array(Lang::txt("location"), FieldType::REFERENCE),
			"contact" => array(Lang::txt("contact"), FieldType::REFERENCE),
			"notes" => array(Lang::txt("notes"), FieldType::TEXT)
		);
		
		$this->references = array(
			"location" => "location",
			"contact" => "contact"
		);
		
		$this->table = "appointment";
		
		$this->init($dir_prefix);
	}
	
	public function getJoinedAttributes() {
		return $this->colExchange;
	}
	
	public function create($values) {
		$id = parent::create($values);
		$this->createCustomFieldData('a', $id, $values);
	}
	
	public function update($id, $values) {
		parent::update($id, $values);
		$this->updateCustomFieldData('a', $id, $values);
	}
	
	public function delete($id) {
		$this->deleteCustomFieldData('a', $id);
		parent::delete($id);
	}
}

?>