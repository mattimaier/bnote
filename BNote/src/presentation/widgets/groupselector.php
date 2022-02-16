<?php

/**
 * Shows a selection of all groups
 * @author Matti
 *
 */
class GroupSelector implements iWriteable {

	/**
	 * DB Selection of groups.
	 * @var Array
	 */
	private $groups;
	
	/**
	 * Simple array with all groups that are marked selected.
	 * @var Array
	 */
	private $selectedGroups;
	
	/**
	 * Name of the field in the form.
	 * @var string
	 */
	private $fieldName;
	
	/**
	 * Stores group IDs not to show/remove from list.
	 * @var Array
	 */
	private $remove;
	
	/**
	 * The name of the column which is shown as option caption.
	 * @var Array
	 */
	private $nameColumn;
	
	/**
	 * Type of the caption content.
	 * @var FieldType
	 */
	private $captionType;
	
	/**
	 * Optional css classes to be added to selection group.
	 * @var String
	 */
	private $cssClass;
	
	/**
	 * Builds a new group selector.
	 * @param array $groups DB Selection of groups.
	 * @param array $selectedGroups Simple array with all groups that are marked selected.
	 * @param string $fieldName Name prefix of the field in the form, e.g. fieldName="group" becomes "group_1" for admins.
	 */
	function __construct($groups, $selectedGroups, $fieldName) {
		$this->groups = $groups;
		$this->selectedGroups = $selectedGroups;
		$this->fieldName = $fieldName;
		$this->remove = array();
		$this->nameColumn = array("name");
		$this->cssClass = null;
	}
	
	function removeGroup($groupId) {
		array_push($remove, $groupId);
	}
	
	/**
	 * Set a single name column.
	 * @param String $nameCol
	 */
	function setNameColumn($nameCol) {
		$this->nameColumn = array($nameCol);
	}
	
	/**
	 * Set multiple name columns.
	 * They will be concatenated by space.
	 * @param Array $nameCols
	 */
	function setNameColumns($nameCols) {
		$this->nameColumn = $nameCols;
	}
	
	function setCaptionType($captionType) {
		$this->captionType = $captionType;
	}
	
	function additionalCssClasses($cssClass) {
		$this->cssClass = $cssClass;
	}
	
	function getName() {
		return $this->fieldName;
	}
	
	function toString() {
		$cssClass = "";
		if($this->cssClass != null) {
			$cssClass = " " . $this->cssClass;
		}
		$out = "<div class=\"groupSelector$cssClass\">\n";
		
		for($i = 1; $i < count($this->groups); $i++) {
			$groupId = $this->groups[$i]["id"];
			if(in_array($groupId, $this->remove)) continue;
			
			// format caption
			$groupName = "";
			for($j = 0; $j < count($this->nameColumn); $j++) {
				if($j > 0) $groupName .= " ";
				$groupName .= $this->groups[$i][$this->nameColumn[$j]];
			}
			switch($this->captionType) {
				case FieldType::BOOLEAN: $groupName = ($groupName == "1") ? Lang::txt("GroupSelector_toString.yes") : Lang::txt("GroupSelector_toString.no"); break;
				case FieldType::DATE: $groupName = Data::convertDateFromDb($groupName); break;
				case FieldType::DATETIME: $groupName = Data::convertDateFromDb($groupName); break;
				case FieldType::CURRENCY:
				case FieldType::DECIMAL: 
					$groupName = Data::convertFromDb($groupName); 
					break;
				case FieldType::INTEGER: $groupName = Data::formatInteger($groupName); break;
			}
			
			$selected = "";
			if(in_array($groupId, $this->selectedGroups)) {
				$selected = "checked";
			}
			
			$out .= '<div class="form-check form-switch">';
			$out .= " <input class=\"form-check-input\" type=\"checkbox\" name=\"" . $this->fieldName . "_$groupId\" $selected />";
			$out .= " <label class=\"form-check-label\" for=\"" . $this->fieldName . "\">$groupName</label>";
			$out .= '</div>';
		}
		
		$out .= "</div>";
		return $out;
	}
	
	public function write() {
		return $this->toString();
	}
	
	/**
	 * Converts the ids given in the $_POST array to a plain array of the selected items.<br/>
	 * The selectedGroups parameter in the constructor has no effect on this method.<br/>
	 * <strong>This method should be called when processing the $_POST request from the client!</strong>
	 * @return Array (flat) with all selected item ids.
	 */
	public function getPlainSelection() {
		$result = array();
		foreach($this->groups as $i => $group) {
			if($i == 0) continue;
			$field = $this->fieldName . "_" . $group["id"];
			if(isset($_POST[$field]) && $_POST[$field] == "on") array_push($result, $group["id"]);
		}
		return $result;
	}
	
	/**
	 * Convenience access method for instance method.<br/>
	 * <i>See instance method for details.</i>
	 */
	public static function getPostSelection($groups, $fieldName) {
		$gs = new GroupSelector($groups, array(), $fieldName);
		return $gs->getPlainSelection();
	}
}

?>