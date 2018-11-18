<?php

/**
 * Superclass for all module views.
 * @author matti
 *
 */
abstract class AbstractView {
	
	private $controller;
	
	/**
	 * Name of the parameter that is used in the URL to represent the ID of the record.
	 * @var String
	 */
	protected $idParameter = "id";
	
	/**
	 * Name of the ID field in the record from the database.
	 * @var String
	 */
	protected $idField = "id";
	
	/**
	 * Entry Point for any view.
	 */
	abstract function start();
	
	/**
	 * Contains the buttons with the options that are available on this page/view.
	 */
	function showOptions() {
		if(isset($_GET["mode"])) {
			$mode = $_GET["mode"];
		}
		else {
			$mode = "start";
		}
		
		$opt = $mode . "Options";
		if(method_exists($this, $opt)) {
			$this->$opt();
		}
		else {
			// no button on start page
			if(!$this->isMode("start")) {
				$this->backToStart();
			}
		}
	}
	
	/**
	 * Write a button with caption "Back" to bring the user back to the
	 * module's home screen.
	 */
	public function backToStart() {
		$link = new Link("?mod=" . $this->getData()->getSysdata()->getModuleId(), Lang::txt("back"));
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	/**
	 * Prints the confirmation message with the buttons. The back-button links to
	 * the view mode with the given ID in the $_GET array. The delete-button links
	 * to the delete mode.
	 * @param String $label Name of the entity to remove, e.g. "user" or "project" 
	 * @param String $linkBack The link the back button links to, usually to the view-mode (by default not shown).
	 * @param String $linkDelete The link the confirmation links to, usually to the delete-mode.
	 */
	protected function deleteConfirmationMessage($label, $linkDelete, $linkBack = null) {
		new Message($label . Lang::txt("delete") . "?", Lang::txt("reallyDeleteQ"));
		$yes = new Link($linkDelete, strtoupper($label) . " " . strtoupper(Lang::txt("delete")));
		$yes->addIcon("remove");
		$yes->write();
		$this->buttonSpace();
		
		if($linkBack != null) {
			$no = new Link($linkBack, Lang::txt("back"));
			$no->addIcon("arrow_left");
			$no->write();
		}
	}
	
	/**
	 * Checks whether $_GET["id"] is set, otherwise terminates with error.
	 */
	public function checkID() {
		if(!isset($_GET[$this->idParameter])) {
			new BNoteError(Lang::txt("noUserId"));
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
	static function buttonSpace() {
		//none
	}
	
	protected function setController($ctrl) {
		$this->controller = $ctrl;
	}
	
	protected function getController() {
		return $this->controller;
	}
	
	/**
	 * Returns the data component for this module.
	 * @return AbstractData
	 */	
	protected function getData() {
		return $this->controller->getData();
	}
	
	protected function getModId() {
		global $system_data;
		return $system_data->getModuleId();
	}
	
	/**
	 * Appends custom fields to a form.
	 * @param Form $form Form to append to.
	 * @param String $otype Object type, e.g. 'c' for Contact.
	 * @param array $entity Optional entity to read custom data from.
	 * @param boolean $public_only If only public fields should be added (default true).
	 */
	protected function appendCustomFieldsToForm($form, $otype, $entity = null, $public_only = true) {
		$customFields = $this->getData()->getCustomFields($otype, $public_only);
		for($i = 1; $i < count($customFields); $i++) {
			$field = $customFields[$i];
			$techName = $field["techname"];
			
			// set the value of the element in case the entity is given
			$default = "";
			if($entity != null) {
				$default = $entity[$techName];
			}
			
			// generate the element based on the type
			$element = new Field($techName, $default, $this->getData()->fieldTypeFromCustom($field["fieldtype"]));
			
			$form->addElement($field["txtdefsingle"], $element);
		}
	}
	
	/**
	 * Creates a formatted from-to date string
	 * @param String $fromDbDate From date in YYYY-MM-DD H:i:s
	 * @param String $toDbDate To date in YYYY-MM-DD H:i:s
	 * @return String formatted date and time from - to
	 */
	protected function formatFromToDateShort($fromDbDate, $toDbDate) {
		$dayPartFrom = substr($fromDbDate, 0, 10);
		$dayPartTo = substr($toDbDate, 0, 10);
		if($dayPartFrom == $dayPartTo) {
			return Data::convertDateFromDb($dayPartFrom) . " " . substr($fromDbDate, 11, 5) . " - " . substr($toDbDate, 11, 5);
		}
		return $fromDbDate . " - " . $toDbDate;
	}
	
	/**
	 * Prints two br-tags.
	 */
	public static function verticalSpace() {
		echo "<br /><br />\n";
	}
	
	protected function isMode($mode) {
		return (isset($_GET["mode"]) && $_GET["mode"] == $mode);
	}
	
	/**
	 * Print a flash message.
	 * @param String $message Message body.
	 * @param string $level Level: info, warn, error
	 */
	public static function flash($message, $level="warn") {
		?>
		<div class="flash_message <?php echo $level; ?>"><?php echo $message; ?></div>
		<?php
	}
	
}

?>