<?php
require_once($GLOBALS["DIR_DATA_MODULES"] . "accommodationdata.php");
require_once($GLOBALS["DIR_DATA_MODULES"] . "probendata.php");
require_once($GLOBALS['DIR_DATA_MODULES'] . "konzertedata.php");
require_once($GLOBALS["DIR_DATA_MODULES"] . "traveldata.php");
require_once($GLOBALS["DIR_DATA_MODULES"] . "aufgabendata.php");
require_once($GLOBALS["DIR_DATA_MODULES"] . "equipmentdata.php");
require_once($GLOBALS["DIR_PRESENTATION_MODULES"] . "accommodationview.php");
require_once $GLOBALS['DIR_PRESENTATION_MODULES'] . "probenview.php";
require_once($GLOBALS['DIR_PRESENTATION_MODULES'] . "konzerteview.php");
require_once($GLOBALS['DIR_PRESENTATION_MODULES'] . "travelview.php");
require_once($GLOBALS['DIR_PRESENTATION_MODULES'] . "aufgabenview.php");
require_once($GLOBALS['DIR_PRESENTATION_MODULES'] . "equipmentview.php");
require_once($GLOBALS['DIR_LOGIC_MODULES'] . "konzertecontroller.php");
require_once($GLOBALS['DIR_LOGIC_MODULES'] . "aufgabencontroller.php");


class TourController extends DefaultController {

	private $accommodationView;
	private $accommodationData;
	
	private $travelView;
	private $travelData;
	
	private $rehearsalView;
	private $concertView;
	private $taskView;
	private $equipmentView;

	function start() {
		if(isset($_GET["tab"]) && $_GET["tab"] == "accommodation") {
			$this->getView()->view();
			// check which func (mode from submodule) to "play"
			$func = "start";
			if(isset($_GET["func"])) {
				$func = $_GET["func"];
			}
			$this->getAccommodationView()->$func();
		}
		elseif(isset($_GET["tab"]) && $_GET["tab"] == "travel") {
			$this->getView()->view();
			// check which func (mode from submodule) to "play"
			$func = "start";
			if(isset($_GET["func"])) {
				$func = $_GET["func"];
			}
			$this->getTravelView()->$func();
		}
		else {
			parent::start();
		}
	}
	
	function getAccommodationView() {
		if($this->accommodationView == null) {
			$defaultCtrl = new DefaultController();
			$this->accommodationData = new AccommodationData();
			$defaultCtrl->setData($this->accommodationData);
			$this->accommodationView = new AccommodationView($defaultCtrl);
			$defaultCtrl->setView($this->accommodationView);
		}
		return $this->accommodationView;
	}
	
	/**
	 * Singleton for Proben access
	 * @return ProbenView View
	 */
	function getRehearsalView() {
		if($this->rehearsalView == null) {
			$defaultCtrl = new DefaultController();
			$probenData = new ProbenData();
			$defaultCtrl->setData($probenData);
			$this->rehearsalView = new ProbenView($defaultCtrl);
			$defaultCtrl->setView($this->rehearsalView);
		}
		return $this->rehearsalView;
	}
	
	function getConcertView() {
		if($this->concertView == null) {
			$ctrl = new KonzerteController();
			$data = new KonzerteData();
			$ctrl->setData($data);
			$this->concertView = new KonzerteView($ctrl);
			$ctrl->setView($this->concertView);
		}
		return $this->concertView;
	}
	
	function getTravelView() {
		if($this->travelView == null) {
			$ctrl = new DefaultController();
			$this->travelData = new TravelData();
			$ctrl->setData($this->travelData);
			$this->travelView = new TravelView($ctrl);
			$ctrl->setView($this->travelView);
		}
		return $this->travelView;
	}
	
	function getChecklistView() {
		if($this->taskView == null) {
			$ctrl = new AufgabenController();
			$data = new AufgabenData();
			$ctrl->setData($data);
			$this->taskView = new AufgabenView($ctrl);
			$ctrl->setView($this->taskView);
		}
		return $this->taskView;
	}
}

?>