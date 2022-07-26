<?php

/**
 * Data access for rehearsal data.
 * @author matti
 *
 */
class ProbenData extends AbstractLocationData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("ProbenData_construct.id"), FieldType::INTEGER),
			"begin" => array(Lang::txt("ProbenData_construct.begin"), FieldType::DATETIME),
			"end" => array(Lang::txt("ProbenData_construct.end"), FieldType::DATETIME),
			"approve_until" => array(Lang::txt("ProbenData_construct.approve_until"), FieldType::DATETIME),
			"location" => array(Lang::txt("ProbenData_construct.location"), FieldType::REFERENCE),
			"conductor" => array(Lang::txt("ProbenData_construct.conductor"), FieldType::REFERENCE),
			"serie" => array(Lang::txt("ProbenData_construct.serie"), FieldType::REFERENCE),
			"notes" => array(Lang::txt("ProbenData_construct.notes"), FieldType::TEXT),
			"status" => array(Lang::txt("ProbenData_construct.status"), FieldType::ENUM)
		);
		
		$this->references = array(
			"location" => "location", 
			"serie" => "rehearsalserie",
			"conductor" => "contact"
		);
		
		$this->table = "rehearsal";

		$this->init($dir_prefix);
		$this->init_trigger($dir_prefix);
	}
	
	private function defaultQuery() {
		$query = "SELECT r.id as id, begin, end, approve_until, conductor, r.notes, r.status, r.serie, r.location, name, street, city, zip";
		$query .= " FROM " . $this->table . " r, location l, address a";
		$query .= " WHERE r.location = l.id AND l.address = a.id";
		return $query;
	}
	
	function getRehearsal($id) {
		$r = $this->findByIdJoined($id, null);
		$c = $this->getCustomFieldData('r', $id);
		$r["groups"] = $this->getRehearsalGroups($id);
		return array_merge($r, $c);
	}
	
	function getNextRehearsal() {
		$query = $this->defaultQuery() . " AND end > NOW()";
		$query .= " ORDER BY begin ASC LIMIT 0,1";
		return $this->database->fetchRow($query, array());
	}
	
	function findByIdJoined($id, $colExchange) {
		$query = $this->defaultQuery() . " AND r.id = ?";
		return $this->database->fetchRow($query, array(array("i", $id)));
	}
	
	function getRehearsalGroups($id) {
		$query = "SELECT g.* FROM `rehearsal_group` ag JOIN `group` g ON ag.`group` = g.id WHERE ag.rehearsal = ?";
		return $this->database->getSelection($query, array(array("i", $id)));
	}
	
	function getParticipants($rid) {
		$query = 'SELECT c.id, CONCAT_WS(" ", c.name, c.surname) as name, c.nickname, i.name as instrument,
					CASE ru.participate WHEN 1 THEN "' . Lang::txt("ProbenData_getParticipants.yes") . '" WHEN 2 THEN "' . Lang::txt("ProbenData_getParticipants.maybe") . '" ELSE "' . Lang::txt("ProbenData_getParticipants.no") . '" END as participate, ru.reason, ru.replyon
					FROM rehearsal_user ru
						JOIN user u ON ru.user = u.id
						JOIN contact c ON u.contact = c.id
						LEFT OUTER JOIN instrument i ON c.instrument = i.id
					WHERE ru.rehearsal = ?
					ORDER BY i.rank, name';
		
		return $this->database->getSelection($query, array(array("i", $rid)));
	}
	
	function getOpenParticipation($rid) {
		// solve this problem programmatically - easier
		$parts = $this->getParticipants($rid);
		$contacts = $this->getRehearsalContacts($rid);
		$result = array();
		$result[0] = $contacts[0];
		for($i = 1; $i < count($contacts); $i++) {
			$contactParts = false;
			for($j = 1; $j < count($parts); $j++) {
				if($parts[$j]["id"] == $contacts[$i]["id"]) {
					$contactParts = true;
					break;
				}
			}
			if(!$contactParts) array_push($result, $contacts[$i]);
		}
		return $result;
	}
	
	function getParticipantStats($rid) {
		$stats = array();
		
		// number of paricipants who...
		$querySum = "SELECT count(*) as cnt FROM rehearsal_user WHERE rehearsal = ?";
		$query = $querySum . " AND participate = ?";
		// ...paricipate
		$stats[Lang::txt("ProbenData_getParticipantStats.Confirmations")] = $this->database->colValue($query, "cnt", array(array("i", $rid), array("i", 1)));
		// ...do not paricipate
		$stats[Lang::txt("ProbenData_getParticipantStats.Cancellations")] = $this->database->colValue($query, "cnt", array(array("i", $rid), array("i", 0)));
		// ...maybe participate
		$stats[Lang::txt("ProbenData_getParticipantStats.Possible")] = $this->database->colValue($query, "cnt", array(array("i", $rid), array("i", 2)));
		// total number of...
		// ...decisions
		$stats[Lang::txt("ProbenData_getParticipantStats.Total")] = $this->database->colValue($querySum, "cnt", array(array("i", $rid)));
		
		return $stats;
	}
	
	function getAttendingInstruments($rid) {
		$query = "SELECT i.name, GROUP_CONCAT(CONCAT(c.name, ' ', c.surname) SEPARATOR ', ') as player, count(c.name)
					FROM rehearsal_user ru
					     JOIN user u ON ru.user = u.id
					     JOIN contact c ON u.contact = c.id
					     JOIN instrument i ON c.instrument = i.id
					WHERE ru.participate = 1 AND ru.rehearsal = ?
					GROUP BY i.name
					ORDER BY i.rank, i.name";
		$res = $this->database->getSelection($query, array(array("i", $rid)));
		$attInstruments = array();
		foreach($res as $i => $info) {
			if($i == 0) continue;
			$attInstruments[$info["name"]] = $info["player"];
		}
		return $attInstruments;
	}
	
	/**
	 * Checks for the given instrument and rehearsal what the status of participation is.
	 * @param Integer $rid Rehearsal ID.
	 * @param Integer $instrumentId Instrument ID, set NULL (default) for all
	 * @param Boolean $stripped Cut first row (true by default)
	 */
	function getParticipantOverview($rid, $instrumentId=NULL, $stripped=TRUE) {
		$this->regex->isPositiveAmount($rid, "rehearsalId");
		$params = array(array("i", $rid), array("i", $rid));
		$instrument = "";
		if($instrumentId != NULL) {
			$this->regex->isPositiveAmount($instrumentId, "instrumentId");
			$instrument = "AND c.instrument = ?";
			array_push($params, array("i", $instrumentId));
		}
		$query = "SELECT i.name as instrument, c.id as contact_id, CONCAT(c.name, ' ', c.surname) as contactname, u.id as user_id, IFNULL(ru.participate, -1) as participate
				FROM rehearsal_contact rc
					JOIN contact c ON rc.contact = c.id
					JOIN user u ON u.contact = c.id
					JOIN instrument i ON c.instrument = i.id
					LEFT OUTER JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = ?
				WHERE rc.rehearsal = ? $instrument
				ORDER BY instrument, contactname";
		
		$participants = $this->database->getSelection($query, $params);
		if($stripped) {
			$participants = array_splice($participants, 1);
		}
		return $participants;
	}
	
	function getRehearsalBegin($rid) {
		$d = $this->database->colValue("SELECT begin FROM rehearsal WHERE id = ?", "begin", array(array("i", $rid)));
		return Data::convertDateFromDb($d);
	}
	
	function getSongsForRehearsal($rid) {
		$query = "SELECT s.id, s.title, rs.notes ";
		$query .= "FROM rehearsal_song rs, song s ";
		$query .= "WHERE rs.song = s.id AND rs.rehearsal = ?";
		$encodedSelection = $this->database->getSelection($query, array(array("i", $rid)));
		return $this->urldecodeSelection($encodedSelection, array("title", "notes"));
	}
	
	function saveSongForRehearsal($sid, $rid, $notes) {
		$this->regex->isText($notes);
		$query = "INSERT INTO rehearsal_song (song, rehearsal, notes) VALUES (?, ?, ?)";
		$this->database->execute($query, array(array("i", $sid), array("i", $rid), array("s", $notes)));
	}
	
	function updateSongForRehearsal($sid, $rid, $notes) {
		$this->regex->isText($notes);
		$query = "UPDATE rehearsal_song SET ";
		$query .= " notes = ? WHERE rehearsal = ? AND song = ?";
		$this->database->execute($query, array(array("s", $notes), array("i", $rid), array("i", $sid)));
	}
	
	function removeSongForRehearsal($sid, $rid) {
		$query = "DELETE FROM rehearsal_song WHERE rehearsal = ? AND song = ?";
		$this->database->execute($query, array(array("i", $rid), array("i", $sid)));
	}
	
	function locationsPresent() {
		$ct = $this->database->colValue("SELECT count(*) as cnt FROM location WHERE id > 0", "cnt", array());
		return ($ct > 0);
	}
	
	function saveSerie() {		
		// validate data
		$this->regex->isName($_POST["name"]);
		if($_POST["notes"] != "") $this->regex->isText($_POST["notes"]);
		$this->regex->isPositiveAmount($_POST["duration"]);
		$this->regex->isTime($_POST["default_time"]);
		$this->regex->isPositiveAmount($_POST[Lang::txt("ProbenData_saveSerie.location")]);
		
		// make sure last date is after first date
		$dateFirstSession = strtotime($_POST["first_session"]);
		$dateLastSession = strtotime($_POST["last_session"]);
		if($dateLastSession - $dateFirstSession < 0) {
			new BNoteError(Lang::txt("ProbenData_saveSerie.error"));
		}
		
		// create serie
		$query = "INSERT INTO rehearsalserie (name) VALUES (?)";
		$serieId = $this->database->prepStatement($query, array(array("s", $_POST["name"])));
		if($serieId == NULL || $serieId == "" || $serieId <= 0) {
			new BNoteError(Lang::txt("ProbenData_saveSerie.dberror"));
		}
		
		// process accoding to cycle
		if($_POST["cycle"] > 0) {
			$rehearsalDates = $this->getRehearsalDates($_POST["first_session"], $_POST["last_session"], intval($_POST["cycle"]));			
			foreach($rehearsalDates as $rehDate) {
				$beginDate = $rehDate . " " . $_POST["default_time"];
				$this->create(array(
					"begin" => $beginDate,
					"end" => Data::addMinutesToDate($beginDate, $_POST["duration"]),
					"approve_until" => $beginDate,
					"conductor" => $_POST["conductor"],
					"notes" => $_POST["notes"],
					"location" => $_POST[Lang::txt("ProbenData_saveSerie.location")],
					"serie" => $serieId
				));
			}
		}		
		else {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Finds all dates inbetween the first and the last date that fit to the cycle.
	 * @param String $firstDate First date of rehearsal.
	 * @param String $lastDate Last date of rehearsal.
	 * @param Integer $cycle Cycle length in weeks.
	 * @return Array All dates when a rehearsal should be created.
	 */
	private function getRehearsalDates($firstDate, $lastDate, $cycle) {
		$dates = array($firstDate);
		$finalDate = strtotime($lastDate);
		
		$currDate = $firstDate;
		$endReached = false;
		$weekCount = 0;
		while(!$endReached && $weekCount < 100) { // infinity prevention
			// always multiples of a week
			$newDate = Data::addDaysToDate($currDate, 7 * $cycle);
			if(strtotime($newDate) - $finalDate > 0) {
				$endReached = true;
			}
			else {
				array_push($dates, $newDate);
			}
			$weekCount++;
			$currDate = $newDate;
		}
		return $dates;
	}
	
	public function getDefaultTime() {
		return $this->getSysdata()->getDynamicConfigParameter("rehearsal_start");
	}
	
	public function getDefaultDuration() {
		return $this->getSysdata()->getDynamicConfigParameter("rehearsal_duration");
	}
	
	public function create($values) {
		// convert data to process format
		if(!isset($values["end"])) {
			$values["end"] = Data::addMinutesToDate($values["begin"], $values["duration"]);
		}
		
		if($values["approve_until"] == "") {
			$values["approve_until"] = $values["begin"];
		}
		
		$values["begin"] = Data::dateTimeTstd($values["begin"]);
		$values["end"] = Data::dateTimeTstd($values["end"]);
		$values["approve_until"] = Data::dateTimeTstd($values["approve_until"]);
		
		// validate
		$this->validate($values);
		
		// save
		$rid = parent::create($values);
		
		// additionally add the groups' contacts to rehearsal_contact
		$groups = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		$contacts = array();
		foreach($groups as $groupId) {
			$cts = $this->adp()->getGroupContacts($groupId);
			for($j = 1; $j < count($cts); $j++) {
				$contact = $cts[$j]["id"];
				if(!in_array($contact, $contacts)) array_push($contacts, $contact);
			}
		}
		
		if(count($contacts) > 0) {
			$s = $this->tupleStmt($rid, $contacts);
			$query = "INSERT INTO rehearsal_contact VALUES " . $s[0];
			$this->database->execute($query, $s[1]);
		}
		$this->updateGroups($rid, $groups);
		
		// custom data
		$this->createCustomFieldData('r', $rid, $values);
		
		// create notification
		if($this->triggerServiceEnabled) {
			$begin_dt = $values["begin"];
			$this->createTrigger(str_replace("T", " ", $begin_dt), $this->buildTriggerData("R", $rid));
		}
		
		return $rid;
	}
	
	public function delete($id) {
		$query = "DELETE FROM rehearsal_contact WHERE rehearsal = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$query = "DELETE FROM rehearsal_song WHERE rehearsal = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$query = "DELETE FROM rehearsal_contact WHERE rehearsal = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$query = "DELETE FROM rehearsal_user WHERE rehearsal = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$query = "DELETE FROM rehearsal_group WHERE rehearsal = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		$this->deleteCustomFieldData('r', $id);
		
		parent::delete($id);
	}
	
	public function getRehearsalContacts($rid) {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname) as name, c.nickname, i.name as instrument, i.id as instrumentid, c.mobile, c.email
					FROM contact c 
						JOIN rehearsal_contact rc ON rc.contact = c.id
						LEFT OUTER JOIN instrument i ON c.instrument = i.id
					WHERE rc.rehearsal = ? 
					ORDER BY i.rank, name";
		return $this->database->getSelection($query, array(array("i", $rid)));
	}
	
	public function getRehearsalsPhases($rid) {
		$query = "SELECT p.* ";
		$query .= "FROM rehearsalphase p JOIN rehearsalphase_rehearsal pr ON pr.rehearsalphase = p.id ";
		$query .= "WHERE pr.rehearsal = ?";
		return $this->database->getSelection($query, array(array("i", $rid)));
	}
	
	public function deleteRehearsalContact($rid, $cid) {
		$query = "DELETE FROM rehearsal_contact WHERE rehearsal = ? AND contact = ?";
		$this->database->execute($query, array(array("i", $rid), array("i", $cid)));
	}
	
	public function addRehearsalContact($rid) {
		$contacts = GroupSelector::getPostSelection($this->getContacts(), "contact");
		$tuples = array();
		$params = array();
		foreach($contacts as $cid) {
			if(!$this->isContactInRehearsal($rid, $cid)) {
				array_push($tuples, "(? ,?)");
				array_push($params, array("i", $rid));
				array_push($params, array("i", $cid));
			}
		}
		if(count($tuples) > 0) {
			$query = "INSERT INTO rehearsal_contact VALUES " . join(",", $tuples);
			$this->database->execute($query, $params);
		}
	}
	
	private function isContactInRehearsal($rid, $cid) {
		$query = "SELECT count(contact) as cnt FROM rehearsal_contact WHERE rehearsal = ? AND contact = ?";
		$ct = $this->database->colValue($query, "cnt", array(array("i", $rid), array("i", $cid)));
		return ($ct > 0);
	}
	
	public function getContacts() {
		$allContacts = $this->adp()->getContacts();
		
		// filter super-contacts
		$result = array();
		array_push($result, $allContacts[0]);
		
		for($i = 1; $i < count($allContacts); $i++) {
			if($this->getSysdata()->isContactSuperUser($allContacts[$i]["id"])) continue;
			
			// adapt naming for output
			$allContacts[$i]["fullname"] = $allContacts[$i]["name"] . " " . $allContacts[$i]["surname"];
			
			array_push($result, $allContacts[$i]);
		}
		
		return $result;
	}
	
	public function getRehearsalYears() {
		$query = "SELECT DISTINCT year(begin) as year FROM rehearsal ORDER BY year DESC";
		return $this->database->getSelection($query);
	}
	
	public function getPastRehearsals($year) {
		$query = "SELECT rehearsal.id, begin, end, location.name as Location, address.street, address.zip, address.city ";
		$query .= "FROM rehearsal JOIN location ON rehearsal.location = location.id ";
		$query .= "LEFT JOIN address ON location.address = address.id ";
		$query .= "WHERE end < now() and YEAR(end) = ? ORDER BY begin DESC";
		return $this->database->getSelection($query, array(array("i", $year)));
	}
	
	public function getPastRehearsalsWithLimit($limit = 5) {
		$query = "SELECT rehearsal.id, begin, end, location.name as Location, address.street, address.zip, address.city 
					FROM rehearsal JOIN location ON rehearsal.location = location.id
					LEFT JOIN address ON location.address = address.id
					WHERE end < now() ORDER BY begin DESC LIMIT ?";
		return $this->database->getSelection($query, array(array("i", $limit)));
	}
	
	public function getUsedInstruments() {
		return $this->adp()->getUsedInstruments();
	}
	
	public function getCurrentSeries() {
		$query = "SELECT DISTINCT s.* FROM rehearsalserie s JOIN rehearsal r ON r.serie = s.id " 
				. "WHERE r.end >= NOW() ORDER BY s.id";
		return $this->database->getSelection($query);
	}
	
	public function updateSerie() {
		// get the serie
		$serieId = $_POST["id"];
		$this->regex->isPositiveAmount($serieId);
		
		// check if the series should be deleted completely
		if(isset($_POST["delete"]) && $_POST["delete"] == "on") {
			$q1 = "DELETE FROM rehearsal WHERE serie = ?";
			$this->database->execute($q1, array(array("i", $serieId)));
			$q2 = "DELETE FROM rehearsalserie WHERE id = ?";
			$this->database->execute($q2, array(array("i", $serieId)));
		}
		else if(isset($_POST["update_begin"]) || isset($_POST["update_location"])) {
			if(isset($_POST["update_begin"])) {
				$begin = $_POST["begin_hour"] . ":" . $_POST["begin_minute"];
				$this->regex->isTime($begin);
				$q3 = "UPDATE rehearsal SET begin = CONCAT(date(begin), ' ', ?, ':00') WHERE serie = ?";
				$this->database->execute($q3, array(array("s", $begin), array("i", $serieId)));
			}
			if(isset($_POST["update_location"])) {
				$locationId = $_POST["location"];
				$this->regex->isPositiveAmount($locationId);
				$q4 = "UPDATE rehearsal SET location = ? WHERE serie = ?";
				$this->database->execute($q4, array(array("i", $locationId), array("i", $serieId)));
			}
		}
	}
	
	public function getRehearsalSerie($rehearsalId) {
		$query = "SELECT s.* FROM rehearsal r JOIN rehearsalserie s ON r.serie = s.id WHERE r.id = ?";
		return $this->database->fetchRow($query, array(array("i", $rehearsalId)));
	}
	
	public function update($id, $values) {
		if(isset($values["serie"]) && $values["serie"] == "") {
			unset($values["serie"]);
		}
		parent::update($id, $values);
		
		// process custom data
		$this->updateCustomFieldData('r', $id, $values);
	}
	
	/**
	 * Overwrite groups
	 * @param int $id Rehearsal ID.
	 * @param array $groups Group IDs to set.
	 */
	private function updateGroups($id, $groups) {
		$delQuery = "DELETE FROM rehearsal_group WHERE rehearsal = ?";
		$this->database->execute($delQuery, array(array("i", $id)));
	
		$s = $this->tupleStmt($id, $groups);
		$insQuery = "INSERT INTO rehearsal_group (rehearsal, `group`) VALUES " . $s[0];
		$this->database->execute($insQuery, $s[1]);
	}
	
	public function validate($input) {
		// custom validation
		$this->regex->isDateTime($input["begin"], "begin");
		$this->regex->isDateTime($input["end"], "end");
		$this->regex->isDateTime($input["approve_until"], "approve_until");
		$this->regex->isDatabaseId($input["location"], "location");
		if(isset($input["serie"]) && $input["serie"] != "") {
			$this->regex->isDatabaseId($input["serie"]);
		}
		$this->regex->isText($input["notes"]);
	}
	
	public function updateParticipations() {
		// cache contacts' users
		$contact_user = array();
		
		// run through participations and update one by one
		foreach($_POST as $item => $participation) {
			$param_parts = explode("_", $item);  # "part", "rX", "cX"
			$rehearsal_id = substr($param_parts[1], 1);
			$contact_id = substr($param_parts[2], 1);
			if(!is_numeric($rehearsal_id) || !is_numeric($contact_id) || $rehearsal_id == "" || $contact_id == "") continue;
			$this->regex->isPositiveAmount($rehearsal_id);
			$this->regex->isPositiveAmount($contact_id);
			
			// convert contact to user -> cache in map
			if(in_array($contact_id, $contact_user)) {
				$user = $contact_user[$contact_id];
			}
			else {
				$user = $this->adp()->getUserForContact($contact_id);
				$contact_user[$contact_id] = $user;
			}
			
			// eventually delete participation first
			$do_update = false;
			if($user != null) {
				$user_id = $user["id"];
				$partQuery = "SELECT participate FROM rehearsal_user WHERE rehearsal = ? AND user = ?";
				$part = $this->database->colValue($partQuery, "participate", array(array("i", $rehearsal_id), array("i", $user_id)));
				if($part != $participation) {
					$del_query = "DELETE FROM rehearsal_user WHERE rehearsal = ? AND user = ?";
					$this->database->execute($del_query, array(array("i", $rehearsal_id), array("i", $user_id)));
					$do_update = true;
				}
			}
			else {
				new BNoteError(Lang::txt("ProbenView_overviewEdit.error"));
			}
			
			// insert new participation
			if($do_update) {
				$update_query = "INSERT INTO rehearsal_user (rehearsal, user, participate, replyon) VALUES (?,?,?, NOW())";
				$this->database->prepStatement($update_query, array(
						array("i", $rehearsal_id),
						array("i", $user_id),
						array("i", $participation)
				));
			}
		}
	}
	
	function getStatusOptions() {
		return array("planned", "confirmed", "cancelled", "hidden");
	}
}