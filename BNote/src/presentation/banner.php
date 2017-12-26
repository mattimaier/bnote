<!-- Banner -->
<div id="banner">
	<div id="bannerContent">
		<div id="logoBanner">
 			<img height="44px" src="style/images/BNote_Logo_white_on_blue_44px.png" />
		 </div>
		
		<div id="CompanyName"><?php echo $system_data->getCompany(); ?></div>
 	   
		<?php
		// check whether autologin is active and user is admin
		if($system_data->isUserMemberGroup(1) && $system_data->isAutologinActive()) {
			?>
			<span id="autoActivation"><?php echo Lang::txt("autoActivation"); ?></span>
			<?php
		}
		
	 	if(!$system_data->loginMode()) {
	 	?> 
		<div id="Logout">
		 	<?php echo Lang::txt("welcome"); ?> <a id="UserInfo"><?php echo $system_data->getUsername(); ?></a>,
		 	<a id="Logout_link" href="main.php?mod=logout">Logout</a>
		</div>
		<?php
	 	}
		?>
	</div> 
</div>