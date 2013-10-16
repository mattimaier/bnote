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
	 * The name of the column which is shown as caption.
	 * @var string
	 */
	private $nameColumn;
	
	/**
	 * Type of the caption content.
	 * @var FieldType
	 */
	private $captionType;
	
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
		$this->nameColumn = "name";
	}
	
	function removeGroup($groupId) {
		array_push($remove, $groupId);
	}
	
	function setNameColumn($nameCol) {
		$this->nameColumn = $nameCol;
	}
	
	function setCaptionType($captionType) {
		$this->captionType = $captionType;
	}
	
	function toString() {
		$out = "<ul>\n";
		
		for($i = 1; $i < count($this->groups); $i++) {
			$groupId = $this->groups[$i]["id"];
			if(in_array($groupId, $this->remove)) continue;
			
			// format caption
			$groupName = $this->groups[$i][$this->nameColumn];
			switch($this->captionType) {
				case FieldType::BOOLEAN: $groupName = ($groupName == "1") ? "ja" : "nein"; break;
				case FieldType::DATE: $groupName = Data::convertDateFromDb($groupName); break;
				case FieldType::DATETIME: $groupName = Data::convertDateFromDb($groupName); break;
				case FieldType::DECIMAL: $groupName = Data::convertFromDb($groupName); break;
				case FieldType::INTEGER: $groupName = Data::formatInteger($groupName); break;
			}
			
			$selected = "";
			if(in_array($groupId, $this->selectedGroups)) {
				$selected = "checked";
			}
			
			$out .= " <li><input type=\"checkbox\" name=\"" . $this->fieldName . "_$groupId\" $selected/>$groupName</li>\n";
		}
		
		$out .= "</ul>\n";
		return $out;
	}
	
	public function write() {
		return $this->toString();
	}
	
	/**
	 * Converts the ids given in the $_POST array to a plain array of the selected items.<br/>
	 * The selectedGroups parameter in the constructor has no effect on this method.<br/>
	 * <strong>This method should be called when processing the $_POST request from the client!</strong>
	 * @return Flat array with all selected item ids.
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