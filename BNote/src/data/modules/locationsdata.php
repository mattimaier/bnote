<?php
/**
 * Data Access Class for rehearsal data.
 * @author matti
 *
 */
class LocationsData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array("ID", FieldType::INTEGER),
			"name" => array("Name", FieldType::CHAR),
			"notes" => array("Notizen", FieldType::TEXT),
			"address" => array("Adresse", FieldType::REFERENCE)
		);
		
		$this->references = array(
			"address" => "address", 
		);
		
		$this->table = "location";
		$this->init($dir_prefix);
	}
	
	function create($values) {
		if(!isset($_POST["city"]) || $_POST["city"] == "") {
			new Error("Bitte gebe eine Stadt für diese Location an.");
		}
		
		$_POST["address"] = $this->adp()->manageAddress(-1, $_POST);
		parent::create($_POST);
	}
	
	function update($id, $values) {
		if(!isset($values["city"]) || $values["city"] == "") {
			new Error("Bitte gebe eine Stadt für diese Location an.");
		}
		
		// update address
		$addressId = $this->getAddressFromId($id);
		$this->adp()->manageAddress($addressId, $_POST);
		$_POST["address"] = $addressId;
		
		// update location
		parent::update($id, $values);
	}
	
	function delete($id) {
		$this->adp()->manageAddress($this->getAddressFromId($id), null);
		parent::delete($id);
	}
	
	private function getAddressFromId($id) {
		$oldEntity = $this->findByIdNoRef($id);
		return $oldEntity["address"];
	}
}
?>