<?php 
//embed jQuery library
$jQuery_dir = $GLOBALS["DIR_LIB"] . "jquery/";
?>

<HEAD>
 <title><?php echo $system_data->getApplicationName() . " | " . $system_data->getModuleTitle(); ?></title>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 
 <link rel="shortcut icon" href="favicon.png" type="image/png" />
 <link rel="icon" href="favicon.png" type="image/png" />
  
 <link href='http://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
  
 <link href="style/css/!reset.css" rel="StyleSheet" type="text/css" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery-ui.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery-ui.theme.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery.datetimepicker.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery.jqplot.min.css" rel="stylesheet" />
 <link type="text/css" href='<?php echo $jQuery_dir; ?>fullcalendar.css' rel='stylesheet' />
 <link type="text/css" href='<?php echo $jQuery_dir; ?>fullcalendar.css' rel='stylesheet' />
 <link type="text/css" href="lib/dropzone.css" rel="stylesheet" />
<?php

# Link all CSS Files in style/css
if($handle = opendir('style/css')) {
	while(false !== ($file = readdir($handle))) {
		if($file != "." && $file != ".." && $file != "reset.css") {
			echo ' <LINK href="style/css/' . $file . '" rel="StyleSheet" type="text/css">'. "\n";
		}
	}
	closedir($handle);
}
?>
 
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery-2.1.1.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery-ui.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.datetimepicker.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.jqplot.min.js"></script>
  <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.dataTables.min.js"></script>

 <script src='<?php echo $jQuery_dir; ?>moment.min.js'></script>
 <script src='<?php echo $jQuery_dir; ?>fullcalendar.js'></script>
 <script src='<?php echo $jQuery_dir; ?>lang-all.js'></script>
 <!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo $jQuery_dir; ?>excanvas.js"></script><![endif]-->
 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LIB"];?>tinymce/tinymce.min.js" ></script>
 <script src="<?php echo $GLOBALS["DIR_LIB"];?>dropzone.js"></script>
 
 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LOGIC"]; ?>main.js"></script>
 
</HEAD>