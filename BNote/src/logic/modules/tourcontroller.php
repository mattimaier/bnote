<?php
require_once($GLOBALS["DIR_DATA_MODULES"] . "accommodationdata.php");
require_once($GLOBALS["DIR_DATA_MODULES"] . "probendata.php");
require_once($GLOBALS["DIR_PRESENTATION_MODULES"] . "accommodationview.php");
require_once $GLOBALS['DIR_PRESENTATION_MODULES'] . "probenview.php";


class TourController extends DefaultController {

	private $accommodationView;
	private $accommodationData;
	
	private $rehearsalView;

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
}

?>