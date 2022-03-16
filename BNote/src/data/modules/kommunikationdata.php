<?php
require_once $GLOBALS["DIR_DATA_MODULES"] . "kontaktedata.php";

/**
 * Data Access Class for communication data.
 * @author matti
 *
 */
class KommunikationData extends KontakteData {
	
	function getRehearsal($id) {
		$query = "SELECT begin, end, name as location, street, city, zip, r.notes ";
		$query .= "FROM rehearsal r, location l, address a ";
		$query .= "WHERE r.location = l.id AND l.address = a.id AND r.id = ?";
		return $this->database->fetchRow($query, array(array("i", $id)));
	}
	
	function getMailaddressesFromGroup($prefix) {
		$selectedGroups = GroupSelector::getPostSelection($this->adp()->getGroups(), $prefix);
		
		if($selectedGroups == null || count($selectedGroups) == 0) {
			return null;
		}
		
		$groupQ = array();
		$params = array();
		foreach($selectedGroups as $i => $group) {
			array_push($groupQ, "cg.group = ?");
			array_push($params, array("i", $group));
		}
		
		$query = "SELECT c.email ";
		$query .= "FROM contact c JOIN contact_group cg ON cg.contact = c.id ";
		$query .= "WHERE " . join(" OR ", $groupQ);
		$mailaddies = $this->database->getSelection($query, $params);
		
		return $this->flattenAddresses($mailaddies);
	}
	
	function getSongsForRehearsal($rid) {
		$query = "SELECT s.title, rs.notes ";
		$query .= "FROM rehearsal_song rs, song s ";
		$query .= "WHERE rs.song = s.id AND rs.rehearsal = ?";
		return $this->database->getSelection($query, array(array("i", $rid)));
	}
	
	function getRehearsalContactMail($rid) {
		$query = "SELECT c.email ";
		$query .= "FROM contact c JOIN rehearsal_contact rc ON rc.contact = c.id ";
		$query .= "WHERE rc.rehearsal = ?";
		$mailaddies = $this->database->getSelection($query, array(array("i", $rid)));
		
		return $this->flattenAddresses($mailaddies);
	}
	
	function getConcerts() {
		return $this->adp()->getFutureConcerts();
	}
	
	function getConcert($cid) {
		return $this->database->fetchRow("SELECT * FROM concert WHERE id = ?", array(array("i", $cid)));
	}
	
	function getConcertContactMail($cid) {
		$query = "SELECT c.email ";
		$query .= "FROM contact c JOIN concert_contact cc ON cc.contact = c.id ";
		$query .= "WHERE cc.concert = ?";
		$mailaddies = $this->database->getSelection($query, array(array("i", $cid)));
		return $this->flattenAddresses($mailaddies);
	}
	
	function getVotes() {
		require_once $GLOBALS["DIR_DATA_MODULES"] . "abstimmungdata.php";
		$vData = new AbstimmungData();
		return $vData->getAllActiveVotes();
	}
	
	function getVote($vid) {
		return $this->database->fetchRow("SELECT * FROM vote WHERE id = ?", array(array("i", $vid)));
	}
	
	function getVoteContactMail($vid) {
		require_once $GLOBALS["DIR_DATA_MODULES"] . "startdata.php";
		$sData = new StartData();
		return $this->flattenAddresses($sData->getContactsForObject("V", $vid));
	}
	
	private function flattenAddresses($selection) {
		return $this->database->flattenSelection($selection, "email");
	}
	
	public function getRehearsalSeries() {
		$query = "SELECT DISTINCT s.* FROM rehearsalserie s JOIN rehearsal r ON r.serie = s.id " 
				. "WHERE r.end >= NOW() ORDER BY s.id";
		return $this->database->getSelection($query);
	}
	
	public function getRehearsalSerie($id) {
		return $this->database->fetchRow("SELECT * FROM rehearsalserie WHERE id = ?", array(array("i", $id)));
	}
	
	public function getRehearsalSerieContactMail($serieId) {
		$query = "SELECT DISTINCT c.email ";
		$query .= "FROM contact c JOIN rehearsal_contact rc ON rc.contact = c.id ";
		$query .= "JOIN rehearsal r ON rc.rehearsal = r.id ";
		$query .= "WHERE r.serie = ?";
		$mailaddies = $this->database->getSelection($query, array(array("i", $serieId)));
		return $this->flattenAddresses($mailaddies);
	}
}

?>