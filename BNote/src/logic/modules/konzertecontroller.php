<?php

/**
 * Controller of the concert module.
 * @author matti
 *
 */
class KonzerteController extends DefaultController {
	
	private $programView;
	
	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "wizzard") {
			$this->wizzard();
		}
		else if(isset($_GET["mode"]) && $_GET["mode"] == "programs") {
			$this->programs();
		}
		else {
			parent::start();
		}
	}
	
	/*
	 * These are the following steps for the user to add a concert:
	 * 1) Basic concert data such as date/time and notes
	 * 2) Choose or add a location
	 * 3) Choose or add a contact person
	 * 4) Choose or create a program
	 * 5) Choose players
	 * 6) summary and saving
	 */
	
	/**
	 * Definition of the steps.<br/>
	 * <i>To add a new step just insert the steps name at the correct position
	 * in this array and change the names of the view accordingly.</i>
	 * @var array
	 */
	private $addSteps = array(
			"Stammdaten",
			"Aufführungsort",
			"Kontaktperson",
			"Programm",
			"Mitglieder",
			"Fertig"
	);
	
	public function getSteps() {
		return $this->addSteps;
	}
	
	/**
	 * Method for concert creation. 
	 */
	private function wizzard() {
		$this->getView()->showAddTitle();
		
		// progress bar
		if(isset($_GET["progress"])) {
			$progress = $_GET["progress"];
		}
		else {
			$progress = 1;
		}
		$this->getView()->showProgressBar($progress);
		
		// save data when done
		$numSteps = count($this->addSteps);
		if($progress == $numSteps) {
			$this->getData()->saveConcert();
		}
		
		// views
		$func = "step" . $progress;
		$action = "wizzard&progress=" . ($progress+1);
		$this->getView()->$func($action);
		
		// always show abort option
		if($progress < $numSteps) $this->getView()->abortButton();
	}
	
	private function programs($init = false) {
		require_once $GLOBALS["DIR_DATA_MODULES"] . "programdata.php";
		require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "programview.php";
		require_once $GLOBALS["DIR_LOGIC_MODULES"] . "programcontroller.php";
		
		$ctrl = new ProgramController();
		$data = new ProgramData();
		$this->programView = new ProgramView($ctrl);
		$ctrl->setData($data);
		$ctrl->setView($this->programView);
		
		if(!$init) {
			$ctrl->start();
		}
	}
	
	function getProgramView() {
		if($this->programView == null) {
			$this->programs(true);
		}
		return $this->programView;
	}
	
}