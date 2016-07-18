<?php

class TourData extends AbstractData {
	
	/*
	 * Data Model
	 * ----------
	 * [TM] = Tour-only sub-modules
	 * 
	 * Tour (main entity that keeps track of tours)
	 * Tour N - M Contact (Players who participate in tour)
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
	
	function getRehearsals($tour_id) {
		# when a tour was deleted it won't show here, because a standard join is used
		$query = "SELECT r.id, r.begin, r.notes as rehearsal_notes, l.name, l.notes as location_notes, a.street, a.city
				FROM rehearsal r 
				JOIN tour_rehearsal t ON r.id = t.rehearsal 
				LEFT OUTER JOIN location l ON r.location = l.id
				LEFT OUTER JOIN address a ON l.address = a.id
				WHERE begin > now() and t.tour = $tour_id
				ORDER BY begin";
		return $this->database->getSelection($query);
	}
	
	function addReference($tour_id, $ref_entity, $ref_id) {
		$this->regex->isPositiveAmount($tour_id);
		$this->regex->isPositiveAmount($ref_id);
		
		$table = "tour_" . $ref_entity;
		$insert = "INSERT INTO $table (tour, $ref_entity) VALUES ($tour_id, $ref_id)";
		
		$this->removeReference($tour_id, $ref_entity, $ref_id);
		$this->database->execute($insert);
	}
	
	function removeReference($tour_id, $ref_entity, $ref_id) {
		$this->regex->isPositiveAmount($tour_id);
		$this->regex->isPositiveAmount($ref_id);
		
		$table = "tour_" . $ref_entity;
		$remove = "DELETE FROM $table WHERE tour = $tour_id AND $ref_entity = $ref_id";
		$this->database->execute($remove);
	}
	
	/**
	 * Finds tour contacts.
	 * @param int $tour_id 0 if all contacts should be returned, otherwise for this tour only.
	 * @return DB selection
	 */
	function getContacts($tour_id) {
		// all contacts (but filtered super users)
		$allContacts = $this->adp()->getContacts();
		if($tour_id == 0) {
			return $allContacts;
		}
		$contactIdsSel = $this->database->getSelection("SELECT contact FROM tour_contact WHERE tour = $tour_id");
		$dict = Data::dbSelectionToDict($contactIdsSel, "contact", array("contact"));
		// remove all contacts from the allContacts array that are not part of the tour
		$contacts = array();
		for($i = 0; $i < count($allContacts); $i++) {
			if($i == 0 || key_exists($allContacts[$i]["id"], $dict)) {
				array_push($contacts, $allContacts[$i]);
			}
		}
		return $contacts;
	}
	
	function addContacts($tour_id, $contactIds) {
		foreach($contactIds as $i => $cid) {
			$this->addReference($tour_id, "contact", $cid);
		}
	}
	
	function getConcerts($tour_id) {
		$query = "SELECT c.id, c.title, c.begin, c.end, c.notes, l.name as locationname, p.name as program, c.approve_until 
				  FROM concert c JOIN tour_concert t ON c.id = t.concert
				  LEFT OUTER JOIN location l ON c.location = l.id
				  LEFT OUTER JOIN program p on c.program = p.id
				  WHERE t.tour = $tour_id";
		return $this->database->getSelection($query);
	}
	
	function addConcert($tour_id, $values) {
		require_once $GLOBALS['DIR_DATA_MODULES'] . "konzertedata.php";
		$konzertData = new KonzerteData();
		$konzertId = $konzertData->saveConcert();
		$this->addReference($tour_id, "concert", $konzertId);
	}
	
	function getTasks($tour_id, $is_complete) {
		$isc = 0;
		if($is_complete) $isc = 1;
		$where = "WHERE tour_task.tour = $tour_id AND is_complete = $isc";
		$query = "SELECT task.id, title, description, CONCAT(c.name, ' ', c.surname) as assigned_to, due_at, is_complete
				  FROM task JOIN tour_task ON task.id = tour_task.task
				  JOIN contact c ON task.assigned_to = c.id
				  $where
				  ORDER BY task.is_complete ASC, task.due_at DESC";
		return $this->database->getSelection($query);
	}
	
	function createTask($tour_id, $values) {
		$taskData = new AufgabenData();
		$tid = $taskData->create($values);
		$this->addReference($tour_id, "task", $tid);
	}
	
	function getEquipment($tour_id, $show_all=true) {
		// return all equipment with tour information where available
		$where = "te.tour = $tour_id OR te.tour IS NULL";
		if(!$show_all) {
			$where = "te.tour = $tour_id and te.quantity > 0";
		}
		$query = "SELECT e.id, e.name, e.model, e.make, e.notes as equipment_notes,
						 te.notes as eq_tour_notes, te.quantity as tour_quantity 
				  FROM equipment e LEFT OUTER JOIN tour_equipment te ON e.id = te.equipment
				  WHERE $where
				  ORDER BY e.name ASC";
		
		return $this->database->getSelection($query);
	}
	
	function saveEquipment($tour, $values) {
		// figure out what was added first
		$equipment = $this->getEquipment($tour);
		for($i = 1; $i < count($equipment); $i++) {
			$eqid = $equipment[$i]["id"];
			$notes_field = "e_t_" . $eqid;
			$quantity_field = "e_q_" . $eqid;
			$quantity = 0;
			$notes = "";
			if(isset($_POST[$quantity_field])) {
				$quantity = $_POST[$quantity_field];
			}
			if(isset($_POST[$notes_field])) {
				$notes = $_POST[$notes_field];
			}
			
			// validate values
			$this->regex->isText($notes);
			
			// remove from list if present --> update by replacement
			$this->removeReference($tour, "equipment", $eqid);
			$insert = "INSERT INTO tour_equipment (tour, equipment, quantity, notes)
					   VALUES ($tour, $eqid, $quantity, \"$notes\")";
			$this->database->execute($insert);
		}
	}
	
}

?>