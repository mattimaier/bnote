<?php
/**
 * BNote - Ensemble Management Software
 * by Matti Maier und Stefan Kreminski BNote Software GbR
 * @author Matti Maier
 * @author Stefan Kreminski
 * 
 * This is the entry point to the software. However, the main application
 * starts with main.php, not with this file. This file is meant for
 * routing between different applications and the installation in case a configuration is missing.
 */

$installFile = "install.php";

if(!file_exists("config/company.xml") 
		|| !file_exists("config/database.xml") 
		|| !file_exists("config/config.xml")) {
	
	if(file_exists($installFile)) {
		header("location: " . $installFile);
	}
	else {
		echo "Fehler! Deine Konfiguration ist nicht vollständig. ";
		echo "Bitte kopiere install.php erneut auf den Server und lade die Seite neu.";
	}
}
else {
	header("location: main.php?mod=login");
}

?>