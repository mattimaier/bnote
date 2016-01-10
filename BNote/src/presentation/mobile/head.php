<?php 
//embed jQuery library
$jQuery_dir = $GLOBALS["DIR_LIB"] . "jquery/";
?>

<head>
	<title>BNote Mobile</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<!-- Include meta tag to ensure proper rendering and touch zooming -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
 
 	<link rel="shortcut icon" href="favicon.png" type="image/png" />
 	<link rel="icon" href="favicon.png" type="image/png" />
 
 	<link href='http://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
 	
 	<link rel="stylesheet" href="<?php echo $jQuery_dir?>themes/BNote3.min.css" />
	<link rel="stylesheet" href="<?php echo $jQuery_dir?>themes/jquery.mobile.icons.min.css" />
	<link rel="stylesheet" href="<?php echo $jQuery_dir?>jquery.mobile.structure-1.4.5.min.css" />

	<link rel="stylesheet" href="<?php echo $GLOBALS["DIR_CSS_MOBILE"] . "mobile.css"; ?>" />
 	
	<script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery-2.1.1.min.js"></script>
	<script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.mobile-1.4.5.min.js"></script>
	<script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.mobile.nestedlists.js"></script>
	
</head>