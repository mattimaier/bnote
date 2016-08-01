<?php
/**
 * View for configuration module.
 * @author matti
 *
 */
class KonfigurationView extends CrudView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Konfiguration");
	}
	
	function showOptions() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "instruments") {
			$this->getController()->getInstrumentsView()->showOptions();
		}
		else {
			parent::showOptions();
		}
	}
	
	function start() {
		Writing::h1("Konfiguration");
		$this->showWarnings();
		
		Writing::p("Bitte klicke auf eine Zeile um deren Wert zu ändern.");
		
		$parameter = $this->getData()->getActiveParameter();

		$table = new Table($parameter);
		$table->renameHeader("caption", "Parameter");
		$table->renameHeader("value", "Wert");
		$table->setEdit("param");
		$table->removeColumn("param");
		$table->changeMode("edit");
		$table->write();
	}
	
	function startOptions() {		
		// instrument configuration
		$istr = new Link($this->modePrefix() . "instruments", "Instrumente");
		$istr->addIcon("instrument");
		$istr->write();
	}
	
	protected function showWarnings() {
		if($this->getData()->getSysdata()->getDynamicConfigParameter("google_api_key") == "") {
			$this->flash("Google Maps API Key not set.");
		}
	}
	
	function edit() {
		$this->checkID();
		
		// header
		Writing::h2("Konfiguration");
		
		// show form
		$this->editEntityForm();
	}
	
	function editOptions() {
		$this->backToStart();
	}
	
	function editEntityForm() {
		$param = $this->getData()->findByIdNoRef($_GET["id"]);
		$default = $param["value"];
		$form = new Form($this->getData()->getParameterCaption($_GET["id"]),
				$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		
		if($_GET["id"] == "default_contact_group") {
			Writing::p("Jeder neu registrierte Benutzer wird dieser Gruppe zugeordnet.");
			$dd = new Dropdown("value");
			$groups = $this->getData()->adp()->getGroups();
			foreach($groups as $i => $group) {
				if($i == 0) continue;
				$dd->addOption($group["name"], $group["id"]);
			}
			$dd->setSelected($default);
			$form->addElement("Wert", $dd);
		}
		else {
			// default case
			$form->addElement("Wert", new Field("value", $default, $this->getData()->getParameterType($_GET["id"])));
		}
		
		$form->write();
	}
	
	public function edit_process() {
		$this->checkID();
	
		// update
		$this->getData()->update($_GET["id"], $_POST);
	
		// show success
		new Message($this->getEntityName() . " geändert",
				"Der Eintrag wurde erfolgreich geändert.");
	}
	
}

?>