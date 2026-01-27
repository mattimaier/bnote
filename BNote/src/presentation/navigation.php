<!-- Navigation -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar overflow-auto collapse d-print-none">
	<div class="position-sticky pt-3">	    
		
		<ul class="nav flex-column">
		<?php
		global $system_data;
		$isHelpMenu = False;
		if($system_data->isUserAuthenticated()) {
			if(isset($_GET["menu"])) {
				$modarr = $system_data->getModuleArray($_GET["menu"]);
			}
			else if(isset($_GET["mod"])) {
				// get the menu of the current module
				$mod = $system_data->getModule($_GET["mod"]);
				$cat = $mod["category"];
				if($cat == "help") {
					global $mainController;
					$modarr = $mainController->getView()->getNavigationItems();
					$isHelpMenu = True;
				}
				else {
					if($cat == "user") {
						$cat = "main";
					}
					$modarr = $system_data->getModuleArray($cat);
				}
			}
			else {
				$modarr = $system_data->getModuleArray("main");
			}
		}
		else {
			$modarr = $system_data->getModuleArray("public");
		}
		
		// render menu
		foreach($modarr as $id => $modRow) {
			// don't show module if user doesn't have permission
			if($system_data->isUserAuthenticated() && !$system_data->userHasPermission($id)) continue;
			
			// don't show module if just technical
			if(in_array($modRow["name"], array("Home", "Logout", "WhyBNote", "Gdpr", "ExtGdpr"))) continue;
			$user_reg = $system_data->getDynamicConfigParameter("user_registration");
			if($modRow["name"] == "Registration" && $user_reg == 0) continue;
			
			// check if to add special menu entry
			$menu = $modRow["category"] == "admin" ? "&menu=admin" : ""; 
	
			if($id == $system_data->getModuleId()) {
				// current Module
				$selected = "active";
			}
			else $selected = "";
			
			if($isHelpMenu) {
				$title = Lang::txt($modRow["name"]);
			}
			else {
				$title = $system_data->getModuleTitle($id);
			}
			?>
			<li class="nav-item">
	        	<a class="nav-link <?php echo $selected; ?>" href="?mod=<?php echo $id . $menu; ?>">
	        		<i class="bi-<?php echo $modRow["icon"]; ?>"></i>
	          		<?php echo $title; ?>
	        	</a>
	      	</li>
			<?php
		}
		?>
		</ul>
	</div>
</nav>