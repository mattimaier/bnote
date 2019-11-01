<?php

class AccommodationData extends AbstractLocationData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(lang::txt("AccommodationData_construct.id"), FieldType::INTEGER),
			"tour" => array(lang::txt("AccommodationData_construct.tour"), FieldType::REFERENCE),
			"location" => array(lang::txt("AccommodationData_construct.location"), FieldType::REFERENCE),
			"checkin" => array(lang::txt("AccommodationData_construct.checkin"), FieldType::DATE),
			"checkout" => array(lang::txt("AccommodationData_construct.checkout"), FieldType::DATE),
			"breakfast" => array(lang::txt("AccommodationData_construct.breakfast"), FieldType::BOOLEAN),
			"lunch" => array(lang::txt("AccommodationData_construct.lunch"), FieldType::BOOLEAN),
			"dinner" => array(lang::txt("AccommodationData_construct.dinner"), FieldType::BOOLEAN),
			"planned_cost" => array(lang::txt("AccommodationData_construct.planned_cost"), FieldType::CURRENCY),
			"notes" => array(lang::txt("AccommodationData_construct.notes"), FieldType::TEXT)
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