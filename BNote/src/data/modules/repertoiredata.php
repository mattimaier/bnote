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
		if(isset($values["composer"]) && $values["composer"] != "") {
			$this->regex->isSubject($values["composer"]);
		}
		
		// convert title and composer
		$values["composer"] = $this->modifyString($values["composer"]);
		$values["title"] = $this->modifyString($values["title"]);
		$values["notes"] = $this->modifyString($values["notes"]);
		$values["title"] = urlencode($values["title"]);
		$values["notes"] = urlencode($values["notes"]);
		
		// modify bpm
		if($values["bpm"] == "") $values["bpm"] = 0;
		
		/* look for composer, if there don't add him/her
		 * -> use key, otherwise add and use key.
		 */
		if(isset($values["composer"]) && $values["composer"] != "") {
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
			$values["composer"] = 0;
		}
		
		$id = parent::create($values);
		
		// custom data
		$this->createCustomFieldData('s', $id, $values);
		
		return $id;
	}
	
	function update($id, $values) {
		$song = $this->findByIdNoRef($id);
		
		// convert title and composer
		$values["title"] = $this->modifyString($values["title"]);
		$values["notes"] = $this->modifyString($values["notes"]);
		$values["title"] = urlencode($values["title"]);
		$values["notes"] = urlencode($values["notes"]);
		
		if(isset($values["composer"]) && $values["composer"] != "") {
			$values["composer"] = $this->modifyString($values["composer"]);
						
			// UPDATE composer only if not used by another song
			if($this->isComposerUsedByAnotherSong($song["composer"])) {
				// Does composer exist?
				$cid = $this->doesComposerExist($values["composer"]);
				if($cid > 0) {
					// YES
					$values["composer"] = $cid;
				}
				else {
					// NO --> create composer
					$values["composer"] = $this->createComposer($values["composer"]);
				}
			}
			else {
				// Does composer exist?
				$cid = $this->doesComposerExist($values["composer"]);
				if($cid > 0) {
					// YES: composer exists, but is not used by another song
					$query = "UPDATE composer SET name = \"" . $values["composer"] . "\" WHERE id = $cid";
					$this->database->execute($query);
					$values["composer"] = $cid;
				}
				else {
					// NO: composer exists and is not used by another song (obviously)
					$values["composer"] = $this->createComposer($values["composer"]);
				}
			}
		}
		else {
			// 3) REMOVE composer from song
			$values["composer"] = 0;
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
	
	protected function createComposer($name) {
		$this->regex->isSubject($name);
		$query = "INSERT INTO composer (name) VALUES (\"$name\")";
		return $this->database->execute($query);
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
	
	/**
	 * Checks whether a similar name exists.
	 * @param String $name Name of the composer.
	 * @return The ID of the existent composer or -1 if not exists.
	 */
	private function doesComposerExist($name) {
		if($name == "") {
			return -1;
		}
		$ct = $this->database->colValue("SELECT count(id) as cnt FROM composer WHERE name = ?", "cnt", array(array("s", $name)));
		if($ct < 1) return -1;
		else {
			return $this->database->colValue("SELECT id FROM composer WHERE name = ?", "id", array(array("s", $name)));
		}
	}
	
	private function modifyString($input) {
		// just replace double quotes with single quotes and remove < and >
		$str = $input;
		if(strpos($input, '"') >= 0) {
			$str = str_replace("\"", "'", $input);
		}
		
		// no HTML injection
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
		if($genreId == null || $genreId == "") {
			return null;
		}
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
				$where .= 's.title LIKE "%' . urlencode($value) . '%"';
			}
			else if($type == FieldType::BOOLEAN) {
				if($value >= 0) {
					$where .= $field . " = ";
					$where .= $value == "on" || $value == 1 ? 1 : 0;
				}
			}
			else if($type == FieldType::INTEGER
					|| $type == FieldType::DECIMAL
					|| $type == FieldType::CURRENCY
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
		
		// get data and decode the encoded content
		$encodedData = $this->database->getSelection($query);
		$decodedData = $this->urldecodeSelection($encodedData, array("title", "notes"));
		
		return array(
			"numFilters" => $numFilters,
			"data" => $decodedData
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
			new BNoteError(Lang::txt("RepertoireData_massUpdate_error"));
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
		$selection = $this->findAllJoinedOrdered(RepertoireData::getJoinedAttributes(), "title");
		$selection = $this->urldecodeSelection($selection, array("title", "notes"));
		return $this->appendCustomDataToSelection('s', $selection);
	}
}