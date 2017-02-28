<?php

/**
 * 
 * Data Access Object for program methods in concert module.
 * @author matti
 *
 */
class ProgramData extends AbstractData {
	
	function __construct($dir_prefix="") {
		$this->fields = array(
			"id" => array("Programm ID", FieldType::INTEGER),
			"name" => array("Name", FieldType::CHAR),
			"notes" => array("Anmerkungen", FieldType::TEXT),
			"isTemplate" => array("Vorlage", FieldType::BOOLEAN)
		);
		
		$this->references = array(
		);
		
		$this->table = "program";
		
		$this->init($dir_prefix);
	}
	
	function getProgramme() {
		$query = "SELECT id, name, isTemplate, notes FROM program";
		return $this->database->getSelection($query);
	}
	
	function create($values) {
		if(isset($values["isTempalte"])) {
			$values["isTemplate"] = "1";
		}
		else {
			$values["isTemplate"] = "0";
		}
		return parent::create($values);
	}
	
	function update($id, $values) {
		if(isset($values["isTemplate"])) {
			$values["isTemplate"] = "1";
		}
		else {
			$values["isTemplate"] = "0";
		}
		parent::update($id, $values);
	}
	
	function delete($id) {
		// also remove the entries in program_song
		$query = "DELETE FROM program_song WHERE program = $id";
		$this->database->execute($query);
		
		parent::delete($id);
	}
	
	function getSongsForProgram($pid) {
		$query = "SELECT ps.id as psid, ps.rank, ps.song, s.title, c.name as composer, s.length,";
		$query .= " g.name as genre, s.notes, st.name as status ";
		$query .= "FROM song s, program_song ps, composer c, genre g, status st ";
		$query .= "WHERE ps.program = $pid AND ps.song = s.id AND s.composer = c.id";
		$query .= " AND s.genre = g.id AND s.status = st.id ";
		$query .= "ORDER BY ps.rank ASC";
		return $this->database->getSelection($query);
	}
	
	function getSongsForProgramPrint($pid) {
		$query = "SELECT s.title, s.notes, s.length ";
		$query .= "FROM program_song ps JOIN song s ON ps.song = s.id ";
		$query .= "WHERE ps.program = $pid ";
		$query .= "ORDER BY ps.rank ASC";
		return $this->database->getSelection($query);
	}
	
	function getProgramName($id) {
		return $this->database->getCell($this->table, "name", "id = $id");
	}
	
	function getAllSongs() {
		$query = "SELECT id, title, length FROM song ORDER BY title";
		return $this->database->getSelection($query);
	}
	
	function addSongToProgram($pid) {
		$max = $this->database->getCell("program_song", "max(rank)", "program = $pid");
		$rank = $max+1;
		
		$query = "INSERT INTO program_song (program, song, rank) VALUES ($pid, " . $_POST["song"] . ", $rank)";
		$this->database->execute($query);
	}
	
	function deleteSongFromProgram($pid, $sid) {
		$query = "DELETE FROM program_song WHERE program = $pid AND song = $sid";
		$this->database->execute($query);
	}
	
	function updateRank($pid, $psid, $r) {
		$query = "UPDATE program_song SET rank = $r WHERE id = $psid";
		$this->database->execute($query);
	}
	
	function totalProgramLength() {
		$query = "SELECT Sec_to_Time(Sum(Time_to_Sec(s.length))) as total ";
		$query .= "FROM song s, program_song ps WHERE ps.song = s.id AND ps.program = " . $_GET["id"];
		$res = $this->database->getRow($query);
		return $res["total"];
	}
	
	function addProgramWithTemplate() {
		$this->validate($_POST);
		$template = $this->database->getCell($this->table, "name", "id = " . $_POST["template"]);
		
		// create program
		$values = array(
			"name" => $_POST["name"],
			"notes" => "aus Vorlage $template erstellt",
			"isTemplate" => 0
		);
		$pid = $this->create($values);
		
		// failure check
		if($pid == null || $pid == "") {
			new BNoteError("Das Programm konnte nicht erstellt werden.");
		}
		
		// copy songs with rank to new program
		$query = "SELECT song, rank FROM program_song WHERE program = " . $_POST["template"];
		$songs = $this->database->getSelection($query);
		
		$query = "INSERT INTO program_song (program, song, rank) VALUES ";
		$count = 0;
		for($i = 1; $i < count($songs); $i++) {
			$query .= "(";
			$query .= $pid . ", " . $songs[$i]["song"] . ", " . $songs[$i]["rank"];
			$query .= "), ";
			$count++;
		}
		if($count > 0) {
			$query = substr($query, 0, strlen($query)-2);
			$this->database->execute($query);
		}
		
		return $pid;
	}
	
	function getTemplates() {
		return $this->adp()->getTemplatePrograms();
	}
}