<?php

/**
 * Superclass for all module views.
 * @author matti
 *
 */
abstract class AbstractView {
	
	private $controller;
	
	/**
	 * Entry Point for any view.
	 */
	abstract function start();
	
	/**
	 * Write a button with caption "Back" to bring the user back to the
	 * module's home screen.
	 */
	public function backToStart() {
		global $system_data;
		$link = new Link("?mod=" . $system_data->getModuleId(), "Zur&uuml;ck");
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	/**
	 * Prints the confirmation message with the buttons. The back-button links to
	 * the view mode with the given ID in the $_GET array. The delete-button links
	 * to the delete mode.
	 * @param String $label Name of the entity to remove, e.g. "user" or "project" 
	 * @param String $linkBack The link the back button links to, usually to the view-mode.
	 * @param String $linkDelete The link the confirmation links to, usually to the delete-mode.
	 */
	protected function deleteConfirmationMessage($label, $linkDelete, $linkBack) {
		new Message("L&ouml;schen?", "Wollen sie diesen Eintrag wirklich l&ouml;schen?");
		$yes = new Link($linkDelete, strtoupper($label) . " L&Ouml;SCHEN");
		$yes->addIcon("remove");
		$yes->write();
		$this->buttonSpace();
		
		$no = new Link($linkBack, "Zur&uuml;ck");
		$no->addIcon("arrow_left");
		$no->write();
	}
	
	/**
	 * Checks whether $_GET["id"] is set, otherwise terminates with error.
	 */
	protected function checkID() {
		if(!isset($_GET["id"])) {
			new Error("Please specify a user id.");
		}
	}
	
	/**
	 * Convenience function.
	 * @return A string like "?mod=<id>&mode=". Append the mode and other GET parameters.
	 */
	protected function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=";
	}
	
	/**
	 * Creates a dropdown widget with all 12 months of a year.
	 * @param String $name The identifier of the select-tag.
	 */
	protected function createMonthDropdown($name) {
		$dd = new Dropdown($name);
			foreach(Data::getMonths() as $num => $name) {
				$dd->addOption($name, $num);
			}
		return $dd;
	}
	
	/**
	 * Creates a dropdown widget with all year from the selection.
	 * @param Array $years Selection object with all (distinct) years in the column "year".
	 * @param String $name The identifier of the select-tag.
	 */
	protected function createYearDropdown($years, $name) {
		$dd = new Dropdown($name);
		$count = 0;
		foreach($years as $row => $y) {
			if($count == 0) {
				$count++;
				continue;
			}
			$dd->addOption($y["year"], $y["year"]);
		}
		return $dd;
	}
	
	/**
	 * Prints the string which contrains the space inbetween buttons.
	 */
	protected function buttonSpace() {
		echo "&nbsp;&nbsp;";
	}
	
	protected function setController($ctrl) {
		$this->controller = $ctrl;
	}
	
	protected function getController() {
		return $this->controller;
	}
	
	//** convenience methods **
	protected function getData() {
		return $this->controller->getData();
	}
	
	protected function getModId() {
		global $system_data;
		return $system_data->getModuleId();
	}
	
	/**
	 * Prints two br-tags.
	 */
	protected function verticalSpace() {
		echo "<br /><br />\n";
	}
}

?>