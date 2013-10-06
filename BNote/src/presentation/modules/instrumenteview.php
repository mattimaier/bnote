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
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=instruments&sub=";
	}
	
	function backToStart() {
		global $system_data;
		$link = new Link("?mod=" . $system_data->getModuleId() . "&mode=instruments", "Zur&uuml;ck");
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	function start() {
		Writing::h2("Instrumentenkonfiguration");
		
		// show a back button
		$back = new Link(parent::modePrefix() . "start", "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
		$this->buttonSpace();
	
		// add new ones
		$new = new Link($this->modePrefix() . "instruments&sub=addEntity", "Instrument hinzufügen");
		$new->addIcon("add");
		$new->write();
		$this->buttonSpace();
	
		// configure visible instrument groups
		$cat = new Link($this->modePrefix() . "activeInstrumentGroups", "Instrumentenfilter");
		$cat->addIcon("edit");
		$cat->write();
		$this->verticalSpace();
	
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
		$this->verticalSpace();
	
		$this->backToStart();
	}
	
	function process_activeInstrumentGroups() {
		$this->getData()->saveInstrumentGroupConfig();
		
		new Message("Aktive Instrumenten-Gruppen gespeichert", "Die neuen aktiven Instrumenten-Gruppen wurden gespeichert.");
		
		$this->backToStart();
	}
	
	function viewDetailTable() {
		$instrument = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		$dv = new Dataview();
		$dv->autoAddElements($instrument);
		$dv->renameElement("categoryname", "Kategorie");
		$dv->write();
	}
}