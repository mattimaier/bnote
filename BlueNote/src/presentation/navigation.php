<!-- Navigation -->
<div id="navigation">

	<?php
	$modarr = $system_data->getModuleArray();

	// render menu
	foreach($modarr as $id => $name) {

		// don't show module if user doesn't have permission
		if(!$system_data->loginMode() && !$system_data->userHasPermission($id)) continue;

		if($id == $system_data->getModuleId()) {
			// current Module
			$selected = "_selected";
		}
		else $selected = "";

		echo '<a class="navi" href="?mod=' . $id . '"><div class="navi_item' . $selected;
		echo '">' . $name . '</div></a>' . "\n";
	}


	?>
</div>
