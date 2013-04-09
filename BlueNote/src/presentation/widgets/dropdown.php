<?php
/**
 * Displays a dropdown list
 **/

class Dropdown implements iWriteable {

	private $name;
	private $options = array();
	private $selected;
	private $styleClass;

	function __construct($name) {
		$this->name = $name;
	}

	public function addOption($label, $value) {
		$this->options[$label] = $value;
	}

	public function setSelected($value) {
		$this->selected = $value;
	}

	public function setStyleClass($class) {
		$this->styleClass = $class;
	}

	public function write() {
		$style = "";
		if(isset($this->styleClass) && $this->styleClass != "") {
			$style = ' class="' . $this->styleClass . '"';
		}
		$str = '<SELECT name="' . $this->name . '"' . $style . '>' . "\n";

		foreach($this->options as $l => $v) {
			$str .= ' <OPTION value="' . $v . '"';
			if($this->selected == $v) $str .= ' selected';
			$str .= '>' . $l . '</OPTION>' . "\n";
		}

		$str .= '</SELECT>' . "\n";
		return $str;
	}

}

?>