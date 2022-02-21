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
			"id" => array(Lang::txt("ProgramData_construct.id"), FieldType::INTEGER),
			"name" => array(Lang::txt("ProgramData_construct.name"), FieldType::CHAR),
			"notes" => array(Lang::txt("ProgramData_construct.notes"), FieldType::TEXT),
			"isTemplate" => array(Lang::txt("ProgramData_construct.isTemplate"), FieldType::BOOLEAN)
		);
		
		$this->references = array(
		);
		
		$this->table = "program";
		
		$this->init($dir_prefix);
	}
	
	function getPrograms() {
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
		$query = "DELETE FROM program_song WHERE program = ?";
		$this->database->execute($query, array(array("i", $id)));
		
		parent::delete($id);
	}
	
	function getSongsForProgram($pid) {
		$this->regex->isPositiveAmount($pid);
		
		$query = "SELECT ps.id as psid, ps.rank, ps.song, s.title, c.name as composer, s.length,";
		$query .= " s.notes, st.name as status, g.name as genre ";
		$query .= "FROM song s
					JOIN program_song ps ON ps.song = s.id
					LEFT OUTER JOIN composer c ON s.composer = c.id
					LEFT OUTER JOIN status st ON s.status = st.id 
					LEFT OUTER JOIN genre g ON s.genre = g.id ";
		$query .= "WHERE ps.program = ? ";
		$query .= "ORDER BY ps.rank ASC";
		$selection = $this->database->getSelection($query, array(array("i", $pid)));
		return $this->urldecodeSelection($selection, array("title", "notes"));
	}
	
	function getSongsForProgramPrint($pid) {
		$query = "SELECT s.title, s.notes, s.length ";
		$query .= "FROM program_song ps JOIN song s ON ps.song = s.id ";
		$query .= "WHERE ps.program = ? ";
		$query .= "ORDER BY ps.rank ASC";
		$selection = $this->database->getSelection($query, array(array("i", $pid)));
		return $this->urldecodeSelection($selection, array("title", "notes"));
	}
	
	function getProgramName($id) {
		return $this->database->colValue("SELECT name FROM program WHERE id = ?", "name", array(array("i", $id)));
	}
	
	function getAllSongs() {
		$query = "SELECT id, title, length FROM song ORDER BY title";
		$selection = $this->database->getSelection($query);
		return $this->urldecodeSelection($selection, array("title"));
	}
	
	function addSongToProgram($pid) {
		$max = $this->database->colValue("SELECT max(rank) as mr FROM program_song WHERE program = ?", "mr", array(array("i", $pid)));
		$rank = $max+1;
		
		$query = "INSERT INTO program_song (program, song, rank) VALUES (?, ?, ?)";
		$this->database->execute($query, array(
				array("i", $pid),
				array("i", $_POST["song"]),
				array("i", $rank)
		));
	}
	
	function deleteSongFromProgram($pid, $sid) {
		$query = "DELETE FROM program_song WHERE program = ? AND song = ?";
		$this->database->execute($query, array(array("i", $pid), array("i", $sid)));
	}
	
	function deleteProgramEntry($psid) {
		$query = "DELETE FROM program_song WHERE id = ?";
		$this->database->execute($query, array(array("i", $psid)));
	}
	
	function updateRank($psid, $r) {
		$query = "UPDATE program_song SET rank = ? WHERE id = ?";
		$this->database->execute($query, array(array("i", $r), array("i", $psid)));
	}
	
	/**
	 * Updates the entire program's ranks
	 * @param Array $uiRows Array of Arrays / rows of program with psid, rank, song as index 0, 1 and 2 in row
	 */
	function updateRanks($uiRows) {
		foreach($uiRows as $row) {
			$this->updateRank($row[0], $row[1]);
		}
	}
	
	function totalProgramLength() {
		$query = "SELECT Sec_to_Time(Sum(Time_to_Sec(s.length))) as total ";
		$query .= "FROM song s, program_song ps WHERE ps.song = s.id AND ps.program = ?";
		$res = $this->database->fetchRow($query, array(array("i", $_GET["id"])));
		return $res["total"];
	}
	
	function addProgramWithTemplate() {
		$this->validate($_POST);
		$template = $this->database->colValue("SELECT name FROM program WHERE id = ?", "name", array(array("i", $_POST["template"])));
		
		// create program
		$values = array(
			"name" => $_POST["name"],
			"notes" => Lang::txt("ProgramData_addProgramWithTemplate.message_1") . $template . Lang::txt("ProgramData_addProgramWithTemplate.message_2"),
			"isTemplate" => 0
		);
		$pid = $this->create($values);
		
		// failure check
		if($pid == null || $pid == "") {
			new BNoteError("Das Programm konnte nicht erstellt werden.");
		}
		
		$this->copySongsFromProgram($pid, $_POST["template"]);
		
		return $pid;
	}
	
	function copySongsFromProgram($program_id, $template_id) {
		// copy songs with rank to new program
		$query = "SELECT song, rank FROM program_song WHERE program = ? ORDER BY rank";
		$songs = $this->database->getSelection($query, array(array("i", $template_id)));
		
		// compute offset if the program already contains songs
		$offset = 0;
		$max = $this->database->colValue("SELECT max(rank) mr FROM program_song WHERE program = ?", "mr", array(array("i", $program_id)));
		if($max > $offset) {
			$offset = $max+1;
		}
		
		// add songs from template to program
		$params = array();
		$items = array();
		for($i = 1; $i < count($songs); $i++) {
			$rank = $offset + $i;
			array_push($items, "(?, ?, ?)");
			array_push($params, array("i", $program_id));
			array_push($params, array("i", $songs[$i]["song"]));
			array_push($params, array("i", $rank));
		}
		if(count($items) > 0) {
			$query = "INSERT INTO program_song (program, song, rank) VALUES ". join(",", $items);
			$this->database->execute($query, $params);
		}
	}
	
	function getTemplates() {
		return $this->adp()->getTemplatePrograms();
	}
	
	function getConcertsWithProgram($pid) {
		$query = "SELECT * FROM concert WHERE program = ?";
		return $this->database->getSelection($query, array(array("i", $pid)));
	}
}