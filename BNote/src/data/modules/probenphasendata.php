<?php
/**
 * Data Access Class for rehearsal phase data.
 * @author matti
 *
 */
class ProbenphasenData extends AbstractData {

	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"name" => array("Name", FieldType::CHAR),
				"begin" => array("Beginn", FieldType::DATE),
				"end" => array("Ende", FieldType::DATE),
				"notes" => array("Notizen", FieldType::TEXT)
		);

		$this->references = array();

		$this->table = "rehearsalphase";

		$this->init();
	}
	
	function getPhases($current = true) {
		$query = "SELECT * FROM " . $this->table . " ";
		if(!$current) {
			$query .= "WHERE end > NOW() ";
		}
		$query .= "ORDER BY begin";
		return $this->database->getSelection($query);
	}
	
	function getConcertsForPhase($phaseId) {
		$query = "SELECT c.id, c.begin, l.name as location, c.notes ";
		$query .= "FROM rehearsalphase_concert rc JOIN concert c ON rc.concert = c.id ";
		$query .= "     JOIN location l ON c.location = l.id ";
		$query .= "WHERE rc.rehearsalphase = $phaseId ";
		$query .= "ORDER BY c.begin";
		return $this->database->getSelection($query);
	}
	
	function getContactsForPhase($phaseId) {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname) as name, i.name as instrument, c.phone, c.mobile, c.email ";
		$query .= "FROM rehearsalphase_contact rc JOIN contact c ON rc.contact = c.id ";
		$query .= "     JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE rc.rehearsalphase = $phaseId ";
		$query .= "ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	function getRehearsalsForPhase($phaseId) {
		$query = "SELECT r.id, r.begin, l.name as location ";
		$query .= "FROM rehearsalphase_rehearsal p JOIN rehearsal r ON p.rehearsal = r.id ";
		$query .= "     JOIN location l ON r.location = l.id ";
		$query .= "WHERE p.rehearsalphase = $phaseId ";
		$query .= "ORDER BY r.begin";
		return $this->database->getSelection($query);
	}
	
	private function idInPhase($phaseId, $entityId, $entity) {
		$ct = $this->database->getCell("rehearsalphase_$entity", "count($entity)",
				"rehearsalphase = $phaseId AND $entity = $entityId");
		return ($ct > 0);
	}
	
	private function addEntities($phaseId, $entity, $data) {
		$query = "INSERT INTO rehearsalphase_$entity VALUES ";
		$count = 0;
		
		foreach($data as $i => $row) {
			$fieldName = $entity . "_" . $row["id"];
		
			if(isset($_POST[$fieldName]) && $_POST[$fieldName] == "on") {
				if($this->idInPhase($phaseId, $row["id"], $entity)) continue;
				if($count > 0) $query .= ",";
				$query .= "($phaseId, " . $row["id"] . ")";
				$count++;
			}
		}
		
		if($count > 0) {
			$this->database->execute($query);
		}
	}
	
	function getFutureRehearsals() {
		$query = "SELECT * FROM rehearsal WHERE begin > NOW()";
		return $this->database->getSelection($query);
	}
	
	function addRehearsals($phaseId) {		
		$this->addEntities($phaseId, "rehearsal", $this->getFutureRehearsals());
	}
	
	function getFutureConcerts() {
		$query = "SELECT * FROM concert WHERE begin > NOW()";
		return $this->database->getSelection($query);
	}
	
	function addConcerts($phaseId) {
		$this->addEntities($phaseId, "concert", $this->getFutureConcerts());
	}
	
	function getContacts() {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname, ' (', i.name, ')') as name ";
		$query .= "FROM contact c JOIN instrument i ON c.instrument = i.id ";
		$query .= "ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	function addContacts($phaseId) {
		$this->addEntities($phaseId, "contact", $this->getContacts());
	}
	
	function addGroupContacts($phaseId) {
		$groups = $this->adp()->getGroups(true);
		$groupsToAdd = array();
		foreach($groups as $i => $group) {
			$field = "group_" . $group["id"];
			if(isset($_POST[$field]) && $_POST[$field] == "on") {
				array_push($groupsToAdd, $group["id"]);
			}
		}
		
		if(count($groupsToAdd) > 0) {
			// get contact ids of selected groups
			$query = "SELECT c.id FROM contact c JOIN contact_group cg ON cg.contact = c.id WHERE ";
			foreach($groupsToAdd as $i => $grp) {
				if($i > 0) $query .= "OR ";
				$query .= "cg.group = $grp ";
			}
			$contacts = $this->database->getSelection($query);
			
			// add non-super-user contacts to phase
			$query = "INSERT INTO rehearsalphase_contact VALUES ";
			$count = 0;			
			for($i = 1; $i < count($contacts); $i++) {
				$cid = $contacts[$i]["id"];
				if(!$this->isContactSuperUser($cid) && !$this->idInPhase($phaseId, $cid, "contact")) {
					if($count++ > 0) $query .= ", ";
					$query .= "( $phaseId, $cid )";
				}
			}
			if($count > 0) {
				$this->database->execute($query);
			}
		}
	}
	
	function deleteEntity($entity, $phaseId, $entityId) {
		$query = "DELETE FROM rehearsalphase_$entity WHERE rehearsalphase = $phaseId AND $entity = $entityId";
		$this->database->execute($query);
	}
	
	function isContactSuperUser($cid) {
		$uid = $this->database->getCell("user", "id", "contact = $cid");
		return $this->getSysdata()->isUserSuperUser($uid);
	}
}