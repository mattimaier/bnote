<?php

/**
 * Submodule main view.
 * @author matti
 *
 */
class AppointmentView extends CrudRefView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setJoinedAttributes(AppointmentData::$colExchange);
		$this->setEntityName("Termin");
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=appointments&func=";
	}
	
	function showOptions() {
		if(!isset($_GET["func"]) || $_GET["func"] == "start") {
			$this->startOptions();
		}
		else {
			$subOptionFunc = $_GET["func"] . "Options";
			if(method_exists($this, $subOptionFunc)) {
				$this->$subOptionFunc();
			}
			else {
				$this->defaultOptions();
			}
		}
	}
	
	function changeDefaultAddEntityForm($form) {
		$this->appendCustomFieldsToForm($form, 'a', null, false);
	}
	
	function startOptions() {
		$backBtn = new Link("?mod=" . $this->getModId() . "&mode=start", "Zurück");
		$backBtn->addIcon("arrow_left");
		$backBtn->write();
	
		$new = new Link($this->modePrefix() . "addEntity", "Termin hinzufügen");
		$new->addIcon("plus");
		$new->write();
	}
	
	function backToStart() {
		$back = new Link($this->modePrefix() . "start", "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function view() {
		$appointment = $this->getData()->findByIdNoRef($_GET["id"]);
		
		Writing::h1($appointment["name"]);
		Writing::p($this->formatFromToDateShort($appointment["begin"], $appointment["end"]));
		
		Writing::h3("Ort");
		Writing::p($this->getData()->adp()->getLocationName($appointment["location"]));
		
		Writing::h3("Ansprechpartner");
		Writing::p($this->getData()->adp()->getConductorname($appointment["contact"]));
		
		Writing::h3("Notizen");
		Writing::p($appointment["notes"]);
		
		Writing::h3("Eingeladene Gruppen");
		Writing::p("#todo");
	}
	
	/*
	function viewOptions() {
		$backBtn = new Link("?mod=" . $this->getModId() . "&mode=start", "Zurück");
		$backBtn->addIcon("arrow_left");
		$backBtn->write();
		
		$edit = new Link($this->modePrefix() . "editEntity", "Termin bearbeiten");
		$edit->addIcon("edit");
		$edit->write();
	}
	*/
	
}

?>