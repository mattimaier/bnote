<?php
require_once $GLOBALS["DIR_DATA"] . "abstractlocationdata.php";

/**
 * Data Access Class for rehearsal data.
 * @author matti
 *
 */
class LocationsData extends AbstractLocationData {
	
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
		$_POST["address"] = $this->createAddress($values);
		$lid = parent::create($_POST);
		$this->createCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $lid, $values);
		return $lid;
	}
	
	function update($id, $values) {
		// update address
		$addressId = $this->getAddressFromId($id);
		$this->updateAddress($addressId, $values);
		$_POST["address"] = $addressId;
		
		// update custom data
		$this->updateCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $id, $values);
		
		// update location
		parent::update($id, $values);
	}
	
	function delete($id) {
		$this->deleteAddress($this->getAddressFromId($id));
		$this->deleteCustomFieldData(LocationsData::$CUSTOM_DATA_OTYPE, $id);
		parent::delete($id);
	}
	
}
?>