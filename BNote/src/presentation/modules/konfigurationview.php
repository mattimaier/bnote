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

	function start() {
		Writing::h1("Konfiguration");
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
	
	function edit() {
		$this->checkID();
		
		// show form
		$this->editEntityForm();
		
		// back button
		$this->verticalSpace();
		$this->backToStart();
		$this->verticalSpace();
	}
	
	function editEntityForm() {
		$param = $this->getData()->findByIdNoRef($_GET["id"]);
		$default = $param["value"];
		$form = new Form($this->getData()->getParameterCaption($_GET["id"]),
				$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->addElement("Wert", new Field("value", $default, $this->getData()->getParameterType($_GET["id"])));
		$form->write();
	}
	
	public function edit_process() {
		$this->checkID();
	
		// validate
		$this->getData()->validate($_POST);
	
		// update
		$this->getData()->update($_GET["id"], $_POST);
	
		// show success
		new Message($this->entityName . " ge&auml;ndert",
				"Der Eintrag wurde erfolgreich ge&auml;ndert.");
	
		// back button
		$this->backToStart();
	}
	
}

?>