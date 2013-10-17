<?php
/**
 * Data Access Class for rehearsal data.
 * @author matti
 *
 */
class ProbenData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct() {
		$this->fields = array(
			"id" => array("Probennummer", FieldType::INTEGER),
			"begin" => array("Beginn", FieldType::DATETIME),
			"end" => array("Ende", FieldType::DATETIME),
			"location" => array("Ort", FieldType::REFERENCE),
			"notes" => array("Notizen", FieldType::TEXT)
		);
		
		$this->references = array(
			"location" => "location", 
		);
		
		$this->table = "rehearsal";
		
		$this->init();
	}
	
	private function defaultQuery() {
		$query = "SELECT r.id as id, begin, end, r.notes as notes, name, street, city, zip";
		$query .= " FROM " . $this->table . " r, location l, address a";
		$query .= " WHERE r.location = l.id AND l.address = a.id";
		return $query;
	}
	
	function getNextRehearsal() {
		$query = $this->defaultQuery() . " AND begin > NOW()";
		$query .= " ORDER BY begin ASC LIMIT 0,1";
		return $this->database->getRow($query);
	}
	
	function getAllRehearsals() {
		return $this->adp()->getAllRehearsals();
	}
	
	function findByIdJoined($id, $colExchange) {
		$query = $this->defaultQuery() . " AND r.id = $id";
		return $this->database->getRow($query);
	}
	
	function getParticipants($rid) {
		$query = 'SELECT CONCAT_WS(" ", c.name, c.surname) as name, ';
		$query .= ' CASE ru.participate WHEN 1 THEN "ja" WHEN 2 THEN "vielleicht" ELSE "nein" END as participate, ru.reason';
		$query .= ' FROM rehearsal_user ru, user u, contact c';
		$query .= ' WHERE ru.rehearsal = ' . $rid . ' AND ru.user = u.id AND u.contact = c.id';
		$query .= ' ORDER BY participate, name';
		return $this->database->getSelection($query);
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
	
	function getRehearsalBegin($rid) {
		$d = $this->database->getCell($this->getTable(), "begin", "id = $rid");
		return Data::convertDateFromDb($d);
	}
	
	function getSongsForRehearsal($rid) {
		$query = "SELECT s.id, s.title, rs.notes ";
		$query .= "FROM rehearsal_song rs, song s ";
		$query .= "WHERE rs.song = s.id AND rs.rehearsal = $rid";
		return $this->database->getSelection($query);
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
	
	function getCommunicationModuleId() {
		global $system_data;
		$mods = $system_data->getModuleArray();
		foreach($mods as $id => $name) {
			if($name == "Kommunikation") return $id;
		}
		return 0;
	}
	
	function locationsPresent() {
		$ct = $this->database->getCell("location", "count(*)", "id > 0");
		return ($ct > 0);
	}
	
	function saveSerie() {		
		// validate data
		if($_POST["notes"] != "") $this->regex->isText($_POST["notes"]);
		$this->regex->isPositiveAmount($_POST["duration"]);
		$this->regex->isPositiveAmount($_POST["default_time_hour"]);
		$this->regex->isPositiveAmount($_POST["Ort"]);
		
		// make sure last date is after first date
		$dateFirstSession = strtotime(Data::convertDateToDb($_POST["first_session"]));
		$dateLastSession = strtotime(Data::convertDateToDb($_POST["last_session"]));
		if($dateLastSession - $dateFirstSession < 0) {
			new Error("Die letzte Probe ist zeitlich vor der ersten Probe.");
		}
		
		// process accoding to cycle
		if($_POST["cycle"] > 0) {
			$rehearsalDates = $this->getRehearsalDates($_POST["first_session"], $_POST["last_session"], intval($_POST["cycle"]));
			foreach($rehearsalDates as $rehDate) {
				$beginDate = $rehDate . " " . $_POST["default_time_hour"] . ":" . $_POST["default_time_minute"];
				$endDate = Data::addMinutesToDate($beginDate, $_POST["duration"]);
				
				$values = array(
					"begin" => $beginDate,
					"end" => $endDate,
					"notes" => $_POST["notes"],
					"location" => $_POST["Ort"]
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
		
		return $rid;
	}
	
	public function getRehearsalContacts($rid) {
		$query = "SELECT c.id, CONCAT(c.name, ' ', c.surname) as name, i.name as instrument, c.mobile, c.email ";
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
}

?>