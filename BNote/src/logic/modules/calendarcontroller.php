<?php
require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "appointmentview.php";
require_once $GLOBALS["DIR_DATA_MODULES"] . "appointmentdata.php";

/**
 * Special controller to support submodules.
 * @author matti
 *
 */
class CalendarController extends DefaultController {
	
	/**
	 * Submodule view.
	 * @var AppointmentView
	 */
	private $appointmentView;
	
	/**
	 * Submodule dao.
	 * @var AppointmentData
	 */
	private $appointmentData;
	
	public function start() {
		$this->initAppointment();
		if(isset($_GET['mode'])) {
			if($_GET["mode"] == "appointments") {
				$this->appointments();
			}
			else {
				$mode = $_GET['mode'];
				$this->getView()->$mode();
			}
		}
		else {
			$this->getView()->start();
		}
	}
	
	private function appointments() {
		if(!isset($_GET["func"])) {
			$this->appointmentView->start();
		}
		else {
			$func = $_GET["func"];
			$this->appointmentView->$func();
		}
	}
	
	private function initAppointment() {
		if($this->appointmentData == null || $this->appointmentView == null) {
			$this->appointmentData = new AppointmentData();
			parent::getData()->setAppointmentData($this->appointmentData);
			$this->appointmentView = new AppointmentView($this);
		}
	}
	
	function appointmentOptions() {
		$this->initAppointment();
		$this->appointmentView->showOptions();
	}
	
	function getData() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "appointments") {
			return $this->appointmentData;
		}
		return parent::getData();	
	}
}

?>