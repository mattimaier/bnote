<?php

/**
 * Data Access Class for Start data.
 * @author matti
 *
 */
class StartData extends AbstractLocationData {
	
	private $newsData;
	private $dir_prefix;
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->dir_prefix = $dir_prefix;
		
		$this->fields = array(
			"id" => array(Lang::txt("StartData_construct.id"), FieldType::INTEGER),
			"login" => array(Lang::txt("StartData_construct.login"), FieldType::CHAR),
			"password" => array(Lang::txt("StartData_construct.password"), FieldType::PASSWORD),
			"realname" => array(Lang::txt("StartData_construct.realname"), FieldType::CHAR),
			"lastlogin" => array(Lang::txt("StartData_construct.lastlogin"), FieldType::DATETIME)
		);
		
		$this->references = array();
		$this->table = "user";
		
		// includes
		require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "nachrichtendata.php");
		$this->newsData = new NachrichtenData($dir_prefix);
		
		$this->init($dir_prefix);
	}
	
	/**
	 * Checks whether the current user participates in a rehearsal.
	 * @param int $rid ID of the rehearsal.
	 * @return Integer 2 if the user maybe participated, 1 if the user participates, 0 if not, -1 if not chosen yet.
	 */
	function doesParticipateInRehearsal($rid) {
		$partQuery = "SELECT participate, reason FROM rehearsal_user WHERE user = ? AND rehearsal = ?";
		$res = $this->database->fetchRow($partQuery, array(array("i", $this->getUserId()), array("i", $rid)));
		if($res == NULL) {
			return array("participate" => -1, "reason" => "");
		}
		return $res;
	}
	
	/**
	 * Checks whether the current user participates in a concert.
	 * @param int $cid ID of the concert.
	 * @param int $uid Optional: user ID.
	 * @return Integer 1 if the user participates, 0 if not, -1 if not chosen yet.
	 */
	function doesParticipateInConcert($cid, $uid = -1) {
		if($uid == -1) {
			$uid = $this->getUserId();
		}
		$partQuery = "SELECT participate, reason FROM concert_user WHERE user = ? AND concert = ?";
		$res = $this->database->fetchRow($partQuery, array(array("i", $uid), array("i", $cid)));
		if($res == NULL) {
			return array("participate" => -1, "reason" => "");
		}
		return $res;
	}
	
	function saveParticipation($otype, $uid, $id, $participate, $reason) {
		if($uid == null) {
			$uid = $this->getUserId();
		}
		switch($otype) {
			case "R": $entity = "rehearsal"; break;
			case "C": $entity = "concert"; break;
			default:
				new BNoteError("Unknown entity for $otype");
		}
		$table = $entity . "_user"; // table name hardcoded, see switch above
		
		// remove
		$query = "DELETE FROM $table WHERE $entity = ? AND user = ?";
		$this->database->execute($query, array(array("i", $id), array("i", $uid)));
		
		// insert
		if($reason != null) {
			// save non-participation with reason
			$this->regex->isText($reason);
		}
		else {
			$reason = "";
		}
		$query = "INSERT INTO $table ($entity, user, participate, reason, replyon)";
		$query .= " VALUES (?, ?, ?, ?, NOW())";
		$this->database->prepStatement($query, array(
				array("i", $id), array("i", $uid), array("i", $participate), array("s", $reason)
		));
	}
	
	function getSongsForRehearsal($rid) {
		$query = "SELECT s.id, s.title, rs.notes ";
		$query .= "FROM rehearsal_song rs, song s ";
		$query .= "WHERE rs.song = s.id AND rs.rehearsal = ?";
		$selection = $this->database->getSelection($query, array(array("i", $rid)));
		return $this->urldecodeSelection($selection, array("title", "notes"));
	}
	
	function getRehearsalParticipants($rid) {
		$query = "SELECT c.name, c.surname, c.nickname, i.name as instrument, i.rank as instrumentrank ";
		$query .= "FROM rehearsal_user r, user u, contact c, instrument i ";
		$query .= "WHERE r.participate = 1 AND ";
		$query .= "r.rehearsal = ? AND r.user = u.id AND u.contact = c.id AND c.instrument = i.id ";
		$query .= "ORDER BY instrumentrank, name, surname";
		return $this->database->getSelection($query, array(array("i", $rid)));
	}
	
	function getConcertParticipants($cid) {
		$query = "SELECT c.name, c.surname, c.nickname, i.name as instrument ";
		$query .= "FROM concert_user r, user u, contact c, instrument i ";
		$query .= "WHERE r.participate = 1 AND ";
		$query .= "r.concert = ? AND r.user = u.id AND u.contact = c.id AND c.instrument = i.id ";
		$query .= "ORDER BY name, surname, instrument";
		return $this->database->getSelection($query, array(array("i", $cid)));
	}
	
	function getVotesForUser($uid = -1) {
		if($uid == -1) $uid = $this->getUserId();
		
		$query = "SELECT v.id, v.name, v.end, v.is_date, v.is_multi ";
		$query .= "FROM vote_group vg JOIN vote v ON vg.vote = v.id ";
		$query .= "WHERE vg.user = ? AND v.is_finished = 0 AND end > now() ";
		$query .= "ORDER BY v.end ASC";
		return $this->database->getSelection($query, array(array("i", $uid)));
	}
	
	function getVote($vid) {
		return $this->database->fetchRow("SELECT * FROM vote WHERE id = ?", array(array("i", $vid)));
	}
	
	function getOptionsForVote($vid) {
		$query = "SELECT * FROM vote_option WHERE vote = ? ORDER BY name, odate";
		return $this->database->getSelection($query, array(array("i", $vid)));
	}
	
	function canUserVote($vid, $uid = null) {
		if($uid == null) {
			$uid = $this->getUserId();
		}
		// security function
		$cq = "SELECT count(vote) as cnt FROM vote_group WHERE vote = ? AND user = ?";
		$c = $this->database->colValue($cq, "cnt", array(array("i", $vid), array("i", $uid)));
		return ($c == 1);
	}
	
	function saveVote($vid, $values, $user = -1) {
		$vote = $this->getVote($vid);
		if($user == -1) $user = $this->getUserId();
		
		// remove eventual old votes first
		$options = $this->getOptionsForVote($vid);
		$params = array();
		$tuples = array();
		for($i = 1; $i < count($options); $i++) {
			array_push($tuples, "vote_option = ?");
			array_push($params, array("i", $options[$i]["id"]));
		}
		$query = "DELETE FROM vote_option_user WHERE (" . join(" OR ", $tuples) . ") AND user = ?";
		array_push($params, array("i", $user));
		$this->database->execute($query, $params);
		
		if($vote["is_multi"] == 1) {
			$triples = array();
			$params2 = array();
			foreach($values as $optionId => $choice) {
				if($choice == "maybe") $choiceNo = 2;
				else if($choice == "no") $choiceNo = 0;
				else $choiceNo = 1; // yes
				array_push($triples, "(?, ?, ?)");
				array_push($params2, array("i", $optionId));
				array_push($params2, array("i", $user));
				array_push($params2, array("i", $choiceNo));
			}
			$query = "INSERT INTO vote_option_user (vote_option, user, choice) VALUES " . join(",", $triples);
 			$this->database->execute($query, $params2);
		}
		else {
			// single option only
			$query = "INSERT INTO vote_option_user (vote_option, user, choice) VALUES (?, ?, 1)";
			$this->database->execute($query, array(array("i", $values["uservote"]), array("i", $user)));
		}
	}
	
	function getNews() {
		return $this->newsData->preparedContent();
	}
	
	function taskComplete($tid) {
		$query = "UPDATE task SET is_complete = 1, completed_at = NOW() WHERE id = ?";
		$params = array(array("i", $tid));
		$this->database->execute($query, $params);
	}
	
	function getUsersRehearsals($uid = -1) {
		$data = $this->adp()->getFutureRehearsals(true);
		
		// super users should see it all
		if($this->getSysdata()->isUserSuperUser($uid)) {
			return $data;
		}
		
		// only show rehearsals of groups and rehearsal phases the user is in
		if($uid == -1) $uid = $this->getUserId();
		
		$usersPhases = $this->adp()->getUsersPhases($uid);
		$rehearsals = array_merge($this->getRehearsalsForUser($uid), $this->getRehearsalsForPhases($usersPhases));
		
		$result = array();
		$result[0] = $data[0]; // header
		
		// add rehearsals to resultset which the user can see
		foreach($data as $i => $row) {
			if($i == 0) continue; // skip header
			if(in_array($row["id"], $rehearsals)) {
				array_push($result, $row);
			}
		}
		
		return $result;		
	}
	
	private function getRehearsalsForUser($uid) {
		$query = "SELECT rehearsal 
					FROM rehearsal_contact rc 
						JOIN contact c ON rc.contact = c.id 
						JOIN user u ON u.contact = c.id 
					WHERE u.id = ?";
		$sel = $this->database->getSelection($query, array(array("i", $uid)));
		return Database::flattenSelection($sel, "rehearsal");
	}
	
	private function getRehearsalsForPhases($phases) {
		if(count($phases) == 0) return array();
		
		$params = array();
		$whereQ = array();
		foreach($phases as $p) {
			array_push($whereQ, "rehearsalphase = ?");
			array_push($params, array("i", $p));
		}
		$query = "SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE " . join(" OR ", $whereQ);
		$sel = $this->database->getSelection($query, $params);
		return Database::flattenSelection($sel, "rehearsal");
	}
	
	function getUsersConcerts($uid = -1) {
		if($uid == -1) $uid = $this->getUserId();
		return $this->adp()->getFutureConcerts($uid);
	}
	
	function getProgramTitles($pid) {
		$query = "SELECT ps.rank, s.title, c.name as composer, s.notes
				FROM program_song ps
				JOIN song s ON ps.song = s.id
				LEFT OUTER JOIN composer c ON s.composer = c.id
			WHERE ps.program = ?
			ORDER BY ps.rank ASC";
		$selection = $this->database->getSelection($query, array(array("i", $pid)));
		return $this->urldecodeSelection($selection, array("title", "notes"));
	}
	
	function getRehearsal($rid) {
		$query = "SELECT *, r.notes FROM rehearsal r 
					JOIN location l ON r.location = l.id
					JOIN address a ON l.address = a.id 
				   WHERE r.id = ?";
		return $this->database->fetchRow($query, array(array("i", $rid)));
	}
	
	function getConcert($cid) {
		return $this->database->fetchRow("SELECT * FROM concert WHERE id = ?", array(array("i", $cid)));
	}
	
	function getUserUpdates($objectListing) {
		// create appropriate where statement
		$params = array();
		$whereQ = array();
		
		// super users and administrators can see all updates
		if($this->getSysdata()->isUserSuperUser() || $this->getSysdata()->isUserMemberGroup(1)) {
			$where = "";
		}
		else {
			$where = "WHERE ";
			foreach($objectListing as $otype => $oids) {
				foreach($oids as $oid) {
					array_push($whereQ, "( otype = ? AND oid = ? )");
					array_push($params, array("s", $otype));
					array_push($params, array("i", $oid));
				}
			}
			if(count($whereQ) == 0) {
				// make sure if there are no objects, no updates are displayed
				$where = "false";
			}
			
		}
		
		$query = "SELECT * FROM comment $where " . join(" OR ", $whereQ);
		$query .= "ORDER BY created_at DESC LIMIT 0, ?";
		array_push($params, array("i", $this->getSysdata()->getDynamicConfigParameter("updates_show_max")));
		
		return $this->database->getSelection($query, $params);
	}
		
	function getContactsForObject($otype, $oid) {
		if($otype == "R") {
			require_once $this->dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "probendata.php";
			$probenData = new ProbenData($this->dir_prefix);
			return $probenData->getRehearsalContacts($oid);
		}
		else if($otype == "C") {
			require_once $this->dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "konzertedata.php";
			$konzerteData = new KonzerteData($this->dir_prefix);
			return $konzerteData->getConcertContacts($oid);
		}
		else if($otype == "V") {
			require_once $this->dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "abstimmungdata.php";
			$absData = new AbstimmungData($this->dir_prefix);
			$users = $absData->getGroup($oid);
			
			if(count($users) == 1) return null;
			
			$whereQ = array();
			$params = array();
			foreach($users as $i => $user) {
				if($i == 0) continue;
				$contact = $this->adp()->getUserContact($user["id"]);
				if($contact != null) {
					arary_push($whereQ, "id = ?");
					array_push($params, array("i", $contact));
				}
			}
			$query = "SELECT * FROM contact WHERE " . join(" OR ", $whereQ);
			return $this->database->getSelection($query, $params);
		}
		return null;
	}
	
	public function getObjectTitle($otype, $oid) {
		$objTitle = "";
		if($otype == "R") {
			$reh = $this->getRehearsal($oid);
			$objTitle = Lang::txt("StartData_getObjectTitle.Rehearsal") . " " . Data::convertDateFromDb($reh["begin"]);
		}
		else if($otype == "C") {
			$con = $this->getConcert($oid);
			$objTitle = Lang::txt("StartData_getObjectTitle.Concert") . " " . Data::convertDateFromDb($con["begin"]);
		}
		else if($otype == "V") {
			$vote = $this->getVote($oid);
			$objTitle = Lang::txt("StartData_getObjectTitle.Vote") . ": " . $vote["name"];
		}
		else if($otype == "T") {
			//NOTE: In case tasks can be commented as well, fix this
			$objTitle = Lang::txt("StartData_getObjectTitle.Task") . " " . $oid;
		}
		else if($otype == "B") {
			$rv = $this->getReservation($oid);
			$objTitle = Lang::txt("StartData_getObjectTitle.Reservation") . " " . Data::convertDateFromDb($rv["begin"]);
		}
		return $objTitle;
	}
	
	public function getSelectedOptionsForUser($optionId, $uid) {
		$choiceQuery = "SELECT choice FROM vote_option_user WHERE vote_option = ? AND user = ?";
		$choice = $this->database->colValue($choiceQuery, "choice", array(array("i", $optionId), array("i", $uid)));
		return $choice;
	}
	
	public function hasInactiveUsers() {
		$ct = $this->database->colValue("SELECT count(*) as cnt FROM user WHERE isActive = 0", "cnt", array());
		return ($ct > 0);
	}
	
	public function hasMembersWithoutRelations() {
		// check if a member has no concerts, no rehearsals, no phase and no vote
		$query = "SELECT count(*) as numNonIntegrated
					FROM contact c
					 LEFT JOIN (SELECT contact, count(*) as ct FROM concert_contact GROUP BY contact) as con ON c.id = con.contact
					 LEFT JOIN (SELECT contact, count(*) as ct FROM rehearsal_contact GROUP BY contact) as reh ON c.id = reh.contact
					 LEFT JOIN (SELECT contact, count(*) as ct FROM rehearsalphase_contact GROUP BY contact) as rph ON c.id = rph.contact
					 LEFT JOIN (SELECT contact, count(*) as ct FROM vote_group JOIN user ON vote_group.user = user.id GROUP BY user) as vot ON c.id = vot.contact
					WHERE con.ct IS NULL AND reh.ct IS NULL AND rph.ct IS NULL AND vot.ct IS NULL";
		$ct = $this->database->getSelection($query);
		return $ct[1]["numNonIntegrated"] > 0;
	}
	
	function hasReservations() {
		$res = $this->database->colValue("SELECT count(*) as cnt FROM reservation WHERE begin >= NOW()", "cnt", array());
		return ($res > 0);
	}
	
	function getReservations() {
		$query = "SELECT r.*, l.name as locationname 
				FROM reservation r JOIN location l ON r.location = l.id
				WHERE begin > NOW() 
				ORDER BY begin";
		return $this->database->getSelection($query);
	}
	
	function getReservation($id) {
		return $this->database->fetchRow("SELECT * FROM reservation WHERE id = ?", array(array("i", $id)));
	}
	
	function getOutfit($id) {
		return $this->database->fetchRow("SELECT * FROM outfit WHERE id = ?", array(array("i", $id)));
	}
	
	function getCustomData($otype, $oid) {
		// show only public fields
		$pubFields = $this->getCustomFields($otype, true);
		$pubTechNames = Database::flattenSelection($pubFields, "techname");
		$data = $this->getCustomFieldData($otype, $oid);
		$cleaned = array();
		foreach($data as $k => $v) {
			if(in_array($k, $pubTechNames)) {
				$cleaned[$k] = $v;
			}
		}
		return $cleaned;
	}
	
	function getAppointments($withCustomData = true) {
		// find all appointments where the user is in the group
		$cid = $this->adp()->getUserContact();
		$query = "SELECT a.*, l.name as locationname, addy.street, addy.zip, addy.city FROM appointment a ";
		$query .= "JOIN location l ON a.location = l.id ";
		$query .= "JOIN address addy ON l.address = addy.id ";
		$query .= "JOIN appointment_group ag ON a.id = ag.appointment ";
		$query .= "JOIN contact_group cg ON ag.group = cg.group ";
		$query .= "WHERE cg.contact = ? AND a.end > NOW()";
		$query .= "ORDER BY a.begin, a.end";
		
		// add custom data
		$appointments = $this->database->getSelection($query, array(array("i", $cid)));
		if($withCustomData) {
			$this->appendCustomDataToSelection('a', $appointments);
		}
		
		return $appointments;
	}
	
	function getAppointment($id) {
		// find all appointments where the user is in the group
		$cid = $this->adp()->getUserContact();
		$query = "SELECT a.*, l.name as locationname, addy.street, addy.zip, addy.city FROM appointment a ";
		$query .= "JOIN location l ON a.location = l.id ";
		$query .= "JOIN address addy ON l.address = addy.id ";
		$query .= "JOIN appointment_group ag ON a.id = ag.appointment ";
		$query .= "JOIN contact_group cg ON ag.group = cg.group ";
		$query .= "WHERE a.id = ? AND cg.contact = ? AND a.end > NOW() ";
		$query .= "ORDER BY a.begin, a.end";
		
		// add custom data
		$appointments = $this->database->getSelection($query, array(array("i", $id), array("i", $cid)));
		$this->appendCustomDataToSelection('a', $appointments);
		
		return $appointments[1]; // ignore header and there can only be this ID just once
	}
	
	function hasAppointments() {
		// to make sure we have the permission included just load 'em
		return count($this->getAppointments(false)) > 0;
	}
	
	function getInboxItems() {
		$items = array();
		
		// rehearsals
		$rehearsals = $this->getUsersRehearsals();
		
		for($i = 1; $i < count($rehearsals); $i++) {
			$r = $rehearsals[$i];
			$previewItems = array();
			if(isset($r["groups"])) {
				$groupPreview = array();
				foreach($r["groups"] as $group) {
					array_push($groupPreview, $group["name"]);
				}
				array_push($previewItems, join("|", $groupPreview));
			}
			array_push($previewItems, $r["name"]);
			if($r["conductor"] > 0) array_push($previewItems, $this->adp()->getConductorname($r["conductor"]));
			
			array_push($items, array(
					"otype" => "R",
					"oid" => $r["id"],
					"title" => Lang::txt("StartData_inboxItems.rehearsalOn") . " " . Data::convertDateFromDb($r["begin"]),
					"preview" => join(", ", $previewItems),
					"due" => Data::convertDateFromDb($r["approve_until"]),
					"eventBegin" => $r["begin"],
					"replyUntil" => $r["approve_until"],
					"participation" => $this->doesParticipateInRehearsal($r["id"])["participate"],
					"status" => $r["status"]
			));
		}
		
		// concerts
		$concerts = $this->getUsersConcerts();
		for($i = 1; $i < count($concerts); $i++) {
			$c = $concerts[$i];
			array_push($items, array(
					"otype" => "C",
					"oid" => $c["id"],
					"title" => Lang::txt("StartData_inboxItems.concertOn") . " " . Data::convertDateFromDb($c["begin"]),
					"preview" => $c["title"] . ", " . $c["location_name"],
					"due" => Data::convertDateFromDb($c["approve_until"]),
					"eventBegin" => $c["begin"],
					"replyUntil" => $c["approve_until"],
					"participation" => $this->doesParticipateInConcert($c["id"])["participate"],
					"status" => $c["status"]
			));
		}
		
		// appointments
		$appointments = $this->getAppointments();
		for($i = 1; $i < count($appointments); $i++) {
			$a = $appointments[$i];
			array_push($items, array(
					"otype" => "A",
					"oid" => $a["id"],
					"title" => Lang::txt("StartData_inboxItems.appointmentOn") . " " . Data::convertDateFromDb($a["begin"]),
					"preview" => $a["name"] . ", " . $a["locationname"],
					"due" => NULL,
					"eventBegin" => $a["begin"],
					"replyUntil" => $a["begin"]
			));
		}
		
		// reservations
		$reservations = $this->getReservations();
		for($i = 1; $i < count($reservations); $i++) {
			$r = $reservations[$i];
			array_push($items, array(
					"otype" => "B",
					"oid" => $r["id"],
					"title" => Lang::txt("StartData_inboxItems.reservationOn") . " " . Data::convertDateFromDb($r["begin"]),
					"preview" => $r["name"] . ", " . $r["locationname"],
					"due" => NULL,
					"eventBegin" => $r["begin"],
					"replyUntil" => $r["begin"]
			));
		}
		
		// votes
		$votes = $this->getVotesForUser();
		for($i = 1; $i < count($votes); $i++) {
			$v = $votes[$i];
			array_push($items, array(
					"otype" => "V",
					"oid" => $v["id"],
					"title" => $v["name"],
					"preview" => Lang::txt("vote"),
					"due" => Data::convertDateFromDb($v["end"]),
					"eventBegin" => $v["end"],
					"replyUntil" => $v["end"]
			));
		}
		
		// tasks
		$tasks = $this->adp()->getUserTasks();
		for($i = 1; $i < count($tasks); $i++) {
			$t = $tasks[$i];
			array_push($items, array(
					"otype" => "T",
					"oid" => $t["id"],
					"title" => $t["title"],
					"preview" => substr($t["description"], 0, 50),
					"due" => Data::convertDateFromDb($t["due_at"]),
					"eventBegin" => $t["created_at"],
					"replyUntil" => $t["due_at"]
			));
		}
		
		return $items;
	}
	
	function getTask($taskId) {
		return $this->database->fetchRow("SELECT * FROM task WHERE id = ?", array(array("i", $taskId)));
	}
}