<?php
/*
 * PHP Version 5.0 and 5.1 -> no json support, thus XML
 *
 * WEB APPLICATION INTERFACE: Blue Note Interface (BNI)
 * This file can be called with various parameters to retrieve
 * information from the web application.
 * 
 * Usage: webapp.php?func=[function]&[p1]=[v1]&...
 */

// connect to application
$dir_prefix = "../../";
global $dir_prefix;

include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "database.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "regex.php";
$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($dir_prefix . $GLOBALS['DIR_LIB'] . "xmlarray.php");

// Build Database Connection
$db = new Database();
global $db;

// "route" requst
if(isset($_GET["func"])) {
	$_GET["func"]();
}

/**
 * Shows the page.
 */
function getPage() {
	if(isset($_GET["id"])) {
		include $GLOBALS["dir_prefix"] . $GLOBALS["DATA_PATHS"]["webpages"] . $_GET["id"] . ".html";
					 
		// infopage addin
		if($_GET["id"] == "infos") {
			displayInfoPageLinks();
		}
	}
}

// Infopage Addin
function displayInfoPageLinks() {
	$query = "SELECT id,createdOn,title FROM infos ORDER BY createdOn DESC";
	$res = $GLOBALS["db"]->getSelection($query);
	
	$link_prefix = "";
	if(isset($_GET["prefix"])) {
		$link_prefix = $_GET["prefix"];
	}
	
	echo "<ul>\n";
	for($i = 1; $i < count($res); $i++) {
		echo "<li>" . '<a href="' . $link_prefix . 'info_' . $res[$i]["id"] . '">';
		echo Data::convertDateFromDb($res[$i]["createdOn"]) . " " . $res[$i]["title"];
		echo "</a></li>\n";
	}
	echo "</ul>\n";
}

/**
 * Calculates the image path and shows it.
 */
function getImagePath() {
	// check for id
	if(!isset($_GET["id"])) {
		new Error("ID not set.");
	}
	
	// get data
	$query = "SELECT * FROM galleryimage WHERE id = " . $_GET["id"];
	$img = $GLOBALS["db"]->getRow($query);
	
	// build path
	$res = "/" . $GLOBALS["DATA_PATHS"]["gallery"];
	$imgtype = substr($img["filename"], strrpos($img["filename"], ".")); // e.g. ".jpg"
	$res .= $img["gallery"] . "/" . $img["id"] . $imgtype;
	
	// output
	echo $res;
}

/**
 * Calculates the path to the thumbnail.
 */
function getThumbPath() {
	// check for id
	if(!isset($_GET["id"])) {
		new Error("ID not set.");
	}
	
	// get data
	$query = "SELECT * FROM galleryimage WHERE id = " . $_GET["id"];
	$img = $GLOBALS["db"]->getRow($query);
	
	// build path
	$res = "/" . $GLOBALS["DATA_PATHS"]["gallery"];
	$res .= "thumbs/";
	$imgtype = substr($img["filename"], strrpos($img["filename"], ".")); // e.g. ".jpg"
	$res .= $img["gallery"] . "/" . $img["id"] . $imgtype;
	
	// output
	echo $res;
}

/**
 * Shows XML array with all galleries.
 */
function getGalleries() {
	$query = "SELECT * FROM gallery";
	echo XmlArray::array_encode($GLOBALS["db"]->getSelection($query));
}

/**
 * Shows XML array with gallery infos.
 */
function getGallery() {
	// check for id
	if(!isset($_GET["id"])) {
		new Error("ID not set.");
	}
	
	$query = "SELECT * FROM gallery WHERE id = " . $_GET["id"];
	echo XmlArray::array_encode($GLOBALS["db"]->getRow($query));
}

/**
 * Shows XML array with all images for the given (GET-id) gallery.
 */
function getImagesForGallery() {
	// check for id
	if(!isset($_GET["id"])) {
		new Error("ID not set.");
	}
	
	$query = "SELECT * FROM galleryimage WHERE gallery = " . $_GET["id"];
	echo XmlArray::array_encode($GLOBALS["db"]->getSelection($query));
}

/**
 * Shows XML array with infos on the given (GET-id) image.
 */
function getImage() {
	// check for id
	if(!isset($_GET["id"])) {
		new Error("ID not set.");
	}
	
	$query = "SELECT * FROM galleryimage WHERE id = " . $_GET["id"];
	echo XmlArray::array_encode($GLOBALS["db"]->getRow($query));
}

?>
