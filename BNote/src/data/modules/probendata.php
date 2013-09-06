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
}

?>