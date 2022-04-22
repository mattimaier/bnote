<?php
/**
 * Data Access Class for repertoire data.
 * @author matti
 *
 */
class RepertoireData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
			"id" => array(Lang::txt("RepertoireData_construct_id"), FieldType::INTEGER),
			"title" => array(Lang::txt("RepertoireData_construct_title"), FieldType::CHAR, true),
			"length" => array(Lang::txt("RepertoireData_construct_length"), FieldType::MINSEC),
			"genre" => array(Lang::txt("RepertoireData_construct_genre"), FieldType::REFERENCE),
			"bpm" => array(Lang::txt("RepertoireData_construct_bpm"), FieldType::INTEGER),
			"music_key" => array(Lang::txt("RepertoireData_construct_music_key"), FieldType::CHAR),
			"composer" => array(Lang::txt("RepertoireData_construct_composer"), FieldType::CHAR, true),
			"status" => array(Lang::txt("RepertoireData_construct_status"), FieldType::REFERENCE),
			"setting" => array(Lang::txt("RepertoireData_construct_setting"), FieldType::CHAR),
			"notes" => array(Lang::txt("RepertoireData_construct_notes"), FieldType::TEXT),
			"is_active" => array(Lang::txt("RepertoireData_construct_is_active"), FieldType::BOOLEAN)
		);
		
		$this->references = array(
			"genre" => "genre",
			"composer" => "composer",
			"status" => "status"
		);
		
		$this->table = "song";
		
		$this->init($dir_prefix);
	}
	
	public static function getJoinedAttributes() {
		return array(
			"genre" => array("name"),
			"composer" => array("name"),
			"status" => array("name")
		);
	}
	
	function create($values) {
		// composer handling
		$composerId = 0;
		if(isset($values["composer"]) && strlen($values["composer"]) > 0) {
			// check if the value contains the '[id=...]' string
			$matches = array();
			if(preg_match('/\[id=\d{1,10}\]/', $values["composer"], $matches) > 0) {
				$composerId = substr($matches[0], 4, -1);
			}
			else {
				// create composer
				$query = "INSERT INTO composer (name) VALUES (?)";
				$composerId = $this->database->prepStatement($query, array(array("s", $this->modifyString($values["composer"]))));
			}
		}
		$values["composer"] = $composerId;
		
		// convert strings
		$values["title"] = $this->modifyString($values["title"]);
		$values["notes"] = $this->modifyString($values["notes"]);
		$values["title"] = urlencode($values["title"]);
		$values["notes"] = urlencode($values["notes"]);
		
		// modify bpm
		if($values["bpm"] == "") $values["bpm"] = 0;
		
		// default "is_active" = FALSE
		if(!isset($values["is_active"])) {
			$values["is_active"] = FALSE;
		}
		
		// create song
		$id = parent::create($values);
		
		// custom data
		$this->createCustomFieldData('s', $id, $values);
		
		return $id;
	}
	
	function update($id, $values) {
		// convert title and composer
		$values["title"] = $this->modifyString($values["title"]);
		$values["notes"] = $this->modifyString($values["notes"]);
		$values["title"] = urlencode($values["title"]);
		$values["notes"] = urlencode($values["notes"]);
		
		// composer handling
		$composerId = 0;
		if(isset($values["composer"]) && strlen($values["composer"]) > 0) {
			// check if the value contains the '[id=...]' string
			$matches = array();
			if(preg_match('/\[id=\d{1,10}\]/', $values["composer"], $matches) > 0) {
				$composerId = substr($matches[0], 4, -1);
			}
			else {
				// create composer
				$query = "INSERT INTO composer (name) VALUES (?)";
				$composerId = $this->database->prepStatement($query, array("s", $this->modifyString($values["composer"])));
			}
		}
		$values["composer"] = $composerId;
		
		// modify bpm
		if($values["bpm"] == "") $values["bpm"] = 0;
		
		// active flag handling
		if(!isset($values["is_active"])) {
			$values["is_active"] = 0;
		}
		
		// core entity
		parent::update($id, $values);
		
		// custom data
		$this->updateCustomFieldData('s', $id, $values);
	}
	
	protected function createComposer($name) {
		$this->regex->isSubject($name);
		$query = "INSERT INTO composer (name) VALUES (?)";
		return $this->database->prepStatement($query, array(array("s", $name)));
	}
	
	function delete($id) {
		// custom data
		$this->deleteCustomFieldData('s', $id);
		
		// don't remove composer
		parent::delete($id);
	}
	
	function getComposerName($id) {
		if($id > 0) {
			return $this->database->colValue("SELECT name FROM composer WHERE id = ?", "name", array(array("i", $id)));
		}
		return "";
	}
	
	function totalRepertoireLength() {
		return $this->database->colValue("SELECT Sec_to_Time(Sum(Time_to_Sec(length))) as tl FROM song WHERE length > 0", "tl", array());
	}
	
	private function isComposerUsedByAnotherSong($composerId) {
		$ct = $this->database->colValue("SELECT count(composer) as cnt FROM song WHERE composer = ?", "cnt", array(array("i", $composerId)));
		return ($ct > 1);
	}
	
	private function modifyString($input) {
		$str = $input;
		
		// single injection prevention
		if(strpos($str, "<") >= 0) {
			$str = str_replace("<", "", $str);
		}
		if(strpos($str, ">") >= 0) {
			$str = str_replace(">", "", $str);
		}
		
		return $str;
	}
	
	function getSolists($songId) {
		$distinct = "";
		if($songId < 1) $distinct = " DISTINCT";
		$query = "SELECT$distinct c.id, c.surname, c.name, i.name as instrument ";
		$query .= "FROM song_solist s JOIN contact c ON s.contact = c.id ";
		$query .= "JOIN instrument i ON c.instrument = i.id ";
		$params = array();
		if($songId > 0) {
			$query .= "WHERE s.song = ? ";
			array_push($params, array("i", $songId));
		}
		$query .= "ORDER BY c.surname, c.name ";
		return $this->database->getSelection($query, $params);
	}
	
	function addSolist($songId) {
		$solistIds = GroupSelector::getPostSelection($this->adp()->getContacts(), "solists");
		$params = array();
		$triples = array();
		foreach($solistIds as $solistId) {
			array_push($triples, "(?, ?, '')");
			array_push($params, array("i", $songId));
			array_push($params, array("i", $solistId));
		}
		$query = "INSERT INTO song_solist VALUES " . join(",", $triples);
		$this->database->execute($query, $params);
	}
	
	function deleteSolist($songId, $solistId) {
		$query = "DELETE FROM song_solist WHERE song = ? AND contact = ?";
		$this->database->execute($query, array(array("i", $songId), array("i", $solistId)));
	}
	
	function getGenres() {
		$query = "SELECT * FROM genre ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	function getGenre($genreId) {
		if($genreId == null || $genreId == "") {
			return null;
		}
		$query = "SELECT * FROM genre WHERE id = ?";
		return $this->database->getSelection($query, array(array("i", $genreId)));
	}
	
	
	function getAllSolists() {
		return $this->getSolists(-1);
	}
	
	function getStatuses() {
		$query = "SELECT * FROM status ORDER BY id";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Finds the status ID by name (case insensitive) and return the given $defaultStatus in case the name is not found.
	 * @param string $name Status name to search for.
	 * @param int $defaultStatus ID of the status to return if the name was not found.
	 * @return int Status ID
	 */
	function getStatusByName($name, $defaultStatus) {
		$query = "SELECT * FROM status WHERE lower(name) = ? LIMIT 1";
		$result = $this->database->fetchRow($query, array(array("s", strtolower($name))));
		if($result == NULL || !isset($result["id"])) {
			return $defaultStatus;
		}
		return $result["id"];
	}
	
	function getComposers() {
		$query = "SELECT * FROM composer ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	function getFilteredRepertoire($filters, $offset=0, $pageSize=100) {
		$query = "SELECT DISTINCT s.id, s.title, c.name as composer, s.length, s.bpm, s.music_key, s.notes, g.name as genre, stat.name as status, s.is_active ";
		$query .= "FROM song s 
				LEFT OUTER JOIN composer c ON s.composer = c.id 
				LEFT OUTER JOIN genre g ON s.genre = g.id 
				JOIN status stat ON s.status = stat.id 
				LEFT OUTER JOIN song_solist sol ON sol.song = s.id ";
		
		// remove empty values from filters
		$cleanFilters = array();
		foreach($filters as $field => $value) {
			if($value != "" && $value != "-1" && $value != -1) {
				// secure $field key
				$this->regex->isDbItem($field, "filters[field]");
				if($field == "composer") {
					$value = $this->getComposerName($value);
				}
				$cleanFilters[$field] = $value;
			}
		}
		
		// return all (limited) if no filters are set
		$numFilters = count($cleanFilters);
		if($numFilters > 0) {
			$query .= "WHERE ";
		}
		
		// build filter query
		$params = array();
		$whereQ = array();
		foreach($cleanFilters as $field => $value) {
			$type = $this->getTypeOfField($field);
			
			if($field == "solist") {
				array_push($whereQ, "sol.contact = ?");
				array_push($params, array("i", $value));
			}
			else if($field == "music_key") {
				array_push($whereQ, "$field LIKE CONCAT('%',?,'%')");
				array_push($params, array("s", $value));
			}
			else if($field == "composer") {
				// get name of composer and filter for that
				array_push($whereQ, "c.name LIKE CONCAT('%',?,'%')");
				array_push($params, array("s", $value));
			}
			else if($field == "title") {
				array_push($whereQ, "s.title LIKE CONCAT('%',?,'%')");
				array_push($params, array("s", urlencode($value)));
			}
			else if($type == FieldType::BOOLEAN && $value >= 0) {
				array_push($whereQ, "$field = ?");
				array_push($params, array("i", $value == "on" || $value == 1 ? 1 : 0));				
			}
			else if($type == FieldType::INTEGER
					|| $type == FieldType::DECIMAL
					|| $type == FieldType::CURRENCY
					|| $type == FieldType::REFERENCE) {
				array_push($whereQ, "$field = ?");
				array_push($params, array("i", $value));	
			}
			else {
				array_push($whereQ, "$field = ?");
				array_push($params, array("s", $value));
			}
		}
		
		$query .= join(" AND ", $whereQ) . " ORDER BY title";
		
		$this->regex->isInteger($offset);
		$this->regex->isPositiveAmount($pageSize);
		if($numFilters == 0 || intval($offset) > 0) {
			$query .= " LIMIT $offset, $pageSize";
		}
		
		// get data and decode the encoded content
		$encodedData = $this->database->getSelection($query, $params);
		$decodedData = $this->urldecodeSelection($encodedData, array("title", "notes"));
		
		return array(
			"numFilters" => $numFilters,
			"data" => $decodedData
		);
	}
	
	function getFiles($songId) {
		$query = "SELECT sf.*, dt.name as doctype_name
			FROM song_files sf JOIN doctype dt ON sf.doctype = dt.id 
			WHERE song = ?";
		return $this->database->getSelection($query, array(array("i", $songId)));
	}
	
	function addFile($songId, $filename, $doctype) {
		$this->regex->isNumber($songId);
		$this->regex->isNumber($doctype);
		$q = "INSERT INTO song_files (song, filepath, doctype) VALUES (?, ?, ?)";
		$this->database->execute($q, array(
				array("i", $songId),
				array("s", $filename),
				array("s", $doctype)
		));
	}
	
	function deleteFileReference($songfileId) {
		$this->database->execute("DELETE FROM song_files WHERE id = ?", array(array("i", $songfileId)));
	}
	
	function getShareFiles() {
		return $this->recursiveFiles("data/share/");
	}
	
	function recursiveFiles($folder) {
		// data body
		$content = array();
		if($handle = opendir($folder)) {
			while(false !== ($file = readdir($handle))) {
				$fullpath = $folder . $file;
				if(Filebrowser::fileValid($fullpath, $file) && $file != "..") {
					if(is_dir($fullpath)) {
						$subdir_content = $this->recursiveFiles($fullpath . "/");
						$content = array_merge($content, $subdir_content);
					}
					else {
						$caption = $file;
						if($folder != "data/share/") {
							$caption = substr($fullpath, strlen("data/share/"));
						}
						array_push($content, array("fullpath" => $fullpath, "filename" => $caption));
					}
				}
			}
		}
		return $content;
	}
	
	function massUpdate() {
		// build update set
		$keyValues = array();
		$params = array();
		if($_POST['genre'] > 0) {
			$this->regex->isPositiveAmount($_POST["genre"]);
			array_push($keyValues, "genre = ?");
			array_push($params, array("i", $_POST['genre']));
		}
		if($_POST['status'] > 0) {
			$this->regex->isPositiveAmount($_POST["status"]);
			array_push($keyValues, "status = ?");
			array_push($params, array("i", $_POST["status"]));
		}
		if($_POST['bpm'] != "") {
			$this->regex->isPositiveAmount($_POST['bpm']);
			array_push($keyValues, "bpm = ?");
			array_push($params, array("i", $_POST['bpm']));
		}
		if($_POST['music_key'] != "") {
			$this->regex->isSubject($_POST['music_key']);
			array_push($keyValues, "music_key = ?");
			array_push($params, array("s", $_POST['music_key']));
		}
		if($_POST['setting'] != "") {
			$this->regex->isText($_POST['setting']);
			array_push($keyValues, "setting = ?");
			array_push($params, array("s", $_POST['setting']));
		}
		$keyValues = join(",", $keyValues);
		
		// build ID query -> selected songs
		$songIds = GroupSelector::getPostSelection($this->findAllNoRef(), "songs");
		if(count($songIds) == 0) {
			new BNoteError(Lang::txt("RepertoireData_massUpdate_error"));
		}
		$idQuery = array();
		foreach($songIds as $sid) {
			array_push($idQuery, "id = ?");
			array_push($params, array("i", $sid));
		}
		
		// execute query
		$query = "UPDATE song SET " . join(",", $keyValues). " WHERE " . join(" OR ", $idQuery);
		$this->database->execute($query, $params);
	}
	
	function findReferences($songId) {
		// validation
		$this->regex->isPositiveAmount($songId);
		
		// init
		$result = array();
		
		// find rehearsals
		$q1 = "SELECT r.* FROM rehearsal_song rs JOIN rehearsal r ON rs.rehearsal = r.id 
				WHERE song = ? ORDER BY r.begin";
		$rehearsals = $this->database->getSelection($q1, array(array("i", $songId)));
		$result["rehearsals"] = $rehearsals;
		
		// find concerts
		$q1 = "SELECT c.* 
				FROM program_song ps JOIN program p ON ps.program = p.id 
				JOIN concert c ON c.program = p.id
				WHERE song = ? ORDER BY c.begin";
		$concerts = $this->database->getSelection($q1, array(array("i", $songId)));
		$result["concerts"] = $concerts;
		
		return $result;
	}
	
	function getSong($id) {
		$this->regex->isPositiveAmount($id);
		$query = "SELECT s.*, g.name as genrename, t.name as statusname, c.name as composername
				FROM " . $this->table . " s
				LEFT OUTER JOIN genre g ON s.genre = g.id
				JOIN status t ON s.status = t.id
				LEFT OUTER JOIN composer c ON s.composer = c.id
				WHERE s.id = ?";
		$song = $this->database->fetchRow($query, array(array("i", $id)));
		$customData = $this->getCustomFieldData('s', $id);
		return array_merge($song, $customData);
	}
	
	function exportData() {
		$exportJoinAttributes = array(
				"genre" => array("name"),
				"composer" => array("name", "id"),
				"status" => array("name")
		);
		$selection = $this->findAllJoinedOrdered($exportJoinAttributes, "id");
		$selection = $this->urldecodeSelection($selection, array("title", "notes"));
		$songs = $this->appendCustomDataToSelection('s', $selection);
		$header = $songs[0];
		array_push($header, "composer");
		$export = array(
			$header
		);
		for($i = 1; $i < count($songs); $i++) {
			$song = $songs[$i];
			$song["composer"] = $song["composername"] . " [id=" . $song["composerid"] . "]";
			array_push($export, $song);
		}
		return $export;
	}
	
	function wipe() {
		$tables = ["program_song", "rehearsal_song", "song_files", "song_solist", "song"];
		foreach($tables as $tname) {
			$q = "DELETE FROM $tname WHERE 1=1";
			$this->database->execute($q);
		}
	}
}