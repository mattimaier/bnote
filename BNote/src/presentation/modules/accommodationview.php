<?php

class AccommodationView extends CrudRefView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("AccommodationView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("AccommodationView_construct.addEntityName"));
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
		$link->addIcon("arrow-left");
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
		$table->renameHeader("locationname", Lang::txt("AccommodationView_showAllTable.locationname"));
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
		$details->renameElement("locationname", Lang::txt("AccommodationView_viewDetailTable.locationname"));
		$details->renameElement("tourname", Lang::txt("AccommodationView_viewDetailTable.tourname"));
		$details->write();
	}
	
	function changeDefaultAddEntityForm($form) {
		// only let the user set a location from the accommodation group
		$locations = $this->getData()->adp()->getLocations(array(3));
		$elem = $form->getForeignElement("location");
		$elem->cleanOptions();
		for($i = 1; $i < count($locations); $i++) {
			$elem->addOption($locations[$i]['name'], $locations[$i]["id"]);
		}
	}
}
?>