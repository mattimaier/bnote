<!-- Banner -->
<div id="banner">
	<div id="bannerContent">
		<div id="logoBanner">
			<a href="/">
	 			<img src="style/images/BNote_Logo_white_on_blue_44px.png" />	
	 		</a>	
		 </div>
		
		<div id="CompanyName"><?php echo $system_data->getCompany(); ?></div>
 	   
		<?php
		// check whether autologin is active and user is admin
		if($system_data->isUserMemberGroup(1) && $system_data->isAutologinActive()) {
			?>
			<span id="autoActivation">Die automatische Registrierung ist aktiviert. Bitte Sicherheitshinweise beachten.</span>
			<?php
		}
		
	 	if(!$system_data->loginMode()) {
	 	?> 
		<div id="Logout">
		 	Willkommen <span id="UserInfo"><?php echo $system_data->getUsername(); ?></span>,
		 	<a id="Logout_link" href="main.php?mod=logout">Logout</a>
		</div>
		<?php
	 	}
		?>
	</div> 
</div>