<?php

/**
 * Subview of the configuration module.
 * @author Matti
 *
 */
class InstrumenteView extends CrudRefView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Instrument");
		$this->setJoinedAttributes(array(
			"category" => array("name")
		));
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
		return "?mod=" . $this->getModId() . "&mode=instruments&sub=";
	}
	
	function backToStart() {
		global $system_data;
		$link = new Link("?mod=" . $system_data->getModuleId() . "&mode=instruments", Lang::txt("back"));
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	function start() {
		Writing::h2("Instrumentenkonfiguration");
	
		// show instruments
		$instruments = $this->getData()->getInstrumentsWithCatName();
		$table = new Table($instruments);
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("id");
		$table->removeColumn("catid");
		$table->removeColumn(2);
		$table->setEdit("id");
		$table->changeMode("instruments&sub=view");
		$table->write();
	}
	
	function startOptions() {
		// show a back button
		$back = new Link(parent::modePrefix() . "start", "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
		$this->buttonSpace();
		
		// add new ones
		$new = new Link($this->modePrefix() . "addEntity", "Instrument hinzufügen");
		$new->addIcon("plus");
		$new->write();
		$this->buttonSpace();
		
		// configure visible instrument groups
		$cat = new Link($this->modePrefix() . "activeInstrumentGroups", "Instrumentenfilter");
		$cat->addIcon("edit");
		$cat->write();
		$this->verticalSpace();
	}
	
	function activeInstrumentGroups() {
		Writing::h2("Instrumente");
		Writing::p("Hier können die Instrumente-Gruppen festgelegt werden, die in der Registrierung angezeigt werden können.");
	
		// show all categories in a form to select the preferred ones
		$form = new Form("Aktive Instrumente-Gruppen", $this->modePrefix() . "process_activeInstrumentGroups");
		$cats = $this->getData()->getCategories();
		$activeCats = $this->getData()->getActiveCategories();
		
		$gs = new GroupSelector($cats, $activeCats, "category");
		$form->addElement("Kategorie", $gs);
	
		$form->changeSubmitButton("speichern");
		$form->write();
	}
	
	function process_activeInstrumentGroups() {
		$this->getData()->saveInstrumentGroupConfig();
		
		new Message("Aktive Instrumenten-Gruppen gespeichert", "Die neuen aktiven Instrumenten-Gruppen wurden gespeichert.");
	}
	
	function viewDetailTable() {
		$instrument = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		$dv = new Dataview();
		$dv->autoAddElements($instrument);
		$dv->renameElement("categoryname", "Kategorie");
		$dv->write();
	}
	
	function editEntityForm() {
		$instrument = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form("Instrument bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
				$this->getData()->getTable(), $_GET["id"]);
		$form->setForeign("category", "category", "id", "name", $instrument["category"]);
		$form->removeElement("id");
		$form->write();
	}
	
}