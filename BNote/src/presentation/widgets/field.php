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
	private $cssClass = null;
	
	/**
	 * Uneditable textfield.
	 * 
	 * @var int
	 */
	const FIELDTYPE_UNEDITABLE = 99;
	
	/**
	 * Shows a tinyMCE editor instead of a textarea.
	 * 
	 * @var int
	 */
	const FIELDTYPE_TINYMCE = 98;
	
	/**
	 * DateTime Selector.
	 * 
	 * @var int
	 */
	const FIELDTYPE_DATETIME_SELECTOR = 97;
	
	/**
	 * Constructor
	 * 
	 * @param String $name
	 *        	label in the post/get array
	 * @param String $default
	 *        	Default data to be displayed in the field
	 * @param FieldType $type
	 *        	Set a constant of the FieldType class or FIELDTYPE-constants of this class.
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
	public function setValue($value) {
		$this->default_value = $value;
	}
	public function setCssClass($cssClass) {
		$this->cssClass = $cssClass;
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
		    case 9: return $this->Passwordfield(); break;
		    case 10: return $this->Checkboxfield(); break;
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
		$css = "";
		if($this->cssClass != null) {
			$css = ' class="' . $this->cssClass . '"';
		}
		return '<input type="text"' . $css . ' size="' . $this->TEXTLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
	}
	
	/**
	 * Output for a textarea
	 */
	private function Textarea() {
		$css = "";
		if($this->cssClass != null) {
			$css = ' class="' . $this->cssClass . '"';
		}
		return '<textarea name="' . $this->name . '" cols="70" rows="10"' . $css . '>' . $this->default_value . '</textarea>' . "\n";
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
		$css = '';
		if ($this->cssClass != null) {
			$css = " " . $this->cssClass;
		}
		return '<input class="dateChooser' . $css . '" type="text" size="' . $this->DATELENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
	}
	
	/**
	 * Output for a textfield in datetime style
	 */
	private function Datetimefield() {
		$css = '';
		if ($this->cssClass != null) {
			$css = ' ' . $this->cssClass;
		}
		return '<input class="datetimeChooser' . $css . '" type="text" size="' . ($this->DATELENGTH + 6) . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
	}
	
	/**
	 * Output for a textfield in datetime style
	 */
	private function Timefield() {
		return '<input type="text" size="' . ($this->DATELENGTH - 4) . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
	}
	
	/**
	 * Output for a textfield in decimalstyle
	 */
	private function Decimalfield() {
		return '<input type="text" size="' . $this->DECIMALLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />' . "\n";
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
		$dv = strtolower ( $this->default_value );
		$checked = "";
		if ($dv == "checked" || $dv == "true" || $dv == 1)
			$checked = "checked";
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
		// value manipulation to use date field properly
		$spacePos = strpos ( $this->default_value, " " );
		$orgValue = $this->default_value;
		$this->default_value = substr ( $this->default_value, 0, $spacePos );
		
		// css classes
		$css_hour = "";
		$css_min = "";
		if ($this->cssClass != null) {
			$css_hour = ' class="' . $this->cssClass . ' hour"';
			$css_min = ' class="' . $this->cssClass . ' minute"';
		}
		
		// date field
		$datefield = $this->Datefield ();
		$this->default_value = $orgValue;
		
		// parse default value for time
		$colonPos = strpos ( $this->default_value, ":" );
		
		// preset defaults
		$hour = "18";
		$minute = "00";
		
		// load defaults from DB
		global $system_data;
		$default_time_str = $system_data->getDynamicConfigParameter ( "rehearsal_start" );
		if ($default_time_str != null && $default_time_str != "") {
			$hour = substr ( $default_time_str, 0, 2 );
			$minute = substr ( $default_time_str, 3, 2 );
		}
		
		if ($colonPos > 0) {
			$hour = substr ( $this->default_value, $spacePos + 1, $colonPos );
			$minute = substr ( $this->default_value, $colonPos + 1 );
		}
		
		// hour field
		$hourfield = '<select name="' . $this->name . '_hour"' . $css_hour . '>';
		for($h = 6; $h <= 23; $h ++) {
			$sel = ($h == $hour) ? ' selected' : '';
			$hourfield .= '<option value="' . $h . '"' . $sel . '>' . $h . '</option>';
		}
		$hourfield .= '</select>';
		
		// minute field
		$minutefield = '<select name="' . $this->name . '_minute"' . $css_min . '>';
		for($m = 0; $m <= 45; $m = $m + 15) {
			$mf = ($m < 10) ? "00" : $m;
			$sel = ($m == $minute) ? "selected" : "";
			$minutefield .= '<option value="' . $mf . '" ' . $sel . '>' . $mf . '</option>';
		}
		$minutefield .= '</select>';
		
		// combination
		return $datefield . "&nbsp;&nbsp;" . $hourfield . ":" . $minutefield;
	}
	
	private function TimeSelector() {
		// split value into hour and minute
		$colonPos = strpos ( $this->default_value, ":" );
		$hour = "18"; // defaults
		$minute = "00"; // defaults
		if ($colonPos > 0) {
			$hour = substr ( $this->default_value, 0, $colonPos );
			$minute = substr ( $this->default_value, $colonPos + 1 );
		}
		
		// css classes
		$css_hour = "";
		$css_min = "";
		if ($this->cssClass != null) {
			$css_hour = ' class="' . $this->cssClass . ' hour"';
			$css_min = ' class="' . $this->cssClass . ' minute"';
		}
		
		// hour field
		$hourfield = '<select name="' . $this->name . '_hour"' . $css_hour . '>';
		for($h = 6; $h <= 23; $h ++) {
			$sel = ($h == $hour) ? ' selected' : '';
			$hourfield .= '<option value="' . $h . '" ' . $sel . '>' . $h . '</option>';
		}
		$hourfield .= '</select>';
		
		// minute field
		$minutefield = '<select name="' . $this->name . '_minute"' . $css_min . '>';
		for($m = 0; $m <= 45; $m = $m + 15) {
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