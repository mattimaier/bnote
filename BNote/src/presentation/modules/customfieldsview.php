<?php

/**
 * Subview of the configuration module.
* @author Matti
*
*/
class CustomFieldsView extends CrudView {

	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("customfield"));
	}

	function showOptions() {
		if(!isset($_GET["sub"])) {
			$this->startOptions();
		}
		else {
			$opt = $_GET["sub"] . "Options";
			if(method_exists($this, $opt)) {
				$this->$opt();
			}
			else {
				if($this->isFunc("edit") || $this->isFunc("edit_process")
						|| $this->isFunc("delete_confirm")) {
							$this->backToViewButton($_GET["id"]);
						}
						else {
							$this->backToStart();
						}
			}
		}
	}

	protected function isFunc($func) {
		return (isset($_GET["sub"]) && $_GET["sub"] == $func);
	}

	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=customfields&sub=";
	}

	function backToStart() {
		global $system_data;
		$link = new Link("?mod=" . $system_data->getModuleId() . "&mode=customfields", Lang::txt("back"));
		$link->addIcon("arrow_left");
		$link->write();
	}

	function start() {
		Writing::h2(Lang::txt("customfields"));

		// show instruments
		$customFields = $this->getData()->findAllNoRef();
		$table = new Table($customFields);
		$table->renameAndAlign($this->getData()->getFields());
		$table->changeMode("customfields&sub=view");
		$table->write();
	}

	function startOptions() {
		// show a back button
		$back = new Link(parent::modePrefix() . "start", Lang::txt("back"));
		$back->addIcon("arrow_left");
		$back->write();

		// add new ones
		$new = new Link($this->modePrefix() . "addEntity", "Feld hinzufügen");
		$new->addIcon("plus");
		$new->write();
	}
	
	function addEntityForm() {
		$form = new Form(Lang::txt("add_entity", array($this->getEntityName())), $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement($this->idField);
		
		
		
		$form->write();
	}

}

?>