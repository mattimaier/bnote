<!-- Banner -->
<div id="banner">
	<div id="bannerContent">
		<div id="logoBanner">
 			<img height="44px" src="style/images/<?php echo $system_data->getLogoFilename(); ?>" />
		 </div>
		
		<div id="CompanyName"><?php echo $system_data->getCompany(); ?></div>
 	   
		<?php
		if(!$system_data->loginMode()) {
	 	?> 
		<div id="Logout">
		 	<?php echo Lang::txt("welcome"); ?>
		 	<a href="?mod=<?php echo $system_data->getModuleId("Kontaktdaten"); ?>" id="UserInfo"><?php echo $system_data->getUsername(); ?></a>,
		 	<a id="Logout_link" href="main.php?mod=logout">Logout</a>
		</div>
		<?php
	 	}
		?>
	</div> 
</div>