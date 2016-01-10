<?php

class MitspielerView extends AbstractView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		$members = $this->getData()->getMembers();
		$list = new MobileList($members, array("name", "surname"));
		$list->enableAutoDivider();
		$list->setEditMode($this->modePrefix() . "view");
		$list->write();
	}
	
	function view() {
		$contact = $this->getData()->getContact($_GET["id"]);
		if($contact == null) {
			new Error("Zugriff verweigert.");
		}
		?>
		<ul data-role="listview">
		<?php
		$cols = array(
			"name" => array("user", "Name"),
			"instrument" => array("info", "Instrument"),
			"phone" => array("phone", "Telefon"),
			"mobile" => array("phone", "Mobil"),
			"business" => array("phone", "Geschäftlich"),
			"email" => array("mail", "E-Mail"),
			"web" => array("home", "Website"),
			"birthday" => array("calendar", "Geburtstag")
		);
		foreach($cols as $k => $arr) {
			$v = $contact[$k];
			if($v == "") continue;
			
			$icon = $cols[$k][0];
			$caption = $cols[$k][1];
			
			if($k == "name") {
				$v = $contact["name"] . " " . $contact["surname"];
			}
			
			$link_begin = "";
			$link_end = "";
			if($k == "email" || $k == "phone" || $k == "mobile" || $k == "business") {
				$proto = "tel";
				if($k == "email") $proto = "mailto";
				$link_begin = "<a href=\"$proto:$v\">";
				$link_end = "</a>";
			}
			
			echo "<li data-icon=\"$icon\">$link_begin<strong>$caption</strong><br/>$v$link_end</li>";
		}
		?>
		</ul>
		<?php
	}
	
}

?>