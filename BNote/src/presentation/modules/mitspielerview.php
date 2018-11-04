<?php

/**
 * View for members module.
 * @author matti
 *
 */
class MitspielerView extends AbstractView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		Writing::h1("Mitglieder");
		?>
		<p class="membercard_hint">Benutze die Tastenkombintation STRG+F (Mac: CMD+F) um auf dieser Seite zu suchen.</p>
		<?php
		if($this->getData()->getSysdata()->getUsersContact() == "") return;
		$members = $this->getData()->getMembers($_SESSION["user"], false);
		$customFields = $this->getData()->getCustomFields('c', true);
		
		for($i = 1; $i < count($members); $i++) {
			$member = $members[$i];
			?>
			<div class="membercard">
			
				<div class="membercard_name"><?php
				echo $member["fullname"];
				if($member["nickname"] != "") {
					echo " (" . $member["nickname"] . ")";
				}
				?></div>
				
				<div class="membercard_instrument"><?php
				echo $member["instrumentname"];
				if($member["birthday"] != "0000-00-00") {
					echo " | " . Data::convertDateFromDb($member["birthday"]);
				}
				?></div>
				
				<div class="membercard_phone"><?php
				$showPhone = false;
				if($member["phone"] != "") {
					echo $member["phone"];
					$showPhone = true;
				} 
				$showMobile = false;
				if($member["mobile"] != "") {
					if($showPhone) {
						echo " | ";
					}
					echo $member["mobile"];
					$showMobile = true;
				}
				if(!$showMobile && !$showPhone) {
					echo "&nbsp;"; // add empty line for same line heights
				}
				?></div>
				
				<div class="membercard_web"><?php 
				echo "<a href=\"mailto:" . $member["email"] . "\">" . $member["email"] . "</a>";
				if($member["web"] != "") {
					echo " | " . $member["web"];
				}
				?></div>
				
				<div class="membercard_address"><?php
				echo $member["street"] . ", " . $member["zip"] . " " . $member["city"]; 
				?></div>
				
				<div class="membercard_customfields"><?php 
				foreach($customFields as $j => $field) {
					if($j == 0) continue;
					?>
					<span class="customfield_entry"><?php echo $field["txtdefsingle"] . ": ";
					if(isset($member[$field["techname"]])) {
						$val = $member[$field["techname"]];
						if($field["fieldtype"] == "BOOLEAN") {
							$val = $val == "" || $val == "0" ? "nein" : "ja";
						}
						else if($field["fieldtype"] == "DOUBLE") {
							$val = Data::convertFromDb($val);
						}
						echo $val;
					} ?></span>
					<?php
				}
				?></div>
				
			</div>
			<?php
		}
	}
	
	function startOptions() {
		$prt = new Link("javascript:print()", "Drucken");
		$prt->addIcon("printer");
		$prt->write();
	}
}

?>