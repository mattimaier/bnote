<?php 
//embed jQuery library
$jQuery_dir = $GLOBALS["DIR_LIB"] . "jquery/";
?>

<HEAD>
 <title><?php echo $system_data->getApplicationName() . " | " . $system_data->getModuleTitle(); ?></title>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

 <link rel="shortcut icon" href="favicon.png" type="image/png" />
 <link rel="icon" href="favicon.png" type="image/png" />
  
 <link href='http://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
  
 <link href="style/css/reset.css" rel="StyleSheet" type="text/css" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery-ui.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery-ui.theme.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery.datetimepicker.min.css" rel="stylesheet" />
 <link type="text/css" href="<?php echo $jQuery_dir; ?>jquery.jqplot.min.css" rel="stylesheet" />
 <link type="text/css" href='<?php echo $jQuery_dir; ?>fullcalendar.css' rel='stylesheet' />
 <link type="text/css" href='<?php echo $jQuery_dir; ?>fullcalendar.css' rel='stylesheet' />
 <link type="text/css" href="lib/dropzone.css" rel="stylesheet" />
 
 <link type="text/css" href="<?php echo "style/css/" . $system_data->getTheme() . "/bnote.css"?>" rel="stylesheet" />

 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery-2.1.1.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery-ui.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.datetimepicker.full.min.js"></script>
 <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.jqplot.min.js"></script>
  <script type="text/javascript" src="<?php echo $jQuery_dir; ?>jquery.dataTables.min.js"></script>

 <script src='<?php echo $jQuery_dir; ?>moment.min.js'></script>
 <script src='<?php echo $jQuery_dir; ?>fullcalendar.js'></script>
 <script src='<?php echo $jQuery_dir; ?>lang-all.js'></script>
 <!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo $jQuery_dir; ?>excanvas.js"></script><![endif]-->
 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LIB"];?>tinymce/tinymce.min.js" ></script>
 <script src="<?php echo $GLOBALS["DIR_LIB"];?>dropzone.js"></script>
 
 <script type="text/javascript" src="<?php echo $GLOBALS["DIR_LOGIC"]; ?>main.js"></script>

 <!-- bootstrap -->
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

 <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

</HEAD>