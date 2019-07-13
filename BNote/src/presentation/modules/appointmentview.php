<?php

/**
 * Submodule main view.
 * @author matti
 *
 */
class AppointmentView extends CrudRefLocationView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setJoinedAttributes(AppointmentData::$colExchange);
		$this->setEntityName(Lang::txt("AppointmentView_construct.EntityName"));
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
		// groups
		$groups = $this->getData()->adp()->getGroups();
		$groupSelector = new GroupSelector($groups, array(), "group");
		$form->addElement(Lang::txt("AppointmentView_changeDefaultAddEntityForm.group"), $groupSelector);
	}
	
	function startOptions() {
		$backBtn = new Link("?mod=" . $this->getModId() . "&mode=start", Lang::txt("AppointmentView_startOptions.back"));
		$backBtn->addIcon("arrow_left");
		$backBtn->write();
	
		$new = new Link($this->modePrefix() . "addEntity", Lang::txt("AppointmentView_startOptions.addEntity"));
		$new->addIcon("plus");
		$new->write();
	}
	
	function view() {
		$appointment = $this->getData()->getAppointment($_GET["id"]);
		
		Writing::h1($appointment["name"]);
		
		Writing::h3(Lang::txt("AppointmentView_view.begin"));
		Writing::p($this->formatFromToDateShort($appointment["begin"], $appointment["end"]));
		
		Writing::h3(Lang::txt("AppointmentView_view.locationname"));
		Writing::p($appointment["locationname"]);
		Writing::p($this->formatAddress($appointment, FALSE, "location", TRUE));
		
		Writing::h3(Lang::txt("AppointmentView_view.contactname"));
		Writing::p($appointment["contactname"] . " " . $appointment["contactsurname"]);
		
		Writing::h3(Lang::txt("AppointmentView_view.techname"));
		$customFields = $this->getData()->getCustomFields('a');
		foreach($customFields as $i => $field) {
			if($i == 0) continue;
			$techname = $field["techname"];
			if(isset($appointment[$techname])) {
				Writing::p($field["txtdefsingle"] . ": " . $appointment[$techname]);
			}
		}
		
		Writing::h3(Lang::txt("AppointmentView_view.notes"));
		Writing::p($appointment["notes"]);
		
		Writing::h3(Lang::txt("AppointmentView_view.groups"));
		Writing::p(join(", ", Database::flattenSelection($appointment["groups"], "name")));
	}
	
	function backToStart() {
		$link = new Link("?mod=" . $this->getData()->getSysdata()->getModuleId(), Lang::txt("AppointmentView_backToStart.back"));
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	function changeDefaultEditEntityForm($form, $record) {
		// full data
		$appointment = $this->getData()->getAppointment($record["id"]);
		$this->appendCustomFieldsToForm($form, 'a', $appointment);
		
		// groups
		$groups = $this->getData()->adp()->getGroups();
		$selectedGroups = Database::flattenSelection($appointment["groups"], "id");
		$groupSelector = new GroupSelector($groups, $selectedGroups, "group");
		$form->addElement(Lang::txt("AppointmentView_changeDefaultEditEntityForm.group"), $groupSelector);
	}
	
	function edit_processOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
}

?>