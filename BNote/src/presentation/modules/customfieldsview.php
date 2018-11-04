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

		// show custom fields
		$customFields = $this->getData()->getAllCustomFields();
		$table = new Table($customFields);
		$table->renameAndAlign($this->getData()->getFields());
		$table->changeMode("customfields&sub=view");
		$table->setEdit($this->idField);
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
		
		// field type
		$form->removeElement("fieldtype");
		$ddFieldType = new Dropdown("fieldtype");
		foreach($this->getData()->getFieldTypes() as $techType => $name) {
			$ddFieldType->addOption($name, $techType);
		}
		$form->addElement(Lang::txt("fieldtype"), $ddFieldType);
		
		// object type
		$form->removeElement("otype");
		$ddObjectType = new Dropdown("otype");
		foreach($this->getData()->getObjectTypes() as $techType => $name) {
			$ddObjectType->addOption($name, $techType);
		}
		$form->addElement(Lang::txt("objecttype"), $ddObjectType);
		
		$form->write();
	}
	
	function editEntityForm() {
		// entry
		$entry = $this->getData()->findByIdNoRef($_GET[$this->idParameter]);
		
		// form
		$form = new Form(Lang::txt("edit", array($this->getEntityName())),
				$this->modePrefix() . "edit_process&" . $this->idParameter . "=" . $_GET[$this->idParameter]);
		$form->autoAddElements($this->getData()->getFields(),
				$this->getData()->getTable(), $_GET[$this->idParameter]);
		$form->removeElement($this->idField);
		
		// field type
		$form->removeElement("fieldtype");
		$ddFieldType = new Dropdown("fieldtype");
		foreach($this->getData()->getFieldTypes() as $techType => $name) {
			$ddFieldType->addOption($name, $techType);
		}
		$ddFieldType->setSelected($entry["fieldtype"]);
		$form->addElement(Lang::txt("fieldtype"), $ddFieldType);
		
		// object type
		$form->removeElement("otype");
		$ddObjectType = new Dropdown("otype");
		foreach($this->getData()->getObjectTypes() as $techType => $name) {
			$ddObjectType->addOption($name, $techType);
		}
		$ddObjectType->setSelected($entry["otype"]);
		$form->addElement(Lang::txt("objecttype"), $ddObjectType);
		
		$form->write();
	}

}

?>