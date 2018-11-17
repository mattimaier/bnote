<?php
/**
 * Data Access Class for rehearsal data.
 * @author matti
 *
 */
class LocationsData extends AbstractData {
	
	public static $CUSTOM_DATA_OTYPE = 'l';
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array("ID", FieldType::INTEGER),
			"name" => array("Name", FieldType::CHAR),
			"notes" => array("Notizen", FieldType::TEXT),
			"address" => array("Adresse", FieldType::REFERENCE),
			"location_type" => array("Location Typ", FieldType::REFERENCE)
		);
		
		$this->references = array(
			"address" => "address", 
			"location_type" => "location_type"
		);
		
		$this->table = "location";
		$this->init($dir_prefix);
	}
	
	function findByIdNoRef($id) {
		$entity = parent::findByIdNoRef($id);
		$customData = $this->getCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $id);
		return array_merge($entity, $customData);
	}
	
	function findByIdJoined($id, $colExchange) {
		$entity = parent::findByIdJoined($id, $colExchange);
		$customData = $this->getCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $id);
		return array_merge($entity, $customData);
	}
	
	function create($values) {
		if(!isset($_POST["city"]) || $_POST["city"] == "") {
			new BNoteError("Bitte gebe eine Stadt für diese Location an.");
		}
		
		$_POST["address"] = $this->adp()->manageAddress(-1, $_POST);
		$lid = parent::create($_POST);
		
		$this->createCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $lid, $values);
		
		return $lid;
	}
	
	function update($id, $values) {
		if(!isset($values["city"]) || $values["city"] == "") {
			new BNoteError("Bitte gebe eine Stadt für diese Location an.");
		}
		
		// update address
		$addressId = $this->getAddressFromId($id);
		$this->adp()->manageAddress($addressId, $_POST);
		$_POST["address"] = $addressId;
		
		// update custom data
		$this->updateCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $id, $values);
		
		// update location
		parent::update($id, $values);
	}
	
	function delete($id) {
		$this->adp()->manageAddress($this->getAddressFromId($id), null);
		$this->deleteCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $id);
		parent::delete($id);
	}
	
	private function getAddressFromId($id) {
		$oldEntity = $this->findByIdNoRef($id);
		return $oldEntity["address"];
	}
	
	public function getLocationTypes() {
		return $this->database->getSelection("SELECT * FROM location_type");
	}
}
?>