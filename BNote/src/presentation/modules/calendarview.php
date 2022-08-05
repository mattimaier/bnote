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
		<style>
		/* fix some bnote defaults for the plugin */
		table {
			margin-top: 0;
			margin-bottom: 0;
		}
		tr {
			border-bottom-width: 0;
		}
		</style>
		<div id='calendar'></div>
		
		<div id="calendar_eventdetail">
			<h3 id="calendar_eventdetail_title"></h3>
			<div id="calendar_eventdetail_block">
			</div>
		</div>
		
		<script>		
		$(function() {
			// all events
			var cal_events = <?php echo json_encode($this->getData()->getEvents()); ?>;
			
			// Full Calendar View
			var calendarEl = document.getElementById('calendar'); 
			var calendar = new FullCalendar.Calendar(calendarEl, {
				plugins: [ 'dayGrid' ],
				events: cal_events,
				locale: <?php echo json_encode($this->getData()->getSysdata()->getLang()); ?>,
				eventClick: function(info) {
					var calEvent = info.event;
					$('#calendar_eventdetail_title').text(calEvent.title);
					
					// show details object
					$('#calendar_eventdetail_block').text("");
					
					for(var k in calEvent.extendedProps.details) {
						if(k == "id") continue;
						$('#calendar_eventdetail_block').append('<div class="calendar_eventdetail_keyvalue">'
								+ '<label class="calendar_eventdetail_key">' + k + '</label>' 
								+ '<span class="calendar_eventdetail_value">'+ calEvent.extendedProps.details[k] + '</span></div>');
					}
					
					if(calEvent.extendedProps.link) {
						$('#calendar_eventdetail_block').append(
								'<a class="linkbox" href="' + calEvent.extendedProps.link + '">' +
								'<div class="linkbox" style="margin-top: 10px;">Details</div></a>');
					}
					
					$('#calendar_eventdetail').show();
				},
				header: {
					left: 'title',
					center: '',
					right: 'prev,next'
				}
		    });

		    calendar.render();

		    $('#calendar_eventdetail').hide();
		});
		</script>
		<?php
	}
	
	function changeDefaultAddEntityForm($form) {
		$beginField = $form->getElement("begin");
		$beginField->setCssClass("copyDateOrigin");
		$endField = $form->getElement("end");
		$endField->setCssClass("copyDateTarget");
		
		// adapt sizing
		$form->setFieldColSize("begin", 3);
		$form->setFieldColSize("end", 3);
		$form->setFieldColSize("notes", 12);
		
		// custom data
		$this->appendCustomFieldsToForm($form, 'v', null, false);
	}
	
	function changeDefaultEditEntityForm($form, $record) {
		// adapt sizing
		$form->setFieldColSize("begin", 3);
		$form->setFieldColSize("end", 3);
		$form->setFieldColSize("notes", 12);
		
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
		$dv->renameElement("ID", Lang::txt("CalendarView_viewDetailTable.id"));
		$dv->removeElement("contactname");
		$dv->removeElement("contactsurname");
		$dv->renameElement("locationname", Lang::txt("CalendarView_viewDetailTable.locationname"));
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