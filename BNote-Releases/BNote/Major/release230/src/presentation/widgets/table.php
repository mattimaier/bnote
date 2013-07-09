<?php
/**
 * Displays a table with data
**/
class Table implements iWriteable {

 private $data;
 private $edit;
 private $primkey;
 private $modid;
 private $mode;
 private $foreign;
 private $lastlines = array();
 private $remove = array();
 private $formats = array();
 private $headernames = array();
 
 /**
  * Creates a new table
  * @param Array $data Table data, e.g. from a getSelection-Query
  */
 function __construct($data) {
  $this->data = $data;
  $this->edit = false;

  global $system_data;
  $this->modid = $system_data->getModuleId();
  $this->mode = "view";
  }

 function setEdit($primkey) {
  $this->edit = true;
  $this->primkey = $primkey;
  }

 function changeMode($mode) {
 	$this->mode = $mode;
 }
 
 function setForeign($field, $table, $idcolumn, $namecolumn) {
 	$this->foreign[$field] = array($table, $idcolumn, $namecolumn);
 }
 
 /**
  * Adds a line at the end of the table with all cells merged except the last and displays the value in the last cell
  * @param $label Text in the merged cell
  * @param $value Value of the last cell
  */
 function addSumLine($label, $value) {
 	$this->lastlines[$label] = $value;
 }
 
 /**
  * Removes the given column from the table
  * @param $name
  */
 function removeColumn($name) {
 	array_push($this->remove, $name);
 }
 
 /**
  * Sets the format of a specific column.
  * @param Integer $column Id of the column.
  * @param String $format One of the following formats: INT, DECIMAL, TEXT, DATE, BOOLEAN
  */
 function setColumnFormat($column, $format) {
 	$this->formats[$column] = $format;
 }
 
 /**
  * Renames the column headers and aligns the column headers according to the type.
  * @param Array $fields Fields-Array as described in AbstractDAO.
  */
 function renameAndAlign($fields) {
 	foreach($fields as $f => $settings) {
 		$this->headernames[$f] = $settings[0];
 		switch($settings[1]) {
 			case FieldType::INTEGER: $this->setColumnFormat($f, "INT"); break;
 			default: $this->setColumnFormat($f, FieldType::getTypeForId($settings[1])); break;
 		}
 	}
 }
 
 /**
  * Changes the name of the header.
  * @param String $field Name of the field.
  * @param String $newName New name of the field.
  */
 function renameHeader($field, $newName) {
 	$this->headernames[$field] = $newName;
 }
 
 /**
  * Function that is called just before the value is written.
  * This method can be overridden by subclasses to implment special behaviour.
  * @param String $value Value before its written (usually formatted).
  * @param String $col Name of the column.
  * @return The value that will be written.
  */
 protected function editValue($value, $col) {
 	return $value;
 }

 /**
  * (non-PHPdoc)
  * @see iWriteable::write()
  */
 function write() { 	
  echo '<table cellpadding="0" cellspacing="0">' . "\n";

  $head = true;
  $empty = false;

  # Check for empty Table
  if(count($this->data) == 1) $empty = true;

  # Table
  $regex = new Regex();
  
  foreach($this->data as $id => $row) {
   echo ' <tr>' . "\n";
   
   foreach($row as $id => $value) {
   	if(in_array($id, $this->remove)) continue;
    if($head) {
     # Header
     if(isset($this->headernames[strtolower($value)])) {
     	$headerLabel = $this->headernames[strtolower($value)];
     }
     else {
     	$headerLabel = $value;
     	}
     echo '  <td class="DataTable_Header">' . $headerLabel . '</td>' . "\n";
     }
     else if(!is_numeric($id)) {
      # Data
      echo '  <td class="DataTable"';
      
      // Check whether the value is a decimal -> if so, align right
      $isMoney = $regex->isMoneyQuiet($value);
      if($isMoney && !isset($this->formats[$id])) echo ' align="right"';
      
      // Check for special format requests
      if(isset($this->formats[$id])) {
      	if($this->formats[$id] == "INT" || $this->formats[$id] == "DECIMAL") echo ' align="right"';
      }
      
      echo '>';
      
      // Check for primary keys
      if($this->edit) { # && $id == $this->primkey
		echo '<a class="silent" href="?mod=' . $this->modid . '&mode=' . $this->mode . '&id=' . $row[$this->primkey] . '">';
      }
      
      // Check for foreign keys
      if(isset($this->foreign[$id]) && !empty($value)) {
      	global $system_data;
      	$arr = $system_data->dbcon->getForeign($this->foreign[$id][0], $this->foreign[$id][1], $this->foreign[$id][2]);
      	$value = $arr[$value];
      }
      
      // Check whether the value is a decimal -> if so, change . to ,
      if($isMoney && !isset($this->formats[$id])) $value = Data::convertFromDb($value);
      
      // Check for special format requests
      if(isset($this->formats[$id])) {
      	if($this->formats[$id] == "DECIMAL") {
      		$value = Data::convertFromDb($value);
      	}
      	if($this->formats[$id] == "DATE") {
      		$value = Data::convertDateFromDb($value);
      	}
      	if($this->formats[$id] == "BOOLEAN") {
      		if($value == 1) $value = "ja";
      		else $value = "nein";
      	}
      }
      
      // Check whether the value is empty -> if so, change to -
      if(empty($value)) $value = "-";
      
      // Check whether the value is a textarea -> if so, display breaks, etc.
      if(strlen($value) > 100) $value = "<pre>$value</pre>";
      
      // Check for date values
      if($regex->isDatabaseDateQuiet($value) && !isset($this->formats[$id])) {
      	$value = Data::convertDateFromDb($value);
      }
      
      // build in functionality to edit values for special cases
      $value = $this->editValue($value, $id);
      
      echo $value;
      
      if($this->edit) echo '</a>'; # && $id == $this->primkey
      echo '</td>' . "\n";
      }
    }
   echo ' </tr>';
   if($head) $head = false;

   # Write empty message
   if($empty) echo ' <TR><TD colspan="' . count($row) . '">[Es wurden keine Eintr&auml;ge gefunden.]</TD></TR>' . "\n";
   }
   
  // write last lines
  foreach($this->lastlines as $label => $value) {
  	echo " <tr>\n";
   	// last row for sums
   	echo "  <td colspan=\"" . (count($this->data[0])-count($this->remove)-1). "\" class=\"DataTable_Sum\" align=\"right\">" . $label . "</td>\n";
   	if($regex->isMoneyQuiet($value)) $value = Data::convertFromDb($value);
	echo "  <td class=\"DataTable_Sum\" align=\"right\">" . $value . "</td>\n";
	echo ' </tr>';
  } 
  
  echo '</table>' . "\n";
  }

}

?>