<?php


/**
 * Abstract Implementation of BNote Application Interface
 * @author Matti
 *
 */
class BNoteApiImpl implements BNoteApiInterface {

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
	 * User ID, by default use the current user
	 * @var Integer
	 */
	protected $uid = -1;

	function __construct() {
		$this->sysdata = new Systemdata($GLOBALS["dir_prefix"]);
		$this->db = $this->sysdata->dbcon;
		$GLOBALS["system_data"] = $this->sysdata;
		global $dir_prefix;
		$this->startdata = new StartData($dir_prefix);
	}
	
	function getSysdata() {
		return $this->sysdata;
	}
	
	function getRehearsals() {
		return $this->getRehearsalsWithParticipation($this->uid);
	}

	function getRehearsalsWithParticipation($user) {
		if($this->sysdata->isUserSuperUser($this->uid)
				|| $this->sysdata->isUserMemberGroup(1, $this->uid)) {
			$query = "SELECT * 
						FROM rehearsal r LEFT JOIN rehearsal_user ru ON ru.rehearsal = r.id
						WHERE end > now() AND (ru.user = ? || ru.user IS NULL)
						ORDER BY begin ASC";
			$rehs = $this->db->getSelection($query, array(array("i", $user)));
		}
		else {
			// only get rehearsals for user considering phases and groups
			$rehs = $this->startdata->getUsersRehearsals($this->uid);
				
			// manually join participation
			array_push($rehs[0], "participate");
			array_push($rehs[0], "reason");
				
			for($i = 1; $i < count($rehs); $i++) {
				$rid = $rehs[$i]["id"];
				$query = "SELECT * FROM rehearsal_user WHERE rehearsal = $rid AND user = ?";
				$part = $this->db->fetchRow($query, array(array("i", $this->uid)));
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
			$query .= "WHERE location.id = ?";
			$rehs[$i]["location"] = $this->db->fetchRow($query, array(array("i", $rehs[$i]["location"])));
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
					$comments[$j]["author"] = array("id" => $comment["author_id"], "fullname" => $comment["author"]);
					unset($comments[$j]["author_id"]);
										$comments[$j]["message"] = urldecode($comments[$j]["message"]);
				}				
				$rehs[$i]["comments"] = array_values($this->removeNumericKeys($comments));
			
		}

		// add  participants
		foreach($rehs as $i => $rehearsal) {
			$query = "SELECT c.id, c.surname, c.name, ru.participate, ru.reason";
			$query .= " FROM rehearsal_user ru, user u, contact c";
			$query .= " WHERE ru.rehearsal = ? AND ru.user = u.id AND u.contact = c.id" ;
			$contacts = $this->db->getSelection($query, array(array("i",  $rehearsal["id"])));
			unset($contacts[0]);

			// ids for filterting contacts without response
			$contactIDs = array();
			$participantsNo = array();
			$participantsYes = array();
			$participantsMaybe = array();
						
			foreach($contacts as $j => $contact) 
			{
				foreach(array_keys($contact) as $ck) {
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
			$query .= " WHERE rc.rehearsal = ? AND rc.contact NOT IN (" . $contactIDsString .")";  // safe statement - IDs as INT from DB
			$participantsNoResponse = $this->db->getSelection($query, array(array("i", $rehearsal["id"])));
			unset($participantsNoResponse[0]);
			
			foreach($participantsNoResponse as $j => $contact) {
				foreach(array_keys($contact) as $ck) {
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
			foreach(array_keys($rehearsal) as $k) {
				if(is_numeric($k) || $k == "rehearsal" || $k == "user") {
					unset($rehs[$i][$k]);
				}
			}
		}
		
		return $rehs;
	}
	
	function getConcerts() {
		$concerts = $this->startdata->getUsersConcerts($this->uid);
		
		// remove header
		unset($concerts[0]);
		
		// enrichment of objects
		foreach($concerts as $i => $concert) {
			$dbConcert = $this->db->fetchRow("SELECT * FROM concert WHERE id = ?", array(array("i", $concert["id"])));
			
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
				$q = "SELECT reason FROM concert_user WHERE concert = ? AND user = ?";
				$concerts[$i]["reason"] = $this->db->colValue($q, "reason", array(array("i", $concert["id"]), array("i", $this->uid)));
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
					$comments[$j]["author"] = array("id" => $comment["author_id"], "fullname" => $comment["author"]);
					unset($comments[$j]["author_id"]);
					$comments[$j]["message"] = urldecode($comment["message"]);
				}				
				$concerts[$i]["comments"] = array_values($this->removeNumericKeys($comments));
		}
		
		
		// add  participants
		foreach($concerts as $i => $concert) {
			$query = "SELECT c.id, c.surname, c.name, cu.participate, cu.reason
						FROM concert_user cu, user u, contact c
						WHERE cu.concert = ? AND cu.user = u.id AND u.contact = c.id" ;
			$contacts = $this->db->getSelection($query, array(array("i", $concert["id"])));
			unset($contacts[0]);

			// ids for filterting contacts without response
			$contactIDs = array();
			$participantsNo = array();
			$participantsYes = array();
			$participantsMaybe = array();
						
			foreach($contacts as $j => $contact) 
			{
				foreach(array_keys($contact) as $ck) {
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
			$contactIDsString = join(',', $contactIDs);  
			
			$query = "SELECT c.id, c.surname, c.name";
			$query .= " FROM concert_contact cc JOIN contact c ON cc.contact = c.id";
			$query .= " WHERE cc.concert = ? AND cc.contact NOT IN (" . $contactIDsString .")";  // statement safe - ids as int from DB
			$participantsNoResponse = $this->db->getSelection($query, array(array("i", $concert["id"])));
			unset($participantsNoResponse[0]);
			
			foreach($participantsNoResponse as $j => $contact) 
			{
				foreach(array_keys($contact) as $ck) {
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
		return $concerts;
	}
	
	function getContacts() {
		$contactData = new KontakteData($GLOBALS["dir_prefix"]);
		$_SESSION["user"] = $this->uid;
		$entities = $contactData->getAllContacts();
		unset($_SESSION["user"]);
		unset($entities[0]);
		$allContacts = array();
		foreach($entities as $entity) {
			$entity = $this->removeNumericKeys($entity);
			$entity["fullname"] = join(' ', array($entity["name"], $entity["surname"]));
			$groups = $contactData->getContactFullGroups($entity["id"]);
			unset($groups[0]);
			$grps = array();
			foreach($groups as $grp) {
				array_push($grps, $this->removeNumericKeys($grp));
			}
			$entity["groups"] = $grps;
			array_push($allContacts, $entity);
		}
		return $allContacts;
	}
	
	function getContact() {
		$contactData = new KontakteData($GLOBALS["dir_prefix"]);
		$contact = $contactData->getSysdata()->getUsersContact($this->uid);
		$contact["address_object"] = $contactData->getAddress($contact["address"]);
		$contact["instrument_object"] = $this->db->fetchRow("SELECT * FROM instrument WHERE id = ?", array(array("i", $contact["instrument"])));
		return $contact;
	}
	
	function getMembers() {
		if(!$this->sysdata->userHasPermission($this->sysdata->getModuleId("Mitspieler"))) {
			$contacts = array();
		}
		else {
			$msd = new MitspielerData($GLOBALS["dir_prefix"]);
			$contacts = $msd->getMembers();
			unset($contacts[0]);  // header
			$contacts = $this->removeNumericKeys($contacts);
		}
		return $contacts;
	}

	function getLocations() {
		$locData = new LocationsData($GLOBALS["dir_prefix"]);
		$locs = $locData->findAllJoined(array(
			"address" => array("street", "city", "zip")
		));
		unset($locs[0]);  // header
		return $locs;
	}

	function getTasks() {
		$tasks = $this->startdata->adp()->getUserTasks($this->uid);
		unset($tasks[0]);  // header
        $this->removeNumericKeys($tasks);
		return $tasks;
	}
	
	function getNews() {
		$newsData = new NachrichtenData($GLOBALS["dir_prefix"]);
		return array("news" => $newsData->preparedContent());
	}
	
	function getVotes() {
		$votes = $this->startdata->getVotesForUser($this->uid);
		unset($votes[0]); // remove header
		foreach($votes as $i => $vote) {
			// remove numeric fields
			foreach(array_keys($vote) as $k) {
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
		
		return $votes;
	}
		
	function getVoteOptions($vid) {
		$options = $this->startdata->getOptionsForVote($vid);
		unset($options[0]);
		return $options;
	}
	
	function getSongs() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$songs = $repData->findAllNoRef();
				
		$entities = array();
		
		foreach($songs as $i => $song) {
			if($i == 0) continue; // header
			$composerId = $song["composer"];
			$song["composer"] = $repData->getComposerName($composerId);
			$song["title"] = urldecode($song["title"]);
			$song["notes"] = urldecode($song["notes"]);
			$song["genre"] = "";
			if(array_key_exists("genre", $song)) {
				$genre = $repData->getGenre($song["genre"]);
				if($genre != null && count($genre) > 1) {
					$song["genre"] = $this->removeNumericKeys($genre[1]);
				}
			}
			
			$songstatus = $this->db->fetchRow("SELECT * FROM status WHERE id = ?", array(array("i", intval($song["status"]))));
			$song["status"] = $songstatus;
			$song = $this -> removeNumericKeys($song);
			array_push($entities, $song);
		}
		
		return $entities;
	}
	
	function getGenres() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$genres = $repData->getGenres();
		unset($genres[0]);
		$genres =  $this -> removeNumericKeys($genres);
		return $genres;
	}
	
	function getStatuses() {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$statuses = $repData->getStatuses();
		unset($statuses[0]);
		return $statuses;
	}
	
	function getComments($otype, $oid) {
		$comments = $this->startdata->getDiscussion($otype, $oid);
		unset($comments[0]);
		return $comments;
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
		return array("success" => True);
	}

	function taskCompleted($tid) {
		$this->startdata->taskComplete($tid);
		return array("success" => True);
	}
	
	function addSong($title, $length, $bpm, $music_key, $notes, $genre, $composer, $status) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		
		// semantic parameter mappings
		$values["title"] = urlencode($title);
		$values["length"] = $length;
		$values["bpm"] = $bpm == "" ? "0" : $bpm;
		$values["music_key"] = $music_key;
		$values["notes"] = urlencode($notes);
		$values["genre"] = $genre["id"];
		$values["composer"] = $composer;
		$values["status"] = $status["id"];
		
		return $repData->create($values);
	}
	
	function addRehearsal($begin, $end, $approve_until, $notes, $location, $groups) {
		// semantic parameter mappings
		$values["begin"] = $begin;
		$values["end"] = $end;
		$values["approve_until"] = ($approve_until == "") ? $begin : $approve_until;
		$values["notes"] = $notes;
		$values["location"] = $location;
		
		if($groups == null || count($groups) == 0) {
			// add rehearsal to default group
			$defaultGroup = $this->sysdata->getDynamicConfigParameter("default_contact_group");
			$values["group_" . $defaultGroup] = "on";
			$_POST["group_" . $defaultGroup] = "on";
		}
		else {
			foreach($groups as $grp) {
				$values["group_" . $grp] = "on";
				$_POST["group_" . $grp] = "on";
			}
		}
		
		// create rehearsal
		require_once $GLOBALS["DIR_WIDGETS"] . "iwriteable.php";
		require_once $GLOBALS["DIR_WIDGETS"] . "groupselector.php";
		$rehData = new ProbenData($GLOBALS["dir_prefix"]);
		return $rehData->create($values);
	}
	
	function vote($vid, $options) {
		$vote = $this->startdata->getVote($vid);
		if($vote["is_multi"] != "1") {
			// single option vote
			$firstOption = "";
			foreach(array_keys($options) as $optionId) {
				$firstOption = $optionId;
				break;
			}
			$options["uservote"] = $firstOption;
		}
		$this->startdata->saveVote($vid, $options, $this->uid);
		return array("success" => True);
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
		$startCtrl->notifyContactsOnComment($this->uid);
		return $response;
	}
	
	function getVersion() {
		return array("version" => $this->sysdata->getVersion());
	}
	
	function getUserInfo() {	
		$contact = $this->sysdata->getUsersContact($this->uid);
		$instrument = $this->db->colValue("SELECT name FROM instrument WHERE id = ?", "name", array(array("i", $contact["instrument"])));
		$addy = $this->startdata->adp()->getAddress($contact["address"]);
		$contact["instrument"] = $instrument;
		$contact["street"] = $addy["street"];
		$contact["zip"] = $addy["zip"];
		$contact["city"] = $addy["city"];
		unset($contact["address"]);
		unset($contact["status"]); // not existent anymore
		
		return $contact;
	}
	
	function hasUserAccess() {
		if(!isset($_GET["moduleId"]) || $_GET["moduleId"] == "") {
			$arr = $this->sysdata->getUserModulePermissions($this->uid);
			return $arr;
		}
		else {
			$res = $this->sysdata->userHasPermission($_GET["moduleId"], $this->uid);
			return array("access" => $res);
		}
	}
	
	function getSongsToPractise($rid) {
		$probenData = new ProbenData($GLOBALS["dir_prefix"]);
		return $probenData->getSongsForRehearsal($rid);
	}
	
	function getGroups() {
		$selection = $this->startdata->adp()->getGroups();
		unset($selection[0]);  // header
		return $selection;
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
			
			$query = "SELECT choice, count(*) as num FROM vote_option_user
						WHERE vote_option = ?
						GROUP BY choice";
			$choice = $this->db->getSelection($query, array(array("i", $opt["id"])));
			
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
		
		return $vote;
	}
		
	function setConcertParticipation($cid, $uid, $part, $reason) {
		$this->startdata->saveParticipation("concert", $uid, $cid, $part, $reason);
		return array("success" => True);
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
			return $con;
		}
		else {
			http_response_code(500);
			return array("success" => False, "message" => "Error: Cannot create concert.");
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
		
		if($groups != null && $groups != "" && count($groups) > 0) {
			foreach($groups as $grp) {
				$values["group_" . $grp] = "on";
				$_POST["group_" . $grp] = "on";
			}
		}
		
		// create rehearsal
		require_once $GLOBALS["DIR_WIDGETS"] . "iwriteable.php";
		require_once $GLOBALS["DIR_WIDGETS"] . "groupselector.php";
		$rehData = new ProbenData($GLOBALS["dir_prefix"]);
		$rehData->update($id, $values);
		
		// return updated entry
		return $rehData->findByIdNoRef($id);
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
		return $con;
	}
	
	function deleteRehearsal($id) {
		$rehData = new ProbenData($GLOBALS["dir_prefix"]);
		$rehData->delete($id);
		return array("success" => True);
	}
	
	function deleteConcert($id) {
		$conData = new KonzerteData($GLOBALS["dir_prefix"]);
		$conData->delete($id);
		return array("success" => True);
	}
	
	function sendMail($subject, $body, $groups) {
		// fetch addresses
		if($groups == null || $groups == "" || count($groups) == 0) {
			http_response_code(400);
			return array("success" => False, "message" => "Error: no groups.");
		}
		
		$whereQ = array();
		$params = array();
		foreach($groups as $group) {
			array_push($whereQ, "cg.group = ?");
			array_push($params, $group);
		}
		$query = "SELECT DISTINCT c.email ";
		$query .= "FROM contact c JOIN contact_group cg ON cg.contact = c.id ";
		$query .= "WHERE " . join(" OR ", $whereQ);
		$mailaddies = $this->db->getSelection($query, $params);
		$addresses = $this->flattenAddresses($mailaddies);
		
		if($addresses == null || count($addresses) == 0) {
			new BNoteError(Lang::txt("AbstractBNA_sendMail.error"));
		}
		
		$mail = new Mailing($subject, "");
		$mail->setBodyInHtml($body);
		$mail->setFromUser($this->uid);
		$mail->setBcc($addresses);
		
		if(!$mail->sendMail()) {
			http_response_code(400);
			return array("success" => False, "message" => "Error: Cannot send mail.");
		}
		else {
			return array("success" => True);
		}
	}
	
	public function addEquipment() {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		unset($_POST["id"]);
		return $eqData->create($_POST);
	}
	
	public function updateEquipment($id) {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		$eqData->update($id, $_POST);
		return array("success" => True);
	}
	
	public function deleteEquipment($id) {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		$eqData->delete($id);
		return array("success" => True);
	}
	
	public function getEquipment() {
		$eqData = new EquipmentData($GLOBALS["dir_prefix"]);
		if(isset($_GET["id"]) && $_GET["id"] != "") {
			$eq = $eqData->findByIdNoRef($_GET["id"]);
			return $eq;
		}
		else {
			$eq = $eqData->findAllNoRef();
			$out = array();
			foreach($eq as $i => $e) {
				if($i == 0) continue;
				$e_out = $this->removeNumericKeys($e);
				array_push($out, $e_out);
			}
			return $out;
		}
	}
	
	public function updateSong($id) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$_POST["status"] = $_POST["status"]["id"];
		$_POST["genre"] = $_POST["genre"]["id"];
		$repData->update($id, $_POST);
		return array("success" => True);
	}
	
	public function getSong($id) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$song = $repData->findByIdNoRef($id);
		$song = $this->removeNumericKeys($song);
		
		$song["title"] = urldecode($song["title"]);
		$song["notes"] = urldecode($song["notes"]);
		$song["composer"] = $repData->getComposerName($song["composer"]);
		
		$genre = $repData->getGenre($song["genre"]);
		$song["genre"] = $this->removeNumericKeys($genre[1]);
		
		$songstatus = $this->db->fetchRow("SELECT * FROM status WHERE id = ?", array(array("i", intval($song["status"]))));
		$song["status"] = $songstatus;
		
		return $song;
	}
	
	public function deleteSong($id) {
		$repData = new RepertoireData($GLOBALS["dir_prefix"]);
		$repData->delete($id);
		return array("success" => True);
	}
	
	public function getReservation($id) {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$entity = $calData->findByIdNoRef($id);
		// resolve contact and location
		$entity["contact"] = $calData->getContact($entity["contact"]);
		$entity["location"] = $calData->getLocation($entity["location"]);
		$this->removeNumericKeys($entity);
		return $entity;
	}
	
	public function getReservations() {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$entities = $calData->findAllNoRefWhere("end >= NOW()");
		unset($entities[0]);
		$reservations = array();
		foreach($entities as $entity) {
			$entity = $this->removeNumericKeys($entity);
			$entity["contact"] = $calData->getContact($entity["contact"]);
			$entity["contact"]["fullname"] = join(" ", array(
				$entity["contact"]["name"], $entity["contact"]["surname"]
			));
			$entity["location"] = $calData->getLocation($entity["location"]);
			array_push($reservations, $entity);
		}
		return $reservations;
	}
	
	public function addReservation() {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$values = $_POST;
		$values["begin"] = $values["begin"];
		$values["end"] = $values["end"];
		return $calData->create($values);
	}
	
	public function updateReservation($id) {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$calData->update($id, $_POST);
		return array("success" => True);
	}
	
	public function deleteReservation($id) {
		$calData = new CalendarData($GLOBALS["dir_prefix"]);
		$calData->delete($id);
		return array("success" => True);
	}
	
	public function addTask() {
		$taskData = new AufgabenData($GLOBALS["dir_prefix"]);
		$_SESSION["user"] = $this->uid;
		$taskId = $taskData->create($_POST);
		
		unset($_SESSION["user"]);
		return $taskData->findByIdNoRef($taskId);
	}
	
	public function addContact() {
		$contactData = new KontakteData($GLOBALS["dir_prefix"]);
		$cid = $contactData->create($_POST);
		return $contactData->findByIdNoRef($cid);
	}
	
	public function updateContact() {
		$contactData = new KontakteData($GLOBALS["dir_prefix"]);
		$_SESSION["user"] = $this->uid;
		$userContact = $this->sysdata->getUsersContact();
		$cid = $userContact["id"];
		
		// don't touch groups or custom fields
		$contactData->update_address($cid, $_POST["address_object"]);
		$contactData->update($cid, $_POST, true);
		unset($_SESSION["user"]);
		
		return array("success" => true);
	}
	
	public function addLocation() {
		$locData = new LocationsData($GLOBALS["dir_prefix"]);
		return $locData->create($_POST);
	}
	
	public function getInstruments() {
		$instrData = new InstrumenteData($GLOBALS["dir_prefix"]);
		$entities = $instrData->getInstrumentsWithCatName();
		unset($entities[0]);
		$instruments = array();
		foreach($entities as $entity) {
			$entity = $this->removeNumericKeys($entity);
			array_push($instruments, $entity);
		}
		return $instruments;
	}
	
	public function signup() {
		$cfgRegistration = $this->sysdata->getDynamicConfigParameter("user_registration");
		if($cfgRegistration == "1") {
			$loginCtrl = new LoginController();
			$loginCtrl->setData(new LoginData($GLOBALS["dir_prefix"]));
			return $loginCtrl->register(false);
		}
		else {
			http_response_code(400);
			return array("success" => False, "message" => "Feature disabled by configuration");
		}
	}
	
	public function getProgram($id) {
		$programData = new ProgramData($GLOBALS["dir_prefix"]);
		$program = $programData->findByIdNoRef($id);
		$songs = $programData->getSongsForProgram($id);
		unset($songs[0]);
		$program["songs"] = $songs;
		return $program;
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
	
	function isMaybeEnabled() {
		$on = $this->sysdata->getDynamicConfigParameter("allow_participation_maybe") == 1;
		return array("isMaybeEnabled" => $on);
	}
}

?>