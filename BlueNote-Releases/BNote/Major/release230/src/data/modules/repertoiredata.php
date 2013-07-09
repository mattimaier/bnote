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
	function __construct() {
		$this->fields = array(
			"id" => array("Titel ID", FieldType::INTEGER),
			"title" => array("Titel", FieldType::CHAR),
			"length" => array("L&auml;nge", FieldType::CHAR), // not TIME, because of second precision
			"genre" => array("Genre", FieldType::REFERENCE),
			"composer" => array("Komponist / Arrangeur", FieldType::CHAR),
			"status" => array("Status", FieldType::REFERENCE),
			"notes" => array("Anmerkungen", FieldType::TEXT)
		);
		
		$this->references = array(
			"genre" => "genre",
			"composer" => "composer",
			"status" => "status"
		);
		
		$this->table = "song";
		
		$this->init();
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
		// modify length
		$values["length"] = "0:" . $values["length"];
		
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
		
		parent::create($values);
	}
	
	function update($id, $values) {
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
		parent::update($id, $values);
	}
	
	function delete($id) {
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
		$ct = $this->database->getCell("composer", "count(id)",
								"name = \"$name\"");
		if($ct < 1) return -1;
		else {
			return $this->database->getCell("composer", "id", "name = \"" . $values["composer"] . "\"");
		}
	}
}

?>