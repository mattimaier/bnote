<?php

/**
 * Simple DAO for Genres
 * @author Matti
 *
 */
class GenreData extends AbstractData {
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"name" => array("Name", FieldType::CHAR)
		);
		
		$this->references = array();
		
		$this->table = "genre";
		$this->init($dir_prefix);
	}
	
	function delete($id) {
		// only delete when no song with gerne present
		if($this->isGenreUsed($id)) {
			new BNoteError("Die Genre wird in einem oder mehreren Songs verwendet und kann daher nicht gelöscht werden.");
		}
		else {
			parent::delete($id);
		}
	}
	
	private function isGenreUsed($id) {
		$ct = $this->database->getCell("song", "count(*)", "genre = $id");
		return ($ct > 0);
	}
}

?>