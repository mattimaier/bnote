<?php
/**
 * Class for Graphic Elements
 **/
class Field implements iWriteable {
	
	private $TEXTLENGTH = 30;
	private $DATELENGTH = 10;
	private $DECIMALLENGTH = 8;
	private $INTEGERLENGTH = 6;
	private $MINSECLENGTH = 6;
	private $name;
	private $default_value;
	private $type;
	private $cssClass = null;
	private $cols = 70;
	private $rows = 10;
	
	/**
	 * Uneditable textfield.
	 * 
	 * @var integer
	 */
	const FIELDTYPE_UNEDITABLE = 99;
	
	/**
	 * Shows a tinyMCE editor instead of a textarea.
	 * 
	 * @var integer
	 */
	const FIELDTYPE_TINYMCE = 98;
	
	/**
	 * DateTime Selector.
	 * 
	 * @var integer
	 */
	const FIELDTYPE_DATETIME_SELECTOR = 97;
	
	/**
	 * Creates a multifile picker
	 * @var integer
	 */
	const FIELDTYPE_MULTIFILE = 95;
	
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
	
	public function getType() {
		return $this->type;
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
	
	public function setColsAndRows($rows, $cols) {
		$this->rows = $rows;
		$this->cols = $cols;
	}
 
	/**
	 * Returns a string with the field in html
	 */
	public function write() {
		switch($this->type) {
		 	case FieldType::TEXT: return $this->Textarea(); break;
		    case FieldType::INTEGER: return $this->Integerfield(); break;
		    case FieldType::DECIMAL: return $this->Decimalfield(); break;
		    case FieldType::DATE: return $this->Datefield(); break;
		    case FieldType::TIME: return $this->Timefield(); break;
		    case FieldType::DATETIME: return $this->Datetimefield(); break;
		    case FieldType::PASSWORD: return $this->Passwordfield(); break;
		    case FieldType::BOOLEAN: return $this->Checkboxfield(); break;
		    case FieldType::FILE: return $this->Filefield(); break;
		    case FieldType::CURRENCY: return $this->Currencyfield(); break;
		    case FieldType::MINSEC: return $this->MinuteSecondfield(); break;
		    case Field::FIELDTYPE_MULTIFILE: return $this->multifilefield(); break;
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
		$css = ($this->cssClass != null) ? $this->cssClass : "";
		return '<input type="text" class="form-control ' . $css . '" size="' . $this->TEXTLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />';
	}
	
	/**
	 * Output for a textarea
	 */
	private function Textarea() {
		$css = ($this->cssClass != null) ? $this->cssClass : "";
		return '<textarea name="' . $this->name . '" cols="' . $this->cols . '" rows="' . $this->rows . '" class="form-control ' . $css . '">' . $this->default_value . '</textarea>' . "\n";
	}
	
	private function tinyMCE() {
		$ret = '<textarea id="tinymce" name="' . $this->name . '" cols="' . $this->cols . '" rows="' . $this->rows . '">';
		$ret .= $this->default_value . '</textarea>' . "\n";
		return $ret;
	}
	
	/**
	 * Output for a textfield in datestyle
	 */
	private function Datefield() {
		$css = ($this->cssClass != null) ? $this->cssClass : "";
		return '<input class="form-control ' . $css . '" type="date" name="' . $this->name . '" value="' . $this->default_value . '" />';
	}
	
	/**
	 * Output for a textfield in datetime style
	 */
	private function Datetimefield() {
		$css = ($this->cssClass != null) ? $this->cssClass : "";
		$val = $this->default_value;
		// optionally convert database dates to HTML5-compatible dates
		if(is_string($val) && strlen($val) > 10 && $val[10] == " ") {
			$val = substr($val, 0, 10) . "T" . substr($val, 11);
		}
		return '<input class="form-control ' . $css . '" type="datetime-local" name="' . $this->name . '" value="' . $val . '" />';
	}
	
	/**
	 * Output for a textfield in datetime style
	 */
	private function Timefield() {
		$css = ($this->cssClass != null) ? $this->cssClass : "";
		return '<input class="form-control ' . $css . '" type="time" size="' . ($this->DATELENGTH - 4) . '" name="' . $this->name . '" value="' . $this->default_value . '" />';
	}
	
	/**
	 * Output for a textfield in decimalstyle
	 */
	private function Decimalfield() {
		$css = ($this->cssClass != null) ? $this->cssClass : "";
		return '<input type="number" step="0.01" class="form-control ' . $css . '" size="' . $this->DECIMALLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />';
	}
	
	/**
	 * Output for a textfield in decimalstyle
	 */
	private function Currencyfield() {
		$sysdata = $GLOBALS["system_data"];
		$currency = $sysdata->getDynamicConfigParameter("currency");
		return '
			<div class="input-group mb-3">
			  <span class="input-group-text">' . $currency . '</span>
			  <input type="number" step="0.01" class="form-control" name="' . $this->name . '" value="' . $this->default_value . '" />
			</div>
		';
	}
	
	/**
	 * Output for a textfield in integerstyle
	 */
	private function Integerfield() {
		return '<input type="number" step="1" class="form-control" size="' . $this->INTEGERLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" />';
	}
	
	/**
	 * Output for a passwordfield
	 */
	private function Passwordfield() {
		return '<input type="password" class="form-control" size="' . $this->TEXTLENGTH . '" name="' . $this->name . '" value="' . $this->default_value . '" aria-describedby="passwordHelpBlock" />
		<div id="passwordHelpBlock" class="form-text">' . Lang::txt("Field.password_description") . '</div>';
	}
	
	/**
	 * Field representing minute and second input.
	 */
	private function MinuteSecondfield() {
		$value = Data::convertMinSecFromDb($this->default_value);
		$name = $this->name;
		return <<<EOS
			<div class="input-group">
				<input type="text" class="form-control" name="$name" value="$value" aria-describedby="minsecunit">
				<span class="input-group-text" id="minsecunit">min</span>
			</div>
		EOS;
	}
	
	/**
	 * Output for a checkbox.
	 */
	private function Checkboxfield() {
		$dv = strtolower ( $this->default_value );
		$checked = "";
		if ($dv == "checked" || $dv == "true" || $dv == 1)
			$checked = "checked";
		return '<input type="checkbox" role="switch" class="form-check-input" name="' . $this->name . '" ' . $checked . '/>';
	}
	
	/**
	 * Just write out the value including a hidden field for the $_POST array.
	 */
	private function UneditableField() {
		return '<input type="text" class="form-control" name="' . $this->name . '" value="' . $this->default_value . '" disabled/>';
	}
	
	/**
	 * Output for a file-input.
	 */
	private function Filefield() {
		return '<input type="file" class="form-control" name="' . $this->name . '" />';
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
		$hourfield = '<select class="form-select" name="' . $this->name . '_hour"' . $css_hour . '>';
		for($h = 6; $h <= 23; $h ++) {
			$sel = ($h == $hour) ? ' selected' : '';
			$hourfield .= '<option value="' . $h . '"' . $sel . '>' . $h . '</option>';
		}
		$hourfield .= '</select>';
		
		// minute field
		$minutefield = '<select class="form-select" name="' . $this->name . '_minute"' . $css_min . '>';
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
		$hourfield = '<select class="form-select" name="' . $this->name . '_hour"' . $css_hour . '>';
		for($h = 6; $h <= 23; $h ++) {
			$sel = ($h == $hour) ? ' selected' : '';
			$hourfield .= '<option value="' . $h . '" ' . $sel . '>' . $h . '</option>';
		}
		$hourfield .= '</select>';
		
		// minute field
		$minutefield = '<select class="form-select" name="' . $this->name . '_minute"' . $css_min . '>';
		for($m = 0; $m <= 45; $m = $m + 15) {
			$mf = ($m < 10) ? "00" : $m;
			$sel = ($m == $minute) ? "selected" : "";
			$minutefield .= '<option value="' . $mf . '" ' . $sel . '>' . $mf . '</option>';
		}
		$minutefield .= '</select>';
		
		// combination
		return $hourfield . ":" . $minutefield;
	}
	
	private function multifilefield() {
		?>
  		<input class="form-control" type="file" name="<?php echo $this->name; ?>[]" multiple />
		<?php
	}
}

?>