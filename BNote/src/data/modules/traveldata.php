<?php

class TravelData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array("ID", FieldType::INTEGER),
			"tour" => array(Lang::txt("tour"), FieldType::REFERENCE),
			"num" => array(Lang::txt("travel_num"), FieldType::CHAR),
			"departure" => array(Lang::txt("travel_departure_datetime"), FieldType::DATETIME),
			"departure_location" => array(Lang::txt("travel_departure_location"), FieldType::CHAR),
			"arrival" => array(Lang::txt("travel_arrival_datetime"), FieldType::DATETIME),
			"arrival_location" => array(Lang::txt("travel_arrival_location"), FieldType::CHAR),
			"planned_cost" => array(Lang::txt("travel_planned_cost"), FieldType::DECIMAL),
			"notes" => array("Notizen", FieldType::TEXT)
		);
	
		$this->references = array(
			"tour" => "tour"
		);
	
		$this->table = "travel";
		$this->init($dir_prefix);
	}
	
}

?>