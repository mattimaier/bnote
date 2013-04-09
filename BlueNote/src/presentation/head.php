<HEAD>
 <TITLE><?php echo $SYSTEM["appname"] . " | " . $SYSTEM["modtitle"]; ?></TITLE>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?php

# Link all CSS Files in style/css
if($handle = opendir('style/css')) {
 while(false !== ($file = readdir($handle))) {
  if($file != "." && $file != "..") {
   echo ' <LINK href="style/css/' . $file . '" rel="StyleSheet" type="text/css">'. "\n";
   }
  }
 closedir($handle);
 }

 //embed jQuery library
 $jQuery_dir = $GLOBALS["DIR_LIB"] . "jquery/";
 ?>
 <link type="text/css" href="<?php echo $jQuery_dir; ?>css/ui-lightness/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
 
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>js/jquery-1.6.4.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>js/jquery-ui-1.8.16.custom.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>js/jquery-ui-timepicker-addon.js"></script>	
 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LIB"];?>tinymce/jscripts/tiny_mce/tiny_mce.js" ></script>
 
 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LOGIC"]; ?>main.js"></script>
 
</HEAD>