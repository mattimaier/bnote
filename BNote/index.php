<?php
/**
 * BNote - Ensemble Management Software
 * by Matti Maier Internet Solutions
 * 
 * This is the entry point to the software. However, the main application
 * starts with main.php, not with this file. This file is meant for
 * routing between the desktop and the mobile application or the installation
 * in case a configuration is missing.
 */

/*
 * As of version 2.5.0 the web app is disabled by default, because 
 * native apps are planned for the future and the current web apps do not
 * implement the interface properly.
 */
$enableWebApp = false;

$detect = null;
if($enableWebApp) {
	include "lib/mobiledetect/Mobile_Detect.php";
	$detect = new Mobile_Detect();
}

// Detect if the user is a mobile user -> forward to app/
if($enableWebApp && $detect->isMobile() && file_exists("app")) {
	header("location: app/");
}
// for all other users including tablet users, send him/her to application
else {
	if(!file_exists("config/company.xml") || !file_exists("config/database.xml") || !file_exists("config/config.xml")) {
		if(file_exists("install.php")) {
			header("location: install.php");
		}
		else {
			echo "Fehler! Deine Konfiguration ist nicht vollst&auml;ndig. Bitte kopiere install.php erneut auf deinen Server und f&uuml;hre die Datei aus.";
		}
	}
	else {
		header("location: main.php?mod=login");
	}
}

?>