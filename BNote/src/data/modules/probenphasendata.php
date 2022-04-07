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
				"id" => array(Lang::txt("ProbenphasenData_construct.id"), FieldType::INTEGER),
				"name" => array(Lang::txt("ProbenphasenData_construct.name"), FieldType::CHAR),
				"begin" => array(Lang::txt("ProbenphasenData_construct.begin"), FieldType::DATE),
				"end" => array(Lang::txt("ProbenphasenData_construct.end"), FieldType::DATE),
				"notes" => array(Lang::txt("ProbenphasenData_construct.notes"), FieldType::TEXT)
		);

		$this->references = array();

		$this->table = "rehearsalphase";

		$this->init();
	}
	
	function getPhases($current = true) {
		$query = "SELECT * FROM rehearsalphase ";
		if($current) {
			$query .= "WHERE end >= NOW() ";
		}
		else {
			$query .= "WHERE end < NOW() ";
		}
		$query .= "ORDER BY begin";
		return $this->database->getSelection($query);
	}
	
	function getConcertsForPhase($phaseId) {
		$query = "SELECT c.id, c.title, c.begin, l.name as location, c.notes ";
		$query .= "FROM rehearsalphase_concert rc JOIN concert c ON rc.concert = c.id ";
		$query .= "     JOIN location l ON c.location = l.id ";
		$query .= "WHERE rc.rehearsalphase = ? ";
		$query .= "ORDER BY c.begin";
		return $this->database->getSelection($query, array(array("i", $phaseId)));
	}
	
	function getContactsForPhase($phaseId) {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname) as name, i.name as instrument, 
					IF(c.share_phones = 1, c.phone, '') as phone, 
					IF(c.share_phones = 1, c.mobile, '') as mobile,
					IF(c.share_email = 1, c.email, '') as email ";
		$query .= "FROM rehearsalphase_contact rc JOIN contact c ON rc.contact = c.id ";
		$query .= "     LEFT JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE rc.rehearsalphase = ? ";
		$query .= "ORDER BY i.rank, c.surname";
		return $this->database->getSelection($query, array(array("i", $phaseId)));
	}
	
	function getRehearsalsForPhase($phaseId) {
		$query = "SELECT r.id, r.begin, l.name as location ";
		$query .= "FROM rehearsalphase_rehearsal p JOIN rehearsal r ON p.rehearsal = r.id ";
		$query .= "     JOIN location l ON r.location = l.id ";
		$query .= "WHERE p.rehearsalphase = ? ";
		$query .= "ORDER BY r.begin";
		return $this->database->getSelection($query, array(array("i", $phaseId)));
	}
	
	private function idInPhase($phaseId, $entityId, $entity) {
		$query = "SELECT count($entity) as cnt FROM rehearsalphase_$entity WHERE rehearsalphase = ? AND $entity = ?";
		$params = array(array("i", $phaseId), array("i", $entityId));
		$ct = $this->database->colValue($query, "cnt", $params);
		return ($ct > 0);
	}
	
	private function addEntities($phaseId, $entity, $data) {
		$this->regex->isDbItem($entity, "entity");
		
		$tuples = array();
		$params = array();
		foreach($data as $i => $row) {
			if($i == 0) continue;
			$fieldName = $entity . "_" . $row["id"];
			$this->regex->isDbItem($fieldName);
			if(isset($_POST[$fieldName]) && $_POST[$fieldName] == "on") {
				if($this->idInPhase($phaseId, $row["id"], $entity)) continue;
				array_push($tuples, "(?, ?)");
				array_push($params, array("i", $phaseId));
				array_push($params, array("i", $row["id"]));
			}
		}
		
		if(count($tuples) > 0) {
			$query = "INSERT INTO rehearsalphase_$entity VALUES " . join(", ", $tuples);
			$this->database->execute($query, $params);
		}
	}
	
	function addRehearsals($phaseId) {		
		$this->addEntities($phaseId, "rehearsal", $this->adp()->getFutureRehearsals());
	}
	
	function getFutureConcerts() {
		$query = "SELECT * FROM concert WHERE end > NOW() ORDER BY begin, end";
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
			if($i == 0) continue;
			$field = "group_" . $group["id"];
			if(isset($_POST[$field]) && $_POST[$field] == "on") {
				array_push($groupsToAdd, $group["id"]);
			}
		}
		
		if(count($groupsToAdd) > 0) {
			// get contact ids of selected groups
			$params = array();
			$groupQ = array();
			foreach($groupsToAdd as $i => $grp) {
				array_push($groupQ, "cg.group = ? ");
				array_push($params, $grp);
			}
			$query = "SELECT c.id FROM contact c JOIN contact_group cg ON cg.contact = c.id WHERE " . join(" OR ", $groupQ);
			$contacts = $this->database->getSelection($query, $params);
			
			// add non-super-user contacts to phase
			$tuples = array();
			$params = array();
			for($i = 1; $i < count($contacts); $i++) {
				$cid = $contacts[$i]["id"];
				if(!$this->isContactSuperUser($cid) && !$this->idInPhase($phaseId, $cid, "contact")) {
					array_push($tuples, "(?, ?)");
					array_push($params, array("i", $phaseId));
					array_push($params, array("i", $cid));
				}
			}
			if($count > 0) {
				$query = "INSERT INTO rehearsalphase_contact VALUES " . join(",", $tuples);
				$this->database->execute($query, $params);
			}
		}
	}
	
	function deleteEntity($entity, $phaseId, $entityId) {
		$this->regex->isDbItem($entity, "rehersalphase_<entity>");
		$query = "DELETE FROM rehearsalphase_$entity WHERE rehearsalphase = ? AND $entity = ?";
		$this->database->execute($query, array(array("i", $phaseId), array("i", $entityId)));
	}
	
	function isContactSuperUser($cid) {
		$uid = $this->database->colValue("SELECT id FROM user WHERE contact = ?", "id", array(array("i", $cid)));
		return $this->getSysdata()->isUserSuperUser($uid);
	}
	
	function delete($id) {
		// first remove all relations
		$query = "DELETE FROM rehearsalphase_rehearsal WHERE rehearsalphase = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$query = "DELETE FROM rehearsalphase_concert WHERE rehearsalphase = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$query = "DELETE FROM rehearsalphase_contact WHERE rehearsalphase = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		// delete rehearsalphase
		parent::delete($id);
	}
}