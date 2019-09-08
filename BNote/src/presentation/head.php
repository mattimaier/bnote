<?php
//embed jQuery library
$jQuery_dir = $GLOBALS["DIR_LIB"] . "jquery/";
$MDBootstrap_dir = $GLOBALS["DIR_LIB"] . "MDBootstrap/";
?>

<HEAD>
 <title><?php echo $system_data->getApplicationName() . " | " . $system_data->getModuleTitle(); ?></title>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

 <link rel="shortcut icon" href="favicon.png" type="image/png" />
 <link rel="icon" href="favicon.png" type="image/png" />

 <!-- <link href='http://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic' rel='stylesheet' type='text/css'> -->

 <!-- <link href="style/css/reset.css" rel="StyleSheet" type="text/css" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery-ui.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery-ui.theme.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery.datetimepicker.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery.jqplot.min.css" rel="stylesheet" />
 <link type="text/css" href='<?php echo $jQuery_dir; ?>fullcalendar.css' rel='stylesheet' />
 <link type="text/css" href='<?php echo $jQuery_dir; ?>fullcalendar.css' rel='stylesheet' />
 <link type="text/css" href="lib/dropzone.css" rel="stylesheet" /> -->

 <!-- <link type="text/css" href="<?php echo "style/css/" . $system_data->getTheme() . "/bnote.css" ?>" rel="stylesheet" /> -->


 <!-- <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery-2.1.1.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery-ui.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.datetimepicker.full.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.jqplot.min.js"></script>
  <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.dataTables.min.js"></script> -->

 <!-- <script src='<?php echo $jQuery_dir; ?>moment.min.js'></script>
 <script src='<?php echo $jQuery_dir; ?>fullcalendar.js'></script>
 <script src='<?php echo $jQuery_dir; ?>lang-all.js'></script> -->
 <!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo $jQuery_dir; ?>excanvas.js"></script><![endif]-->
 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LIB"]; ?>tinymce/tinymce.min.js" ></script>
 <script src="<?php echo $GLOBALS["DIR_LIB"]; ?>dropzone.js"></script>

 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LOGIC"]; ?>main.js"></script>

 <!-- bootstrap -->
 <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta http-equiv="x-ua-compatible" content="ie=edge">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
  <!-- Bootstrap core CSS -->
  <link href="<?php echo $MDBootstrap_dir; ?>css/bootstrap.min.css" rel="stylesheet">
  <!-- Material Design Bootstrap -->
  <link href="<?php echo $MDBootstrap_dir; ?>css/mdb.min.css" rel="stylesheet">
  <!-- Your custom styles (optional) -->
  <link href="<?php echo $MDBootstrap_dir; ?>css/style.css" rel="stylesheet">

  <link href="<?php echo $MDBootstrap_dir; ?>css/addons/datatables.min.css" rel="stylesheet">
  <link href="<?php echo $MDBootstrap_dir; ?>css/addons/responsive.dataTables.min.css" rel="stylesheet">

  <script type="text/javascript" src="<?php echo $MDBootstrap_dir; ?>js/jquery-3.4.1.min.js"></script>


  <link type="text/css" href="<?php echo "style/css/default/bnote.css" ?>" rel="stylesheet" />



</HEAD>