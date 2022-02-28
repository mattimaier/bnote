<?php

class ListField implements iWriteable {
	
	private $nameAttribute;
	private $entries;
	private $value = "";
	
	function __construct($name, $entries) {
		$this->nameAttribute = $name;
		$this->entries = $entries;
	}
	
	public function getName() {
		return $this->nameAttribute;
	}
	
	public function setValue($val) {
		$this->value = $val;
	}
	
	public function setIdNameValue($id, $name) {
		$this->value = "$name [id=$id]";
	}
	
	public function write() {
		$placeholder = Lang::txt("ListField_write.placeholder");
		$name = $this->nameAttribute;
		$optionsName = $name . "Options";
		$val = $this->value;
		$out = <<<EOS
			<input name="$name" class="form-control" list="$optionsName" id="$name" placeholder="$placeholder" value="$val">
			<datalist id="$optionsName">
			EOS;
		foreach($this->entries as $i => $row) {
			if($i == 0) continue;
			$out .= '<option value="' . $row["name"] . " [id=" . $row["id"] . ']"></option>'; 
		}
		$out .= "</datalist>";
		return $out;
	}

	
}

?>