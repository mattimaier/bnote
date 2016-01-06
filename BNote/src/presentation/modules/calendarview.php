<?php

class CalendarView extends AbstractView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
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
		});
		</script>
		<?php
	}
	
	function startOptions() {
		// none
	}
	
}

?>