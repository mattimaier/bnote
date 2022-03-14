<?php

require __DIR__ . '/vendor/autoload.php';

/*
 * Define the directory paths.
 */
$DIR_CONFIG = "config/";
$DIR_CSS = "style/css/";
$DIR_CSS_MOBILE = "style/mobile/";
$DIR_ICONS = "style/icons/";
$DIR_DATA = "src/data/";
$DIR_DATA_MODULES = $DIR_DATA . "modules/";
$DIR_LOGIC = "src/logic/";
$DIR_LOGIC_MODULES = $DIR_LOGIC . "modules/";
$DIR_PRESENTATION = "src/presentation/";
$DIR_PRESENTATION_MODULES = $DIR_PRESENTATION . "modules/";
$DIR_PRESENTATION_MOBILE = $DIR_PRESENTATION . "mobile/";
$DIR_WIDGETS = $DIR_PRESENTATION . "widgets/";
$DIR_WIDGETS_MOBILE = $DIR_PRESENTATION . "widgets_mobile/";
$DIR_PRINT = "src/print/";
$DIR_LIB = "lib/";
$DIR_EXPORT = "src/export/";

$DATA_PATHS = array(
	"programs" => "data/programs/",
	"members" => "data/members/",
	"webpages" => "data/webpages/",
	"gallery" => "data/gallery/",
	"share" => "data/share/",
	"userhome" => "data/share/users/",
	"grouphome" => "data/share/groups/"
);

// set as global constants
global $DIR_CONFIG;
global $DIR_CSS;
global $DIR_CSS_MOBILE;
global $DIR_ICONS;
global $DIR_DATA;
global $DIR_DATA_MODULES;
global $DIR_LOGIN;
global $DIR_LOGIN_MODULES;
global $DIR_PRESENTATION;
global $DIR_PRESENTATION_MODULES;
global $DIR_PRESENTATION_MOBILE;
global $DIR_WIDGETS;
global $DIR_WIDGETS_MOBILE;
global $DIR_PRINT;
global $DIR_LIB;
global $DIR_EXPORT;
global $DATA_PATHS;

?>