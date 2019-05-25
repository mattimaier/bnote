<?php

/**
 * View for configuration module.
 * @author matti
 *
 */
class KonfigurationView extends CrudRefLocationView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("KonfigurationView_construct.EntityName"));
	}
	
	function showOptions() {
		if(isset($_GET["mode"])) {
			if($_GET["mode"] == "instruments") {
				$this->getController()->getInstrumentsView()->showOptions();
			}
			else if($_GET["mode"] == "customfields") {
				$this->getController()->getCustomFieldsView()->showOptions();
			}
			else {
				parent::showOptions();
			}
		}
		else {
			parent::showOptions();
		}
	}
	
	function start() {
		$this->showWarnings();
		
		Writing::p(Lang::txt("KonfigurationView_start.warning"));
		
		$parameter = $this->getData()->getActiveParameter();

		$table = new Table($parameter);
		$table->renameHeader("caption", Lang::txt("KonfigurationView_start.caption"));
		$table->renameHeader("value", Lang::txt("KonfigurationView_start.value"));
		$table->setEdit("param");
		$table->removeColumn("param");
		$table->changeMode("edit");
		$table->write();
	}
	
	function startOptions() {		
		// instrument configuration
		$istr = new Link($this->modePrefix() . "instruments", Lang::txt("KonfigurationView_start.instruments"));
		$istr->addIcon("instrument");
		$istr->write();
		
		// fields configuration
		$cuf = new Link($this->modePrefix() . "customfields", Lang::txt("KonfigurationView_start.customfields"));
		$cuf->addIcon("copy_link");
		$cuf->write();
	}
	
	protected function showWarnings() {
		if($this->getData()->getSysdata()->getDynamicConfigParameter("google_api_key") == "") {
			$this->flash(Lang::txt("KonfigurationView_showWarnings.Warnings"));
		}
	}
	
	function edit() {
		$this->checkID();
		
		// header
		Writing::h2(Lang::txt("KonfigurationView_edit.header"));
		
		// show form
		$this->editEntityForm();
	}
	
	function editOptions() {
		$this->backToStart();
	}
	
	function editEntityForm($write = true) {
		$param = $this->getData()->findByIdNoRef($_GET["id"]);
		$default = $param["value"];
		$form = new Form($this->getData()->getParameterCaption($_GET["id"]),
				$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		
		if($_GET["id"] == "default_contact_group") {
			Writing::p(Lang::txt("KonfigurationView_editEntityForm.message"));
			$dd = new Dropdown("value");
			$groups = $this->getData()->adp()->getGroups();
			foreach($groups as $i => $group) {
				if($i == 0) continue;
				$dd->addOption($group["name"], $group["id"]);
			}
			$dd->setSelected($default);
			$form->addElement(Lang::txt("KonfigurationView_start.group"), $dd);
		}
		else if($_GET["id"] == "default_conductor") {
			$dd = new Dropdown("value");
			$contacts = $this->getData()->adp()->getConductors();
			foreach($contacts as $i => $contact) {
				if($i == 0) continue;
				$dd->addOption($contact["name"] . " " . $contact["surname"], $contact["id"]);
			}
			$dd->addOption("-", 0);
			$dd->setSelected($default);
			$form->addElement(Lang::txt("KonfigurationView_start.conductor"), $dd);
		}
		else if($_GET["id"] == "default_country") {
			$dd = $this->buildCountryDropdown($default);
			$dd->setName("value");
			$form->addElement(Lang::txt("KonfigurationView_start.country"), $dd);
		}
		else {
			// default case
			$form->addElement(Lang::txt("KonfigurationView_start.value"), new Field("value", $default, $this->getData()->getParameterType($_GET["id"])));
		}
		
		$form->write();
	}
	
	public function edit_process() {
		$this->checkID();
	
		// update
		$this->getData()->update($_GET["id"], $_POST);
	
		// show success
		new Message($this->getEntityName() . Lang::txt("KonfigurationView_edit_process.message_1"),
				Lang::txt("KonfigurationView_edit_process.message_2"));
	}
	
}

?>