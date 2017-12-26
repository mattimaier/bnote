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
	
	/**
	 * View for custom fields submodule.
	 * @var CustomFieldsView
	 */
	private $customFieldsView;
	
	private $customFieldsData;
	private $customFieldsCtrl;
	
	function start() {
		if(isset($_GET["mode"])) {
			if($_GET["mode"] == "instruments") {
				$this->instruments();
			}
			else if($_GET["mode"] == "customfields") {
				$this->customFields();
			}
			else {
				parent::start();
			}
		}
		else {
			parent::start();
		}
	}
	
	protected function initInstruments() {
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
	
	protected function initCustomFields() {
		if($this->customFieldsView == null) {
			require_once $GLOBALS["DIR_DATA_MODULES"] . "customfieldsdata.php";
			require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "customfieldsview.php";
			require_once $GLOBALS["DIR_LOGIC_MODULES"] . "customfieldscontroller.php";
			
			$this->customFieldsCtrl = new CustomFieldsController();
			$this->customFieldsData = new CustomFieldsData();
			$this->customFieldsView = new CustomFieldsView($this->customFieldsCtrl);
			$this->customFieldsCtrl->setData($this->customFieldsData);
			$this->customFieldsCtrl->setView($this->customFieldsView);
		}
	}
	
	private function instruments() {
		$this->initInstruments();
		$this->instCtrl->start();
	}
	
	private function customFields() {
		$this->initCustomFields();
		$this->customFieldsCtrl->start();
	}
	
	function getInstrumentsView() {
		$this->initInstruments();
		return $this->instView;
	}
	
	function getCustomFieldsView() {
		$this->initCustomFields();
		return $this->customFieldsView;
	}
}

?>