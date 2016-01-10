<?php

class MobileList implements iWriteable {
	
	protected $data;
	protected $titleFields;
	protected $subFields;
	protected $doFilter;
	protected $autoDivider;
	protected $editMode;
	protected $keyField;
	protected $nestedListMode;
	
	function __construct($data, $titleFields, $subFields=null) {
		$this->data = $data;
		$this->titleFields = $titleFields;
		$this->subFields = $subFields;
		$this->doFilter = false;
		$this->autoDivider = false;
		$this->editMode = null;
		$this->keyField = "id";
		$this->nestedListMode = false;
	}
	
	function enableFilter() {
		$this->doFilter = true;
	}
	
	function enableAutoDivider() {
		$this->autoDivider = true;
	}
	
	function setEditMode($editMode) {
		$this->editMode = $editMode;
	}
	
	function setKeyField($keyField) {
		$this->keyField = $keyField;
	}
	
	function setNestedListMode() {
		$this->nestedListMode = true;
	}
	
	protected function combineFields($row, $fields) {
		$vals = array();
		foreach($fields as $field) {
			array_push($vals, $row[$field]);
		}
		return join(" ", $vals);
	}
	
	function write() {
		$options = "";
		if($this->doFilter) {
			$options .= "  data-filter=\"true\"";
		}
		if($this->autoDivider) {
			$options .= " data-autodividers=\"true\""; 
		}
		?>
		<ul data-role="listview" <?php echo $options;?>>
		<?php 
		for($i = 1; $i < count($this->data); $i++) {
			$row = $this->data[$i];
			$href = "#";
			if($this->editMode != null) {
				$href = $this->editMode . "&id=" . $row[$this->keyField];
			}
			
			$txt = $this->combineFields($row, $this->titleFields);
			if($this->subFields != null) {
				$txt .= " " . $this->combineFields($row, $this->subFields);
			}
			
			echo "<li>";
			if($this->nestedListMode) {
				echo "<h1>$txt</h1>";
				echo "<ul>";
				foreach($row as $k => $v) {
					if(is_numeric($k)) continue;
					echo "<li><strong>$k</strong><br/>$v</li>";
				}
				echo "</ul>";
			}
			else {
				echo "<a href=\"$href\">$txt</a>";
			}
			echo "</li>\n";
		}
		?>
		</ul>
		<?php
	}
	
}

?>