<?php
/**
 * Container to display data
**/

class Dataview {

 /* ATTRIBUTES */
 private $elements;
 private $allowNumericLabels = false;
 
 /**
  * Adds an element to the view
  * @param String $label Name of the attribute
  * @param String $value Value of the attribute
  */
 function addElement($label, $value) {
  $this->elements[$label] = $value;
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