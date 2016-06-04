<?php
require_once($GLOBALS["DIR_DATA_MODULES"] . "accommodationdata.php");
require_once($GLOBALS["DIR_DATA_MODULES"] . "probendata.php");
require_once($GLOBALS['DIR_DATA_MODULES'] . "konzertedata.php");
require_once($GLOBALS["DIR_PRESENTATION_MODULES"] . "accommodationview.php");
require_once $GLOBALS['DIR_PRESENTATION_MODULES'] . "probenview.php";
require_once($GLOBALS['DIR_PRESENTATION_MODULES'] . "konzerteview.php");
require_once($GLOBALS['DIR_LOGIC_MODULES'] . "konzertecontroller.php");


class TourController extends DefaultController {

	private $accommodationView;
	private $accommodationData;
	
	private $rehearsalView;
	private $concertView;

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
}

?>