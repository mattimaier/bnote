<?php
global $dir_prefix;
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php";

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
	 * Filename where to find the ISO countries CSV.
	 * @var string Filename.
	 */
	private static $ISO_COUNTRIES_FILENAME = 'iso3166-code3.csv';
	
	/**
	 * Content of the file data/iso3166-code3.csv
	 * @var array Country list.
	 */
	private $isoCountries = NULL;
	
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
	public function getAddressViewFields() {
		$fields = $this->getAddressFields();
		return array(
				$fields["street"] => array(Lang::txt("AbstractLocationData_getAddressViewFields.street"), FieldType::CHAR, false, 3),
				$fields["zip"] => array(Lang::txt("AbstractLocationData_getAddressViewFields.zip"), FieldType::CHAR, false, 1),
				$fields["city"] => array(Lang::txt("AbstractLocationData_getAddressViewFields.city"), FieldType::CHAR, true, 2),
				$fields["state"] => array(Lang::txt("AbstractLocationData_getAddressViewFields.state"), FieldType::CHAR, false, 3),
				$fields["country"] => array(Lang::txt("AbstractLocationData_getAddressViewFields.country"), FieldType::CHAR, true, 3)
		);
	}
	
	/**
	 * Find the address with the given address ID.
	 * @param int $id Address ID.
	 * @return Array Row object of the address.
	 */
	function getAddress($id) {
		if($id < 1) return null;		
		$q = "SELECT street, city, zip, state, country FROM address WHERE id = ?";
		return $this->database->fetchRow($q, array(array("i", $id)));
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
		
		if(isset($values[$fields["country"]]) && $values[$fields["country"]] != "") {
			$this->regex->isSubject($values[$fields["country"]]);
		}
	}
	
	/**
	 * Creates the address row in the address table.
	 * @param Array $values Usually post array containing fields as specified in getAddressFields().
	 * @return int Address ID.
	 */
	public function createAddress($values) {
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
		$fields = $this->getAddressFields();
		$params = array(
				array("s", $values[$fields["street"]]),
				array("s", $values[$fields["city"]]),
				array("s", $values[$fields["zip"]])
		);
		
		// add optional values
		$state = "";
		if(isset($values[$fields["state"]])) {
			$state = $values[$fields["state"]];
		}
		array_push($params, array("s", $state));
		
		$country = "";
		if(isset($values[$fields["country"]])) {
			$country = $values[$fields["country"]];
		}
		array_push($params, array("s", $country));
		
		return $params;
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
		$query = "SELECT l.*, a.street, a.city, a.zip, a.state, a.country
			FROM location l JOIN address a ON l.address = a.id 
			WHERE l.id = ?";
		return $this->database->fetchRow($query, array(array("i", $id)));
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
	
	protected function readCountries() {
		$countryFilename = $this->dirPrefix . "data/" . AbstractLocationData::$ISO_COUNTRIES_FILENAME;
		if(($countryFileHandle = fopen($countryFilename, "r")) !== FALSE) {
			$this->isoCountries = array();
			$rowIdx = 0;
			while (($data = fgetcsv($countryFileHandle, 0, ";")) !== FALSE) {
				if($rowIdx == 0) {
					$rowIdx++;
					continue;
				}
				array_push($this->isoCountries, array(
					"code" => $data[1],
					"de" => $data[0],
					"en" => $data[2],
					"fr" => $data[3]					
				));
				$rowIdx++;
			}
			fclose($countryFileHandle);
		}
		else {
			new BNoteError("Unable to read $countryFilename");
		}
	}
	
	/**
	 * Returns the list of countries from the ISO file as a simple array (no selection!).
	 */
	public function getCountries() {
		if($this->isoCountries == NULL) {
			$this->readCountries();
		}
		return $this->isoCountries;
	}
	
	/**
	 * Finds the address with its fields by the location ID.
	 * @param int $locationId Location ID.
	 * @return Array Address as from getAddress($id)
	 */
	public function getAddressFromLocation($locationId) {
		$addressId = $this->database->colValue("SELECT address FROM location WHERE id = ?", "address", array(array("i", $locationId)));
		return $this->getAddress($addressId);
	}
}