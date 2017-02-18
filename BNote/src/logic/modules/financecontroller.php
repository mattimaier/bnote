<?php
require_once($GLOBALS['DIR_LOGIC_MODULES'] . "recpaycontroller.php");
require_once($GLOBALS['DIR_DATA_MODULES'] . "recpaydata.php");
require_once($GLOBALS['DIR_PRESENTATION_MODULES'] . "recpayview.php");


class FinanceController extends DefaultController {
	
	private $recpayCtrl = null;
	
	public function getRecpayCtrl() {
		if($this->recpayCtrl == null) {
			$ctrl = new RecpayController();
			$data = new RecpayData();
			$view = new RecpayView($ctrl);
			$data->setSysdata($this->getData()->getSysdata());
			$ctrl->setData($data);
			$ctrl->setView($view);
			$this->recpayCtrl = $ctrl;
		}
		return $this->recpayCtrl;
	}
	
	/**
	 * Entry point of module.
	 * Controls the flow of a module.
	 */
	public function start() {
		if($this->getView() == null) {
			echo "No view.";
		}
		else {
			if(isset($_GET['mode'])) {
				if($_GET['mode'] == "recpay") {
					$ctrl = $this->getRecpayCtrl();
					$ctrl->start();
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
	}
	
}

?>