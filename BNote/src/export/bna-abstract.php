<?php

/**********************************************************
 * Abstract Implementation of BNote Application Interface *
 **********************************************************/

// connect to application
$dir_prefix = "../../";
global $dir_prefix;

require_once $dir_prefix . "dirs.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "regex.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "startdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "mitspielerdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "locationsdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "nachrichtendata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "repertoiredata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "probendata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "konzertedata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "equipmentdata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "calendardata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "aufgabendata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "instrumentedata.php";
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "kontaktedata.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC"] . "mailing.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "startcontroller.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
require_once $dir_prefix . "lang.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");

/**
 * Abstract Implementation of BNote Application Interface
 * @author Matti
 *
 */
abstract class AbstractBNA implements iBNA {

	/**
	 * Database instance.
	 * @var Database
	 */
	protected $db;

	/**
	 * System data instance.
	 * @var Systemdata
	 */
	protected $sysdata;

	/**
	 * Data Access Object for "Start" Module which contains
	 * many valuable functions for this interface.
	 * @var StartData
	 */
	protected $startdata;

	/**
	 * The user ID assoicated with the PIN.
	 * @var Integer
	 */
	protected $uid;

	function __construct() {
		$this->sysdata = new Systemdata($GLOBALS["dir_prefix"]);
		$this->db = $this->sysdata->dbcon;
		global $system_data;
		$system_data = $this->sysdata;
		$this->uid = -1;
		global $dir_prefix;
		$this->startdata = new StartData($dir_prefix);

		$this->init();
		$this->authentication();
		$this->route();
	}

	/**
	 * Use this function to execute code before authentication and routing.
	 */
	protected function init() {
		// do nothing by default
	}

	/**
	 * Authenticates users with pin.
	 */
	protected function authentication() {
		if(isset($_GET["func"]) && $_GET["func"] == "mobilePin") {
			$this->uid = null;
		}
		else if(!isset($_GET["pin"])) {
			header("HTTP/1.0 401 Permission Denied.");
			exit();
		}
		else {
			$pin = $_GET["pin"];
			$this->uid = $this->db->getCell($this->db->getUserTable(), "id", "pin = $pin");
			if($this->uid == null || $this->uid < 1) {
				header("HTTP/1.0 401 Permission Denied.");
				exit();
			}
		}
	}

