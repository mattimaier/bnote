<?php

/**
 * Help view.
 * @author matti
 *
 */
class HilfeView extends AbstractView {
	
	private $helpPagesDir = "data/help/";
	
	// format: name of the html-file => title
	private $helpPages = array(
			"bluenote2" => "Neuerungen in BlueNote 2.3",
			"mitspieler" => "Modul Mitspieler",
			"abstimmung" => "Modul Abstimmung",
			"support" => "Support"
	);
	
	// format: code => description
	private $videos = array(
			"ovB7s2dIwCU" => "Grundlagen und Einführungsvideo",
			"6OTzjJbMsHY" => "Tutorial 1 - Mitgliedersicht",
			"dVJYFbWgj4E" => "Tutorial 2 - Administrations&uuml;berblick",
			"CXCbngJM8zU" => "Tutorial 3 - Benutzer und Kontakte",
			"PCWTS0jq-24" => "Tutorial 4 - Kommunikation",
			"VGCWdZr3reU" => "Tutorial 5 - Locations",
			"dWlnssimDzs" => "Tutorial 6 - Repertoire",
			"jAmp2H7GaDg" => "Tutorial 7 - Probe",
			"UsqMTUEWNiw" => "Tutorial 8 - Konzerte",
			"A09dIMCfuig" => "Tutorial 9 - Website",
			"kbBNbmlC__U" => "Tutorial 10 - Share"
	);
	
	private $introVideoCode = "ovB7s2dIwCU";
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		Writing::h1("Hilfe");
		
		// Help Navigation
		?>
		<table class="help_navigator">
			<tr>
			<td class="help_navigator_menu">
				<div class="help_navigator_menu_topic">Einführung</div>
				<?php
				// show an introduction video
				$active = false;
				if(isset($_GET["vid"]) && $_GET["vid"] == $this->introVideoCode) $active = true;
				$this->writePageLink($this->videos[$this->introVideoCode], $this->modePrefix() . "start&vid=" . $this->introVideoCode, $active);
				$active = false;
				?>
						
				<div class="help_navigator_menu_topic">Video Tutorials</div>
				<?php 
				// show all the videos available for this software
				foreach($this->videos as $code => $vid) {
					if($code == "ovB7s2dIwCU") continue;
					if(isset($_GET["vid"]) && $_GET["vid"] == $code) $active = true;
					$this->writePageLink($vid, $this->modePrefix() . "start&vid=" . $code, $active);
					$active = false;
				}
				
				?>
				
				<div class="help_navigator_menu_topic">Hilfeseiten</div>
				<?php
				// show help pages available for this software
				foreach($this->helpPages as $helpPageId => $helpPageTitle) {
					if(isset($_GET["page"]) && $_GET["page"] == $helpPageId) $active = true;
					$this->writePageLink($helpPageTitle, $this->modePrefix() . "start&page=" . $helpPageId, $active);
					$active = false;
				}
				?>
				
			</td>
			<td class="help_navigator_content">
				<?php 
				if(isset($_GET["vid"])) {
					Writing::h2("Video Tutorial");
					echo '<iframe width="560" height="315" src="http://www.youtube.com/embed/' . $_GET["vid"] . '" frameborder="0" allowfullscreen></iframe>';
				}
				else if(isset($_GET["page"])) {
					echo '<span class="help_page_title">' . $this->helpPages[$_GET["page"]] . '</span>';
					include $this->helpPagesDir . $_GET["page"] . ".html";
				}
				else {
					Writing::p("Bitte wähle eine Hilfeseite.");
				}
				?>
			</td>
			</tr>
		</table>
		<?php
	}
	
	private function writePageLink($title, $href, $isActive = false) {
		$divCls = "help_navigator_menu_page";
		if($isActive) $divCls = "help_navigator_menu_page_active";
		echo '  <a class="help_navigator_menu_page_link" href="' . $href . '">';
		echo '<div class="' . $divCls . '">';
		echo $title . '</div></a>';
	}
	
}

?>