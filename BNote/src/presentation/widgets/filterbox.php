<?php

/**
 * Shows a box with the defined filters which can be applied to a data selection.
 * @author Matti
 *
 */
class Filterbox implements iWriteable {
	
	/**
	 * Saves the filters in the format:  [colname] => array( [caption], [type], [db-selection of values] )
	 * @var Array
	 */
	private $filters;
	
	/**
	 * Link where the filters are submitted.
	 * @var String
	 */
	private $link;
	
	/**
	 * Heading over the filters.
	 * @var String
	 */
	private $formname;
	
	/**
	 * Columns of the dropdown captions.
	 * @var Array
	 */
	private $nameCols;
	
	/**
	 * Css classes to add to main filterbox.
	 * @var String
	 */
	private $cssClass;
	
	function __construct($link) {
		$this->filters = array();
		$this->nameCols = array();
		$this->link = $link;
		
		$this->formname = "Filter";
	}
	
	function addFilter($column, $caption, $type, $values) {
		$this->filters[$column] = array( "caption" => $caption, "type" => $type, "values" => $values );
		$this->nameCols[$column] = array("name"); // default
	}
	
	function setHeading($heading) {
		$this->formname = $heading;
	}
	
	function setCssClass($cssClass) {
		$this->cssClass = $cssClass;
	}
	
	/**
	 * Set the name columuns that are concatinated by space and refer to the given value selection.
	 * @param String $column Name of the column to set the naming for.
	 * @param Array $nameCols Naming columns in the value selection, by default array("name").
	 */
	function setNameCols($column, $nameCols) {
		$this->nameCols[$column] = $nameCols;
	}
	
	function write() {
		$form = new Form($this->formname, $this->link);
		
		foreach($this->filters as $column => $infos) {
			if($infos["type"] == FieldType::SET) {
				// create a dropdown
				$element = new Dropdown($column);
				$element->addOption(Lang::txt("show_all"), -1);
				
				foreach($infos["values"] as $i => $val) {
					// build name
					$name = "";
					for($j = 0; $j < count($this->nameCols[$column]); $j++) {
						if($j > 0) $name .= " ";
						$nameCol = $this->nameCols[$column][$j];
						if(isset($val[$nameCol])) {
							$name .= $val[$nameCol];
						}
					}
					
					// build element
					if(isset($val["id"])) {
						$element->addOption($name, $val["id"]);
					}
				}
				
				if(isset($_POST[$column])) {
					$element->setSelected($_POST[$column]);
				}
				else {
					$element->setSelected(-1);
				}
			}
			else {
				$element = new Field($column, "", $infos["type"]);
			}
			
			$form->addElement($infos["caption"], $element);
		}
		
		$form->changeSubmitButton("Filtern");
		
		if($this->cssClass != null) {
			echo '<div class="' . $this->cssClass . '">';
			$form->write();
			echo '</div>';
		}
		else {
			$form->write();
		}
	}
	
}

?>