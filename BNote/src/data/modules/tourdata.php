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
	
}

?>