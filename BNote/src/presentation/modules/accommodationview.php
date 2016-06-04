<?php

class AccommodationView extends CrudRefView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Übernachtung");
		$this->setJoinedAttributes(array(
			"location" => array("name"),
			"tour" => array("id", "name")
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
		return "?mod=" . $this->getModId() . "&mode=view&accId=$tid&tab=accommodation&func=";
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
		$link = new Link("?mod=" . $this->getModId() . "&mode=view&tab=accommodation&accId=" . $_GET["accId"], "Zurück");
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	function showAllTable() {
		$accommodations = $this->getData()->findAllJoinedOrdered($this->getJoinedAttributes(), "checkin");
		if(isset($_GET["accId"])) {
			$tour_id = $_GET["accId"];
			$accommodations = $this->getData()->filterTourAccommodations($accommodations, $tour_id, "tourid");
		}
		$table = new Table($accommodations);
		$table->setEdit("id");
		$table->changeMode("view&tab=accommodation&func=view&accId=" . $_GET["accId"]);
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("locationname", "Unterkunft");
		$table->removeColumn("tour");
		$table->removeColumn("id");
		$table->removeColumn("tourname");
		$table->removeColumn("tourid");
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