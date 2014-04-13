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
require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "startcontroller.php";
require_once $dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";

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
			header("HTTP/1.0 403 Permission Denied.");
			exit();
		}
		else {
			$pin = $_GET["pin"];

			$this->uid = $this->db->getCell($this->db->getUserTable(), "id", "pin = $pin");

			if($this->uid == null || $this->uid < 1) {
				header("HTTP/1.0 403 Permission Denied.");
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

		if($function == "getParticipation" || $function == "setParticipation") {
			if(!isset($_GET["rehearsal"])) {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			else if($function == "getParticipation") {
				$this->getParticipation($_GET["rehearsal"], $this->uid);
			}
			else if($function == "setParticipation") {
				if(!isset($_GET["participation"])) {
					header("HTTP/1.0 412 Insufficient Parameters.");
					exit();
				}
				$part = $_GET["participation"];
				if($part > 2 || $part < -1) {
					$part = -1;
				}
				$reason = "";
				if(isset($_GET["reason"])) {
					$reason = $_GET["reason"];
				}
				$this->setParticipation($_GET["rehearsal"], $this->uid, $part, $reason);
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
			if(!$this->sysdata->userHasPermission(5, $this->uid)) { // 6=Rehearsals
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
			
			$this->addRehearsal($begin, $end, $approve_until, $notes, $location);
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
			if(!isset($_GET["moduleId"]) || $_GET["moduleId"] == "") {
				header("HTTP/1.0 412 Insufficient Parameters.");
				exit();
			}
			$this->hasUserAccess($_GET["moduleId"]);
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
		$entities = $this->startdata->getUsersRehearsals($this->uid);
		$this->printEntities($entities, "rehearsal");
	}

	function getRehearsalsWithParticipation($user) {
		if($this->sysdata->isUserSuperUser($this->uid)
				|| $this->sysdata->isUserMemberGroup(1, $this->uid)) {
			$query = "SELECT * ";
			$query .= "FROM rehearsal r LEFT JOIN rehearsal_user ru ON ru.rehearsal = r.id ";
			$query .= "WHERE end > now() AND (ru.user = $user || ru.user IS NULL) ";
			$query .= "ORDER BY begin ASC";
			$rehs = $this->db->getSelection($query);
			$this->printEntities($rehs, "rehearsal");
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
					$part = array( "participate" => "", "reason" => "" );
				}
				$rehs[$i]["participate"] = $part["participate"];
				$rehs[$i]["reason"] = $part["reason"];
			}
				
			$this->printEntities($rehs, "rehearsal");
		}
	}

	function getConcerts() {
		$concerts = $this->startdata->getUsersConcerts($this->uid);
		$this->printEntities($concerts, "concert");
	}

	function getContacts() {
		$msd = new MitspielerData($GLOBALS["dir_prefix"]);
		$contacts = $msd->getMembers($this->uid);
		$this->printEntities($contacts, "contact");
	}

	function getLocations() {
		$locData = new LocationsData($GLOBALS["dir_prefix"]);
		$locs = $locData->findAllJoined(array(
				"address" => array("street", "city", "zip")
		));
		$this->printEntities($locs, "location");
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
		
		$this->printEntities($entities, "task");
	}
	
	function getNews() {
		$newsData = new NachrichtenData($GLOBALS["dir_prefix"]);
		echo $newsData->preparedContent();
	}
	
	function getVotes() {
		$votes = $this->startdata->getVotesForUser($this->uid);
		$this->printEntities($votes, "vote");
	}
	
	function getVoteOptions($vid) {
		$options = $this->startdata->getOptionsForVote($vid);
		$this->printEntities($options, "vote_option");
	}
	
	function getSongs() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$songs = $repData->findAllNoRef();
		
		$entities = array();
		array_push($entities, $songs[0]);
		
		foreach($songs as $i => $song) {
			if($i == 0) continue; // header
			
			// convert stirngs
			$song["notes"] = urlencode($song["notes"]);
			$song["title"] = urlencode($song["title"]);
				
			array_push($entities, $song);
		}
		
		$this->printEntities($entities, "song");
	}
	
	function getGenres() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$this->printEntities($repData->getGenres(), "genre");
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
		$this->getContacts(); echo $sep . "\n";
		$this->getLocations(); echo $sep . "\n";
		$this->getTasks(); echo $sep . "\n";
		$this->getVotes(); echo $sep . "\n";
		$this->getGenres(); echo $sep . "\n";
		$this->getStatuses(); echo $sep . "\n";
		
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

	function getParticipation($rid, $uid) {
		$_SESSION["user"] = $uid;
		$res = $this->startdata->doesParticipateInRehearsal($rid);
		unset($_SESSION["user"]);
		return $res;
	}

	function setParticipation($rid, $uid, $part, $reason) {
		$_GET["rid"] = $rid;
		$_SESSION["user"] = $uid;

		if($part == 1) {
			// participate
			$_GET["status"] = "yes";
		}
		elseif($part == 2) {
			// maybe participate
			$_POST["rehearsal"] = $rid;
			$_GET["status"] = "maybe";
		}
		else {
			// do not participate
			$_POST["rehearsal"] = $rid;
			$_GET["status"] = "no";
		}
		if($reason == "") {
			$_POST["explanation"] = "nicht angegeben";
		}
		else {
			$_POST["explanation"] = $reason;
		}
		$this->startdata->saveParticipation();
		unset($_SESSION["user"]);
		echo "true";
	}

	function taskCompleted($tid) {
		$this->startdata->taskComplete($tid);
	}
	
	function addSong($title, $length, $bpm, $music_key, $notes, $genre, $composer, $status) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		
		// semantic parameter mappings
		$values["title"] = $title;
		$values["length"] = $length;
		$values["bpm"] = $bpm == "" ? "0" : $bpm;
		$values["music_key"] = $music_key;
		$values["notes"] = urldecode($notes);
		$values["genre"] = $genre;
		$values["composer"] = $composer;
		$values["status"] = $status;
		
		echo $repData->create($values);
	}
	
	function addRehearsal($begin, $end, $approve_until, $notes, $location) {
		// semantic parameter mappings
		$values["begin"] = $begin;
		$values["end"] = $end;
		$values["approve_until"] = ($approve_until == "") ? $begin : $approve_until;
		$values["notes"] = $notes;
		$values["location"] = $location;
		
		// add rehearsal to default group
		$defaultGroup = $this->sysdata->getDynamicConfigParameter("default_contact_group");
		$values["group_" . $defaultGroup] = "on";
		$_POST["group_" . $defaultGroup] = "on";
		
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
	}
	
	function addComment($otype, $oid, $message) {
		// save comments
		echo $this->startdata->addComment($otype, $oid, $message, $this->uid);
		
		// notify contacts
		$startCtrl = new StartController();
		$startCtrl->setData($this->startdata);
		
		// $_POST["message"] is set from the interface
		// set $_GET array
		$_GET["oid"] = $_POST["oid"];
		$_GET["otype"] = $_POST["otype"];
		
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
			$pin = $this->db->getCell($this->db->getUserTable(), "pin", "id = " . $_SESSION["user"]);
			unset($_SESSION["user"]); // logout
			echo $pin;
		}
		else {
			header("HTTP/1.0 403 Permission Denied.");
			echo "Invalid Credentials.";
		}
	}
	
	function hasUserAccess($moduleId) {
		echo ($this->sysdata->userHasPermission($moduleId, $this->uid)) ? "true" : "false";
	}
	
	function getSongsToPractise($rid) {
		$probenData = new ProbenData($GLOBALS["dir_prefix"]);
		$songs = $probenData->getSongsForRehearsal($rid);
		$this->printEntities($songs, "song");
	}
}
?>