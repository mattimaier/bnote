<?php
/**
 * Class for Graphic Elements
**/
class Field implements iWriteable {

 private $TEXTLENGTH = 30;
 private $DATELENGTH = 10;
 private $DECIMALLENGTH = 8;
 private $INTEGERLENGTH = 6;

 private $name;
 private $default_value;
 private $type;

 /**
  * Constructor
  * @param String $name label in the post/get array
  * @param String $default Default data to be displayed in the field
  * @param FieldType $type Set a constant of the FieldType class,
  * 						99 (uneditable text), 98 tinyMCE, 97 Datetime selector.
  */
 function __construct($name, $default, $type) {
  $this->name = $name;
  $this->default_value = $default;
  $this->type = $type;
 }
 
 /**
  * Returns the name of the element
  */
 public function getName() {
 	return $this->name;
 }
 
 /**
  * Returns the default value for the element
  */
 public function getValue() {
 	return $this->default_value;
 }
 
 /**
  * Returns a string with the field in html
  */
 public function write() {
 switch($this->type) {
  case 0: return $this->Textarea(); break;
  case 1: return $this->Integerfield(); break;
  case 2: return $this->Decimalfield(); break;
  case 4: return $this->Datefield(); break;
  case 5: return $this->Timefield(); break;
  case 6: return $this->Datetimefield(); break;
  case 10: return $this->Checkboxfield(); break;
  case 9: return $this->Passwordfield(); break;
  case 12: return $this->Filefield(); break;
  case 96: return $this->TimeSelector(); break;
  case 97: return $this->DatetimeSelector(); break;
  case 98: return $this->tinyMCE(); break;
  case 99: return $this->UneditableField(); break;
  default: return $this->Textfield();
  }
 }

 /**
  * Output for a textfield
  */
 private function Textfield() {
  return  '<input type="text" size="' . $this->TEXTLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
 }

 /**
  * Output for a textarea
  */
 private function Textarea() {
  return '<textarea name="' . $this->name . '" cols="70" rows="10">' . $this->default_value . '</textarea>' . "\n";
 }
 
 private function tinyMCE() {
 	$ret = '<textarea id="tinymce" name="' . $this->name . '" cols="70" rows="10">';
 	$ret .= $this->default_value . '</textarea>' . "\n";
 	return $ret;
 }

 /**
  * Output for a textfield in datestyle
  */
 private function Datefield() {
  return  '<input class="dateChooser" type="text" size="' . $this->DATELENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
 }
 
 /**
  * Output for a textfield in datetime style
  */
 private function Datetimefield() {
  return  '<input class="datetimeChooser" type="text" size="' . ($this->DATELENGTH+6) . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
 }

 /**
  * Output for a textfield in datetime style
  */
 private function Timefield() {
  return  '<input type="text" size="' . ($this->DATELENGTH-4) . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
 }
 
 /**
  * Output for a textfield in decimalstyle
  */
 private function Decimalfield() {
  if(strpos($this->default_value, ",") !== false) $value = str_replace(",", "", $this->default_value);
   else $value = $this->default_value;
  return  '<input type="text" size="' . $this->DECIMALLENGTH . '" name="' . $this->name . '" value="' . $value . '" />' . "\n";
 }

 /**
  * Output for a textfield in integerstyle
  */ 
 private function Integerfield() {
  return '<input type="text" size="' . $this->INTEGERLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
 }
 
 /**
  * Output for a passwordfield
  */
 private function Passwordfield() {
  return '<input type="password" size="' . $this->TEXTLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
 }
 
 /**
  * Output for a checkbox.
  */
 private function Checkboxfield() {
 	$dv = strtolower($this->default_value);
 	$checked = "";
 	if($dv == "checked" || $dv == "true" || $dv == 1) $checked = "checked";
 	return '<input type="checkbox" name="' . $this->name . '" ' . $checked . '/>';
 }
 
 /**
  * Just write out the value including a hidden field for the $_POST array.
  */
 private function UneditableField() {
 	$hidden = '<input type="hidden" name="' . $this->name . '" value="' . $this->default_value . '" />';
 	return $this->default_value . $hidden;
 }
 
 /**
  * Output for a file-input.
  */
 private function Filefield() {
 	return '<input type="file" name="' . $this->name . '" />';
 }
 
 private function DatetimeSelector() {
 	// date field
 	$datefield = $this->Datefield();
 	
 	// hour field
 	$hourfield = '<select name="' . $this->name . '_hour">';
 	for($h = 6; $h <= 23; $h++) {
 		$sel = ($h == 18) ? ' selected' : '';
 		$hourfield .= '<option value="' . $h . '"' . $sel . '>' . $h . '</option>'; 
 	}
 	$hourfield .= '</select>';
 	
 	// minute field
 	$minutefield = '<select name="' . $this->name . '_minute">';
 	for($m = 0; $m <= 45; $m = $m+15) {
 		$mf = ($m < 10) ? "00" : $m;
 		$minutefield .= '<option value="' . $mf . '">' . $mf . '</option>';
 	}
 	$minutefield .= '</select>';
 	
 	// combination
 	return $datefield . "&nbsp;&nbsp;" . $hourfield . ":" . $minutefield;
 }
 
 private function TimeSelector() {
 	// split value into hour and minute
 	$colonPos = strpos($this->default_value, ":");
 	$hour = "18"; // defaults
 	$minute = "00"; // defaults
 	if($colonPos > 0) {
 		$hour = substr($this->default_value, 0, $colonPos);
 		$minute = substr($this->default_value, $colonPos+1);
 	}
 	
 	// hour field
 	$hourfield = '<select name="' . $this->name . '_hour">';
 	for($h = 6; $h <= 23; $h++) {
 		$sel = ($h == $hour) ? ' selected' : '';
 		$hourfield .= '<option value="' . $h . '" ' . $sel . '>' . $h . '</option>';
 	}
 	$hourfield .= '</select>';
 	
 	// minute field
 	$minutefield = '<select name="' . $this->name . '_minute">';
 	for($m = 0; $m <= 45; $m = $m+15) {
 		$mf = ($m < 10) ? "00" : $m;
 		$sel = ($m == $minute) ? "selected" : ""; 
 		$minutefield .= '<option value="' . $mf . '" ' . $sel . '>' . $mf . '</option>';
 	}
 	$minutefield .= '</select>';
 	
 	// combination
 	return $hourfield . ":" . $minutefield;
 }
}

?>