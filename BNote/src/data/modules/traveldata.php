<?php

class TravelData extends AbstractLocationData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("TravelData_construct.id"), FieldType::INTEGER),
			"tour" => array(Lang::txt("TravelData_construct.tour"), FieldType::REFERENCE),
			"num" => array(Lang::txt("TravelData_construct.num"), FieldType::CHAR),
			"departure" => array(Lang::txt("TravelData_construct.departure"), FieldType::DATETIME),
			"departure_location" => array(Lang::txt("TravelData_construct.departure_location"), FieldType::CHAR),
			"arrival" => array(Lang::txt("TravelData_construct.arrival"), FieldType::DATETIME),
			"arrival_location" => array(Lang::txt("TravelData_construct.arrival_location"), FieldType::CHAR),
			"planned_cost" => array(Lang::txt("TravelData_construct.planned_cost"), FieldType::CURRENCY),
			"notes" => array(Lang::txt("TravelData_construct.notes"), FieldType::TEXT)
		);
	
		$this->references = array(
			"tour" => "tour"
		);
	
		$this->table = "travel";
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