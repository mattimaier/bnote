<?php

/**
 * Instrumente Data Access Object.
 * @author Matti
 *
 */
class InstrumenteData extends AbstractData {
	
	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->fields = array(
				"id" => array(Lang::txt("InstrumenteData_construct.id"), FieldType::INTEGER),
				"name" => array(Lang::txt("InstrumenteData_construct.name"), FieldType::CHAR),
				"rank" => array(Lang::txt("InstrumenteData_construct.rank"), FieldType::INTEGER),
				"category" => array(Lang::txt("InstrumenteData_construct.category"), FieldType::REFERENCE)
		);
	
		$this->references = array(
				"category" => "category",
		);
	
		$this->table = "instrument";
		$this->init($dir_prefix);
	}
	
	function getCategories() {
		return $this->database->getSelection("SELECT * FROM category ORDER BY name");
	}
	
	function getInstruments($cat = 0) {
		$params = array();
		$query = "SELECT * FROM instrument";
		if($cat > 0) {
			$query .= " WHERE category = ? ";
			array_push($params, array("i", $cat));
		}
		$query .= " ORDER BY name";
		return $this->database->getSelection($query, $params);
	}
	
	function getInstrumentsWithCatName() {
		$query = "SELECT i.id, i.name, i.rank, i.category as catid, c.name as category ";
		$query .= "FROM instrument i JOIN category c ON i.category = c.id ";
		$query .= "ORDER BY i.rank, i.name";
		return $this->database->getSelection($query);
	}
	
	function saveInstrumentGroupConfig() {
		$cats = $this->getCategories();		
		$newActiveCats = "";
		foreach($cats as $i => $cat) {
			if($i == 0) continue;
			$fieldName = "category_" . $cat["id"];
			if(isset($_POST[$fieldName]) && $_POST[$fieldName] == "on") {
				if($newActiveCats != "") $newActiveCats .= ",";
				$newActiveCats .= $cat["id"];
			}
		}
		
		$query = "UPDATE configuration SET value = ? WHERE param = 'instrument_category_filter'";
		$this->database->execute($query, array(array("s", $newActiveCats)));
	}
	
	function getActiveCategories() {
		return $this->getSysdata()->getInstrumentCategories();
	}
	
	function delete($id) {
		// check whether instrument is used by someone
		$ct = $this->database->colValue("SELECT count(instrument) as cnt FROM contact WHERE instrument = ?", "cnt", array(array("i", $id)));
		$isUsed = ($ct > 0);
		
		// only delete if not used
		if(!$isUsed) {
			parent::delete($id);
		}
		else {
			new BNoteError(Lang::txt("InstrumenteData_delete.BNoteError"));
		}
	}
}