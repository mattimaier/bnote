<?php

class AccommodationData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array("ID", FieldType::INTEGER),
			"location" => array(lang::txt("location"), FieldType::REFERENCE),
			"checkin" => array("Checkin", FieldType::DATE),
			"checkout" => array("Checkout", FieldType::DATE),
			"breakfast" => array("Frühstück", FieldType::BOOLEAN),
			"lunch" => array("Mittagessen", FieldType::BOOLEAN),
			"dinner" => array("Abendessen", FieldType::BOOLEAN),
			"planned_cost" => array(lang::txt("accommodation_price"), FieldType::DECIMAL),
			"notes" => array("Notizen", FieldType::TEXT)
		);
	
		$this->references = array(
			"location" => "location"
		);
	
		$this->table = "accommodation";
		$this->init($dir_prefix);
	}
	
}

?>