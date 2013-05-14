<?php
/**
 * BNote - Band Management Software
 * by Matti Maier Internet Solutions
 * 
 * This is the entry point to the software. However, the main application
 * starts with main.php, not with this file. This file is meant for
 * routing between the desktop and the mobile application.
 */

include "lib/mobiledetect/Mobile_Detect.php";
$detect = new Mobile_Detect();

// Detect if the user is a mobile user -> forward to app/
if($detect->isMobile()) {
	header("location: app/");
}
// for all other users including tablet users, send him/her to application
else {
	header("location: main.php?mod=login");
}

?>