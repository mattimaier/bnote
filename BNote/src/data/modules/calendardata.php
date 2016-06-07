<?php
require_once($GLOBALS["DIR_DATA_MODULES"] . "startdata.php");
require_once($GLOBALS["DIR_DATA_MODULES"] . "mitspielerdata.php");

class CalendarData extends AbstractData {

	private $startdata;
	private $mitspielerdata;
	
	public static $colExchange = array(
		"contact" => array("name", "surname"),
		"location" => array("name")
	);
	
	function __construct() {
		$this->fields = array(
			"id" => array(Lang::txt("id"), FieldType::INTEGER),
			"begin" => array(Lang::txt("begin"), FieldType::DATETIME),
			"end" => array(Lang::txt("end"), FieldType::DATETIME),
			"name" => array(Lang::txt("name"), FieldType::CHAR),
			"location" => array(Lang::txt("location"), FieldType::REFERENCE),
			"contact" => array(Lang::txt("contact"), FieldType::REFERENCE),
			"notes" => array(Lang::txt("notes"), FieldType::TEXT)
		);
		
		$this->references = array(
			"location" => "location",
			"contact" => "contact"
		);
		
		$this->table = "reservation";
		
		$this->startdata = new StartData();
		$this->mitspielerdata = new MitspielerData();
		$this->init();
	}
	
	public function getJoinedAttributes() {
		return $this->colExchange;
	}
	
	private function reduce_data($entityType, $dbsel, $fields, $key_replace=array(), $title_prefix="", $link="#") {
		$result = array();
		for($i = 1; $i < count($dbsel); $i++) {
			$row = $dbsel[$i];
			$res_row = array();
			foreach($fields as $field) {
				if(isset($key_replace[$field])) {
					$res_row[$key_replace[$field]] = $row[$field];
				}
				else {
					$res_row[$field] = $row[$field];
				}
				
				// special replacements for dates
				if(($field == "start" || $field == "end") && isset($res_row[$field])) {
					$res_row[$field] = str_replace(" ", "T", $res_row[$field]);
				}
			}
			
			if(isset($res_row["title"])) {
				$res_row["title"] = $title_prefix . " " . $res_row["title"]; 
			}
			else {
				$res_row["title"] = $title_prefix;
			}
			$res_row["bnoteType"] = $entityType;
			$res_row["link"] = $link . $res_row["id"];
			array_push($result, $res_row);
		}
		
		return $result;
	}
	
	function getEvents() {
		$rehs_db = $this->startdata->getUsersRehearsals();
		$phases_db = $this->adp()->getUsersPhases();
		$concerts_db = $this->adp()->getFutureConcerts();
		$votes_db = $this->startdata->getVotesForUser();
		$contacts_db = $this->mitspielerdata->getMembers();
		$reservations_db = $this->findAllNoRef();
		
		// birthday: replace year with the current year
		$contacts_db_edit = array();
		for($i = 0; $i < count($contacts_db); $i++) {
			$row = $contacts_db[$i];
			if($i == 0) {
				array_push($contacts_db_edit, $row);
				continue;
			}
			if(!isset($row["birthday"])) continue;
			
			$bday = $row["birthday"]; 
			if($bday == null || $bday == "" || $bday == "-") {
				continue;
			}
			$bday = date("Y") . substr($bday, 4);
			$row["birthday"] = $bday;
			
			$row["title"] = $row["name"] . " " . $row["surname"];
			
			array_push($contacts_db_edit, $row);
		}
		
		$rehs = $this->reduce_data(
				"rehearsal",
				$rehs_db,
				array("id", "begin", "end", "approve_until", "notes"),
				array("begin" => "start"),
				Lang::txt("calendar_rehearsal"),
				"?mod=" . $this->getSysdata()->getModuleId("Proben") . "&mode=view&id="
		);
		$phases = $this->reduce_data(
				"phase",
				$phases_db,
				array("id", "name", "begin", "end", "notes"),
				array("begin" => "start", "name" => "title"),
				"?mod=" . $this->getSysdata()->getModuleId("Probenphasen") . "&mode=view&id="
		);
		$concerts = $this->reduce_data(
				"concert",
				$concerts_db,
				array("id", "begin", "end", "approve_until", "location_name","notes"),
				array("begin" => "start"),
				Lang::txt("calendar_concert"),
				"?mod=" . $this->getSysdata()->getModuleId("Konzerte") . "&mode=view&id="
		);
		$votes = $this->reduce_data(
				"vote",
				$votes_db,
				array("id", "name", "end"),
				array("end" => "start", "name" => "title"),
				Lang::txt("calendar_end_vote"),
				"?mod=" . $this->getSysdata()->getModuleId("Abstimmung") . "&mode=view&id="
		);
		$contacts = $this->reduce_data(
				"contact",
				$contacts_db_edit,
				array("id", "birthday", "title"),
				array("birthday" => "start"),
				Lang::txt("calendar_birthday"),
				"?mod=" . $this->getSysdata()->getModuleId("Kontakte") . "&mode=view&id="
		);
		$reservations = $this->reduce_data(
				"reservation",
				$reservations_db,
				array("id", "begin", "name"),
				array("begin" => "start", "name" => "title"),
				Lang::txt("calendar_reservation"),
				"?mod=" . $this->getSysdata()->getModuleId("Calendar") . "&mode=view&id="
		);
		
		return array(
			"rehearsals" => $rehs,
			"rehearsalphases" => $phases,
			"concerts" => $concerts,
			"votes" => $votes,
			"contacts" => $contacts,
			"reservations" => $reservations
		);
	}
	
}

?>