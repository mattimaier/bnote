<?php
/**
 * Displays a list of items
 * @author matti
 *
 */
class Plainlist implements iWriteable {
	
	private $data;
	private $nameField = "name";
	private $ulCssClass = "plainlist";
	private $liCssClass;
	private $showEmptyRemark = true;
	
	/**
	 * Creates a new plain list.
	 * @param array $selection DB selection
	 */
	function __construct($selection) {
		$this->data = $selection;
	}
	
	/**
	 * Field to use for the item's name. Default is "name".
	 * @param string $nameField Key of the field in the data selection.
	 */
	function setNameField($nameField) {
		$this->nameField = $nameField;
	}
	
	/**
	 * CSS class(es) that are attached to the list itself. 
	 * @param string $cssClassName
	 */
	function setListCssClass($cssClassName) {
		$this->ulCssClass = $cssClassName;
	}
	
	/**
	 * CSS class(es) that are attached to a list item.
	 * @param String $cssClassName
	 */
	function setItemCssClass($cssClassName) {
		$this->liCssClass = $cssClassName;
	}
	
	/**
	 * Set if a message should be shown when the list is empty.
	 * @param boolean $show True to show, false to hide.
	 */
	function showEmptyRemark($show) {
		$this->showEmptyRemark = $show;
	}
	
	public function write() {
		// css handling
		if($this->ulCssClass != null) {
			echo '<ul class="' . $this->ulCssClass . '">'; 
		}
		else {
			echo '<ul>';
		}
		$attrib = "";
		if($this->liCssClass != null) {
			$attrib = ' class="' . $this->liCssClass . '">';
		}
		
		// data processing
		for($i = 1; $i < count($this->data); $i++) {
			echo "<li$attrib>" . $this->data[$i][$this->nameField] . "</li>";
		}
		echo '</ul>';
		
		// empty remark
		if($this->showEmptyRemark && count($this->data) == 1) {
			Writing::p(Lang::txt("Plainlist_write.empty"));
		}
	}
	
	public static function simpleListToSelection($simpleList) {
		$selection = array(array("name"));
		for($i = 0; $i < count($simpleList); $i++) {
			array_push($selection, array("name" => $simpleList[$i]));
		}
		return $selection;
	}
	
	public function getName() {
		return $this->nameField;
	}
}

?>