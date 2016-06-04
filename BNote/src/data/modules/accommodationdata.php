<?php

class AccommodationData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array("ID", FieldType::INTEGER),
			"tour" => array(lang::txt("tour"), FieldType::REFERENCE),
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
			"tour" => "tour",
			"location" => "location"
		);
	
		$this->table = "accommodation";
		$this->init($dir_prefix);
	}
	
	function filterTourAccommodations($items, $tour_id, $filterAttribute="tour") {
		$result = array();
		for($i = 0; $i < count($items); $i++) {
			if($i == 0 || $items[$i][$filterAttribute] == $tour_id) {
				array_push($result, $items[$i]);
			}
		}
		return $result;
	}
}

?>