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
	}
	
	function removeGroup($groupId) {
		array_push($remove, $groupId);
	}
	
	function toString() {
		$out = "<ul>\n";
		
		for($i = 1; $i < count($this->groups); $i++) {
			$groupId = $this->groups[$i]["id"];
			if(in_array($groupId, $this->remove)) continue;
			
			$groupName = $this->groups[$i]["name"];
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
}

?>