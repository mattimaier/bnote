<?php
require_once $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";

/**
 * Data Access Class for vote data.
 * @author matti
 *
 */
class AbstimmungData extends AbstractData {

	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("AbstimmungData_construct.id"), FieldType::INTEGER),
				"name" => array(Lang::txt("AbstimmungData_construct.name"), FieldType::CHAR),
				"author" => array(Lang::txt("AbstimmungData_construct.author"), FieldType::REFERENCE),
				"end" => array(Lang::txt("AbstimmungData_construct.end"), FieldType::DATETIME),
				"is_date" => array(Lang::txt("AbstimmungData_construct.is_date"), FieldType::BOOLEAN),
				"is_multi" => array(Lang::txt("AbstimmungData_construct.is_multi"), FieldType::BOOLEAN),
				"is_finished" => array(Lang::txt("AbstimmungData_construct.is_finished"), FieldType::BOOLEAN)
		);

		$this->references = array("user");
		
		$this->table = "vote";

		$this->init($dir_prefix);
		$this->init_trigger($dir_prefix);
	}
	
	function create($values) {
		// validation
		$this->regex->isSubject($_POST["name"]);
		$_POST["end"] = trim($_POST["end"]);	
		$this->regex->isDateTime($_POST["end"]);
		
		$author = $this->getUserId();
		if(isset($_POST["is_date"])) { $is_date = 1; } else { $is_date = 0; }
		if(isset($_POST["is_multi"])) { $is_multi = 1; } else { $is_multi = 0; }
		$is_finished = 0;
		
		$end_dt = $_POST["end"];
		
		// insert vote
		$query = "INSERT INTO vote (name, author, end, is_multi, is_date, is_finished) VALUES (?, ?, ?, ?, ?, ?)";
		$params = array(
				array("s", $_POST["name"]), 
				array("s", $author),
				array("s", $end_dt),
				array("i", $is_multi),
				array("i", $is_date),
				array("i", $is_finished)
		);
		$vid = $this->database->prepStatement($query, $params);
		
		// resolve groups and add members
		$grps = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
		$this->registerVoters($vid, $grps);
		
		// create trigger if available
		if($this->triggerServiceEnabled) {
			$this->createTrigger($end_dt, $this->buildTriggerData("V", $vid));
		}
		
		return $vid;
	}
	
	/**
	 * @return Database Selection with the votes for the current user that are not marked as finished.
	 */
	function getUserActiveVotes() {
		$query = "SELECT id, name, end, is_multi, is_date FROM " . $this->table;
		$query .= " WHERE is_finished = 0 AND author = ?";
		$query .= " ORDER BY end ASC";
		return $this->database->getSelection($query, array(array("i", $this->getUserId())));
	}
	
	/**
	 * @return Database Selection with all active votes within the last year that are not marked as finished.
	 */
	function getAllActiveVotes() {
		$query = "SELECT id, name, end, is_multi, is_date FROM vote";
		$query .= " WHERE is_finished = 0 AND YEAR(end) >= (YEAR(NOW())-1)";
		$query .= " ORDER BY end ASC";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Returns all votes the user is part of.
	 * @param Boolean $active optionl: Whether to show only active (true) or only inactive (false).
	 * @param Integer $uid optional: User ID, by default current user.
	 */
	function getVotesForUser($active = true, $uid = -1) {
		if($uid == -1) $uid = $this->getUserId();
		
		$finished = $active ? 0 : 1;
		$params = array(array("i", $finished));
		 
		if($this->getSysdata()->isUserSuperUser() || $this->getSysdata()->isUserMemberGroup(1)) {
			// in case the system admin looks at the votes, show all of them
			$query = "SELECT id, name, end, is_multi, is_date, is_finished ";
			$query .= "FROM vote";
			$query .= " WHERE is_finished = ?";
			$query .= " ORDER BY is_finished, end ASC";
		}
		else {
			$query = "SELECT v.id, v.name, v.end, v.is_multi, v.is_date ";
			$query .= " FROM vote v JOIN vote_group vg ON vg.vote = v.id";
			$query .= " WHERE is_finished = ? AND vg.user = ?";
			$query .= " ORDER BY v.end ASC";
			array_push($params, array("i", $uid));
		}
		
		return $this->database->getSelection($query, $params);
	}
	
	/**
	 * Checks whether the user is admin/author of vote.
	 * @param Integer $uid User ID.
	 * @param Integer $vid Vote ID.
	 * @return True if the user is the author of this vote, otherwise false.
	 */
	function isUserAuthorOfVote($uid, $vid) {
		$author = $this->database->colValue("SELECT author FROM vote WHERE id = ?", "author", array(array("i", $vid)));
		return ($author == $uid);
	}
	
	/**
	 * Checks whether the vote is still on (time before end date and isFinished false).
	 * @param Integer $vid Vote ID.
	 * @return boolean True if the vote is active, otherwise false.
	 */
	function isVoteActive($vid) {
		$vote = $this->findByIdNoRef($vid);
		// check is finished
		if($vote["is_finished"] == 1) {
			return false;
		}
		
		// check time
		$time = strtotime($vote["end"]);
		$now = date("U");
		return ($time > $now);
	}
	
	function getOptions($vid) {
		$query = "SELECT vo.* ";
		$query .= "FROM vote_option vo, vote v ";
		$query .= "WHERE vo.vote = v.id AND v.id = ?";
		if($this->isDateVote($vid)) {
			$query .= " ORDER BY vo.odate ASC";
		}
		return $this->database->getSelection($query, array(array("i", $vid)));
	}
	
	private function isDateVote($vid) {
		return ($this->database->colValue("SELECT is_date FROM vote WHERE id = ?", "is_date", array(array("i", $vid))) == 1);
	}
	
	function addOption($vid) {
		$params = array(array("i", $vid));
		if($this->isDateVote($vid)) {
			$query = "INSERT INTO vote_option (vote, odate) VALUES (?, ?)";
			$_POST["odate"] = trim($_POST["odate"]);
			$this->regex->isDateTime($_POST["odate"]);
			array_push($params, array("s", $_POST["odate"]));
		}
		else {
			$query = "INSERT INTO vote_option (vote, name) VALUES (?, ?)";
			$this->regex->isSubject($_POST["name"]);
			array_push($params, array("s", $_POST["name"]));
		}
		return $this->database->prepStatement($query, $params);
	}
	
	function addOptions($vid, $from, $to) {
		$options = array();
		$current = $from;
		$infPrevention = 0; // max. 1 year, every day
		while(Data::compareDates($current, $to) < 1 && $infPrevention < 365) {
			if(strlen($current) <= 10) { // only date, no time
				$current .= substr($from, 10);
			}
			array_push($options, $current);
			$current = Data::addDaysToDate(substr(Data::convertDateFromDb($current), 0, 10), 1);
			$infPrevention++;
		}
		
		foreach($options as $option) {
			$_POST["odate"] = $option;
			$this->addOption($vid);
		}
	}
	
	function deleteOption($oid) {
		$query = "DELETE FROM vote_option WHERE id = ?";
		$this->database->execute($query, array(array("i", $oid)));
	}
	
	function finish($id) {
		// do not delete a vote, just set is_finished
		$query = "UPDATE vote SET is_finished = 1 WHERE id = ?";
		$this->database->execute($query, array(array("i", $id)));
	}
	
	function update($id, $values) {
		// validation
		$this->regex->isSubject($values["name"]);
		$values["end"] = trim($values["end"]);
		$this->regex->isDateTime($values["end"]);
		
		// update db
		$query = "UPDATE vote SET name = ?, end = ? WHERE id = ?";
		$params = array(
			array("s", $values["name"]),
			array("s", $values["end"]),
			array("i", $id)
		);
		$this->database->execute($query, $params);
	}
	
	function getGroup($vid) {
		$query = "SELECT vg.user as id, c.surname, c.name ";
		$query .= "FROM vote_group vg, user u, contact c ";
		$query .= "WHERE vg.vote = ? AND vg.user = u.id AND u.contact = c.id ";
		$query .= "ORDER BY c.name, c.surname";
		return $this->database->getSelection($query, array(array("i", $vid)));
	}
	
	function getUsers() {
		$query = "SELECT u.id, c.surname, c.name ";
		$query .= "FROM user u JOIN contact c ON u.contact = c.id ";
		
		// bug #13: Filter out admins
		global $system_data;
		$superUsers = $system_data->getSuperUserContactIDs();
		
		// filter out super users
		$suContacts = $this->getSysdata()->getSuperUserContactIDs();
		$params = array();
		
		if(count($suContacts) > 0 && !$this->sysdata->isUserSuperUser()) {
			$sus = array();
			foreach($suContacts as $i => $suc) {
				$sus = "c.id <> ?";
				array_push($params, array("i", $suc));
			}
			$query .= "WHERE " . join(" AND ", $sus);
		}
		
		$query .= "ORDER BY c.name, c.surname";
		return $this->database->getSelection($query);
	}
	
	function addToGroup($vid, $uid) {
		$query = "INSERT INTO vote_group (vote, user) VALUES (?, ?)";
		return $this->database->execute($query, array(array("i", $vid), array("i", $uid)));
	}
	
	/**
	 * Takes the groups and extracts the users for a group. Then adds these users
	 * to the given vote.
	 * @param Integer $vid Vote ID.
	 * @param array $groups Flat array with group IDs of users to add.
	 */
	function registerVoters($vid, $groups) {
		if($groups == null || count($groups) == 0) return;
		
		$groupQ = array();
		$params = array();
		foreach($groups as $i => $group) {
			array_push($groupQ, "cg.group = ?");
			array_push($params, array("i", $group));
		}
		
		// get all users for the given groups
		$query = "SELECT DISTINCT u.id
				  FROM `contact_group` cg JOIN user u ON u.contact = cg.contact
				  WHERE " . join(" OR ", $groupQ);
		$users = $this->database->getSelection($query, $params);
		
		/* bug #13 and #14:
		 * remove admins from being added to the group
		 * and don't add people who are already in the group
		 */
		$q1 = "SELECT user FROM vote_group WHERE vote = $vid";
		$contactsInList = $this->database->getSelection($q1);
		$contactList = $this->database->flattenSelection($contactsInList, "user");
		$superUsers = $this->getSysdata()->getSuperUsers();
        
        // add the user ids to group
		$query = "INSERT INTO vote_group (vote, user) VALUES ";
		$addset = "";
		for($i = 1; $i < count($users); $i++) {
			// exclude users already in the list and admins
			if(in_array($users[$i]["id"], $superUsers) || in_array($users[$i]["id"], $contactList)) {
				continue;
			}
			
			if($addset != "") $addset .= ",";
			$addset .= "($vid, " . $users[$i]["id"] . ")";
		}
		if($addset != "") {
			$this->database->execute($query . $addset);
		}
	}
	
	function deleteFromGroup($vid, $uid) {
		$query = "DELETE FROM vote_group WHERE vote = $vid AND user = $uid";
		$this->database->execute($query);
	}
	
	function getResult($vid) {
		$vote = $this->findByIdNoRef($vid);
		$maybeOn = ($this->getSysdata()->getDynamicConfigParameter("allow_participation_maybe") == 1);
		
		if($vote["is_date"] == 1 && $vote["is_multi"] == 1 && $maybeOn) {
			/* 
			 * compile result for date-multi-maybe votes
			 * 
			 * target table look:
			 * OPTION  STIMMEN        WÄHLER
			 * =================================================
			 * <Date>  Ja: 4		  Hans, Hektor, Oskar, Heidi
			 *         Nein: 2        Josef, Viktor
			 *         Vielleicht: 1  Marta
			 * -------------------------------------------------
			 */
			$result = array();
			
			$options = $this->getOptions($vid);			
			foreach($options as $i => $row) {
				if($i == 0) {
					// header
					array_push($result, array(
						"id", "Option", "votes", "voters"
					));
				}
				else {
					// body of result table
					$optionId = $row["id"];
					$name = substr(Data::getWeekdayFromDbDate($row["odate"]), 0, 2) . ", ";
					$name .= Data::convertDateFromDb($row["odate"]) . Lang::txt("AbstimmungData_getResult.odate");
					
					$choiceY = $this->getOptionVotes($optionId, 1);
					$yes = $choiceY["count"];
					$yesNames = $choiceY["names"];
					
					$choiceN = $this->getOptionVotes($optionId, 0);
					$no = $choiceN["count"];
					$noNames = $choiceN["names"];
					
					$choiceM = $this->getOptionVotes($optionId, 2);
					$may = $choiceM["count"];
					$mayNames = $choiceM["names"];
					
					
					// build 3 rows
					$resRow = array(
						"id" => $optionId,
						"Option" => $name,
						"votes" => 0,
						"voters" => 0
					);
					$resYes = $resRow;
					$resYes["votes"] = $yes . Lang::txt("AbstimmungData_getResult.yes");
					$resYes["voters"] = $yesNames;
					array_push($result, $resYes);
					
					$resNo = $resRow;
					$resNo["votes"] = $no . Lang::txt("AbstimmungData_getResult.no");
					$resNo["voters"] = $noNames;
					array_push($result, $resNo);
					
					$resMay = $resRow;
					$resMay["votes"] = $may . Lang::txt("AbstimmungData_getResult.maybe");
					$resMay["voters"] = $mayNames;
					array_push($result, $resMay);
				}
			}
			return $result;
		}
		else {
			// result for all types of votes, but date-multi-maybe votes
			$query = 'SELECT vo.id, IF(v.is_date=1, vo.odate, vo.name) as `option`, 
						       count(vo.id) as votes, 
						       GROUP_CONCAT(CONCAT(c.name, \' \', c.surname, " (", i.name, ")" ) SEPARATOR \', \') as voters 
						FROM vote v JOIN vote_option vo ON v.id = vo.vote
						     JOIN vote_option_user vou ON vou.vote_option = vo.id
						     JOIN user u ON vou.user = u.id
						     LEFT OUTER JOIN contact c ON u.contact = c.id
						     LEFT OUTER JOIN instrument i ON c.instrument = i.id
						WHERE v.id = ?
						GROUP BY vo.id 
						ORDER BY vo.id';
			return $this->database->getSelection($query, array(array("i", $vid)));
		}
	}
	
	private function getOptionVotes($optionId, $choice) {
		$result = array( "count" => 0, "names" => "" );
		
		$query = 'SELECT CONCAT(c.name, \' \', c.surname, " (", i.name, ")" ) as voter ';
		$query .= 'FROM vote_option_user vou LEFT OUTER JOIN user u ON vou.user = u.id ';
		$query .= '     LEFT OUTER JOIN contact c ON u.contact = c.id ';
		$query .= '     LEFT OUTER JOIN instrument i ON c.instrument = i.id ';
		$query .= 'WHERE vou.vote_option = ? AND vou.choice = ? ';
		$query .= 'ORDER BY voter';
		
		$voters = $this->database->getSelection($query, array(array("i", $optionId), array("i", $choice)));
		foreach($voters as $i => $voter) {
			if($i == 0) continue;
			if($result["names"] != "") $result["names"] .= ", ";
			$result["names"] .= $voter["voter"];
			$result["count"]++;
		}
		
		return $result;
	}
	
	function validate($input, $groupRequired = false) {
		parent::validate($input);
		
		// additionally validate whether a group is set -> otherwise the vote "disappears"
		if($groupRequired) {
			$grps = GroupSelector::getPostSelection($this->adp()->getGroups(), "group");
			if(count($grps) == 0) {
				new BNoteError(Lang::txt("AbstimmungData_validate.BNoteError"));
			}
		}
	}
	
	function getOpenVoters($voteId) {
		// find all contacts that can vote
		$votersDbSel = $this->database->getSelection("SELECT c.id 
				FROM vote_group vg JOIN user u ON vg.user = u.id
				JOIN contact c ON u.contact = c.id
				WHERE vote = ?", array(array("i", $voteId))
		);
		$voterContacts = $this->database->flattenSelection($votersDbSel, "id");  # contact ids
		
		// find all contacts that have voted already
		$alreadyVotedContactsDbSel = $this->database->getSelection("SELECT DISTINCT c.id 
				FROM vote_option vo JOIN vote_option_user vou ON vo.id = vou.vote_option
			    JOIN user u ON vou.user = u.id
			    JOIN contact c ON u.contact = c.id
			    WHERE vote = 1;"
		);
		$alreadyVotedContacts = $this->database->flattenSelection($alreadyVotedContactsDbSel, "id");
		
		// return only an array of contact ids that have not voted yet
		$laggards = array();
		foreach($voterContacts as $i => $contact) {
			if(!in_array($contact, $alreadyVotedContacts)) {
				array_push($laggards, $contact);
			}
		}
		return $laggards;
	}
}

?>