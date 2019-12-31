<?php
/**
 * Class to display errors
**/

class BNoteError {

 function __construct($message) {
 	/*
 	 * The styles are directly in the HTML, because sometimes the error
 	 * is used independent from stylesheets.
 	 */
  ?>
 <div style="padding: 10px;
	background: #FF6161;
	border: 1px solid #FCBDBD;
	color: #fff;
	font-family: 'PT Sans', 'Raleway', Arial, sans-serif;">
 	<span style="font-size: 16pt;"><?php 
 	if(class_exists("Lang")) {
 		echo Lang::txt("BNoteError_construct.error");
 	}
 	else {
 		echo "Error";
 	}
 	?></span><br/>
 	<p><?php echo $message; ?></p>
 </div>
  <?php
  exit(-1);
 }

}