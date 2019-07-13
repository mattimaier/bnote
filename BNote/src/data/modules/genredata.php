<?php

/**
 * Simple DAO for Genres
 * @author Matti
 *
 */
class GenreData extends AbstractData {
	
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("GenreData_construct.id"), FieldType::INTEGER),
				"name" => array(Lang::txt("GenreData_construct.name"), FieldType::CHAR)
		);
		
		$this->references = array();
		
		$this->table = "genre";
		$this->init($dir_prefix);
	}
	
	function delete($id) {
		// only delete when no song with gerne present
		if($this->isGenreUsed($id)) {
			new BNoteError(Lang::txt("GenreData_delete.BNoteError"));
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