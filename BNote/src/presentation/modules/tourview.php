<?php

class TourView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("tour"));
		$this->idParameter = "accId";
	}
	
	function view() {
		$this->checkID();
		
		$tabs = array(
				"details" => "Details",
				"rehearsals" => "Proben",
				"contacts" => "Teilnehmer",
				"concerts" => "Konzerte",
				"accommodation" => "Übernachtungen",
				"travel" => "Reisen",
				"checklist" => "Checklist",
				"equipment" => "Equipment"
		);
		echo "<div class=\"view_tabs\">\n";
		foreach($tabs as $tabid => $label) {
			$href = $this->modePrefix() . "view&" . $this->idParameter . "=" . $_GET[$this->idParameter] . "&tab=$tabid";
		
			$active = "";
			if(isset($_GET["tab"]) && $_GET["tab"] == $tabid) $active = "_active";
			else if(!isset($_GET["tab"]) && $tabid == "details") $active = "_active";
		
			echo "<a href=\"$href\"><span class=\"view_tab$active\">$label</span></a>";
		}
		echo "</div>\n";
		echo "<div class=\"view_tab_content\">\n";
		
		// content
		if(!isset($_GET["tab"]) || $_GET["tab"] == "details") {
			$this->viewDetailTable();
		}
		else if($_GET["tab"] != "accommodation" && $_GET["tab"] != "travel") {
			$func = "tab_" . $_GET["tab"];
			$this->$func();
		}
			
		echo "</div>\n";
	}
	
	function viewOptions() {
		if((isset($_GET["func"]) && $_GET["func"] == "start") || !isset($_GET["func"])) {
			parent::viewOptions();
		}
		else {
			$this->additionalViewButtons();
		}
	}
	
	function additionalViewButtons() {
		// show options based on selected tab
		if(isset($_GET["tab"])) {
			$tab = $_GET["tab"];
			
			switch($tab) {
				case "accommodation": 
					$view = $this->getController()->getAccommodationView();
					$view->subModuleOptions();
					break;
			}
		}
	}
	
	function tab_rehearsals() {
		//TODO implement rehearsal tab --> use submodule?
	}
	
	function tab_contacts() {
		//TODO implement contact tab --> use submodule?
	}
	
	function tab_concerts() {
		//TODO implement concert tab --> use submodule?
	}
	
	function tab_checklist() {
		
	}
	
	function tab_equipment() {
		//TODO implement equipment tab --> use submodule?
	}
}


?>