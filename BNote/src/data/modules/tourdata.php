<?php

class TourData extends AbstractData {
	
	/*
	 * Data Model
	 * ----------
	 * [TM] = Tour-only sub-modules
	 * 
	 * Tour (main entity that keeps track of tours)
	 * Tour N - M Contact (Ppl who participate in tour)
	 * Tour 1 - M Concert (Concerts played within a tour) --> Programs attached
	 * Tour 1 - N Rehearsal (Rehearsals on tour)
	 * Tour 1 - N [TM] Accommodation (where -> Location, notes, checkin, checkout)
	 * Tour 1 - N [TM] Travel (means of transportatation, departure, arrival, notes)
	 * Tour 1 - N Checklist (items, simple textline, that can be marked as done) --> Aufgaben (with tour_id)
	 * Tour N - M Equipment (select what needs to be packed)
	 */
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"name" => array("Name", FieldType::CHAR),
				"start" => array("Von", FieldType::DATE),
				"end" => array("Bis", FieldType::DATE),
				"notes" => array("Notizen", FieldType::TEXT)
		);
	
		$this->references = array();
	
		$this->table = "tour";
		$this->init($dir_prefix);
	}
	
	function createRehearsal($values) {
		require_once $GLOBALS['DIR_DATA_MODULES'] . "probendata.php";
		$rehData = new ProbenData();
		$rehId = $rehData->create($values);
		$tour_id = $values["tour"];
		$this->addReference($tour_id, "rehearsal", $rehId);
	}
	
	function addReference($tour_id, $ref_entity, $ref_id) {
		$this->regex->isPositiveAmount($tour_id);
		$this->regex->isPositiveAmount($ref_id);
		
		$table = "tour_" . $ref_entity;
		$remove = "DELETE FROM $table WHERE tour = $tour_id AND $ref_entity = $ref_id";
		$insert = "INSERT INTO $table (tour, $ref_entity) VALUES ($tour_id, $ref_id)";
		
		$this->database->execute($remove);
		$this->database->execute($insert);
	}
	
}

?>