<?php

/**
 * Controller of the configuration module.
 * @author matti
 *
 */
class KonfigurationController extends DefaultController {

	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "instruments") {
			$this->instruments();
		}
		else {
			parent::start();
		}
	}
	
	private function instruments() {
		require_once $GLOBALS["DIR_DATA_MODULES"] . "instrumentedata.php";
		require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "instrumenteview.php";
		require_once $GLOBALS["DIR_LOGIC_MODULES"] . "instrumentecontroller.php";
	
		$ctrl = new InstrumenteController();
		$data = new InstrumenteData();
		$view = new InstrumenteView($ctrl);
		$ctrl->setData($data);
		$ctrl->setView($view);
		$ctrl->start();
	}
}

?>