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
	function __construct() {
		$this->fields = array(
				"id" => array("ID", FieldType::INTEGER),
				"name" => array("Name", FieldType::CHAR),
				"category" => array("Kategorie", FieldType::REFERENCE)
		);
	
		$this->references = array(
				"category" => "category",
		);
	
		$this->table = "instrument";
		$this->init();
	}
	
	function getCategories() {
		return $this->database->getSelection("SELECT * FROM category ORDER BY name");
	}
	
	function getInstruments($cat = 0) {
		$where = "";
		if($cat > 0) {
			$where = "WHERE category = $cat";
		}
		return $this->database->getSelection("SELECT * FROM instrument $where ORDER BY name");
	}
	
	function getInstrumentsWithCatName() {
		$query = "SELECT i.id, i.name, i.category as catid, c.name as category ";
		$query .= "FROM instrument i JOIN category c ON i.category = c.id ";
		$query .= "ORDER BY i.name";
		return $this->database->getSelection($query);
	}
	
	function saveInstrumentGroupConfig() {
		$cats = $this->getCategories();		
		$newActiveCats = "";
		
		foreach($cats as $i => $cat) {
			$fieldName = "category_" . $cat["id"];
			if(isset($_POST[$fieldName]) && $_POST[$fieldName] == "on") {
				if($newActiveCats != "") $newActiveCats .= ",";
				$newActiveCats .= $cat["id"];
			}
		}
		
		$query = "UPDATE configuration SET value = '$newActiveCats' WHERE param = 'instrument_category_filter'";
		$this->database->execute($query);
	}
	
	function getActiveCategories() {
		return $this->getSysdata()->getInstrumentCategories();
	}
	
	function delete($id) {
		// check whether instrument is used by someone
		$ct = $this->database->getCell("contact", "count(instrument)", "instrument = $id");
		$isUsed = ($ct > 0);
		
		// only delete if not used
		if(!$isUsed) {
			parent::delete($id);
		}
		else {
			new Error("Das Instrument kann nicht gelöscht werden, da es mindestens einem Kontakt zugeordnet ist.");
		}
	}
}

?>