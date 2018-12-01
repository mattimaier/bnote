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
			"id" => array("Titel ID", FieldType::INTEGER),
			"title" => array("Titel", FieldType::CHAR, true),
			"length" => array("Länge", FieldType::CHAR), // not TIME, because of second precision
			"genre" => array("Genre", FieldType::REFERENCE),
			"bpm" => array("Tempo (bpm)", FieldType::INTEGER),
			"music_key" => array("Tonart", FieldType::CHAR),
			"composer" => array("Komponist / Arrangeur", FieldType::CHAR, true),
			"status" => array("Status", FieldType::REFERENCE),
			"setting" => array("Besetzung", FieldType::CHAR),
			"notes" => array("Anmerkungen", FieldType::TEXT),
			"is_active" => array("Aktuell", FieldType::BOOLEAN)
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
	
	/**
	 * @return A list in Javascript format for autocompletion.
	 */
	function listComposers() {
		$query = "SELECT name FROM composer";
		$data = $this->database->getSelection($query);
		$result = "";
		for($i = 1; $i < count($data); $i++) {
			$result .= "\"" . $data[$i]["name"] . "\",\n";
		}
		$len = strlen($result);
		if($len > 2) {
			$result = substr($result, 0, $len-2);
		}
		
		return $result;
	}
	
	function create($values) {
		// validation
		$this->regex->isSubject($values["composer"]);
		
		// modify length
		if($values["length"] == "") $values["length"] = "00"; 
		$values["length"] = "0:" . $values["length"];
		
		// convert title and composer
		$values["composer"] = $this->modifyString($values["composer"]);
		$values["title"] = $this->modifyString($values["title"]);
		
		// modify bpm
		if($values["bpm"] == "") $values["bpm"] = 0;
		
		/* look for composer, if there don't add him/her
		 * -> use key, otherwise add and use key.
		 */
		$cid = $this->doesComposerExist($values["composer"]);
		if($cid > 0) {
			$values["composer"] = $cid;
		}
		else {
			// add as a new composer
			$query = "INSERT INTO composer (name) VALUES (\"" . $values["composer"] . "\")";
			$values["composer"] = $this->database->execute($query);
		}
		
		$id = parent::create($values);
		
		// custom data
		$this->createCustomFieldData('s', $id, $values);
		
		return $id;
	}
	
	function update($id, $values) {
		// convert title and composer
		$values["composer"] = $this->modifyString($values["composer"]);
		$values["title"] = $this->modifyString($values["title"]);
		
		$song = $this->findByIdNoRef($id);
		// don't update composer if used by another song
		if($this->isComposerUsedByAnotherSong($song["composer"])) {
			// is new composer already in list?
			$cid = $this->doesComposerExist($values["composer"]);
			if($cid > 0) {
				$values["composer"] = $cid;
			}
			else {
				// add as a new composer
				$query = "INSERT INTO composer (name) VALUES (\"" . $values["composer"] . "\")";
				$values["composer"] = $this->database->execute($query);
			}
		}
		else {
			// update composer
			$query = "UPDATE composer SET name = \"" . $values["composer"] . "\" WHERE id = " . $song["composer"];
			$this->database->execute($query);
			$values["composer"] = $song["composer"];
		}
		
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
	
	function delete($id) {
		// custom data
		$this->deleteCustomFieldData('s', $id);
		
		// don't remove composer
		parent::delete($id);
	}
	
	function getComposerName($id) {
		return $this->database->getCell("composer", "name", "id = $id");
	}
	
	function totalRepertoireLength() {
		return $this->database->getCell($this->table, "Sec_to_Time(Sum(Time_to_Sec(length)))", "length > 0");
	}
	
	private function isComposerUsedByAnotherSong($composerId) {
		$ct = $this->database->getCell($this->table, "count(composer)", "composer = $composerId");
		return ($ct > 1);
	}
	
	/**
	 * Checks whether a similar name exists.
	 * @param String $name Name of the composer.
	 * @return The ID of the existent composer or -1 if not exists.
	 */
	private function doesComposerExist($name) {
		$ct = $this->database->getCell("composer", "count(id)", "name = \"$name\"");
		if($ct < 1) return -1;
		else {
			return $this->database->getCell("composer", "id", "name = \"$name\"");
		}
	}
	
	private function modifyString($input) {
		// just replace double quotes with single quotes and remove < and >
		$str = $input;
		if(strpos($input, '"') >= 0) {
			$str = str_replace("\"", "'", $input);
		}
		if(strpos($str, "<") >= 0) {
			$str = str_replace("<", "", $str); // no HTML injection
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
		if($songId > 0) {
			$query .= "WHERE s.song = $songId ";
		}
		$query .= "ORDER BY c.surname, c.name ";
		return $this->database->getSelection($query);
	}
	
	function addSolist($songId) {
		$solistIds = GroupSelector::getPostSelection($this->adp()->getContacts(), "solists");
		
		$query = "INSERT INTO song_solist VALUES ";
		foreach($solistIds as $i => $solistId) {
			if($i > 0) $query .= ",";
			$query .= "($songId, $solistId, \"\")";
		}
		
		$this->database->execute($query);
	}
	
	function deleteSolist($songId, $solistId) {
		$query = "DELETE FROM song_solist WHERE song = $songId AND contact = $solistId";
		$this->database->execute($query);
	}
	
	function getGenres() {
		$query = "SELECT * FROM genre ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	function getGenre($genreId) {
		$query = "SELECT * FROM genre WHERE id = ". $genreId;
		return $this->database->getSelection($query);
	}
	
	
	function getAllSolists() {
		return $this->getSolists(-1);
	}
	
	function getStatuses() {
		$query = "SELECT * FROM status ORDER BY id";
		return $this->database->getSelection($query);
	}
	
	function getComposers() {
		$query = "SELECT DISTINCT * FROM composer ORDER BY name";
		return $this->database->getSelection($query);
	}
	
	function getFilteredRepertoire($filters, $offset=0, $pageSize=100) {
		$query = "SELECT DISTINCT s.id, s.title, c.name as composer, s.length, s.bpm, s.music_key, s.notes, g.name as genre, stat.name as status ";
		$query .= "FROM song s JOIN composer c ON s.composer = c.id ";
		$query .= "JOIN genre g ON s.genre = g.id ";
		$query .= "JOIN status stat ON s.status = stat.id ";
		$query .= "LEFT OUTER JOIN song_solist sol ON sol.song = s.id ";
		
		// remove empty values from filters
		$cleanFilters = array();
		foreach($filters as $field => $value) {
			if($value != "" && $value != "-1" && $value != -1) {
				if($field == "composer") {
					$value = $this->database->getCell("composer", "name", "id = $value");
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
		$where = "";
		foreach($cleanFilters as $field => $value) {
			if($where != "") {
				$where .= " AND ";
			}
			$type = $this->getTypeOfField($field);
			
			if($field == "solist") {
				$where .= "sol.contact = $value";
			}
			else if($field == "music_key") {
				$where .= $field . " LIKE \"%$value%\"";
			}
			else if($field == "composer") {
				// get name of composer and filter for that
				$where .= "c.name LIKE \"%$value%\"";
			}
			else if($field == "title") {
				$where .= "s.title LIKE \"%$value%\"";
			}
			else if($type == FieldType::INTEGER
					|| $type == FieldType::BOOLEAN
					|| $type == FieldType::DECIMAL
					|| $type == FieldType::REFERENCE) {
				$where .= $field . " = " . $value;
			}
			else {
				$where .= $field . " = \"" . $value . "\"";
			}
		}
		
		$query .= "$where ORDER BY title";
		if($numFilters == 0 || intval($offset) > 0) {
			$query .= " LIMIT $offset,$pageSize";
		}
		
		return array(
			"numFilters" => $numFilters,
			"data" => $this->database->getSelection($query)
		);
	}
	
	function getFiles($songId) {
		$this->regex->isPositiveAmount($songId);
		$query = "SELECT sf.*, dt.name as doctype_name
			FROM song_files sf JOIN doctype dt ON sf.doctype = dt.id 
			WHERE song = $songId";
		return $this->database->getSelection($query);
	}
	
	function addFile($songId, $filename, $doctype) {
		$this->regex->isNumber($songId);
		$this->regex->isNumber($doctype);
		$q = "INSERT INTO song_files (song, filepath, doctype) VALUES ($songId, '$filename', '$doctype')";
		$this->database->execute($q);
	}
	
	function deleteFileReference($songfileId) {
		$this->database->execute("DELETE FROM song_files WHERE id = $songfileId");
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
		if($_POST['genre'] > 0) {
			$this->regex->isPositiveAmount($_POST["genre"]);
			array_push($keyValues, "genre = " . $_POST['genre']);
		}
		if($_POST['status'] > 0) {
			$this->regex->isPositiveAmount($_POST["status"]);
			array_push($keyValues, "status = " . $_POST["status"]);
		}
		if($_POST['bpm'] != "") {
			$this->regex->isPositiveAmount($_POST['bpm']);
			array_push($keyValues, "bpm = " . $_POST['bpm']);
		}
		if($_POST['music_key'] != "") {
			$this->regex->isSubject($_POST['music_key']);
			array_push($keyValues, "music_key = \"" . $_POST['music_key'] . "\"");
		}
		if($_POST['setting'] != "") {
			$this->regex->isText($_POST['setting']);
			array_push($keyValues, "setting = \"" . $_POST['setting'] . "\"");
		}
		$keyValues = join(",", $keyValues);
		
		// build ID query -> selected songs
		$songIds = GroupSelector::getPostSelection($this->findAllNoRef(), "songs");
		if(count($songIds) == 0) {
			new BNoteError("Bitte wähle mindestens einen Song zum Update aus.");
		}
		$idQuery = join(" OR id=", $songIds);
		
		// execute query
		$query = "UPDATE " . $this->getTable() . " SET " . $keyValues . " WHERE id=" . $idQuery;
		$this->database->execute($query);
	}
	
	function findReferences($songId) {
		// validation
		$this->regex->isPositiveAmount($songId);
		
		// init
		$result = array();
		
		// find rehearsals
		$q1 = "SELECT r.* FROM rehearsal_song rs JOIN rehearsal r ON rs.rehearsal = r.id " 
				. "WHERE song = $songId ORDER BY r.begin";
		$rehearsals = $this->database->getSelection($q1);
		$result["rehearsals"] = $rehearsals;
		
		// find concerts
		$q1 = "SELECT c.* " 
				. "FROM program_song ps JOIN program p ON ps.program = p.id "
				. " JOIN concert c ON c.program = p.id "
				. "WHERE song = $songId ORDER BY c.begin";
		$concerts = $this->database->getSelection($q1);
		$result["concerts"] = $concerts;
		
		return $result;
	}
	
	function getSong($id) {
		$row = $this->findByIdNoRef($id);
		$song = $this->findByIdJoined($id, RepertoireData::getJoinedAttributes());
		$customData = $this->getCustomFieldData('s', $id);
		return array_merge($row, $song, $customData);
	}
}

?>