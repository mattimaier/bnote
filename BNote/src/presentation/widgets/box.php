<?php

/**
 * 
 * Displays boxes from a given data-selection
 * @author matti
 *
 */

class Box implements iWriteable {
	
	/** Attributes **/
	private $data;
	private $sysdata;
	
	private $key;
	private $header;
	
	/**
	 * Constructor
	 * @param Data $data Data-selection from a database->getSelection-call
	 */
	function __construct($data) {
		$this->data = $data;
		$this->key = "";
		$this->header = "";
		
		global $system_data;
		$this->sysdata = $system_data;
	}
	
	/**
	 * Sets the fieldname as the key of the boxes
	 * @param String $fieldname Name of the primary key column
	 */
	public function setKey($fieldname) {
		$this->key = $fieldname;
	}
	
	/**
	 * Sets a fieldname as the header of the boxes,
	 * if not set, no field is set as header
	 * @param String $fieldname Name of the column
	 */
	public function setHeader($fieldname) {
		$this->header = $fieldname;
	}
	
	function write() {
		
		for($i = 1; $i < count($this->data); $i++) {
			if($this->key != "") {
				echo '<a href="?mod=' . $this->sysdata->getModuleId() . '&mode=view&id=' . $this->data[$i][$this->key] . '">';
			}
			
			echo "<div class=\"account\">";
			
			foreach($this->data[$i] as $field => $content) {
				if(is_numeric($field)) continue;
				if($field == $this->key) continue;
				if($field == $this->header) echo "<b>";
				echo $content;
				if($field == $this->header) echo "</b>";
				echo "<br>";
			}
			
			echo "</div>";
			
			if($this->key != "") echo "</a>\n";
		}
	}
	
	public function getName() {
		return $this->key;
	}
	
	// END OF CLASS
}