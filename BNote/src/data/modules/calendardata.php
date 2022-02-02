<?php

class CalendarData extends AbstractLocationData {

	private $startdata;
	private $mitspielerdata;
	
	/**
	 * Submodule dao.
	 * @var AppointmentData
	 */
	private $appointmentdata;
	
	public static $colExchange = array(
		"contact" => array("name", "surname"),
		"location" => array("name")
	);
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("CalendarData_construct.id"), FieldType::INTEGER),
			"begin" => array(Lang::txt("CalendarData_construct.begin"), FieldType::DATETIME),
			"end" => array(Lang::txt("CalendarData_construct.end"), FieldType::DATETIME),
			"name" => array(Lang::txt("CalendarData_construct.name"), FieldType::CHAR),
			"location" => array(Lang::txt("CalendarData_construct.location"), FieldType::REFERENCE),
			"contact" => array(Lang::txt("CalendarData_construct.contact"), FieldType::REFERENCE),
			"notes" => array(Lang::txt("CalendarData_construct.notes"), FieldType::TEXT)
		);
		
		$this->references = array(
			"location" => "location",
			"contact" => "contact"
		);
		
		$this->table = "reservation";
		require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "startdata.php");
		require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "mitspielerdata.php");
		
		$this->startdata = new StartData($dir_prefix);
		$this->mitspielerdata = new MitspielerData($dir_prefix);
		$this->init($dir_prefix);
	}
	
	public function getJoinedAttributes() {
		return $this->colExchange;
	}
	
	public function setAppointmentData($appointmentData) {
		$this->appointmentdata = $appointmentData;
	}
	
	private function reduce_data($entityType, $dbsel, $fields, $key_replace=array(), $title_prefix="", $link="#") {
		$result = array();
		
		// check if the user has access to the module associated with the entity type to provide an edit button for the event
		$modName = null;
		switch($entityType) {
			case "rehearsal": $modName = "Proben"; break;
			case "phase": $modName = "Probenphasen"; break;
			case "concert": $modName = "Konzerte"; break;
			case "vote": $modName = "Abstimmung"; break;
			case "contact": $modName = "Kontakte"; break;
			case "reservation": $modName = "Calendar"; break;
			case "appointment": $modName = "Calendar"; break;
		}
		$modAccess = false;
		if($modName != null) {
			$modAccess = $this->getSysdata()->userHasPermission($this->getSysdata()->getModuleId($modName));
		}
		
		// compile result
		for($i = 1; $i < count($dbsel); $i++) {
			$row = $dbsel[$i];
			$res_row = array();
			$res_row["details"] = array();
			
			foreach($fields as $field) {
				if(isset($key_replace[$field])) {
					$replaceKey = $key_replace[$field];
					$res_row[$replaceKey] = $row[$field];
				}
				else {
					$res_row[$field] = $row[$field];
				}
				
				// special replacements for dates
				if(($field == "begin" || $field == "end") && isset($res_row[$field])) {
					$res_row[$field] = str_replace(" ", "T", $res_row[$field]);
				}
				
				// details
				if($field == "id") continue;
				$detailValue = $row[$field];
				if($field == "begin" || $field == "end" || $field == "approve_until" || $field == "birthday") {
					$detailValue = Data::convertDateFromDb($detailValue);
				}
				if($detailValue == null) {
					$detailValue = "";
				}
				if(isset($key_replace[$field])) {
					$res_row["details"][Lang::txt($replaceKey)] = $detailValue;
				}
				else {
					$res_row["details"][Lang::txt("calendar_" . $field)] = $detailValue;
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
			$res_row["access"] = $modAccess;
			$res_row["groupId"] = $entityType;
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
		$appointments_db = $this->appointmentdata->findAllJoined(AppointmentData::$colExchange);
		
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
				Lang::txt("CalendarData_getEvents.rehearsal"),
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
				array("id", "title", "begin", "end", "approve_until", "location_name", "outfit", "notes"),
				array("begin" => "start"),
				Lang::txt("CalendarData_getEvents.concert"),
				"?mod=" . $this->getSysdata()->getModuleId("Konzerte") . "&mode=view&id="
		);
		$votes = $this->reduce_data(
				"vote",
				$votes_db,
				array("id", "name", "end"),
				array("end" => "start", "name" => "title"),
				Lang::txt("CalendarData_getEvents.end_vote"),
				"?mod=" . $this->getSysdata()->getModuleId("Abstimmung") . "&mode=view&id="
		);
		$contacts = $this->reduce_data(
				"contact",
				$contacts_db_edit,
				array("id", "birthday", "title"),
				array("birthday" => "start"),
				Lang::txt("CalendarData_getEvents.birthday"),
				"?mod=" . $this->getSysdata()->getModuleId("Kontakte") . "&mode=view&id="
		);
		$reservations = $this->reduce_data(
				"reservation",
				$reservations_db,
				array("id", "begin", "end", "name"),
				array("begin" => "start", "name" => "title"),
				Lang::txt("CalendarData_getEvents.reservation"),
				"?mod=" . $this->getSysdata()->getModuleId("Calendar") . "&mode=view&id="
		);
		$appointments = $this->reduce_data(
				"appointment",
				$appointments_db,
				array("id", "begin", "end", "name"),
				array("begin" => "start", "name" => "title"),
				Lang::txt("CalendarData_getEvents.appointment"),
				"?mod=" . $this->getSysdata()->getModuleId("Calendar") . "&mode=appointments&func=view&id=");
		return array_merge($rehs, $phases, $concerts, $votes, $contacts, $reservations, $appointments);
	}
	
	function getContact($id) {
		return $this->database->fetchRow("SELECT * FROM contact WHERE id = ?", array(array("i", $id)));
	}
	
	function getCustomData($id) {
		return $this->getCustomFieldData('v', $id);
	}
	
	function create($values) {
		$id = parent::create($values);
		$this->createCustomFieldData('v', $id, $values);
	}
	
	function update($id, $values) {
		parent::update($id, $values);
		$this->updateCustomFieldData('v', $id, $values);
	}
	
	function delete($id) {
		$this->deleteCustomFieldData('v', $id);
		parent::delete($id);
	}
}