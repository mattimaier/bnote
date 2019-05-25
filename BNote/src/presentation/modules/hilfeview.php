<?php

/**
 * Help view.
 * @author matti
 *
 */
class HilfeView extends AbstractView {
	
	private $introPages = array(
//
// Bug when trying to translate with this method_exists
//
//			"p7LrJzVxl-M" => "HilfeView_introPages.video", // introduction video
//			"bnote_news" => "HilfeView_introPages.bnote_news",
//			"sicherheit" => "HilfeView_introPages.sicherheit",
//			"support" => "HilfeView_introPages.support"
			"p7LrJzVxl-M" => "vid", // introduction video
			"bnote_news" => "Neuerungen in BNote",
			"sicherheit" => "Sicherheitshinweise",
			"support" => "Support / Kontakt"
	);
	
	private $helpPagesDir = "data/help/";
	
	// alphabetically, format: name of the html-file => title
	private $helpPages = array(
//
// Bug when trying to translate with this method_exists
//
//			"abstimmung" => "HilfeView_helpPages.abstimmung",
//			"aufgaben" => "HilfeView_helpPages.aufgaben",
//			"equipment" => "HilfeView_helpPages.equipment",
//			"finance" => "HilfeView_helpPages.finance",
//			"konfiguration" => "HilfeView_helpPages.konfiguration",
//			"calendar" => "HilfeView_helpPages.calendar",
//			"kontakte" => "HilfeView_helpPages.kontakte",
//			"mitspieler" => "HilfeView_helpPages.mitspieler",
//			"nachrichten" => "HilfeView_helpPages.nachrichten",
//			"proben" => "HilfeView_helpPages.proben",
//			"probenphase" => "HilfeView_helpPages.probenphase",
//			"repertoire" => "HilfeView_helpPages.repertoire",
//			"share" => "HilfeView_helpPages.share",
//			"tour" => "HilfeView_helpPages.tour"
			"abstimmung" => "Modul Abstimmung",
			"aufgaben" => "Modul Aufgaben",
			"equipment" => "Modul Equipment",
			"finance" => "Modul Finanzen",
			"konfiguration" => "Modul Konfiguration",
			"calendar" => "Modul Kalender",
			"kontakte" => "Modul Kontakte (und Datenschutz)",
			"mitspieler" => "Modul Mitglieder",
			"nachrichten" => "Modul Nachrichten",
			"proben" => "Modul Proben",
			"probenphase" => "Modul Probenphase",
			"repertoire" => "Modul Repertoire",
			"share" => "Modul Share",
			"tour" => "Modul Tour"
	);
	
	// format: code => description
	private $videos = array(
			"p7LrJzVxl-M" => "BNote Teaser",
			"TEdY7biXXpw" => "BNote Einführung",
			"kOWQjX8kSaQ" => "Administrationsüberblick"
	);
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function showOptions() {
		// none
	}
	
	function start() {		
		// Help Navigation
		?>
		<style>
		p { text-align: justify; }
		.bn-help-strong { font-weight: bold; }
		.bn-help-item { margin-left: 25px; text-align: justify; }
		.bn-help-link { color: #61b3ff; }
		</style>
		<table class="help_navigator">
			<tr>
			<td class="help_navigator_menu">
				<div class="help_navigator_menu_topic">Einführung</div>
				<?php
				// show an introduction video
				$active = false;
				foreach($this->introPages as $code => $page) {
					if(isset($_GET["page"]) && $_GET["page"] == $code) $active = true;
					else if(isset($_GET["vid"]) && $_GET["vid"] == $code) $active = true;
					
					if($page == "vid") {
						$this->writePageLink($this->videos[$code], $this->modePrefix() . "start&vid=" . $code, $active);
					}
					else {
						$this->writePageLink($page, $this->modePrefix() . "start&page=" . $code, $active);
					}
					$active = false;
				}
				?>
						
				<div class="help_navigator_menu_topic">Video Tutorials</div>
				<?php 
				// show all the videos available for this software
				foreach($this->videos as $code => $vid) {
					if($code == "p7LrJzVxl-M") continue;
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
					
					// add a link to the bnote website for admin stuff
					if($_GET["vid"] == "kOWQjX8kSaQ") {
						?>
						<p>Aktuelle Informationen zur Installation und zum Betrieb von BNote findest du auch auf unserer Website:</p>
						<a href="https://github.com/mattimaier/bnote/wiki">BNote Wiki</a><br>
						<a href="http://bnote.info/provider.php">Providerauswahl</a><br>
						<a href="http://bnote.info/install-tutorial.php">Installation und Update</a>
						<?php
					}
				}
				else if(isset($_GET["page"])) {
					if(isset($this->helpPages[$_GET["page"]])) $title = $this->helpPages[$_GET["page"]];
					else if(isset($this->introPages[$_GET["page"]])) $title = $this->introPages[$_GET["page"]];
					else $title = $_GET["page"];
					
					echo '<span class="help_page_title">' . $title . '</span>';
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