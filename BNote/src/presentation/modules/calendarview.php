<?php

class CalendarView extends CrudRefLocationView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("CalendarView_construct.EntityName"));
		
		$this->setJoinedAttributes(CalendarData::$colExchange);
	}
	
	protected function isSubModule($mode) {
		if($mode == "appointments") return true;
		return false;
	}
	
	protected function subModuleOptions() {
		$this->getController()->appointmentOptions();
	}
	
	function start() {
		?>
		<script>
		<?php
		$events = $this->getData()->getEvents();
		
		// load all events in a globally available JS array
		echo "calendar_events = " . json_encode($events) . ";";
		?>
		</script>
		
		<div id='calendar'></div>
		
		<div id="calendar_eventdetail">
			<h3 id="calendar_eventdetail_title"></h3>
			<div id="calendar_eventdetail_block">
			</div>
		</div>
		
		<script>
		$(function() {
			$("#calendar").fullCalendar( 'addEventSource', {
				events: calendar_events["rehearsals"],
				color: '#61b3ff'  // bnote blue
			});
			$("#calendar").fullCalendar( 'addEventSource', {
				events: calendar_events["rehearsalphases"],
				color: '#B361FF'  // purple
			});
			$("#calendar").fullCalendar( 'addEventSource', {
				events: calendar_events["concerts"],
				color: '#FF6161'  // red
			});
			$("#calendar").fullCalendar( 'addEventSource', {
				events: calendar_events["votes"],
				color: '#FFCD61'  // orange
			});
			$("#calendar").fullCalendar( 'addEventSource', {
				events: calendar_events["contacts"],
				color: '#66FF61'  // green
			});
			$("#calendar").fullCalendar( 'addEventSource', {
				events: calendar_events["reservations"],
				color: '#A0A0A0'  // gray
			});
			$("#calendar").fullCalendar( 'addEventSource', {
				events: calendar_events["appointments"],
				color: '#DF61FF'  // pink
			});
		});
		</script>
		<?php
	}
	
	function changeDefaultAddEntityForm($form) {
		$beginField = $form->getElement("begin");
		$beginField->setCssClass("copyDateOrigin");
		$endField = $form->getElement("end");
		$endField->setCssClass("copyDateTarget");
		
		// custom data
		$this->appendCustomFieldsToForm($form, 'v', null, false);
	}
	
	function changeDefaultEditEntityForm($form, $record) {
		// custom data
		$customData = $this->getData()->getCustomData($record["id"]);
		$reservation = array_merge($record, $customData);
		$this->appendCustomFieldsToForm($form, 'v', $reservation, false);
	}
	
	function viewDetailTable() {
		// data
		$reservation = $this->getData()->findByIdJoined($_GET["id"], CalendarData::$colExchange);
		$customData = $this->getData()->getCustomData($reservation["id"]);
		
		// display
		$dv = new Dataview();
		$dv->autoAddElements($reservation);
		$dv->autoRename($this->getData()->getFields());
		$dv->renameElement("id", Lang::txt("CalendarView_viewDetailTable.id"));
		$dv->removeElement("contactname");
		$dv->removeElement("contactsurname");
		$dv->renameElement("locationname", Lang::txt("CalendarView_viewDetailTable.id"));
		$dv->addElement(Lang::txt("CalendarView_viewDetailTable.contact"), $reservation["contactname"] . " " . $reservation["contactsurname"]);
		
		// custom data
		$customFields = $this->getData()->getCustomFields('v');
		for($i = 1; $i < count($customFields); $i++) {
			$field = $customFields[$i];
			$techName = $field["techname"];
			if(isset($customData[$techName])) {
				$dv->addElement($field["txtdefsingle"], $customData[$techName]);
			}
		}
		
		$dv->write();
	}
	
	function startOptions() {
		$reservation = new Link($this->modePrefix() . "addEntity", Lang::txt("CalendarView_startOptions.addEntity"));
		$reservation->addIcon("plus");
		$reservation->write();
		
		$appointment = new Link($this->modePrefix() . "appointments&func=addEntity", Lang::txt("CalendarView_startOptions.appointments"));
		$appointment->addIcon("plus");
		$appointment->write();
	}
}

?>