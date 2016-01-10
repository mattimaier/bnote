<div data-role="page" id="pageone">
  <div data-role="header" id="mobile_header">
    <?php if(isset($_GET["mod"]) && $_GET["mod"] != "login") { ?>
  	<a href="#" class="ui-btn ui-shadow ui-corner-all ui-btn-icon-left ui-icon-arrow-l" data-rel="back" data-icon="arrow-l">&nbsp;</a>
  	<?php } ?>
    <h1><?php echo $system_data->getModuleTitle(); ?></h1>
  </div>

  <div data-role="main" class="ui-content">
	<?php $mainController->getController()->start(); ?>  
  </div>

  <div data-role="footer" data-position="fixed">
  <?php
  if(!isset($_GET["mod"]) || $_GET["mod"] == "login") {
  	?>
  	<div style="text-align: center; padding: 10px 10px; font-size: 10pt;">BNote Software GbR</div>
  	<?php 
  }
  else {
  	?>
  	<div data-role="navbar" data-iconpos="top">
  		<ul>
  			<li><a href="?mod=1" data-icon="home">Start</a></li>
  			<li><a href="?mod=13" data-icon="user">Mitspieler</a></li>
	    </ul>
  	</div>
  	<?php
  }
  ?>
  </div>
  
</div> 