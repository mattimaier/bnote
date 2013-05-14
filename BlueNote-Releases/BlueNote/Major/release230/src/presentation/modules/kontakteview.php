<?php

/**
 * View for contact module.
 * @author matti
 *
 */
class KontakteView extends CrudRefView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Kontakt");
		$this->setJoinedAttributes(array(
			"address" => array("street", "city", "zip"),
			"instrument" => array("name")
		));
	}
	
	function start() {
		Writing::h1("Kontakte");
		
		// Options		
		$add = new Link($this->modePrefix() . "addForm", "Kontakt hinzuf&uuml;gen");
		$add->addIcon("add");
		$add->write();
		$this->buttonSpace();
		
		$print = new Link($this->modePrefix() . "printMembers", "Mitspielerliste drucken");
		$print->addIcon("printer");
		$print->write();
		$this->buttonSpace();
		
		$vc = new Link($GLOBALS["DIR_EXPORT"] . "kontakte.vcd", "Kontakte Export");
		$vc->addIcon("arrow_down");
		$vc->setTarget("_blank");
		$vc->write();
		$this->verticalSpace();
		
		// show band members
		$this->showContacts();
	}
	
	function showContacts() {
		$title = "";
		$data = array();
		
		switch($_GET["status"]) {
			case "admins":		$title = "Administratoren";
								$data = $this->getData()->getAdmins();
								break;
			case "externals":	$title = "Externe Mitspieler";
								$data = $this->getData()->getExternals();
								break;
			case "applicants":	$title = "Bewerber";
								$data = $this->getData()->getApplicants();
								break;
			case "others":		$title = "Sonstige Kontakte";
								$data = $this->getData()->getOthers();
								break;
			default:			$title = "Band Mitspieler";
								$data = $this->getData()->getMembers();
								break;
		}
		
		// write
		$this->showContactTable($data);
	}
	
	private function showContactTable($data) {
		$tabs = array(
				"members" => "Mitspieler",
				"admins" => "Administratoren",
				"externals" => "Externe Mitspieler",
				"applicants" => "Bewerber",
				"others" => "Sonstige"
		);
		
		// show tabs
		echo "<div class=\"contact_view\">\n";
		echo " <div class=\"contact_view_tabs\">";
		foreach($tabs as $cmd => $label) {
			$active = "";
			if($_GET["status"] == $cmd) $active = "_active";
			else if(!isset($_GET["status"]) && $cmd == "members") $active = "_active";
			echo "<a href=\"" . $this->modePrefix() . "start&status=$cmd\"><span class=\"contact_view_tab$active\">$label</span></a>";
		}
		
		// show data
		echo " <table class=\"contact_view\">\n";
		foreach($data as $i => $row) {
			echo "  <tr>\n";
			
			if($i == 0) {
				// header
				echo "   <td class=\"DataTable_Header\">Name, Vorname</td>";
				echo "   <td class=\"DataTable_Header\">Instrument</td>";
				echo "   <td class=\"DataTable_Header\">Adresse</td>";
				echo "   <td class=\"DataTable_Header\">Telefone</td>";
				echo "   <td class=\"DataTable_Header\">Online</td>";
				//echo "   <td class=\"DataTable_Header\">Notizen</td>";
			}
			else {
				// body
				echo "   <td class=\"DataTable\"><a href=\"" . $this->modePrefix() . "view&id=" . $row["id"] . "\">" . $row["surname"] . ", " . $row["name"] . "</a></td>";
				echo "   <td class=\"DataTable\">" . $row["instrumentname"] . "</td>";
				echo "   <td class=\"DataTable\" style=\"width: 150px;\">" . $row["street"] . "<br/>" . $row["zip"] . " " . $row["city"] . "</td>";
				
				// phones
				$phones = "";
				if($row["phone"] != "") {
					$phones .= "Tel: " . $row["phone"];
				}
				if($row["mobile"] != "") {
					if($phones != "") $phones .= "<br/>";
					$phones .= "Mobil: " . $row["mobile"]; 
				}
				if($row["business"] != "") {
					if($phones != "") $phones .= "<br/>";
					$phones .= "Arbeit: " . $row["business"];
				}
				echo "   <td class=\"DataTable\" style=\"width: 150px;\">$phones</td>";
				
				// online
				echo "   <td class=\"DataTable\"><a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a>";
				if($row["web"] != "") {
					echo "<br/><a href=\"http://" . $row["web"] . "\">" . $row["web"] . "</a>";
				} 
				echo "</td>";
				
				// notizen
				//echo "   <td class=\"DataTable\">" . $row["notes"] . "</td>";
			}
			
			echo "  </tr>";
		}
		// show "no entries" row when this is the case
		if(count($data) == 1) {
			echo "<tr><td colspan=\"5\">Keine Kontaktdaten vorhanden.</td></tr>\n";
		}
		
		echo "</table>\n";
		echo " </div>";
		echo "</div>";
	}
	
	function addForm() {
		$form = new Form("Kontakt hinzuf&uuml;gen", $this->modePrefix() . "add");
		
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->setForeign("instrument", "instrument", "id", "name", -1);
		$form->addForeignOption("instrument", "[keine Angabe]", 0);
		
		$form->removeElement("address");
		$form->addElement("Stra&szlig;e", new Field("street", "", FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", "", FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", "", FieldType::CHAR));
		
		$form->removeElement("status");
		$dd = new Dropdown("status");
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_ADMIN), KontakteData::$STATUS_ADMIN);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_MEMBER), KontakteData::$STATUS_MEMBER);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_EXTERNAL), KontakteData::$STATUS_EXTERNAL);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_APPLICANT), KontakteData::$STATUS_APPLICANT);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_OTHER), KontakteData::$STATUS_OTHER);
		$dd->setSelected(KontakteData::$STATUS_MEMBER);
		$form->addElement("Status", $dd);
		
		$form->write();
		
		$this->verticalSpace();
		$this->backToStart();
	}
	
	function viewDetailTable() {
		$entity = $this->getData()->getContact($_GET["id"]);
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->removeElement("Instrument");
		$details->renameElement("instrumentname", "Instrument");
		$details->removeElement("Adresse");
		$details->renameElement("street", "Stra&szlig;e");
		$details->renameElement("zip", "PLZ");
		$details->renameElement("city", "Stadt");
		$details->write();
	}
	
	function additionalViewButtons() {
		// only show when it doesn't already exist
		if(!$this->getData()->hasContactUserAccount($_GET["id"])) {
			// show button
			$btn = new Link($this->modePrefix() . "createUserAccount&id=" . $_GET["id"],
						"Benutzerkonto erstellen");
			$btn->addIcon("user");
			$btn->write();
			$this->buttonSpace();
		}
	}
	
	function editEntityForm() {
		$contact = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form("Kontakt hinzuf&uuml;gen", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);
		
		$address = $this->getData()->getAddress($contact["address"]);
		$form->removeElement("address");
		$form->addElement("Stra&szlig;e", new Field("street", $address["street"], FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", $address["city"], FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", $address["zip"], FieldType::CHAR));
		
		$form->removeElement("status");
		$dd = new Dropdown("status");
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_ADMIN), KontakteData::$STATUS_ADMIN);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_MEMBER), KontakteData::$STATUS_MEMBER);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_EXTERNAL), KontakteData::$STATUS_EXTERNAL);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_APPLICANT), KontakteData::$STATUS_APPLICANT);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_OTHER), KontakteData::$STATUS_OTHER);
		$dd->setSelected($contact["status"]);
		$form->addElement("Status", $dd);
		
		$form->write();
	}
	
	function printMembers() {
		Writing::h2("Mitspielerliste");
		
		// determine filename
		$filename = $GLOBALS["DATA_PATHS"]["members"];
		$filename .= "Mitspielerliste-" . date('Y-m-d') . ".pdf";
		
		// create report
		require_once $GLOBALS["DIR_PRINT"] . "memberlist.php";
		new MembersPDF($filename, $this->getData(), KontakteData::$STATUS_MEMBER);
		
		// show report
		echo "<embed src=\"$filename\" width=\"90%\" height=\"700px\" />\n";
		echo "<br /><br />\n";
		
		// back button
		$this->backToStart();
		$this->verticalSpace();
	}
	
	function userCreatedAndMailed($username, $email) {
		$m = "Die Zugangsdaten wurden an $email geschickt.";
		new Message("Benutzer $username erstellt", $m);
		$this->backToViewButton($_GET["id"]);
	}
	
	function userCredentials($username, $password) {
		$m = "<br />Die Zugangsdaten konnten dem Benutzer nicht zugestellt werden ";
		$m .= "da keine E-Mail-Adresse hinterlegt ist oder die E-Mail nicht ";
		$m .= "versandt werden konnte. Bitte teile dem Benutzer folgende ";
		$m .= "Zugangsdaten mit:<br /><br />";
		$m .= "Benutzername <strong>$username</strong><br />";
		$m .= "Passwort <strong>$password</strong>";
		new Message("Benutzer $username erstellt", $m);
		$this->backToViewButton($_GET["id"]);
	}
}

?>