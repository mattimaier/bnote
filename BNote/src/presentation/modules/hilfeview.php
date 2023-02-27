<?php

use function Composer\Autoload\includeFile;

/**
 * Help view.
 * @author matti
 *
 */
class HilfeView extends AbstractView {
	
	private $helpPagesDir = "data/help/";
	
	// alphabetically, format: name of the html-file => title
	private $helpPages = array(
			"help" => "HilfeView_start.help_pages",
			"abstimmung" => "HilfeView_helpPages.abstimmung",
			"aufgaben" => "HilfeView_helpPages.aufgaben",
			"equipment" => "HilfeView_helpPages.equipment",
			"finance" => "HilfeView_helpPages.finance",
			"konfiguration" => "HilfeView_helpPages.konfiguration",
			"calendar" => "HilfeView_helpPages.calendar",
			"kontakte" => "HilfeView_helpPages.kontakte",
			"mitspieler" => "HilfeView_helpPages.mitspieler",
			"nachrichten" => "HilfeView_helpPages.nachrichten",
			"proben" => "HilfeView_helpPages.proben",
			"probenphase" => "HilfeView_helpPages.probenphase",
			"repertoire" => "HilfeView_helpPages.repertoire",
			"share" => "HilfeView_helpPages.share",
			"tour" => "HilfeView_helpPages.tour",
			"sicherheit" => "HilfeView_introPages.sicherheit"
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
	
	function getNavigationItems() {
		$items = array();
		$modId = $this->getData()->getSysdata()->getModuleId();
		foreach($this->helpPages as $helpPageId => $helpPageTitle) {
			if($helpPageId == "help") {
				$id = $modId;
				$icon = "house";
			}
			else {
				$id = $modId . "&mode=help&page=$helpPageId";
				$icon = "info";
			}
			if($helpPageId == "sicherheit") {
				$icon = "exclamation-square";
			}
			
			$items[$id] = array(
				"id" => $id,
				"name" => $helpPageTitle,
				"icon" => $icon,
				"category" => "help"
			);
		}
		return $items;
	}
	
	function start() {
		// Introduction
		?>
		
		
		<div class="mt-3 justify-content-start">
			
		</div>
		
		<div class="d-flex row">
		
			<div class="card col-md-3 m-2">
			  <div class="card-body">
			    <h5 class="card-title"><?php echo Lang::txt("HilfeView_start.intro_title"); ?></h5>
			    <p class="card-text"><?php echo Lang::txt("HilfeView_start.intro_description"); ?></p>
			    <a class="btn btn-primary mt-1" target="_blank" href="https://github.com/mattimaier/bnote/wiki"><?php echo Lang::txt("HilfeView_start.vid_1_wiki"); ?></a>
				<a class="btn btn-primary mt-1" target="_blank" href="http://bnote.info/provider.php"><?php echo Lang::txt("HilfeView_start.vid_1_provider"); ?></a>
				<a class="btn btn-primary mt-1" target="_blank" href="http://bnote.info/install-tutorial.php"><?php echo Lang::txt("HilfeView_start.vid_1_install"); ?></a>
			  </div>
			</div>
		
		<?php
		
		// show all links as cards with an icon indicating the page category
		foreach($this->videos as $code => $info) {
			$ytLink = "http://www.youtube.com/embed/" . $code;
			$card = new Card(Lang::txt($info["title"]), Lang::txt($info["descr"]), $ytLink, Lang::txt($info["button"]));
			$card->setColSize(3);
			$card->setLinkTarget("_blank");
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
		$helpPagePath = $this->helpPagesDir . "/$lang/" . $_GET["page"] . ".html";
		if(file_exists($helpPagePath)) {
			include $helpPagePath;
		} else {
			# fallback to German
			include $this->helpPagesDir . "/de/" . $_GET["page"] . ".html";
		}
	}
	
	function helpTitle() {
		# get the title
		if(isset($this->helpPages[$_GET["page"]])) $title = $this->helpPages[$_GET["page"]];
		else if(isset($this->introPages[$_GET["page"]])) $title = $this->introPages[$_GET["page"]];
		else $title = $_GET["page"];
		return Lang::txt($title);
	}
	
}

?>