<?php

/**
 * Help view.
 * @author matti
 *
 */
class HilfeView extends AbstractView {
	
	private $helpPagesDir = "data/help/";
	
	// alphabetically, format: name of the html-file => title
	private $helpPages = array(
			"abstimmung_" => "HilfeView_helpPages.abstimmung",
			"aufgaben_" => "HilfeView_helpPages.aufgaben",
			"equipment_" => "HilfeView_helpPages.equipment",
			"finance_" => "HilfeView_helpPages.finance",
			"konfiguration_" => "HilfeView_helpPages.konfiguration",
			"calendar_" => "HilfeView_helpPages.calendar",
			"kontakte_" => "HilfeView_helpPages.kontakte",
			"mitspieler_" => "HilfeView_helpPages.mitspieler",
			"nachrichten_" => "HilfeView_helpPages.nachrichten",
			"proben_" => "HilfeView_helpPages.proben",
			"probenphase_" => "HilfeView_helpPages.probenphase",
			"repertoire_" => "HilfeView_helpPages.repertoire",
			"share_" => "HilfeView_helpPages.share",
			"tour_" => "HilfeView_helpPages.tour",
			"sicherheit_" => "HilfeView_introPages.sicherheit"
	);
	
	// format: code => description
	private $videos = array(
			"TEdY7biXXpw" => array(
					"title" => "HilfeView_videos.intro",
					"descr" => "HilfeView_videos.intro_description",
					"button" => "HilfeView_videos.intro_button"
			),
			"kOWQjX8kSaQ" => array(
					"title" => "HilfeView_videos.admin_overview",
					"descr" => "HilfeView_videos.admin_description",
					"button" => "HilfeView_videos.admin_button"
			)
	);
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		// Introduction
		?>
		<div class="embed-responsive embed-responsive-16by9 mb-2">
			<iframe class="embed-responsive-item" src="http://www.youtube.com/embed/p7LrJzVxl-M" allowfullscreen></iframe>
		</div>
		
		<div class="d-flex row mt-3 justify-content-start">
			<a href="https://github.com/mattimaier/bnote/wiki"><?php echo Lang::txt("HilfeView_start.vid_1_wiki"); ?></a>
			<a href="http://bnote.info/provider.php"><?php echo Lang::txt("HilfeView_start.vid_1_provider"); ?></a>
			<a href="http://bnote.info/install-tutorial.php"><?php echo Lang::txt("HilfeView_start.vid_1_install"); ?></a>
		</div>
		
		<div class="d-flex row">
		<?php
		
		// show all links as cards with an icon indicating the page category
		foreach($this->videos as $code => $info) {
			$ytLink = "http://www.youtube.com/embed/" . $code;
			$card = new Card(Lang::txt($info["title"]), Lang::txt($info["descr"]), $ytLink, Lang::txt($info["button"]));
			$card->setColSize(3);
			$card->setLinkTarget("_blank");
			$card->write();
		}
		echo '</div>';
		
		// --- HELP PAGES ---
		echo '<div class="d-flex row mt-3">';
		echo Writing::h4(Lang::txt("HilfeView_start.help_pages"));
		
		// show all links as cards with an icon indicating the page category
		foreach($this->helpPages as $helpPageId => $helpPageTitle) {
			$card = new Card(
					Lang::txt($helpPageTitle), 
					Lang::txt("HilfeView_start.module_documentation"), 
					$this->modePrefix() . "help&page=$helpPageId", 
					Lang::txt("HilfeView_start.read_page"));
			$card->setColSize(3);
			$card->write();
		}
		?>
		</div>
		<?php
	}
	
	function startOptions() {
		// none
	}
	
	function help() {
		$lang = $this->getData()->getSysdata()->getLang();
		if(isset($this->helpPages[$_GET["page"]])) $title = $this->helpPages[$_GET["page"]];
		else if(isset($this->introPages[$_GET["page"]])) $title = $this->introPages[$_GET["page"]];
		else $title = $_GET["page"];
		
		echo '<span class="help_page_title">' . Lang::txt($title) . '</span>';
		
		if (file_exists($this->helpPagesDir . $_GET["page"] . $lang . ".html")) {
		} else {
		$lang = "de";
		}
		
		include $this->helpPagesDir . $_GET["page"] . $lang . ".html";
	}
	
}

?>