<?php

/**
 * Data Access Class for member data.
 * @author matti
 *
 */
class MitspielerData extends AbstractData {
	
	function __construct() {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"surname" => array("Name", FieldType::CHAR),
				"name" => array("Vorname", FieldType::CHAR),
				"phone" => array("Telefon", FieldType::CHAR),
				"fax" => array("Fax", FieldType::CHAR),
				"mobile" => array("Mobil", FieldType::CHAR),
				"business" => array("Geschäftlich", FieldType::CHAR),
				"email" => array("E-Mail", FieldType::EMAIL),
				"address" => array("Adresse", FieldType::REFERENCE),
				"instrument_name" => array("Instrument", FieldType::REFERENCE),
				"status" => array("Status", FieldType::ENUM)
		);
		
		$this->init();
	}
	
	function getMembers() {
		$query = "SELECT c.surname, c.name, c.phone, c.mobile, c.business, c.email, i.name as instrument_name ";
		$query .= "FROM contact c JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE c.status = 'ADMIN' or c.status = 'MEMBER' ";
		$query .= "ORDER BY c.surname, c.name ASC";
		return $this->database->getSelection($query);
	}
	
}

?>