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
			"notes" => array(Lang::txt("ProbenData_construct.notes"), FieldType::TEXT)
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
		$query = "SELECT r.id as id, begin, end, approve_until, conductor, r.notes, r.serie, r.location, name, street, city, zip";
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
		return $this->database->getRow($query);
	}
	
	function findByIdJoined($id, $colExchange) {
		$query = $this->defaultQuery() . " AND r.id = $id";
		return $this->database->getRow($query);
	}
	
	function getRehearsalGroups($id) {
		$query = "SELECT g.* FROM `rehearsal_group` ag JOIN `group` g ON ag.`group` = g.id WHERE ag.rehearsal = $id";
		return $this->database->getSelection($query);
	}
	
	function getParticipants($rid) {
		$query = 'SELECT c.id, CONCAT_WS(" ", c.name, c.surname) as name, c.nickname, ';
		$query .= ' CASE ru.participate WHEN 1 THEN "ja" WHEN 2 THEN "vielleicht" ELSE "nein" END as participate, ru.reason';
		$query .= ' FROM rehearsal_user ru, user u, contact c';
		$query .= ' WHERE ru.rehearsal = ' . $rid . ' AND ru.user = u.id AND u.contact = c.id';
		$query .= ' ORDER BY name';
		return $this->database->getSelection($query);
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
		// ...paricipate
		$stats["Zusagen"] = $this->database->getCell("rehearsal_user", "count(*)", "rehearsal = $rid AND participate = 1");
		// ...do not paricipate
		$stats["Absagen"] = $this->database->getCell("rehearsal_user", "count(*)", "rehearsal = $rid AND participate = 0");
		// ...maybe participate
		$stats["Eventuell"] = $this->database->getCell("rehearsal_user", "count(*)", "rehearsal = $rid AND participate = 2");
		// total number of...
		// ...decisions
		$stats["Summe"] = $this->database->getCell("rehearsal_user", "count(*)", "rehearsal = $rid");
		
		return $stats;
	}
	
	function getAttendingInstruments($rid) {
		$query = "SELECT i.name, GROUP_CONCAT(CONCAT(c.name, ' ', c.surname) SEPARATOR ', ') as player, count(c.name)
					FROM rehearsal_user ru
					     JOIN user u ON ru.user = u.id
					     JOIN contact c ON u.contact = c.id
					     JOIN instrument i ON c.instrument = i.id
					WHERE ru.participate = 1 AND ru.rehearsal = $rid
					GROUP BY i.name
					ORDER BY i.name";
		$res = $this->database->getSelection($query);
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
	 * @param Integer $instrumentId Instrument ID.
	 */
	function getParticipantOverview($rid, $instrumentId) {
		$query = "SELECT c.id as contact_id, CONCAT(c.name, ' ', c.surname) as contactname, u.id as user_id, ru.participate
				FROM rehearsal_contact rc
					JOIN contact c ON rc.contact = c.id
					JOIN user u ON u.contact = c.id
					LEFT OUTER JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = $rid
				WHERE c.instrument = $instrumentId AND rc.rehearsal = $rid";
		
		$participants = $this->database->getSelection($query);
		$participants = array_splice($participants, 1);
		return $participants;
	}
	
	function getRehearsalBegin($rid) {
		$d = $this->database->getCell($this->getTable(), "begin", "id = $rid");
		return Data::convertDateFromDb($d);
	}
	
	function getSongsForRehearsal($rid) {
		$query = "SELECT s.id, s.title, rs.notes ";
		$query .= "FROM rehearsal_song rs, song s ";
		$query .= "WHERE rs.song = s.id AND rs.rehearsal = $rid";
		$encodedSelection = $this->database->getSelection($query);
		return $this->urldecodeSelection($encodedSelection, array("title", "notes"));
	}
	
	function saveSongForRehearsal($sid, $rid, $notes) {
		$this->regex->isText($notes);
		$query = "INSERT INTO rehearsal_song (song, rehearsal, notes) VALUES ";
		$query .= "($sid, $rid, \"$notes\")";
		$this->database->execute($query);
	}
	
	function updateSongForRehearsal($sid, $rid, $notes) {
		$this->regex->isText($notes);
		$query = "UPDATE rehearsal_song SET ";
		$query .= " notes = \"$notes\" WHERE rehearsal = $rid AND song = $sid";
		$this->database->execute($query);
	}
	
	function removeSongForRehearsal($sid, $rid) {
		$query = "DELETE FROM rehearsal_song WHERE rehearsal = $rid AND song = $sid";
		$this->database->execute($query);
	}
	
	function locationsPresent() {
		$ct = $this->database->getCell("location", "count(*)", "id > 0");
		return ($ct > 0);
	}
	
	function saveSerie() {		
		// validate data
		$this->regex->isName($_POST["name"]);
		if($_POST["notes"] != "") $this->regex->isText($_POST["notes"]);
		$this->regex->isPositiveAmount($_POST["duration"]);
		$this->regex->isPositiveAmount($_POST["default_time_hour"]);
		$this->regex->isPositiveAmount($_POST["Ort"]);
		
		// make sure last date is after first date
		$dateFirstSession = strtotime(Data::convertDateToDb($_POST["first_session"]));
		$dateLastSession = strtotime(Data::convertDateToDb($_POST["last_session"]));
		if($dateLastSession - $dateFirstSession < 0) {
			new BNoteError(Lang::txt("ProbenData_saveSerie.error"));
		}
		
		// create serie
		$query = "INSERT INTO rehearsalserie (name) VALUES ('" . $_POST["name"] . "')";
		$serieId = $this->database->execute($query);
		
		// process accoding to cycle
		if($_POST["cycle"] > 0) {
			$rehearsalDates = $this->getRehearsalDates($_POST["first_session"], $_POST["last_session"], intval($_POST["cycle"]));			
			foreach($rehearsalDates as $rehDate) {
				$beginDate = $rehDate . " " . $_POST["default_time_hour"] . ":" . $_POST["default_time_minute"];
				$endDate = Data::addMinutesToDate($beginDate, $_POST["duration"]);
				
				$values = array(
					"begin" => $beginDate,
					"end" => $endDate,
					"approve_until" => $beginDate,
					"conductor" => $_POST["conductor"],
					"notes" => $_POST["notes"],
					"location" => $_POST["Ort"],
					"serie" => $serieId
				);
				$this->create($values);
			}
		}		
		else {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Finds all dates inbetween the first and the last date that fit to the cycle.
	 * @param Date $firstDate First date of rehearsal.
	 * @param Date $lastDate Last date of rehearsal.
	 * @param Integer $cycle Cycle length in weeks.
	 * @return Array All dates when a rehearsal should be created.
	 */
	private function getRehearsalDates($firstDate, $lastDate, $cycle) {
		$dates = array($firstDate);
		$finalDate = strtotime(Data::convertDateToDb($lastDate));
		
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
		// convert data from view to process format
		if(strpos($values["begin"], ":") === false) {
			$hour = $values["begin_hour"];
			if($hour < 10) $hour = "0" . $hour;
			$values["begin"] = $values["begin"] . " " . $hour . ":" . $values["begin_minute"];
		}
		
		if(!isset($values["end"])) {
			$values["end"] = Data::addMinutesToDate($values["begin"], $values["duration"]);
		}
		else if(strpos($values["end"], ":") === false) {
			$endhour = $values["end_hour"];
			if($endhour < 10) $endhour = "0" . $endhour;
			$values["end"] = $values["end"] . " " . $endhour . ":" . $values["end_minute"];
		}
		
		if($values["approve_until"] == "") {
			$values["approve_until"] = $values["begin"];
		}
		
		// validate
		$this->validate($values);
		
		// save
		$rid = parent::create($values);
		
		// additionally add the groups' contacts to rehearsal_contact
		$groups = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		$contacts = array();
		foreach($groups as $i => $groupId) {
			$cts = $this->adp()->getGroupContacts($groupId);
			for($j = 1; $j < count($cts); $j++) {
				$contact = $cts[$j]["id"];
				if(!in_array($contact, $contacts)) array_push($contacts, $contact);
			}
		}
		$query = "INSERT INTO rehearsal_contact VALUES ";
		
		foreach($contacts as $i => $contact) {
			if($i > 0) $query .= ", ";
			$query .= "($rid, $contact)";
		}
		if(count($contacts) > 0) {
			$this->database->execute($query);
		}
		$this->updateGroups($rid, $groups);
		
		// custom data
		$this->createCustomFieldData('r', $rid, $values);
		
		// create notification
		if($this->triggerServiceEnabled) {
			$begin_dt = Data::convertDateToDb($values["begin"]);
			$this->createTrigger($begin_dt, $this->buildTriggerData("R", $rid));
		}
		
		return $rid;
	}
	
	public function delete($id) {
		$query = "DELETE FROM rehearsal_contact WHERE rehearsal = $id";
		$this->database->execute($query);
		
		$query = "DELETE FROM rehearsal_song WHERE rehearsal = $id";
		$this->database->execute($query);
		
		$query = "DELETE FROM rehearsal_contact WHERE rehearsal = $id";
		$this->database->execute($query);
		
		$query = "DELETE FROM rehearsal_user WHERE rehearsal = $id";
		$this->database->execute($query);
		
		$query = "DELETE FROM rehearsal_group WHERE rehearsal = $id";
		$this->database->execute($query);
		
		$this->deleteCustomFieldData('r', $id);
		
		parent::delete($id);
	}
	
	public function getRehearsalContacts($rid) {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname) as name, c.nickname, i.name as instrument, i.id as instrumentid, c.mobile, c.email ";
		$query .= "FROM contact c JOIN rehearsal_contact rc ON rc.contact = c.id ";
		$query .= " LEFT JOIN instrument i ON c.instrument = i.id ";
		$query .= "WHERE rc.rehearsal = $rid ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	public function getRehearsalsPhases($rid) {
		$query = "SELECT p.* ";
		$query .= "FROM rehearsalphase p JOIN rehearsalphase_rehearsal pr ON pr.rehearsalphase = p.id ";
		$query .= "WHERE pr.rehearsal = $rid";
		return $this->database->getSelection($query);
	}
	
	public function deleteRehearsalContact($rid, $cid) {
		$query = "DELETE FROM rehearsal_contact WHERE rehearsal = $rid AND contact = $cid";
		$this->database->execute($query);
	}
	
	public function addRehearsalContact($rid) {
		$contacts = GroupSelector::getPostSelection($this->getContacts(), "contact");
		$query = "INSERT INTO rehearsal_contact VALUES ";
		$count = 0;
		foreach($contacts as $i => $cid) {
			if(!$this->isContactInRehearsal($rid, $cid)) {
				if($count++ > 0) $query .= ", ";
				$query .= "($rid, $cid)";
			}
		}
		if($count > 0) $this->database->execute($query);
	}
	
	private function isContactInRehearsal($rid, $cid) {
		$ct = $this->database->getCell("rehearsal_contact", "count(contact)", "rehearsal = $rid AND contact = $cid");
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
		$query .= "WHERE end < now() and YEAR(end) = $year ORDER BY begin DESC";
		return $this->database->getSelection($query);
	}
	
	public function getUsedInstruments() {
		$query = "SELECT DISTINCT i.* FROM instrument i JOIN contact c ON c.instrument = i.id";
		return $this->database->getSelection($query);
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
			$q1 = "DELETE FROM " . $this->table . " WHERE serie = $serieId";
			$this->database->execute($q1);
			$q2 = "DELETE FROM rehearsalserie WHERE id = $serieId";
			$this->database->execute($q2);
		}
		else if(isset($_POST["update_begin"]) || isset($_POST["update_location"])) {
			if(isset($_POST["update_begin"])) {
				$begin = $_POST["begin_hour"] . ":" . $_POST["begin_minute"];
				$this->regex->isTime($begin);
				$q3 = "UPDATE rehearsal SET begin = CONCAT(date(begin), ' $begin:00') WHERE serie = $serieId";
				$this->database->execute($q3);
			}
			if(isset($_POST["update_location"])) {
				$locationId = $_POST["location"];
				$this->regex->isPositiveAmount($locationId);
				$q4 = "UPDATE rehearsal SET location = $locationId WHERE serie = $serieId";
				$this->database->execute($q4);
			}
		}
	}
	
	public function getRehearsalSerie($rehearsalId) {
		$this->regex->isPositiveAmount($rehearsalId);
		$query = "SELECT s.* FROM rehearsal r JOIN rehearsalserie s ON r.serie = s.id WHERE r.id = $rehearsalId";
		return $this->database->getRow($query);
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
		$delQuery = "DELETE FROM rehearsal_group WHERE rehearsal = $id";
		$this->database->execute($delQuery);
	
		$insQuery = "INSERT INTO rehearsal_group (rehearsal, `group`) VALUES ($id,";
		$insQuery .= join("), ($id,", $groups) . ")";
		$this->database->execute($insQuery);
	}
	
	public function validate($input) {
		// custom validation
		$this->regex->isDateTime($input["begin"]);
		$this->regex->isDateTime($input["end"]);
		$this->regex->isDateTime($input["approve_until"]);
		$this->regex->isDatabaseId($input["location"]);
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
			$sep_pos = strrpos($item, "_c");
			$rehearsal_id = substr($item, 6, strlen($item) - $sep_pos-1);
			$this->regex->isPositiveAmount($rehearsal_id);
			$contact_id = substr($item, $sep_pos+2);
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
				$part = $this->database->getCell("rehearsal_user", "participate", "rehearsal = $rehearsal_id AND user = $user_id");
				if($part != $participation) {
					$del_query = "DELETE FROM rehearsal_user WHERE rehearsal = $rehearsal_id AND user = $user_id";
					$this->database->execute($del_query);
					$do_update = true;
				}
			}
			else {
				new BNoteError(Lang::txt("ProbenView_overviewEdit.error"));
			}
			
			// insert new participation
			if($do_update) {
				$update_query = "INSERT INTO rehearsal_user (rehearsal, user, participate) VALUES (?,?,?)";
				$this->database->prepStatement($update_query, array(
						array("i", $rehearsal_id),
						array("i", $user_id),
						array("i", $participation)
				));
			}
		}
	}
}

?>