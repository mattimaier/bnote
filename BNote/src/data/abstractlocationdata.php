<?php

/**
 * All classes needing address functionality should extend this class instead from direct AbstractData.
 * @author matti
 *
 */
abstract class AbstractLocationData extends AbstractData {
	
	/**
	 * Name of the address table in the database.
	 * @var string
	 */
	public static $ADDRESS_TABLE = "address";
	
	/**
	 * Method to return field array.
	 * @return Array (default fieldname) => (fieldname)
	 */
	protected function getAddressFields() {
		return array(
			"street" => "street",
			"city" => "city",
			"zip" => "zip",
			"state" => "state",
			"country" => "country"
		);
	}
	
	/**
	 * Returns the field array for an address. You can merge this to the getFields() result.
	 * @return Array field information.
	 */
	public function getAddressFields() {
		$fields = $this->getAddressFields();
		return array(
			$fields["street"] => array(Lang::txt("street"), FieldType::CHAR),
			$fields["city"] => array(Lang::txt("city"), FieldType::CHAR),
			$fields["zip"] => array(Lang::txt("zip"), FieldType::CHAR),
			$fields["state"] => array(Lang::txt("state"), FieldType::CHAR),
			$fields["country"] => array(Lang::txt("country"), FieldType::CHAR)
		);
	}
	
	/**
	 * Validation for the address fields.
	 * @param Array $values Make sure the fields match to getAddressFields().
	 */
	protected function validateAddress($values) {
		$fields = $this->getAddressFields();
		
		if(isset($values[$fields["street"]]) && $values[$fields["street"]] != "") {
			$this->regex->isStreet($values[$fields["street"]]);
		}
		
		// city is a required field
		$this->regex->isCity($values[$fields["city"]]);
		
		if(isset($values[$fields["zip"]]) && $values[$fields["zip"]] != "") {
			$this->regex->isZip($values[$fields["zip"]]);
		}
		
		if(isset($values[$fields["state"]]) && $values[$fields["state"]] != "") {
			$this->regex->isSubject($values[$fields["state"]]);
		}
		
		if($values[$fields["country"]] && $values[$fields["country"]] != "") {
			$this->regex->isSubject($values[$fields["country"]]);
		}
	}
	
	/**
	 * Creates the address row in the address table.
	 * @param Array $values Usually post array containing fields as specified in getAddressFields().
	 * @return int Address ID.
	 */
	protected function createAddress($values) {
		$this->validateAddress($values);
		$fields = $this->getAddressFields();
		
		// build query
		$query = "INSERT INTO " . AbstractLocationData::$ADDRESS_TABLE;
		$query .= " (street, city, zip, state, country) VALUES (?, ?, ?, ?, ?)";
		$params = $this->getPrepStatementValueArray($values);
		
		// run
		$addressId = $this->database->prepStatement($query, $params);
		return $addressId;
	}
	
	private function getPrepStatementValueArray($values) {
		return array(
				array("s", $values[$fields["street"]]),
				array("s", $values[$fields["city"]]),
				array("s", $values[$fields["zip"]]),
				array("s", $values[$fields["state"]]),
				array("s", $values[$fields["country"]])
		);
	}
	
	/**
	 * Update address with the given ID and values.
	 * @param int $addressId Address to update.
	 * @param Array $values Values where to read the fields from.
	 */
	protected function updateAddress($addressId, $values) {
		$this->validateAddress($values);
		$fields = $this->getAddressFields();
		
		// build query
		$query = "UPDATE " . AbstractLocationData::$ADDRESS_TABLE . " SET ";
		$query .= "street = ?, city = ?, zip = ?, state = ?, country = ? ";
		$query .= "WHERE id = ?";
		
		$params = $this->getPrepStatementValueArray($values);
		array_push($params, array("i", $addressId));
		
		// run
		$this->database->prepStatement($query, $params);
	}
	
	/**
	 * Deletes the address with the given ID.
	 * @param int $addressId Address to delete.
	 */
	protected function deleteAddress($addressId) {
		$query = "DELETE FROM " . AbstractLocationData::$ADDRESS_TABLE . " WHERE id = ?";
		$this->database->prepStatement($query, array(array("i", $addressId)));
	}
	
	/**
	 * Joins address to location.
	 * @param int $id Location ID.
	 * @return Array with address and location data.
	 */
	public function getLocation($id) {
		$this->regex->isPositiveAmount($id);
		
		$query = "SELECT l.*, a.street, a.city, a.zip, a.state, a.country
			FROM location l JOIN address a ON l.address = address.id 
			WHERE l.id = $id";
		
		return $this->database->getRow($query);
	}
	
	/**
	 * Uses the findByIdNoRef() method to find the ID of the address in this entity.
	 * @param int $id ID of the base entity.
	 * @param string $addressField Name of the address field, by default "address".
	 * @return int Address ID.
	 */
	protected function getAddressFromId($id, $addressField = "address") {
		$entity = $this->findByIdNoRef($id);
		return $entity[$addressField];
	}
	
	/**
	 * Returns all available location types as a selection object.
	 */
	public function getLocationTypes() {
		return $this->database->getSelection("SELECT * FROM location_type");
	}
}

?>