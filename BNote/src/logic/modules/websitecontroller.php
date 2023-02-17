<?php

/**
 * Custom controller for the website module.
 * @author matti
 *
 */
class WebsiteController extends DefaultController {
	
	private $webpages_dir;
	
	function __construct() {
		$this->webpages_dir = $GLOBALS["DATA_PATHS"]["webpages"];
	}
	
	public function start() {
		$this->getData()->setController($this);
		if(isset($_GET['mode'])) {
			if($_GET["mode"] != "save" && isset($_GET["page"])) {
				$this->getView()->start();
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
	
	function getFilenameFromPage($page) {
		$this->createDirIfNotExists($this->webpages_dir);
		$page_file = $this->webpages_dir . $page . ".html";
		if(!file_exists($page_file)) {
			file_put_contents($page_file, "");
		}
		return $page_file;
	}
	
	function getFilenameForInfo($id) {
		$infoPagePrefix = "info_";
		$pagesPath = $GLOBALS["DATA_PATHS"]["webpages"];
		return $pagesPath . $infoPagePrefix . $id . ".html";
	}
	
	private function createDirIfNotExists($dir) {
		if(!file_exists($dir)) {
			mkdir($dir);
		}
	}
	
}