<?php

class TravelView extends CrudRefView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("travel"));
		$this->setJoinedAttributes(array(
			"tour" => array("name")
		));
		$this->internalReferenceFields = array(
			"tour" => $_GET["accId"]
		);
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		$tid = $_GET["accId"];
		return "?mod=" . $this->getModId() . "&mode=view&accId=$tid&tab=travel&func=";
	}
	
	function isSubModule($mode) {
		return true;
	}
	
	function subModuleOptions() {
		$subOptionFunc = isset($_GET["func"]) ? $_GET["func"] . "Options" : "startOptions";
		if(method_exists($this, $subOptionFunc)) {
			$this->$subOptionFunc();
		}
		else {
			$this->defaultOptions();
		}
	}
	
	function backToStart() {
		$link = new Link("?mod=" . $this->getModId() . "&mode=view&tab=travel&accId=" . $_GET["accId"], "Zurück");
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	function showAllTable() {
		$table = new Table($this->getData()->findAllJoinedOrdered($this->getJoinedAttributes(), "departure"));
		$table->setEdit("id");
		$table->changeMode("view&tab=travel&func=view&accId=" . $_GET["accId"]);
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("tour");
		$table->removeColumn("id");
		$table->removeColumn("tourname");
		$table->setColumnFormat("departure", "DATE");
		$table->setColumnFormat("arrival", "DATE");
		$table->write();
	}
	
	function viewDetailTable() {
		$entity = $this->getData()->findByIdJoined($_GET[$this->idParameter], $this->getJoinedAttributes());
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->renameElement("locationname", Lang::txt("accommodation_locationname"));
		$details->renameElement("tourname", Lang::txt("accommodation_tourname"));
		$details->write();
	}
}
?>