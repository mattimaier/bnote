<?php
/**
 * Displays a dropdown list
 **/

class Dropdown implements iWriteable {

	private $name;
	private $options = array();
	private $selected;
	private $disabled = "";
	private $styleClass;
	private $jsChange;
	private $jsLoad;
	private $id;

	function __construct($name) {
		$this->name = $name;
	}

	public function setName($name) {
		$this->name = $name;
	}
	
	public function addOption($label, $value) {
		$this->options[$label] = $value;
	}
	
	public function sortOptions() {
		ksort($this->options);
	}

	public function cleanOptions() {
		$this->options = array();
	}

	public function setSelected($value) {
		$this->selected = $value;
	}

	public function setStyleClass($class) {
		$this->styleClass = $class;
	}
	
	public function setDisabled($disabled = true) {
		$this->disabled = ($disabled) ? "disabled" : "";
	}
	
	public function setOnChange($js) {
		$this->jsChange = $js;
	}
	
	/**
	 * @deprecated Won't work according to HTML5 specs.
	 * @param String $js Function name or inline coding.
	 */
	public function setOnLoad($js) {
		$this->jsLoad = $js;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getName() {
		return $this->name;
	}

	public function write() {
		$style = "";
		if(isset($this->styleClass) && $this->styleClass != "") {
			$style = ' class="' . $this->styleClass . '"';
		}
		
		$jsChange = "";
		if(isset($this->jsChange) && $this->jsChange != "") {
			$jsChange = ' onchange="' . $this->jsChange . '"';
		}
		
		$jsLoad = "";
		if(isset($this->jsLoad) && $this->jsLoad != "") {
			$jsLoad = ' onload="' . $this->jsLoad . '"';
		}
		
		$id = "";
		if(isset($this->id) && $this->id != "") {
			$id = ' id="' . $this->id . '"';
		}
		
		$str = '<SELECT class="form-select" name="' . $this->name . '"' . $style . $jsChange . $jsLoad . $id . ' ' . $this->disabled .'>' . "\n";

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