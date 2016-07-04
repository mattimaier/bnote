<?php

class CalendarView extends CrudRefView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("reservation"));
		
		$this->setJoinedAttributes(CalendarData::$colExchange);
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
		});
		</script>
		<?php
	}
	
	function changeDefaultAddEntityForm($form) {
		$beginField = $form->getElement("begin");
		$beginField->setCssClass("copyDateOrigin");
		$endField = $form->getElement("end");
		$endField->setCssClass("copyDateTarget");
	}
	
	function viewDetailTable() {
		$reservation = $this->getData()->findByIdJoined($_GET["id"], CalendarData::$colExchange);
		$dv = new Dataview();
		$dv->autoAddElements($reservation);
		$dv->autoRename($this->getData()->getFields());
		$dv->renameElement("id", Lang::txt("reservation_id"));
		$dv->removeElement("contactname");
		$dv->removeElement("contactsurname");
		$dv->renameElement("locationname", Lang::txt("location"));
		$dv->addElement(Lang::txt("contact"), $reservation["contactname"] . " " . $reservation["contactsurname"]);
		$dv->write();
	}
}

?>