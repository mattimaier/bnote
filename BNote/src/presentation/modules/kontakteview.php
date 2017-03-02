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
		
		// show band members
		$this->showContacts();
	}
	
	protected function isSubModule($mode) {
		if($mode == "groups") return true;
		return false;
	}
	
	protected function subModuleOptions() {
		$this->getController()->groupOptions();
	}
	
	protected function startOptions() {
		parent::startOptions();
		$this->buttonSpace();
		
		$eps = new Link($this->modePrefix() . "integration", "Einphasung");
		$eps->addIcon("integration");
		$eps->write();
		$this->buttonSpace();
		
		$groups = new Link($this->modePrefix() . "groups&func=start", "Gruppen verwalten");
		$groups->addIcon("mitspieler");
		$groups->write();
		$this->buttonSpace();
		
		$print = new Link($this->modePrefix() . "selectPrintGroups", "Mitgliederliste drucken");
		$print->addIcon("printer");
		$print->write();
		$this->buttonSpace();
		
		$vc = new Link($GLOBALS["DIR_EXPORT"] . "kontakte.vcd", "Kontakte Export (vCard)");
		$vc->addIcon("save");
		$vc->setTarget("_blank");
		$vc->write();
		$this->buttonSpace();
		
		$vc = new Link($this->modePrefix() . "contactImport", "Kontakte Import (vCard)");
		$vc->addIcon("arrow_down");
		$vc->write();
		
		$this->verticalSpace();
	}
	
	function showContacts() {		
		// show correct group
		if(isset($_GET["group"]) && $_GET["group"] == "all") {
			$data = $this->getData()->getAllContacts();
		}
		else if(isset($_GET["group"])) {
			$data = $this->getData()->getGroupContacts($_GET["group"]);
		}
		else {
			// default: MEMBERS
			$data = $this->getData()->getMembers();
		}
		
		// write
		$this->showContactTable($data);
	}
	
	private function showContactTable($data) {
		$groups = $this->getData()->getGroups();
		
		// show groups as tabs
		echo "<div class=\"contact_view\">\n";
		echo " <div class=\"view_tabs\">";
		foreach($groups as $cmd => $info) {
			if($cmd == 0) {
				// instead of skipping the first header-row,
				// insert a tab with all contacts
				$cmd = "all";
				$info = array("name" => "Alle Kontakte", "id" => "all");
			}
			$label = $info["name"];
			$groupId = $info["id"];
			
			$active = "";
			if(isset($_GET["group"]) && $_GET["group"] == $groupId) $active = "_active";
			else if(!isset($_GET["group"]) && $groupId == 2) $active = "_active";
			
			echo "<a href=\"" . $this->modePrefix() . "start&group=$groupId\"><span class=\"view_tab$active\">$label</span></a>";
		}
		
		// show data
		echo " <table id=\"contact_table\" class=\"contact_view\">\n";
		foreach($data as $i => $row) {
			
			
			if($i == 0) {
				// header
				echo "<thead>";
				echo "   <td class=\"DataTable_Header\">Name, Vorname</td>";
				echo "   <td class=\"DataTable_Header\">Instrument</td>";
				echo "   <td class=\"DataTable_Header\">Adresse</td>";
				echo "   <td class=\"DataTable_Header\">Telefone</td>";
				echo "   <td class=\"DataTable_Header\">Online</td>";
				echo "</thead>";
				echo "<tbody>";
			}
			else {
				echo "  <tr>\n";
				// body
				$contact_name = $row["surname"] . ", " . $row["name"];
				if($row['nickname'] != "") {
					$contact_name .= "<br/>(" . $row['nickname'] . ")";
				}
				echo "   <td class=\"DataTable\"><a href=\"" . $this->modePrefix() . "view&id=" . $row["id"] . "\">$contact_name</a></td>";
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
			}
			
			echo "  </tr>";
		}
		// show "no entries" row when this is the case
		if(count($data) == 1) {
			echo "<tr><td colspan=\"5\">Keine Kontaktdaten vorhanden.</td></tr>\n";
		}
		
		echo "</tbody>";
		echo "</table>\n";
		echo " </div>";
		echo "</div>";

		?>
			<script>
		
		// convert table to javasript DataTable
		$(document).ready(function() {
			var identifier = "#contact_table";
    		$(identifier).DataTable({
				 "paging": false, 
				 "info": false,  
				 "oLanguage": {
					 		 "sEmptyTable":  "<?php echo Lang::txt("table_no_entries"); ?>",
							  "sInfoEmpty":  "<?php echo Lang::txt("table_no_entries"); ?>",
							  "sZeroRecords":  "<?php echo Lang::txt("table_no_entries"); ?>",
        					 "sSearch": "<?php echo Lang::txt("table_search"); ?>"
		       }
			});
	});
		</script>
		<?php
	}
	
	function addEntity() {
		$form = new Form("Kontakt hinzufügen", $this->modePrefix() . "add");
		
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->setForeign("instrument", "instrument", "id", "name", -1);
		$form->addForeignOption("instrument", "[keine Angabe]", 0);
		
		$form->removeElement("address");
		$form->addElement("Stra&szlig;e", new Field("street", "", FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", "", FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", "", FieldType::CHAR));
		
		$form->removeElement("status");
		
		// group selection
		$groups = $this->getData()->getGroups();
		$gs = new GroupSelector($groups, array(), "group");
		$form->addElement("Gruppen", $gs);
		
		$form->write();
	}
	
	function add() {
		$this->groupSelectionCheck();
		
		// do as usual
		parent::add();
	}
	
	function viewDetailTable() {
		// user details
		$entity = $this->getData()->getContact($_GET["id"]);
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->removeElement("Status");
		$details->removeElement("Instrument");
		$details->renameElement("instrumentname", "Instrument");
		$details->removeElement("Adresse");
		$details->renameElement("street", "Stra&szlig;e");
		$details->renameElement("zip", "PLZ");
		$details->renameElement("city", "Stadt");
		
		// the contact is a member of these groups
		$groups = $this->getData()->getContactGroups($_GET["id"]);
		$details->addElement("Gruppen", $groups);
		
		$details->write();
	}
	
	function additionalViewButtons() {
		// only show when it doesn't already exist
		if(!$this->getData()->hasContactUserAccount($_GET["id"])) {
			// show button
			$btn = new Link($this->modePrefix() . "createUserAccount&id=" . $_GET["id"], "Benutzerkonto erstellen");
			$btn->addIcon("user");
			$btn->write();
			$this->buttonSpace();
		}
	}
	
	function editEntityForm($write=true) {
		$contact = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form("Kontakt bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);
		
		$address = $this->getData()->getAddress($contact["address"]);
		$form->removeElement("address");
		$form->addElement("Stra&szlig;e", new Field("street", $address["street"], FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", $address["city"], FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", $address["zip"], FieldType::CHAR));
		
		$form->removeElement("status");
		// group selection
		$groups = $this->getData()->getGroups();
		$userGroups = $this->getData()->getContactGroupsArray($_GET["id"]);
		$gs = new GroupSelector($groups, $userGroups, "group");
		$form->addElement("Gruppen", $gs);
		
		$form->write();
	}
	
	function edit_process() {
		$this->groupSelectionCheck();
		
		// do as usual
		parent::edit_process();
	}
	
	private function groupSelectionCheck() {
		// make sure at least one group is selected
		$groups = $this->getData()->getGroups();
		$isAGroupSelected = false;
		
		for($i = 1; $i < count($groups); $i++) {
			$fieldId = "group_" . $groups[$i]["id"];
			if(isset($_POST[$fieldId])) {
				$isAGroupSelected = true;
				break;
			}
		}
		
		if(!$isAGroupSelected) {
			new BNoteError("Bitte weise dem Kontakt mindestens eine Gruppe zu.");
		}
	}
	
	function selectPrintGroups() {
		Writing::h2("Mitgliederliste drucken");
		Writing::p("Alle Mitglieder sind in Gruppen sortiert. Bitte wähle die Gruppen deren Mitglieder du drucken möchtest.");
		
		$form = new Form("Gruppenauswahl", $this->modePrefix() . "printMembers");
		
		// group selection
		$groups = $this->getData()->getGroups();
		$gs = new GroupSelector($groups, array(), "group");
		$form->addElement("Gruppen", $gs);
		
		$form->changeSubmitButton("Druckvorschau anzeigen");
		$form->write();
	}
	
	function printMembers() {
		// convert $_POST groups into a flat groups array
		$allGroups = $this->getData()->getGroups();
		$groups = array();
		for($i = 1; $i < count($allGroups); $i++) {
			$gid = $allGroups[$i]["id"];
			if(isset($_POST["group_" . $gid])) {
				$name = $allGroups[$i]["name"];
				$groups[$gid] = $name;
			}
		}
		if(count($groups) == 0) {
			new Message("Fehler bei Gruppenauswahl", "Wähle mindestens eine Gruppe zum drucken aus.");
			$this->backToStart();
			return;
		}
		
		// print styles
		?>
		<style>
			@media print {
				.dataTables_filter {
					display: none;
				}
				
				thead > tr {
					border-color: #61b3ff;
				}
				
				td.DataTable_Header {
					color: #61b3ff;
				}
			}
		</style>
		<?php
		// show a printable list of members for each group
		foreach($groups as $gid => $name) {
			Writing::h3($name);
			
			$members = $this->getData()->getGroupContacts($gid);
			$tab = new Table($this->formatMemberPrintTable($members));
			$tab->write();
			
			$this->verticalSpace();
		}
	}
	
	private function formatMemberPrintTable($members) {
		$formatted = array();
		// header
		array_push($formatted, array(
				"Name",
				"Spitzname",
				"Instrument",
				"Telefon",
				"Mobil",
				"Business",
				"Email",
				"Adresse")
		);
		
		// body
		for($i = 1; $i < count($members); $i++) {
			$row = $members[$i];
			$fRow = array(
				"Name" => $row["name"] . " " . $row["surname"],
				"Spitzname" => $row["nickname"],
				"Instrument" => $row["instrumentname"],
				"Telefon" => $row["phone"],
				"Mobil" => $row["mobile"],
				"Business" => $row["business"],
				"Email" => $row["email"],
				"Adresse" => $row["street"] . ", " . $row["zip"] . " " . $row["city"]
			);
			array_push($formatted, $fRow);
		}
		
		return $formatted;
	}
	
	function printMembersOptions() {
		$this->backToStart();
		$this->buttonSpace();
		
		$prt = new Link("javascript:window.print();", Lang::txt("print"));
		$prt->addIcon("printer");
		$prt->write();
	}
	
	function userCreatedAndMailed($username, $email) {
		$m = "Die Zugangsdaten wurden an $email geschickt.";
		new Message("Benutzer $username erstellt", $m);
	}
	
	function userCredentials($username, $password) {
		$m = "<br />Die Zugangsdaten konnten dem Benutzer nicht zugestellt werden ";
		$m .= "da keine E-Mail-Adresse hinterlegt ist oder die E-Mail nicht ";
		$m .= "versandt werden konnte. Bitte teile dem Benutzer folgende ";
		$m .= "Zugangsdaten mit:<br /><br />";
		$m .= "Benutzername <strong>$username</strong><br />";
		$m .= "Passwort <strong>$password</strong>";
		new Message("Benutzer $username erstellt", $m);
	}
	
	function integration() {
		Writing::h2("Einphasung neuer Mitglieder");
		Writing::p("Wähle zunächst die Mitglieder aus, die du einphasen möchtest.
				Dann klickst du alle Einträge an, die du diesen Mitgliedern zuweisen möchtest.
				Schließlich klickst du auf den Speichern Button um die Zuweisungen zu speichern.");
		?>
		<form method="POST" action="<?php echo $this->modePrefix(); ?>integration_process">
		<div class="start_box_table">
			<div class="start_box_row">
				<div class="start_box">
					<div class="start_box_heading">Mitglieder</div>
					<div class="start_box_content">
						<?php 
						$members = $this->getData()->getMembers();
						$group = new GroupSelector($members, array(), "member");
						$group->setNameColumns(array("name", "surname"));
						echo $group->write();
						?>
					</div>
				</div>
				<div class="start_box">
					<div class="start_box_heading">Proben</div>
					<div class="start_box_content">
						<?php
						$rehearsals = $this->getData()->adp()->getFutureRehearsals();
						$group = new GroupSelector($rehearsals, array(), "rehearsal");
						$group->setNameColumn("begin");
						$group->setCaptionType(FieldType::DATE);
						echo $group->write();
						?>
					</div>
					<div class="start_box_heading">Probenphasen</div>
					<div class="start_box_content">
						<?php 
						$phases = $this->getData()->getPhases();
						$group = new GroupSelector($phases, array(), "rehearsalphase");
						echo $group->write();
						?>
					</div>
				</div>
				<div class="start_box">
					<div class="start_box_heading">Auftritte</div>
					<div class="start_box_content">
						<?php
						$concerts = $this->getData()->adp()->getFutureConcerts();
						$group = new GroupSelector($concerts, array(), "concert");
						$group->setNameColumn("begin");
						$group->setCaptionType(FieldType::DATE);
						echo $group->write();
						?>
					</div>
					<div class="start_box_heading">Abstimmungen</div>
					<div class="start_box_content">
						<?php
						$votes = $this->getData()->getVotes();
						$group = new GroupSelector($votes, array(), "vote");
						echo $group->write();
						?>
					</div>
				</div>
			</div>
		</div>
		
		<input type="submit" value="speichern" />
		</form>
		<?php
	}
	
	function contactImport() {
		$form = new Form("Kontaktdaten Import", $this->modePrefix() . "contactImportProcess");
		$form->setMultipart();
		$form->addElement("VCard Datei", new Field("vcdfile", "", FieldType::FILE));
		$form->write();
	}
	
	function importVCardSuccess($message) {
		new Message("VCard Import", $message);
	}
}

?>