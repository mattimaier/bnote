<!-- Banner -->
<div id="banner">
	<div id="bannerContent">
		<div id="CompanyName"><?php echo $system_data->getCompany(); ?></div>
 	   
		<?php
		if(!$system_data->loginMode()) {
	 	?> 
		<div id="Logout">
		 	<?php echo Lang::txt("banner_Logout.welcome"); ?>
		 	<a href="?mod=<?php echo $system_data->getModuleId("Kontaktdaten"); ?>" id="UserInfo"><?php echo $system_data->getUsername(); ?></a>,
		 	<a id="Logout_link" href="main.php?mod=logout"><?php echo Lang::txt("banner_Logout.Logout"); ?></a>
		</div>
		<?php
	 	}
		?>
	</div> 
</div>