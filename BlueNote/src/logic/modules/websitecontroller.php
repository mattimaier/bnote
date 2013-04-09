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
	
	function __construct() {
		$this->thumb_dir = $GLOBALS["DATA_PATHS"]["gallery"] . "thumbs/";
		$this->gallery_dir = $GLOBALS["DATA_PATHS"]["gallery"];
		
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
				$this->getView()->editPage($_GET["page"]);
			}
			else {
				$this->getView()->$_GET['mode']();
			}
		}
		else {
			$this->getView()->start();
		}
	}
	
	function getFilenameFromPage($page) {
		return $GLOBALS["DATA_PATHS"]["webpages"] . $page . ".html";
	}
	
	function getFilenameForInfo($id) {
		$infoPagePrefix = "info_";
		$pagesPath = $GLOBALS["DATA_PATHS"]["webpages"];
		return $pagesPath . $infoPagePrefix . $id . ".html";
	}
	
	function getThumbnailDir() {
		return $this->thumb_dir;
	}
	
	function getGalleryDir() {
		return $this->gallery_dir;
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