	/**
	 * Routes a request to the correct function.
	 */
	protected function route() {
		$function = "";
		if(!isset($_GET["func"])) {
			header("HTTP/1.0 400 Function not specified.");
			exit();
		}
		else {
			$function = $_GET["func"];
		}

		if($function == "getRehearsalParticipation" || $function == "setRehearsalParticipation") {
			if(!isset($_GET["rehearsal"]) && !isset($_POST["rehearsal"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			else if($function == "getRehearsalParticipation") {
				$this->getRehearsalParticipation($_GET["rehearsal"], $this->uid);
			}
			else if($function == "setRehearsalParticipation") {
				if(!isset($_POST["participation"])) {
					header("HTTP/1.0 412 Insufficient Parameters.");
					exit();
				}
				$part = $_POST["participation"];
				if($part > 2 || $part < -1) {
					$part = -1;
				}
				$reason = "";
				if(isset($_POST["reason"])) {
					$reason = $_POST["reason"];
				}
				$this->setRehearsalParticipation($_POST["rehearsal"], $this->uid, $part, $reason);
			}
		}
		else if($function == "getRehearsalsWithParticipation") {
			$this->getRehearsalsWithParticipation($this->uid);
		}
		else if($function == "getVoteOptions") {
			// validation
			if(!isset($_GET["vid"]) || $_GET["vid"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->getVoteOptions($_GET["vid"]);
		}
		else if($function == "getComments") {
			if(!isset($_GET["otype"]) || !isset($_GET["oid"])
				|| $_GET["otype"] == "" || $_GET["oid"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->getComments($_GET["otype"], $_GET["oid"]);
		}
		else if($function == "taskCompleted") {
			if(!isset($_POST["taskId"]) || $_POST["taskId"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->taskCompleted($_POST["taskId"]);
		}
		else if($function == "addSong") {
			// check permission
			if(!$this->sysdata->userHasPermission(6, $this->uid)) { // 6=Repertoire
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			
			// validation
			if(!isset($_POST["title"]) || $_POST["title"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			// Parameter mapping
			$title = isset($_POST["title"]) ? $_POST["title"] : "";
			$length = isset($_POST["length"]) ? $_POST["length"] : "";
			$bpm = isset($_POST["bpm"]) ? $_POST["bpm"] : "";
			$music_key = isset($_POST["music_key"]) ? $_POST["music_key"] : "";
			$notes = isset($_POST["notes"]) ? $_POST["notes"] : "";
			$genre = isset($_POST["genre"]) ? $_POST["genre"] : "";
			$composer = isset($_POST["composer"]) ? $_POST["composer"] : "";
			$status = isset($_POST["status"]) ? $_POST["status"] : "";
			
			$this->addSong($title, $length, $bpm, $music_key, $notes, $genre, $composer, $status);
		}
		else if($function == "addRehearsal") {
			// check permission
			if(!$this->sysdata->userHasPermission(5, $this->uid)) { // 5=Rehearsals
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			
			// validation
			if(!isset($_POST["begin"]) || $_POST["begin"] == ""
				|| !isset($_POST["end"]) || $_POST["end"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			// Parameter mapping
			$begin = isset($_POST["begin"]) ? $_POST["begin"] : "";
			$end = isset($_POST["end"]) ? $_POST["end"] : "";
			$approve_until = isset($_POST["approve_until"]) ? $_POST["approve_until"] : "";
			$notes = isset($_POST["notes"]) ? $_POST["notes"] : "";
			$location = isset($_POST["location"]) ? $_POST["location"] : "";
			$groups = isset($_POST["groups"]) ? $_POST["groups"] : array();
			if(!is_array($groups)) {
				$groups = explode(",", $groups);
			}
			
			$this->addRehearsal($begin, $end, $approve_until, $notes, $location, $groups);
		}
		else if($function == "vote") {
			// validation
			if(!isset($_POST["vid"]) || $_POST["vid"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			// Parameter mapping
			$options = array();
			foreach($_POST as $k => $v) {
				if(is_numeric($k) && $v <= 2 && $v >= 0) {
					$options[$k] = $v;
				}
			}
			
			$this->vote($_POST["vid"], $options);
		}
		else if($function == "addComment") {
			if(!isset($_POST["otype"]) || !isset($_POST["oid"]) || !isset($_POST["message"])
					|| $_POST["otype"] == "" || $_POST["oid"] == ""
					|| $_POST["message"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			$this->addComment($_POST["otype"], $_POST["oid"], $_POST["message"]);
		}
		else if($function == "hasUserAccess") {
			$this->hasUserAccess();
		}
		else if($function == "getSongsToPractise") {
			if(!isset($_GET["rid"]) || $_GET["rid"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->getSongsToPractise($_GET["rid"]);
		}
		else if($function == "mobilePin") {
			if(!isset($_POST["login"]) || !isset($_POST["password"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->mobilePin($_POST["login"], $_POST["password"]);
		}
		else if($function == "getVoteResult") {
			// validation
			if(!isset($_GET["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			// permission check
			if(!$this->startdata->canUserVote($_GET["id"], $this->uid)
					&& !$this->sysdata->isUserSuperUser($this->uid)) {
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			
			$this->getVoteResult($_GET["id"]);
		}
		else if($function == "setConcertParticipation") {
			if(!isset($_POST["concert"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			if(!isset($_POST["participation"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$part = $_POST["participation"];
			if($part > 2 || $part < -1) {
				$part = -1;
			}
			$reason = "";
			if(isset($_POST["explanation"])) {
				$reason = $_POST["explanation"];
			}
			$this->setConcertParticipation($_POST["concert"], $this->uid, $part, $reason);
		}
		else if($function == "addConcert") {
			// check permission
			if(!$this->sysdata->userHasPermission(4, $this->uid)) { // 4=Concerts
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			
			// validation
			if(!isset($_POST["begin"]) || $_POST["begin"] == ""
					|| !isset($_POST["end"]) || $_POST["end"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			// Parameter mapping
			$begin = isset($_POST["begin"]) ? $_POST["begin"] : "";
			$end = isset($_POST["end"]) ? $_POST["end"] : "";
			$approve_until = isset($_POST["approve_until"]) ? $_POST["approve_until"] : "";
			$notes = isset($_POST["notes"]) ? $_POST["notes"] : "";
			$location = isset($_POST["location"]) ? $_POST["location"] : "";
			$program = isset($_POST["program"]) ? $_POST["program"] : "";
			$contact = isset($_POST["contact"]) ? $_POST["coxntact"] : "";
			$groups = isset($_POST["groups"]) ? $_POST["groups"] : array();
			if(!is_array($groups)) {
				$groups = explode(",", $groups);
			}
			$this->addConcert($begin, $end, $approve_until, $notes, $location, $program, $contact, $groups);
		}
		else if($function == "updateRehearsal") {
			// check permission
			if(!$this->sysdata->userHasPermission(5, $this->uid)) { // 5=Rehearsals
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
				
			// validation
			if(!isset($_POST["id"]) || $_POST["id"] == ""
					|| !isset($_POST["begin"]) || $_POST["begin"] == ""
					|| !isset($_POST["end"]) || $_POST["end"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
				
			// Parameter mapping
			$id = $_POST["id"];
			$begin = isset($_POST["begin"]) ? $_POST["begin"] : "";
			$end = isset($_POST["end"]) ? $_POST["end"] : "";
			$approve_until = isset($_POST["approve_until"]) ? $_POST["approve_until"] : "";
			$notes = isset($_POST["notes"]) ? $_POST["notes"] : "";
			$location = isset($_POST["location"]) ? $_POST["location"] : "";
			$groups = isset($_POST["groups"]) ? $_POST["groups"] : array();
			if(!is_array($groups)) {
				$groups = explode(",", $groups);
			}
			$this->updateRehearsal($id, $begin, $end, $approve_until, $notes, $location, $groups);
		}
		else if($function == "updateConcert") {
			// check permission
			if(!$this->sysdata->userHasPermission(4, $this->uid)) { // 4=Concerts
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
				
			// validation
			if(!isset($_POST["id"]) || $_POST["id"] == ""
					|| !isset($_POST["begin"]) || $_POST["begin"] == ""
					|| !isset($_POST["end"]) || $_POST["end"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
				
			// Parameter mapping
			$id = $_POST["id"];
			$begin = isset($_POST["begin"]) ? $_POST["begin"] : "";
			$end = isset($_POST["end"]) ? $_POST["end"] : "";
			$approve_until = isset($_POST["approve_until"]) ? $_POST["approve_until"] : "";
			$notes = isset($_POST["notes"]) ? $_POST["notes"] : "";
			$location = isset($_POST["location"]) ? $_POST["location"] : "";
			$program = isset($_POST["program"]) ? $_POST["program"] : "";
			$contact = isset($_POST["contact"]) ? $_POST["contact"] : "";
			$groups = isset($_POST["groups"]) ? $_POST["groups"] : array();
			if(!is_array($groups)) {
				$groups = explode(",", $groups);
			}
			$this->updateConcert($id, $begin, $end, $approve_until, $notes, $location, $program, $contact, $groups);
		}
		else if($function == "deleteRehearsal") {
			// check permission
			if(!$this->sysdata->userHasPermission(5, $this->uid)) { // 5=Rehearsals
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
				
			// validation
			if(!isset($_POST["id"]) || $_POST["id"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			$this->deleteRehearsal($_POST["id"]);
		}
		else if($function == "deleteConcert") {
			// check permission
			if(!$this->sysdata->userHasPermission(4, $this->uid)) { // 4=Concerts
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			
			// validation
			if(!isset($_POST["id"]) || $_POST["id"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			$this->deleteConcert($_POST["id"]);
		}
		else if($function == "sendMail") {
			// check permission
			if(!$this->sysdata->userHasPermission(7, $this->uid)) { // 7=Communication
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			
			// validation
			if(!isset($_POST["subject"]) || !isset($_POST["body"]) 
					|| !isset($_POST["groups"]) || $_POST["groups"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			
			// mapping
			$groups = isset($_POST["groups"]) ? $_POST["groups"] : array();
			if(!is_array($groups)) {
				$groups = explode(",", $groups);
			}
			
			$this->sendMail($_POST["subject"], $_POST["body"], $groups);
		}
		else if($function == "updateSong") {
			// permission
			if(!$this->sysdata->userHasPermission(6, $this->uid)) { // 6=Repertoire
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_POST["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->updateSong($_POST["id"]);
		}
		else if($function == "addEquipment") {
			// permission
			if(!$this->sysdata->userHasPermission(22, $this->uid)) { // 22=Equipment
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			
			$this->addEquipment();
		}
		else if($function == "updateEquipment") {
			// permission
			if(!$this->sysdata->userHasPermission(22, $this->uid)) { // 22=Equipment
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_POST["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->updateEquipment($_POST["id"]);
		}
		else if($function == "getEquipment") {
			// permission
			if(!$this->sysdata->userHasPermission(22, $this->uid)) { // 22=Equipment
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			$this->getEquipment();
		}
		else if($function == "deleteEquipment") {
			// permission
			if(!$this->sysdata->userHasPermission(22, $this->uid)) { // 22=Equipment
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_POST["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->deleteEquipment($_POST["id"]);
		}
		else if($function == "deleteSong") {
			// permission
			if(!$this->sysdata->userHasPermission(6, $this->uid)) { // 6=Repertoire
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_POST["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->deleteSong($_POST["id"]);
		}
		else if($function == "getSong") {
			// permission
			if(!$this->sysdata->userHasPermission(6, $this->uid)) { // 6=Repertoire
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_GET["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->getSong($_GET['id']);
		}
		else if($function == "getReservation") {
			// permission
			if(!$this->sysdata->userHasPermission(20, $this->uid)) { // 20=Calendar
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_GET["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->getReservation($_GET['id']);
		}
		else if($function == "getReservations") {
			// permission
			if(!$this->sysdata->userHasPermission(20, $this->uid)) { // 20=Calendar
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			$this->getReservations();
		}
		else if($function == "addReservation") {
			// permission
			if(!$this->sysdata->userHasPermission(20, $this->uid)) { // 20=Calendar
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			$this->addReservation();
		}
		else if($function == "updateReservation") {
			// permission
			if(!$this->sysdata->userHasPermission(20, $this->uid)) { // 20=Calendar
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_POST["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->updateReservation($_POST["id"]);
		}
		else if($function == "deleteReservation") {
			// permission
			if(!$this->sysdata->userHasPermission(20, $this->uid)) { // 20=Calendar
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			if(!isset($_POST["id"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->deleteReservation($_POST["id"]);
		}
		else if($function == "addTask") {
			// permission
			if(!$this->sysdata->userHasPermission(16, $this->uid)) { // 16=Tasks
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			$this->addTask();
		}
		else if($function == "addLocation") {
			// NO PERMISSION CHECK, because we want to allow users to create locations
			// as references where needed. The worst case really is that a registered user
			// adds a very large number of locations to flood the system. But from the
			// server logs we would know who (at least IP address).
			$this->addLocation();
		}
		else if($function == "addContact") {
			// permission
			if(!$this->sysdata->userHasPermission(3, $this->uid)) { // 3=Contacts
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			$this->addContact();
		}
		else if($function == "getContacts") {
			// permission
			if(!$this->sysdata->userHasPermission(3, $this->uid)) { // 3=Contacts
				header("HTTP/1.0 403 Permission denied.");
				exit();
			}
			$this->getContacts();
		}
		else {
			$this->$function();
		}
	}

	/* METHODS TO IMPLEMENT BY SUBCLASSES */

	/**
	 * Prints out a statement with which the entity starts,
	 * e.g. "<location>".
	 */
	protected abstract function beginOutputWith();

	/**
	 * Prints out a statement with which the entity ends,
	 * e.g. "</location>".
	 */
	protected abstract function endOutputWith();

	/**
	 * Prints the entities out.
	 * @param Array $entities SQL selection array with the entities.
	 * @param String $nodeName Name of the node in case required, e.g. singluar.
	 */
	protected abstract function printEntities($entities, $nodeName);
	
	/**
	 * Writes a single, flat entity.
	 * @param Object $entity Object with attributes and values.
	 * @param String $type Name, e.g. "song", "rehearsal", "concert"
	 */
	protected abstract function writeEntity($entity, $type);

	/* DEFAULT IMPLEMENTATIONS */
	
	function getRehearsals() {
		return $this->getRehearsalsWithParticipation($this->uid);
	}
	

	function getRehearsalsWithParticipation($user) {
		if($this->sysdata->isUserSuperUser($this->uid)
				|| $this->sysdata->isUserMemberGroup(1, $this->uid)) {
			$query = "SELECT * ";
			$query .= "FROM rehearsal r LEFT JOIN rehearsal_user ru ON ru.rehearsal = r.id ";
			$query .= "WHERE end > now() AND (ru.user = $user || ru.user IS NULL) ";
			$query .= "ORDER BY begin ASC";
			$rehs = $this->db->getSelection($query);
		}
		else {
			// only get rehearsals for user considering phases and groups
			$rehs = $this->startdata->getUsersRehearsals($this->uid);
				
			// manually join participation
			array_push($rehs[0], "participate");
			array_push($rehs[0], "reason");
				
			for($i = 1; $i < count($rehs); $i++) {
				$rid = $rehs[$i]["id"];
				$query = "SELECT * FROM rehearsal_user WHERE rehearsal = $rid AND user = " . $this->uid;
				$part = $this->db->getRow($query);
				if($part == null) {
					$part = array( "participate" => "-1", "reason" => "" );
				}
				$rehs[$i]["participate"] = intval($part["participate"]);
				$rehs[$i]["reason"] = $part["reason"];
			}
		}
		
		
		// resolve location
		for($i = 1; $i < count($rehs); $i++) {
			$query = "SELECT location.id, name, street, city, zip ";
			$query .= "FROM location JOIN address ON location.address = address.id ";
			$query .= "WHERE location.id = " . $rehs[$i]["location"];
			$loc = $this->db->getRow($query);
			
			$rehs[$i]["location"] = $loc;
		}
		

		
		// remove header
		unset($rehs[0]);
		
		// resolve songs for rehearsal
		for($i = 1; $i < count($rehs); $i++)
		{
			$rehearsal = $rehs[$i];
			$probenData = new ProbenData($GLOBALS["dir_prefix"]);
			$songs = $probenData->getSongsForRehearsal($rehearsal["id"]);
			unset($songs[0]);
			
			$rehs[$i]["songsToPractice"] = array_values($this->removeNumericKeys($songs));
		}

		// resolve comments
		for($i = 1; $i < count($rehs); $i++) 
		{
			$rehearsal = $rehs[$i];
				$comments = $this->startdata->getDiscussion("r", $rehearsal["id"]);
				unset($comments[0]);
				
				foreach($comments as $j => $comment)
				{
					$comments[$j]["author"] = array("id" => $comments[$j]["author_id"], "fullname" => $comments[$j]["author"]);
					unset($comments[$j]["author_id"]);
										$comments[$j]["message"] = urldecode($comments[$j]["message"]);
				}				
				$rehs[$i]["comments"] = array_values($this->removeNumericKeys($comments));
			
		}

	
		// add  participants
		foreach($rehs as $i => $rehearsal) {
			$query = "SELECT c.id, c.surname, c.name, ru.participate, ru.reason";
			$query .= " FROM rehearsal_user ru, user u, contact c";
			$query .= " WHERE ru.rehearsal = " . $rehearsal["id"] . " AND ru.user = u.id AND u.contact = c.id" ;
			$contacts = $this->db->getSelection($query);
			unset($contacts[0]);

			// ids for filterting contacts without response
			$contactIDs = array();
			$participantsNo = array();
			$participantsYes = array();
			$participantsMaybe = array();
						
			foreach($contacts as $j => $contact) 
			{
				foreach($contact as $ck => $cv) {
					if(is_numeric($ck)) {
						unset($contact[$ck]);
					}
				}
				array_push($contactIDs, $contact["id"]);
				
				if ($contact["participate"] == 0)
				{
					array_push($participantsNo, $contact);
				}
				else if ($contact["participate"] == 1)
				{
					array_push($participantsYes, $contact);
				}
				else if ($contact["participate"] == 2)
				{
					array_push($participantsMaybe, $contact);
				}
			}

			
			// get contacts without response (filter other contacts)
			array_push($contactIDs, PHP_INT_MAX);
			$contactIDsString = join(',',$contactIDs);  
		
			$query = "SELECT c.id, c.surname, c.name";
			$query .= " FROM rehearsal_contact rc JOIN contact c ON rc.contact = c.id";
			$query .= " WHERE rc.rehearsal = " . $rehearsal["id"] . " AND rc.contact NOT IN (" . $contactIDsString .")";
			$participantsNoResponse = $this->db->getSelection($query);
			unset($participantsNoResponse[0]);
			
			foreach($participantsNoResponse as $j => $contact) 
			{
				
				foreach($contact as $ck => $cv) {
					if(is_numeric($ck)) {
						unset($participantsNoResponse[$j][$ck]);
					}
				
				}
				
			}
			
			$rehs[$i]["participantsNo"] = array_values($participantsNo);
			$rehs[$i]["participantsYes"] = array_values($participantsYes);
			$rehs[$i]["participantsMaybe"] = array_values($participantsMaybe);
			$rehs[$i]["participantsNoResponse"] = array_values($participantsNoResponse);
			
		}
		
	
		// cleanup
		foreach($rehs as $i => $rehearsal) {
			foreach($rehearsal as $k => $v) {
				if(is_numeric($k) || $k == "rehearsal" || $k == "user") {
					unset($rehs[$i][$k]);
				}
			}
		}
		
		$this->printEntities($rehs, "rehearsals");
		return $rehs;
	}
	
	protected abstract function printRehearsals($rehs);

	function getConcerts() {
		$concerts = $this->startdata->getUsersConcerts($this->uid);
		
		// remove header
		unset($concerts[0]);
		
		// enrichment of objects
		foreach($concerts as $i => $concert) {
			$dbConcert = $this->db->getRow("SELECT * FROM concert WHERE id = " . $concert["id"]);
			
			// location
			$concerts[$i]["location"] = array(
				"id" => $dbConcert["location"],
				"name" => $concert["location_name"],
				"notes" => $concert["location_notes"],
				"street" => $concert["location_street"],
				"city" => $concert["location_city"],
				"zip" => $concert["location_zip"],
			);
			unset($concerts[$i]["location_name"]);
			unset($concerts[$i]["location_notes"]);
			unset($concerts[$i]["location_street"]);
			unset($concerts[$i]["location_city"]);
			unset($concerts[$i]["location_zip"]);
			
			// contact
			$concerts[$i]["contact"] = array(
				"id" => $dbConcert["contact"],
				"name" => $concert["contact_name"],
				"phone" => $concert["contact_phone"],
				"mobile" => $concert["contact_mobile"],
				"email" => $concert["contact_email"],
				"web" => $concert["contact_web"]
			);
			unset($concerts[$i]["contact_name"]);
			unset($concerts[$i]["contact_phone"]);
			unset($concerts[$i]["contact_mobile"]);
			unset($concerts[$i]["contact_email"]);
			unset($concerts[$i]["contact_web"]);
			
			// program
			$concerts[$i]["program"] = array(
					"id" => $dbConcert["program"],
					"name" => $concert["program_name"],
					"notes" => $concert["program_notes"]
			);
			unset($concerts[$i]["program_id"]);
			unset($concerts[$i]["program_name"]);
			unset($concerts[$i]["program_notes"]);
			
			// participation
			$concerts[$i]["participate"] = $this->startdata->doesParticipateInConcert($concert["id"], $this->uid);
			if($concerts[$i]["participate"] >= 0) {
				$concerts[$i]["reason"] = $this->db->getCell(
						"concert_user", "reason",
						"concert = " . $concert["id"] . " AND user = " . $this->uid );
			}
			else {
				$concerts[$i]["reason"] = "";
			}
		}
		
		// resolve comments
		foreach($concerts as $i => $concert) {

				$comments = $this->startdata->getDiscussion("c", $concert["id"]);
				unset($comments[0]);
				
				foreach($comments as $j => $comment)
				{
					$comments[$j]["author"] = array("id" => $comments[$j]["author_id"], "fullname" => $comments[$j]["author"]);
					unset($comments[$j]["author_id"]);
										$comments[$j]["message"] = urldecode($comments[$j]["message"]);
				}				
				$concerts[$i]["comments"] = array_values($this->removeNumericKeys($comments));
		}
		
		
		// add  participants
		foreach($concerts as $i => $concert) {
			$query = "SELECT c.id, c.surname, c.name, cu.participate, cu.reason";
			$query .= " FROM concert_user cu, user u, contact c";
			$query .= " WHERE cu.concert = " . $concert["id"] . " AND cu.user = u.id AND u.contact = c.id" ;
			$contacts = $this->db->getSelection($query);
			unset($contacts[0]);

			// ids for filterting contacts without response
			$contactIDs = array();
			$participantsNo = array();
			$participantsYes = array();
			$participantsMaybe = array();
						
			foreach($contacts as $j => $contact) 
			{
				foreach($contact as $ck => $cv) {
					if(is_numeric($ck)) {
						unset($contact[$ck]);
					}
				}
				array_push($contactIDs, $contact["id"]);
				
				if ($contact["participate"] == 0)
				{
					array_push($participantsNo, $contact);
				}
				else if ($contact["participate"] == 1)
				{
					array_push($participantsYes, $contact);
				}
				else if ($contact["participate"] == 2)
				{
					array_push($participantsMaybe, $contact);
				}
			}

			// get contacts without response (filter other contacts)
			array_push($contactIDs, PHP_INT_MAX);
			$contactIDsString = join(',',$contactIDs);  
			
			$query = "SELECT c.id, c.surname, c.name";
			$query .= " FROM concert_contact cc JOIN contact c ON cc.contact = c.id";
			$query .= " WHERE cc.concert = " . $concert["id"] . " AND cc.contact NOT IN (" . $contactIDsString .")";
			$participantsNoResponse = $this->db->getSelection($query);
			unset($participantsNoResponse[0]);
			
			foreach($participantsNoResponse as $j => $contact) 
			{
				foreach($contact as $ck => $cv) {
					if(is_numeric($ck)) {
						unset($participantsNoResponse[$j][$ck]);
					}
				}
			}
			
			$concerts[$i]["participantsNo"] = array_values($participantsNo);
			$concerts[$i]["participantsYes"] = array_values($participantsYes);
			$concerts[$i]["participantsMaybe"] = array_values($participantsMaybe);
			$concerts[$i]["participantsNoResponse"] = array_values($participantsNoResponse);	
		}
		
		$concerts = $this -> removeNumericKeys($concerts);
		$this->printEntities($concerts, "concerts");
		return $concerts;
	}
	
	protected abstract function printConcerts($concerts);

	function getContacts() {
		$contactData = new KontakteData($GLOBALS["dir_prefix"]);
		$_SESSION["user"] = $this->uid;
		$entities = $contactData->getAllContacts();
		unset($_SESSION["user"]);
		unset($entities[0]);
		$allContacts = array();
		foreach($entities as $i => $entity) {
			$entity = $this->removeNumericKeys($entity);
			$entity["fullname"] = join(' ', array($entity["name"], $entity["surname"]));
			$groups = $contactData->getContactFullGroups($entity["id"]);
			unset($groups[0]);
			$grps = array();
			foreach($groups as $i => $grp) {
				array_push($grps, $this->removeNumericKeys($grp));
			}
			$entity["groups"] = $grps;
			array_push($allContacts, $entity);
		}
		$this->printEntities($allContacts, "contacts");
	}
	
	function getMembers() {
		$msd = new MitspielerData($GLOBALS["dir_prefix"]);
		$contacts = $msd->getMembers($this->uid);
		unset($contacts[0]);  // header
		
		$contacts = $this->removeNumericKeys($contacts);
		$this->printEntities($contacts, "contacts");
		return $contacts;
	}

	function getLocations() {
		$locData = new LocationsData($GLOBALS["dir_prefix"]);
		$locs = $locData->findAllJoined(array(
			"address" => array("street", "city", "zip")
		));
		unset($locs[0]);  // header
		$this->printEntities($locs, "locations");
		return $locs;
	}

	function getTasks() {
		$tasks = $this->startdata->adp()->getUserTasks($this->uid);
		$entities = array();
		array_push($entities, $tasks[0]);
		
		foreach($tasks as $i => $task) {
			if($i == 0) continue; // header
			// convert description
			$task["description"] = urlencode($task["description"]);
			
			array_push($entities, $task);
		}
		unset($entities[0]);  // header
		$this->printEntities($entities, "task");
	}
	
	function getNews() {
		$newsData = new NachrichtenData($GLOBALS["dir_prefix"]);
		echo $newsData->preparedContent();
	}
	
	function getVotes() {
		$votes = $this->startdata->getVotesForUser($this->uid);
		unset($votes[0]); // remove header
		foreach($votes as $i => $vote) {
			// remove numeric fields
			foreach($vote as $k => $v) {
				if(is_numeric($k)) {
					unset($votes[$i][$k]);
				}
			}
			
			// add vote options
			$opts = $this->startdata->getOptionsForVote($vote["id"]);
			unset($opts[0]); // options header
			foreach($opts as $oi => $option) {
				foreach($option as $ok => $ov)
				if(is_numeric($ok) || $ok == "vote") {
					unset($opts[$oi][$ok]);
				}
				if($ok == "odate" && $ov != "") {
					$opts[$oi]["name"] = $ov;
					unset($opts[$oi][$ok]);
				}
			}

			$votes[$i]["options"] = $opts;
		}
		
		$this->printVotes($votes);
		return $votes;
	}
	
	protected abstract function printVotes($votes);
	
	function getVoteOptions($vid) {
		$options = $this->startdata->getOptionsForVote($vid);
		$this->printEntities($options, "vote_option");
	}
	
	function getSongs() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$songs = $repData->findAllNoRef();
				
		$entities = array();
		
		foreach($songs as $i => $song) {
			if($i == 0) continue; // header
			$composerId = $song["composer"];
			$song["composer"] = $repData -> getComposerName($composerId);
			
			$genre = $repData -> getGenre($song["genre"]);

			$song["genre"] = $this -> removeNumericKeys($genre[1]);
			
			$songstatus = $this->db->getRow("SELECT * FROM status WHERE id = " . intval($song["status"]));
			$song["status"] = $songstatus;
			$song = $this -> removeNumericKeys($song);
			array_push($entities, $song);
		}
		
		$this->printEntities($entities, "songs");
		return $songs;
	}
	
	function getGenres() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$genres = $repData->getGenres();
		unset($genres[0]);
		$genres =  $this -> removeNumericKeys($genres);
		
		$this->printEntities($genres, "genres");
		return $genres;
	}
	
	function getStatuses() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$this->printEntities($repData->getStatuses(), "status");
	}
	
	function getAll() {
		$this->documentBegin();
		
		$sep = $this->entitySeparator();

		$this->getRehearsalsWithParticipation($this->uid); echo $sep . "\n";
		$this->getConcerts(); echo $sep . "\n";
		$this->getMembers(); echo $sep . "\n";
		$this->getLocations(); echo $sep . "\n";
		$this->getTasks(); echo $sep . "\n";
		$this->getReservations(); echo $sep . "\n";
		$this->getVotes(); echo $sep . "\n";
		$this->getGenres(); echo $sep . "\n";
		$this->getStatuses(); echo $sep . "\n";
		$this->getGroups(); echo "\n";
		
		$this->documentEnd();
	}
	
	/**
	 * @return A separator between entities, in JSON for example ",".
	 */
	protected function entitySeparator() {
		return "";
	}
	
	/**
	 * Used in the getAll method to begin the document.
	 */
	protected function documentBegin() {
		// empty by default
	}
	
	/**
	 * Used in the getAll method to end the document.
	 */
	protected function documentEnd() {
		// empty by default
	}
	
	function getComments($otype, $oid) {
		$comments = $this->startdata->getDiscussion($otype, $oid);
		$this->printEntities($comments, "comment");
	}

	function getRehearsalParticipation($rid, $uid) {
		$_SESSION["user"] = $uid;
		$res = $this->startdata->doesParticipateInRehearsal($rid);
		unset($_SESSION["user"]);
		return $res;
	}

	function setRehearsalParticipation($rid, $uid, $part, $reason) {
		$reason = "";
		if(isset($_POST["reason"])) {
			$reason = $_POST["reason"];
		}
		$this->startdata->saveParticipation("rehearsal", $uid, $rid, $part, $reason);
		$this->writeEntity(array("success" => "true"), null);
	}

	function taskCompleted($tid) {
		$this->startdata->taskComplete($tid);
		echo '{"success": true}';
	}
	
	function addSong($title, $length, $bpm, $music_key, $notes, $genre, $composer, $status) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		
		// semantic parameter mappings
		$values["title"] = $title;
		$values["length"] = $length;
		$values["bpm"] = $bpm == "" ? "0" : $bpm;
		$values["music_key"] = $music_key;
		$values["notes"] = urldecode($notes);
		$values["genre"] = $genre["id"];
		$values["composer"] = $composer;
		$values["status"] = $status["id"];
		
		echo $repData->create($values);
	}
	
	function addRehearsal($begin, $end, $approve_until, $notes, $location, $groups) {
		// semantic parameter mappings
		$values["begin"] = Data::convertDateFromDb($begin);
		$values["end"] = Data::convertDateFromDb($end);
		$values["approve_until"] = ($approve_until == "") ? Data::convertDateFromDb($begin) : Data::convertDateFromDb($approve_until);
		$values["notes"] = $notes;
		$values["location"] = $location;
		
		if($groups == null || count($groups) == 0) {
			// add rehearsal to default group
			$defaultGroup = $this->sysdata->getDynamicConfigParameter("default_contact_group");
			$values["group_" . $defaultGroup] = "on";
			$_POST["group_" . $defaultGroup] = "on";
		}
		else {
			foreach($groups as $i => $grp) {
				$values["group_" . $grp] = "on";
				$_POST["group_" . $grp] = "on";
			}
		}
		
		// create rehearsal
		require_once $GLOBALS["DIR_WIDGETS"] . "iwriteable.php";
		require_once $GLOBALS["DIR_WIDGETS"] . "groupselector.php";
		$rehData = new ProbenData($GLOBALS["dir_prefix"]);
		echo $rehData->create($values);
	}
	
	function vote($vid, $options) {
		$vote = $this->startdata->getVote($vid);
		if($vote["is_multi"] != "1") {
			// single option vote
			$firstOption = "";
			foreach($options as $optionId => $choice) {
				$firstOption = $optionId;
				break;
			}
			$options["uservote"] = $firstOption;
		}
		$this->startdata->saveVote($vid, $options, $this->uid);
		echo '{"success": true}';
	}
	
	function addComment($otype, $oid, $message) {
		// save comments
		$commentId = $this->startdata->addComment($otype, $oid, $message, $this->uid);
		
		// notify contacts
		$startCtrl = new StartController();
		$startCtrl->setData($this->startdata);
		
		// $_POST["message"] is set from the interface
		// set $_GET array
		$_GET["oid"] = $_POST["oid"];
		$_GET["otype"] = $_POST["otype"];

		$response = array(
			"id" => $commentId,
			"oid" => $oid,
			"message" => $message,
			"otype" => $otype,
		);
		
		$this -> writeEntity($response);
	
		
		$startCtrl->notifyContactsOnComment($this->uid);
	}
	
	function getVersion() {
		echo $this->sysdata->getVersion();
	}
	
	function getUserInfo() {	
		$contact = $this->sysdata->getUsersContact($this->uid);
		$instrument = $this->db->getCell("instrument", "name", "id = " . $contact["instrument"]);
		$addy = $this->startdata->adp()->getEntityForId("address", $contact["address"]);
		$contact["instrument"] = $instrument;
		$contact["street"] = $addy["street"];
		$contact["zip"] = $addy["zip"];
		$contact["city"] = $addy["city"];
		unset($contact["address"]);
		unset($contact["status"]); // not existent anymore
		
		$this->writeEntity($contact, "contact");
	}
	
	function mobilePin($login, $password) {
		$loginCtrl = new LoginController();
		$loginData = new LoginData($GLOBALS["dir_prefix"]);
		$loginCtrl->setData($loginData);
		if($loginCtrl->doLogin(true)) {
			$uid = $_SESSION["user"];
			$pin = $this->db->getCell($this->db->getUserTable(), "pin", "id = $uid");
			if($pin == "") {
				$pin = LoginController::createPin($this->db, $uid);
			}
			unset($_SESSION["user"]); // logout
			echo $pin;
		}
		else {
			header("HTTP/1.0 403 Permission Denied.");
			echo "Invalid Credentials.";
		}
	}
	
	function hasUserAccess() {
		if(!isset($_GET["moduleId"]) || $_GET["moduleId"] == "") {
			$arr = $this->sysdata->getUserModulePermissions($this->uid);
			$this->writeEntity($arr, "permission");
		}
		else {
			echo ($this->sysdata->userHasPermission($_GET["moduleId"], $this->uid)) ? "true" : "false";
		}
	}
	
	function getSongsToPractise($rid) {
		$probenData = new ProbenData($GLOBALS["dir_prefix"]);
		$songs = $probenData->getSongsForRehearsal($rid);
		$this->printEntities($songs, "song");
	}
	
	function getGroups() {
		$selection = $this->startdata->adp()->getGroups();
		unset($selection[0]);  // header
		$this->printEntities($selection, "group");
	}
	
	function getVoteResult($vid) {
		/* target structure:
		 * array(
		 * 	id => ...
		 *  name => ...
		 *  options => array(
		 *  	0 => array(
		 *  		id => ...
		 *  		name => ...
		 *  		choice => array(
		 *  			0 => 2 // no
		 *  			1 => 4 // yes
		 *  			2 => 0 // maybe
		 *  		)
		 *  	)
		 *  )
		 * )
		 */
		$vote = $this->startdata->getVote($vid);
		$options = $options = $this->startdata->getOptionsForVote($vid);
		
		$opts = array();
		for($i = 1; $i < count($options); $i++) {
			$opt = array();
			$opt["id"] = $options[$i]["id"];
			if(isset($options[$i]["odate"]) && $options[$i]["odate"] != "") {
				$opt["name"] = $options[$i]["odate"];
			}
			else {
				$opt["name"] = $options[$i]["name"];
			}
			$opt["choice"] = array();
			
			$query = "SELECT choice, count(*) as num FROM vote_option_user";
			$query .= " WHERE vote_option = " . $opt["id"];
			$query .= " GROUP BY choice";
			$choice = $this->db->getSelection($query);
			
			if($vote["is_multi"]) {
				$numYes = 0;
				$numNo = 0;
				$numMay = 0;
				
				for($c = 1; $c < count($choice); $c++) {
					if($choice[$c]["choice"] == 1) {
						$numYes = $choice[$c]["num"];
					}
					else if($choice[$c]["choice"] == 0) {
						$numNo = $choice[$c]["num"];
					}
					else if($choice[$c]["choice"] == 2) {
						$numMay = $choice[$c]["num"];
					}
				}
				
				$opt["choice"]["0"] = $numNo;
				$opt["choice"]["1"] = $numYes;
				$opt["choice"]["2"] = $numMay;
			}
			else {
				$opt["choice"]["0"] = 0;
				if(count($choice) > 1) {
					$opt["choice"]["1"] = $choice[1]["num"];
				}
				else {
					$opt["choice"]["1"] = 0;
				}
				$opt["choice"]["2"] = 0;
			}
			
			array_push($opts, $opt);
		}
		
		$vote["options"] = $opts;
		
		$this->printVoteResult($vote);
	}
	
	protected abstract function printVoteResult($vote);
	
	function setConcertParticipation($cid, $uid, $part, $reason) {
		$this->startdata->saveParticipation("concert", $uid, $cid, $part, $reason);
		$this->writeEntity(array("success" => "true"), null);
	}
	
	function addConcert($begin, $end, $approve_until, $notes, $location, $program, $contact, $groups) {
		// semantic parameter mappings
		$values["begin"] = $begin;
		$values["end"] = $end;
		$values["approve_until"] = ($approve_until == "") ? $begin : $approve_until;
		$values["notes"] = $notes;
		$values["location"] = $location;
		if($program != null && $program != "") {
			$values["program"] = $program;
		}
		$values["contact"] = $contact;
		
		$conData = new KonzerteData($GLOBALS["dir_prefix"]);
		$id = $conData->create($values);
		
		if($id > 0) {
			// add contacts to concert
			if($groups == null || count($groups) == 0) {
				// add default group to concert
				$groups = $this->sysdata->getDynamicConfigParameter("default_contact_group");
			}
			$conData->addMembersToConcert($groups, $id);
			
			// write output
			$con = $conData->findByIdNoRef($id);
			$this->writeEntity($con, "concert");
		}
		else {
			echo "Error: Cannot create concert.";
		}
	}
	
	function updateRehearsal($id, $begin, $end, $approve_until, $notes, $location, $groups) {
		// semantic parameter mappings
		$values["begin"] = $begin;
		$values["end"] = $end;
		$values["approve_until"] = ($approve_until == "") ? $begin : $approve_until;
		$values["notes"] = $notes;
		if($location == null || $location == "") {
			unset($values["location"]);
		}
		else {
			$values["location"] = $location;
		}
		
		if($groups != null && $group != "" && count($groups) > 0) {
			foreach($groups as $i => $grp) {
				$values["group_" . $grp] = "on";
				$_POST["group_" . $grp] = "on";
			}
		}
		
		// create rehearsal
		require_once $GLOBALS["DIR_WIDGETS"] . "iwriteable.php";
		require_once $GLOBALS["DIR_WIDGETS"] . "groupselector.php";
		$rehData = new ProbenData($GLOBALS["dir_prefix"]);
		echo $rehData->update($id, $values);
		
		// return updated entry
		$reh = $rehData->findByIdNoRef($id);
		$this->writeEntity($reh, "rehearsal");
	}
	
	function updateConcert($id, $begin, $end, $approve_until, $notes, $location, $program, $contact, $groups) {
		// semantic parameter mappings
		$values["begin"] = $begin;
		$values["end"] = $end;
		$values["approve_until"] = ($approve_until == "") ? $begin : $approve_until;
		$values["notes"] = $notes;
		$values["location"] = $location;
		if($program != null && $program != "") {
			$values["program"] = $program;
		}
		$values["contact"] = $contact;
		
		$conData = new KonzerteData($GLOBALS["dir_prefix"]);
		$conData->update($id, $values);
		
		// add contacts to concert
		if($groups != null && count($groups) > 0) {
			$conData->addMembersToConcert($groups, $id);
		}
			
		// write output
		$con = $conData->findByIdNoRef($id);
		$this->writeEntity($con, "concert");
	}
	
	function deleteRehearsal($id) {
		$rehData = new ProbenData($GLOBALS["dir_prefix"]);
		$rehData->delete($id);
		echo "true";
	}
	
	function deleteConcert($id) {
		$conData = new KonzerteData($GLOBALS["dir_prefix"]);
		$conData->delete($id);
		echo "true";
	}
	
	function sendMail($subject, $body, $groups) {
		// fetch addresses
		if($groups == null || $groups == "" || count($groups) == 0) {
			echo "Error: no groups.";
			exit;
		}
		
		$query = "SELECT DISTINCT c.email ";
		$query .= "FROM contact c JOIN contact_group cg ON cg.contact = c.id ";
		$query .= "WHERE ";
		foreach($groups as $i => $group) {
			if($i > 0) $query .= "OR ";
			$query .= "cg.group = $group ";
		}
		
		$mailaddies = $this->db->getSelection($query);
		$addresses = $this->flattenAddresses($mailaddies);
		
		if($addresses == null || count($addresses) == 0) {
			new Error("Es wurden keine Empfänger gefunden.");
		}
		
		// Receipient Setup
		$ci = $this->sysdata->getCompanyInformation();
		$receipient = $ci["Mail"];
		
		// place sender addresses into the bcc field
		$bcc_addresses = "";
		foreach($addresses as $i => $to) {
			if($i > 0) $bcc_addresses .= ",";
			$bcc_addresses .= $to;
		}
		
		$mail = new Mailing($receipient, $subject, "");
		$mail->setBodyInHtml($body);
		$userContact = $this->sysdata->getUsersContact($this->uid);
		$mail->setFrom($userContact["email"]);
		$mail->setBcc($bcc_addresses);
		
		if(!$mail->sendMail()) {
			echo "Error: Cannot send mail.";
		}
		else {
			echo "true";
		}
	}
	
	public function addEquipment() {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		unset($_POST["id"]);
		echo $eqData->create($_POST);
	}
	
	public function updateEquipment($id) {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		$eqData->update($id, $_POST);
		echo '{"success": true}';
	}
	
	public function deleteEquipment($id) {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		$eqData->delete($id);
		echo '{"success": true}';
	}
	
	public function getEquipment() {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		if(isset($_GET["id"]) && $_GET["id"] != "") {
			$eq = $eqData->findByIdNoRef($_GET["id"]);
			$this->writeEntity($eq, "equipment");
		}
		else {
			$eq = $eqData->findAllNoRef();
			$out = array();
			foreach($eq as $i => $e) {
				if($i == 0) continue;
				$e_out = $this->removeNumericKeys($e);
				array_push($out, $e_out);
			}
			$this->printEntities($out, "equipment");
		}
	}
	
	public function updateSong($id) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$_POST["status"] = $_POST["status"]["id"];
		$_POST["genre"] = $_POST["genre"]["id"];
		$repData->update($id, $_POST);
		echo '{"success": true}';
	}
	
	public function getSong($id) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$song = $repData->findByIdNoRef($id);
		$song = $this->removeNumericKeys($song);
		
		$song["composer"] = $repData->getComposerName($song["composer"]);
		
		$genre = $repData->getGenre($song["genre"]);
		$song["genre"] = $this->removeNumericKeys($genre[1]);
		
		$songstatus = $this->db->getRow("SELECT * FROM status WHERE id = " . intval($song["status"]));
		$song["status"] = $songstatus;
		
		$this->writeEntity($song, "song");
	}
	
	public function deleteSong($id) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$repData->delete($id);
		echo '{"success": true}';
	}
	
	public function getReservation($id) {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$entity = $calData->findByIdNoRef($id);
		// resolve contact and location
		$entity["contact"] = $calData->getContact($entity["contact"]);
		$entity["location"] = $calData->getLocation($entity["location"]);
		$this->removeNumericKeys($entity);
		$this->writeEntity($entity, "reservation");
	}
	
	public function getReservations() {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$entities = $calData->findAllNoRefWhere("begin >= NOW()");
		unset($entities[0]);
		$reservations = array();
		foreach($entities as $i => $entity) {
			$entity = $this->removeNumericKeys($entity);
			$entity["contact"] = $calData->getContact($entity["contact"]);
			$entity["contact"]["fullname"] = join(" ", array(
				$entity["contact"]["name"], $entity["contact"]["surname"]
			));
			$entity["location"] = $calData->getLocation($entity["location"]);
			array_push($reservations, $entity);
		}
		$this->printEntities($reservations, "reservation");
	}
	
	public function addReservation() {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$values = $_POST;
		$values["begin"] = Data::convertDateFromDb($values["begin"]);
		$values["end"] = Data::convertDateFromDb($values["end"]);
		echo $calData->create($values);
	}
	
	public function updateReservation($id) {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$calData->update($id, $_POST);
		echo '{"success": true}';
	}
	
	public function deleteReservation($id) {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$calData->delete($id);
		echo '{"success": true}';
	}
	
	public function addTask() {
		$taskData = new AufgabenData($GLOBALS["dir_prefix"]);
		$_SESSION["user"] = $this->uid;
		
		$values = $_POST;
		$values["due_at"] = Data::convertDateFromDb($values["due_at"]);
		$taskId = $taskData->create($values);
		
		unset($_SESSION["user"]);
		echo $taskId;
	}
	
	public function addContact() {
		$contactData = new KontakteData($GLOBALS["dir_prefix"]);
		$values = $_POST;
		if($values["birthday"] != "") {
			$values["birthday"] = Data::convertDateFromDb($values["birthday"]);
		}
		$cid = $contactData->create($values);
		echo $cid;
	}
	
	public function addLocation() {
		$locData = new LocationsData($GLOBALS["dir_prefix"]);
		$lid = $locData->create($_POST);
		echo $lid;
	}
	
	public function getInstruments() {
		$instrData = new InstrumenteData($GLOBALS["dir_prefix"]);
		$entities = $instrData->getInstrumentsWithCatName();
		unset($entities[0]);
		$instruments = array();
		foreach($entities as $i => $entity) {
			$entity = $this->removeNumericKeys($entity);
			array_push($instruments, $entity);
		}
		$this->printEntities($instruments, "instrument");
	}
	
	private function flattenAddresses($selection) {
		$addresses = array();
		for($i = 1; $i < count($selection); $i++) {
			$addy = $selection[$i]["email"];
			if($addy != "") array_push($addresses, $addy);
		}
		return $addresses;
	}
	
	/* array helpers */
	function isArrayAllKeyInt($InputArray) {
	    if(!is_array($InputArray)) {
	        return false;
	    }

	    if(count($InputArray) <= 0) {
	        return true;
	    }

	    return array_unique(array_map("is_int", array_keys($InputArray))) === array(true);
	}
	
	function removeNumericKeys($array) {
		$isArrayAllKeysInt = $this->isArrayAllKeyInt($array);
		foreach ($array as $key => $value) {
			if (is_numeric($key)  &&  $isArrayAllKeysInt == false) {
				unset($array[$key]);
			}
			if(is_array($value)) {
				$array[$key] = $this->removeNumericKeys($value);
			}
		}
		return $array;
	}
}


?>
