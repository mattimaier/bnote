<?php

/**
 * Help view.
 * @author matti
 *
 */
class HilfeView extends AbstractView {
	
	private $introPages = array(
			"p7LrJzVxl-M" => "vid", // introduction video
			"bnote_news" => "HilfeView_introPages.bnote_news",
			"sicherheit" => "HilfeView_introPages.sicherheit",
			"support" => "HilfeView_introPages.support"
	);
	
	private $helpPagesDir = "data/help/";
	
	// alphabetically, format: name of the html-file => title
	private $helpPages = array(
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
			"tour" => "HilfeView_helpPages.tour"
	);
	
	// format: code => description
	private $videos = array(
			"p7LrJzVxl-M" => "HilfeView_videos.teaser",
			"TEdY7biXXpw" => "HilfeView_videos.intro",
			"kOWQjX8kSaQ" => "HilfeView_videos.admin_overview"
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
				<div class="help_navigator_menu_topic"><?php echo Lang::txt("HilfeView_start.intro"); ?></div>
				<?php
				// show an introduction video
				$active = false;
				foreach($this->introPages as $code => $page) {
					if(isset($_GET["page"]) && $_GET["page"] == $code) {
						$active = true;
					}
					else if(isset($_GET["vid"]) && $_GET["vid"] == $code) {
						$active = true;
					}
					if($page == "vid") {
						$this->writePageLink(Lang::txt($this->videos[$code]), $this->modePrefix() . "start&vid=" . $code, $active);
					}
					else {
						$this->writePageLink(Lang::txt($page), $this->modePrefix() . "start&page=" . $code, $active);
					}
					$active = false;
				}
				?>
						
				<div class="help_navigator_menu_topic"><?php echo Lang::txt("HilfeView_start.video_tutorials"); ?></div>
				<?php 
				// show all the videos available for this software
				foreach($this->videos as $code => $vid) {
					if($code == "p7LrJzVxl-M") continue;
					if(isset($_GET["vid"]) && $_GET["vid"] == $code) $active = true;
					$this->writePageLink(Lang::txt($vid), $this->modePrefix() . "start&vid=" . $code, $active);
					$active = false;
				}
				
				?>
				
				<div class="help_navigator_menu_topic"><?php echo Lang::txt("HilfeView_start.help_pages"); ?></div>
				<?php
				// show help pages available for this software
				foreach($this->helpPages as $helpPageId => $helpPageTitle) {
					if(isset($_GET["page"]) && $_GET["page"] == $helpPageId) $active = true;
					$this->writePageLink(Lang::txt($helpPageTitle), $this->modePrefix() . "start&page=" . $helpPageId, $active);
					$active = false; 
				}
				?>
				
			</td>
			<td class="help_navigator_content">
				<?php 
				if(isset($_GET["vid"])) {
					Writing::h2(Lang::txt("HilfeView_start.videos_title"));
					echo '<iframe width="560" height="315" src="http://www.youtube.com/embed/' . $_GET["vid"] . '" frameborder="0" allowfullscreen></iframe>';
					
					// add a link to the bnote website for admin stuff
					if($_GET["vid"] == "kOWQjX8kSaQ") {
						?>
						<p><?php echo Lang::txt("HilfeView_start.vid_1_desc"); ?></p>
						<a href="https://github.com/mattimaier/bnote/wiki"><?php echo Lang::txt("HilfeView_start.vid_1_wiki"); ?></a><br>
						<a href="http://bnote.info/provider.php"><?php echo Lang::txt("HilfeView_start.vid_1_provider"); ?></a><br>
						<a href="http://bnote.info/install-tutorial.php"><?php echo Lang::txt("HilfeView_start.vid_1_install"); ?></a>
						<?php
					}
				}
				else if(isset($_GET["page"])) {
					if(isset($this->helpPages[$_GET["page"]])) $title = $this->helpPages[$_GET["page"]];
					else if(isset($this->introPages[$_GET["page"]])) $title = $this->introPages[$_GET["page"]];
					else $title = $_GET["page"];
					
					echo '<span class="help_page_title">' . Lang::txt($title) . '</span>';
					include $this->helpPagesDir . $_GET["page"] . ".html";
				}
				else {
					Writing::p(Lang::txt("HilfeView_start.select_help"));
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