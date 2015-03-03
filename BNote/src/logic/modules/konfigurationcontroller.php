<?php

/**
 * Controller of the configuration module.
 * @author matti
 *
 */
class KonfigurationController extends DefaultController {

	/**
	 * View for submodule.
	 * @var InstrumenteView
	 */
	private $instView;
	
	private $instData;
	private $instCtrl;
	
	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "instruments") {
			$this->instruments();
		}
		else {
			parent::start();
		}
	}
	
	private function initInstruments() {
		if($this->instView == null) {
			require_once $GLOBALS["DIR_DATA_MODULES"] . "instrumentedata.php";
			require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "instrumenteview.php";
			require_once $GLOBALS["DIR_LOGIC_MODULES"] . "instrumentecontroller.php";
			
			$this->instCtrl = new InstrumenteController();
			$this->instData = new InstrumenteData();
			$this->instView = new InstrumenteView($this->instCtrl);
			$this->instCtrl->setData($this->instData);
			$this->instCtrl->setView($this->instView);
		}
	}
	
	private function instruments() {
		$this->initInstruments();
		$this->instCtrl->start();
	}
	
	function getInstrumentsView() {
		$this->initInstruments();
		return $this->instView;
	}
}

?>