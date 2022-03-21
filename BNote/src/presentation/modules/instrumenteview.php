<?php

/**
 * Subview of the configuration module.
 * @author Matti
 *
 */
class InstrumenteView extends CrudRefView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("InstrumenteView_construct.EntityName"));
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
		$link = new Link("?mod=" . $system_data->getModuleId() . "&mode=instruments", Lang::txt("InstrumenteView_backToStart.back"));
		$link->addIcon("arrow-left");
		$link->write();
	}
	
	function start() {
		Writing::h2(Lang::txt("InstrumenteView_start.Title"));
	
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
		$back = new Link(parent::modePrefix() . "start", Lang::txt("InstrumenteView_startOptions.start"));
		$back->addIcon("arrow-left");
		$back->write();
		
		// add new ones
		$new = new Link($this->modePrefix() . "addEntity", Lang::txt("InstrumenteView_startOptions.addEntity"));
		$new->addIcon("plus");
		$new->write();
		
		// configure visible instrument groups
		$cat = new Link($this->modePrefix() . "activeInstrumentGroups", Lang::txt("InstrumenteView_startOptions.activeInstrumentGroups"));
		$cat->addIcon("pen");
		$cat->write();
		$this->verticalSpace();
	}
	
	function activeInstrumentGroups() {
		Writing::h2(Lang::txt("InstrumenteView_activeInstrumentGroups.Title"));
		Writing::p(Lang::txt("InstrumenteView_activeInstrumentGroups.Message"));
	
		// show all categories in a form to select the preferred ones
		$form = new Form(Lang::txt("InstrumenteView_activeInstrumentGroups.Form"), $this->modePrefix() . "process_activeInstrumentGroups");
		$cats = $this->getData()->getCategories();
		$activeCats = $this->getData()->getActiveCategories();
		
		$gs = new GroupSelector($cats, $activeCats, "category");
		$form->addElement(Lang::txt("InstrumenteView_activeInstrumentGroups.addElement"), $gs);
	
		$form->changeSubmitButton(Lang::txt("InstrumenteView_activeInstrumentGroups.SubmitButton"));
		$form->write();
	}
	
	function process_activeInstrumentGroups() {
		$this->getData()->saveInstrumentGroupConfig();
		
		new Message(Lang::txt("InstrumenteView_process_activeInstrumentGroups.Message_1"), Lang::txt("InstrumenteView_process_activeInstrumentGroups.Message_2"));
	}
	
	function viewDetailTable() {
		$instrument = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		$dv = new Dataview();
		$dv->autoAddElements($instrument);
		$dv->renameElement("rank", Lang::txt("InstrumenteData_construct.rank"));
		$dv->renameElement("categoryname", Lang::txt("InstrumenteView_viewDetailTable.categoryname"));
		$dv->write();
	}
	
	function editEntityForm($write=true) {
		$instrument = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form("Instrument bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
				$this->getData()->getTable(), $_GET["id"], array("rank"));
		$form->setForeign("category", "category", "id", "name", $instrument["category"]);
		$form->removeElement("id");
		$form->write();
	}
	
}

?>