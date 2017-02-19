<?php
/**
 * Container to display data
**/

class Dataview {

 /**
  * Key-Value pairs that are going to be displayed.
  * @var array
  */
 private $elements;
 
 /**
  * If numeric labels should be displayed (true) or not (false).
  * @var boolean
  */
 private $allowNumericLabels = false;
 
 /**
  * Sets an element / value with the given lable.
  * @param String $label Name of the attribute
  * @param String $value Value of the attribute
  */
 function addElement($label, $value) {
  $this->elements[$label] = $value;
 }
 
 /**
  * Gets the value of the given element.
  * @param string $label Label / key of the element.
  * @return string value
  */
 function getElement($label) {
 	if(key_exists($label, $this->elements)) {
		return $this->elements[$label];
 	}
 	return null;
 }
 
 /**
  * Resolves the given foreign field to its named value.
  * @param string $label Element key.
  * @param string $table Foreign table that is referenced by the foreign key.
  * @param string $idField ID field in that table, by default "id"
  * @param array $nameArray Name array, by default just "name"
  */
 function resolveForeignElement($label, $table, $idField="id", $nameArray=array("name")) {
 	$refId = $this->getElement($label);
 	if($refId == null || $refId == "" || $refId < 1) {
 		$this->elements[$label] = "";
 		return;
 	}
 	global $system_data;
 	$row = $system_data->dbcon->getRow("SELECT " . join(",", $nameArray) . 
 			" FROM $table WHERE $idField = " . $refId);
 	$values = array();
 	foreach($nameArray as $i => $nameField) {
 		array_push($values, $row[$nameField]);
 	}
 	$this->addElement($label, join(" ", $values));
 }
 
 /**
  * Automatically adds all records from array.
  * @param Array $selection Flat array in form of [name] => [value].
  */
 function autoAddElements($selection) {
 	$this->elements = $selection;
 }
 
 /**
  * Remove the given element from view.
  * @param String $name The name/label of the attribute.
  */
 function removeElement($name) {
 	unset($this->elements[$name]);
 }
 
 /**
  * Rename the attribute.
  * @param unknown_type $name The name/label of the attribute.
  * @param unknown_type $newName The new name/label of the attribute.
  */
 function renameElement($name, $newName) {
 	if(array_key_exists($name, $this->elements)) {
 		$v = $this->elements[$name];
 		unset($this->elements[$name]);
 		$this->elements[$newName] = $v;
 	}
 }
 
 function allowNumericLabels($anl = true) {
 	$this->allowNumericLabels = $anl;
 }
 
 function autoRename($fields) {
 	foreach($fields as $col => $info) {
 		// convert values in case of dates and decimals
 		if($info[1] == FieldType::DATE || $info[1] == FieldType::DATETIME) {
 			$this->elements[$col] = Data::convertDateFromDb($this->elements[$col]);
 		}
 		else if($info[1] == FieldType::DECIMAL) {
 			$this->elements[$col] = Data::convertFromDb($this->elements[$col]);
 		}
 		else if($info[1] == FieldType::BOOLEAN) {
 			if($this->elements[$col] == 1) $this->elements[$col] = "ja";
 			else {
 				$this->elements[$col] = "nein";
 			}
 		}
 		
 		$this->renameElement($col, $info[0]);
 	}
 }

 function write() {
  echo '<table>' . "\n";

  foreach($this->elements as $label => $element) {
  	if(is_numeric($label) && !$this->allowNumericLabels) continue;
  	echo " <tr>\n";
  	echo "  <td><b>" . ucfirst($label) . "</b></td>\n";
  	echo "  <td>" . $element . "</td>\n";
  	echo " </tr>\n";
  }
  echo '</table>' . "\n<br>\n";
 }

}

?>