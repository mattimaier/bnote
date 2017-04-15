<?php

/**
 * Custom controller for the website module.
 * @author matti
 *
 */
class WebsiteController extends DefaultController {
	
	private $thumb_dir; //directory including path
	private $gallery_dir; //directory including path
	private $default_image_width; // integer in pixel
	private $default_thumbnail_height; // integer in pixel
	private $webpages_dir;
	
	function __construct() {
		$this->thumb_dir = $GLOBALS["DATA_PATHS"]["gallery"] . "thumbs/";
		$this->gallery_dir = $GLOBALS["DATA_PATHS"]["gallery"];
		$this->webpages_dir = $GLOBALS["DATA_PATHS"]["webpages"];
		
		$this->default_image_width = 800;
		$this->default_thumbnail_height = 50;
	}
	
	public function start() {
		$this->getData()->setController($this);
		if(isset($_GET['mode'])) {
			if($_GET["mode"] == "gallery") {
				if(isset($_GET["sub"])) {
					$func = "gallery_" . $_GET["sub"];
					$this->getView()->$func();
				}
				else {
					$this->getView()->gallery();
				}
			}
			else if($_GET["mode"] != "save" && isset($_GET["page"])) {
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
	
	function getThumbnailDir() {
		$this->createDirIfNotExists($this->gallery_dir);
		$this->createDirIfNotExists($this->thumb_dir);
		return $this->thumb_dir;
	}
	
	function getGalleryDir() {
		$this->createDirIfNotExists($this->gallery_dir);
		return $this->gallery_dir;
	}
	
	private function createDirIfNotExists($dir) {
		if(!file_exists($dir)) {
			mkdir($dir);
		}
	}
	
	/**
	 * @return The default width as an integer in pixel.
	 */
	function getDefaultImageWidth() {
		return $this->default_image_width;
	}
	
	/**
	 * @return The default height for a thumbnail in pixel.
	 */
	function getDefaultThumbnailHeight() {
		return $this->default_thumbnail_height;
	}
}

?>
