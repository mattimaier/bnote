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
	 * @return 2 if the user maybe participated, 1 if the user participates, 0 if not, -1 if not chosen yet.
	 */
	function doesParticipateInRehearsal($rid) {
		$partQuery = "SELECT participate FROM rehearsal_user WHERE user = ? AND rehearsal = ?";
		$part = $this->database->colValue($partQuery, "participate", array(array("i", $_SESSION["user"]), array("i", $rid)));
		if($part == "0" || $part == "1" || $part == "2") {
			return $part;
		}
		else return -1;
	}
	
	/**
	 * Checks whether the current user participates in a concert.
	 * @param int $cid ID of the concert.
	 * @param int $uid Optional: user ID.
	 * @return 1 if the user participates, 0 if not, -1 if not chosen yet.
	 */
	function doesParticipateInConcert($cid, $uid = -1) {
		if($uid == -1) {
			$uid = $_SESSION["user"];
		}
		$partQuery = "SELECT participate FROM concert_user WHERE user = ? AND concert = ?";
		$part = $this->database->colValue($partQuery, "participate", array(array("i", $uid), array("i", $cid)));
		if($part == "1") return 1;
		else if($part == "0") return 0;
		else if($part == "2") return 2;
		else return -1;
	}
	
	function saveParticipation($entity, $uid, $id, $participate, $reason) {
		if($uid == null) {
			$uid = $_SESSION["user"];
		}
		$table = $entity . "_user";
		
		// remove
		$query = "DELETE FROM $table WHERE $entity = $id AND user = $uid";
		$this->database->execute($query);
		
		// insert
		if($reason != null && isset($_POST["explanation"])) {
			// save non-participation with reason
			$this->regex->isText($_POST["explanation"]);
		}
		else {
			$reason = "";
		}
		$query = "INSERT INTO $table ($entity, user, participate, reason)";
		$query .= " VALUES ($id, $uid, $participate, \"$reason\")";
		$this->database->execute($query);
	}
	
	function getSongsForRehearsal($rid) {
		$query = "SELECT s.id, s.title, rs.notes ";
		$query .= "FROM rehearsal_song rs, song s ";
		$query .= "WHERE rs.song = s.id AND rs.rehearsal = ?";
		$selection = $this->database->getSelection($query, array(array("i", $rid)));
		return $this->urldecodeSelection($selection, array("title", "notes"));
	}
	
	function getRehearsalParticipants($rid) {
		$query = "SELECT c.name, c.surname, c.nickname, i.name as instrument ";
		$query .= "FROM rehearsal_user r, user u, contact c, instrument i ";
		$query .= "WHERE r.participate = 1 AND ";
		$query .= "r.rehearsal = ? AND r.user = u.id AND u.contact = c.id AND c.instrument = i.id ";
		$query .= "ORDER BY name, surname, instrument";
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
		if($uid == -1) $uid = $_SESSION["user"];
		
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
			$uid = $_SESSION["user"];
		}
		// security function
		$cq = "SELECT count(vote) as cnt FROM vote_group WHERE vote = ? AND user = ?";
		$c = $this->database->colValue($cq, "cnt", array(array("i", $vid), array("i", $uid)));
		return ($c == 1);
	}
	
	function saveVote($vid, $values, $user = -1) {
		$vote = $this->getVote($vid);
		if($user == -1) $user = $_SESSION["user"];
		
		// remove eventual old votes first
		$options = $this->getOptionsForVote($vid);
		$query = "DELETE FROM vote_option_user WHERE (";
		for($i = 1; $i < count($options); $i++) {
			if($i > 1) $query .= " OR ";
			$query .= " vote_option = " . $options[$i]["id"];
		}
		$query .= ") AND user = $user";
		$this->database->execute($query);
		
		if($vote["is_multi"] == 1) {
			// mutiple options choosable
			$maybeOn = ($this->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") == 1);
			$query = "INSERT INTO vote_option_user (vote_option, user, choice) VALUES ";
			$c = 0;
			foreach($values as $optionId => $choice) {
				if($c > 0) $query .= ",";
				if($maybeOn) {
					$query .= "($optionId, $user, $choice)";
				}
				else {
					$query .= "($optionId, $user, 1)";
				}
				$c++;
			}
 			$this->database->execute($query);
		}
		else {
			// single option only
			$query = "INSERT INTO vote_option_user (vote_option, user, choice) VALUES ";
			$query .= "(" . $values["uservote"] . ", $user, 1)";
			$this->database->execute($query);
		}
	}
	
	function getNews() {
		return $this->newsData->preparedContent();
	}
	
	function taskComplete($tid) {
		$date = date("Y-m-d H:i:s");
		$query = "UPDATE task SET is_complete = 1, completed_at = \"$date\" WHERE id = $tid";
		$this->database->execute($query);
	}
	
	function getUsersRehearsals($uid = -1) {
		$data = $this->adp()->getFutureRehearsals(true);
		
		// super users should see it all
		if($this->getSysdata()->isUserSuperUser($uid)) {
			return $data;
		}
		
		// only show rehearsals of groups and rehearsal phases the user is in
		if($uid == -1) $uid = $_SESSION["user"];
		
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
		foreach($phases as $i => $p) {
			array_push($whereQ, "rehearsalphase = ?");
			array_push($params, array("i", $p));
		}
		$query = "SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE " . join(" OR ", $whereQ);
		$sel = $this->database->getSelection($query, $params);
		return Database::flattenSelection($sel, "rehearsal");
	}
	
	function getUsersConcerts($uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
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
		return $this->database->fetchRow("SELECT * FROM rehearsal WHERE id = ?", array(array("i", $rid)));
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
				foreach($oids as $i => $oid) {
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
		array_push($params, $this->getSysdata()->getDynamicConfigParameter("updates_show_max"));
		
		return $this->database->getSelection($query, $params);
	}
	
	function hasObjectDiscussion($otype, $oid) {
		$ctq = "SELECT count(*) as cnt FROM comment WHERE otype = ? AND oid = ?";
		$ct = $this->database->colValue($ctq, "cnt", array(array("s", $otype), array("i", $oid)));
		return ($ct > 0);
	}
	
	function getDiscussion($otype, $oid) {
		$query = "SELECT c.*, CONCAT(a.name, ' ', a.surname) as author, a.id as author_id ";
		$query .= "FROM comment c JOIN user u ON c.author = u.id ";
		$query .= "JOIN contact a ON u.contact = a.id ";
		$query .= "WHERE c.oid = ? AND c.otype = ? ";
		$query .= "ORDER BY c.created_at DESC";
		return $this->database->getSelection($query, array(array("i", $oid), array("s", $otype)));
	}
	
	function addComment($otype, $oid, $message = "", $author = -1) {
		if($message == "") {
			$message = $_POST["message"];
		}
		
		// validation
		require_once $this->dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "nachrichtendata.php";
		$newsData = new NachrichtenData();
		$newsData->check($message);
		
		// preparation
		$message = urlencode($message);
		
		if($author == -1) $author = $_SESSION["user"];
		
		// insertion
		$query = "INSERT INTO comment (otype, oid, author, created_at, message) VALUES (";
		$query .= "'$otype', $oid, $author, now(), '$message'";
		$query .= ")";
		
		return $this->database->execute($query);
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
		if($choice == null || $choice == "") {
			return -1;
		}
		else {
			return $choice;
		}
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
		$this->appendCustomDataToSelection('a', $appointments);
		
		return $appointments;
	}
	
	function hasAppointments() {
		// to make sure we have the permission included just load 'em
		return count($this->getAppointments(false)) > 0;
	}
}