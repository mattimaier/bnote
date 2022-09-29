<?php

/**
 * View for contact module.
 * @author matti
 *
 */
class KontakteView extends CrudRefLocationView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("KontakteView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("KontakteView_construct.addEntityName"));
		$this->setJoinedAttributes(array(
			"address" => array("street", "city", "zip", "state", "country"),
			"instrument" => array("name")
		));
	}
	
	function start() {		
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
		
		// set group filter if group is selected
		$groupFilter = "&group=";
		if(isset($_GET["group"])) {
			$groupFilter .= $_GET["group"];
		}
		else {
			$groupFilter .= KontakteData::$GROUP_MEMBER; // members by default
		}
		$eps = new Link($this->modePrefix() . "integration" . $groupFilter, Lang::txt("KontakteView_startOptions.integration"));
		$eps->addIcon("box-arrow-in-up-right");
		$eps->write();
		
		$groups = new Link($this->modePrefix() . "groups&func=start", Lang::txt("KontakteView_startOptions.players"));
		$groups->addIcon("people-fill");
		$groups->write();
		
		$print = new Link($this->modePrefix() . "selectPrintGroups", Lang::txt("KontakteView_startOptions.selectPrintGroups"));
		$print->addIcon("printer");
		$print->write();
		
		$vc = new Link($this->modePrefix() . "contactImport", Lang::txt("KontakteView_startOptions.contactImport"));
		$vc->addIcon("person-rolodex");
		$vc->write();
		
		$vc = new Link($GLOBALS["DIR_EXPORT"] . "kontakte.vcd", Lang::txt("KontakteView_startOptions.contactExport"));
		$vc->addIcon("file-earmark-arrow-down-fill");
		$vc->setTarget("_blank");
		$vc->write();
	
		$gdprOk = new Link($this->modePrefix() . "gdprOk", Lang::txt("KontakteView_startOptions.gdprOk"));
		$gdprOk->addIcon("journal-check");
		$gdprOk->write();
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
		echo "<div class=\"contact_view\">";
		echo " <div class=\"nav nav-tabs\">";
		foreach($groups as $cmd => $info) {
			if($cmd == 0) {
				// instead of skipping the first header-row,
				// insert a tab with all contacts
				$cmd = "all";
				$info = array("name" => Lang::txt("KontakteView_showContactTable.all"), "id" => "all");
			}
			$label = $info["name"];
			$groupId = $info["id"];
			
			$active = "";
			if(isset($_GET["group"]) && $_GET["group"] == $groupId) $active = "active";
			else if(!isset($_GET["group"]) && $groupId == 2) $active = "active";
			
			echo "<div class=\"nav-item\">";
			echo " <a class=\"nav-link $active\" href=\"" . $this->modePrefix() . "start&group=$groupId\">$label</a>";
			echo "</div>";
		}
		echo " </div>";
		
		// show data
		echo " <table id=\"contact_table\" class=\"table contact_view\">\n";
		foreach($data as $i => $row) {
					
			if($i == 0) {
				// header
				echo "<thead>";
				echo "   <th class=\"DataTable_Header\">" . Lang::txt("KontakteView_showContactTable.name") . "</th>";
				echo "   <th class=\"DataTable_Header\">" . Lang::txt("KontakteView_showContactTable.music") . "</th>";
				echo "   <th class=\"DataTable_Header\">" . Lang::txt("KontakteView_showContactTable.adress") . "</th>";
				echo "   <th class=\"DataTable_Header\">" . Lang::txt("KontakteView_showContactTable.phone") . "</th>";
				echo "   <th class=\"DataTable_Header\">" . Lang::txt("KontakteView_showContactTable.online") . "</th>";
				echo "</thead>";
				echo "<tbody>";
			}
			else {
				echo "  <tr>\n";
				// body
				$names = array();
				if($row["surname"] != "") array_push($names, $row["surname"]);
				if($row["name"] != "") array_push($names, $row["name"]);
				$contact_name = join(", ", $names);
				if($row['nickname'] != "") {
					$contact_name .= "<br/>(" . $row['nickname'] . ")";
				}
				echo "   <td class=\"DataTable\"><a href=\"" . $this->modePrefix() . "view&id=" . $row["id"] . "\">$contact_name</a></td>";
				
				// instrument, conductor
				echo "   <td class=\"DataTable\">". Lang::txt("KontakteView_showContactTable.instrumentname") . $row["instrumentname"];
				if(isset($row["is_conductor"])) {
					echo "<br>". Lang::txt("KontakteView_showContactTable.is_conductor");
					echo $row["is_conductor"] == "1" ? Lang::txt("KontakteView_showContactTable.yes") : Lang::txt("KontakteView_showContactTable.no");
				}
				echo "</td>";
				
				echo "   <td class=\"DataTable\" style=\"width: 150px;\">" . $this->formatAddress($row, TRUE, "", TRUE) . "</td>";
				
				// phones
				$phones = "";
				if($row["phone"] != "") {
					$phones .= Lang::txt("KontakteView_showContactTable.phone") . $row["phone"];
				}
				if($row["mobile"] != "") {
					if($phones != "") $phones .= "<br/>";
					$phones .= Lang::txt("KontakteView_showContactTable.mobile") . $row["mobile"]; 
				}
				if($row["business"] != "") {
					if($phones != "") $phones .= "<br/>";
					$phones .= Lang::txt("KontakteView_showContactTable.business") . $row["business"];
				}
				echo "   <td class=\"DataTable\" style=\"width: 150px;\">$phones</td>";
				
				// online
				echo "   <td class=\"DataTable\"><a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a>";
				if($row["web"] != "") {
					echo "<br/><a href=\"https://" . $row["web"] . "\" target=\"_blank\">" . $row["web"] . "</a>";
				} 
				echo "</td>";
			}
			
			echo "  </tr>";
		}
		// show "no entries" row when this is the case
		if(count($data) == 1) {
			echo "<tr><td colspan=\"5\">" . Lang::txt("KontakteView_showContactTable.no_entries") . "</td></tr>\n";
		}
		
		echo "</tbody>";
		echo "</table>\n";
		echo "</div>";

		?>
			<script>
		
		// convert table to javasript DataTable
		$(document).ready(function() {
			var identifier = "#contact_table";
    		$(identifier).DataTable({
				 "paging": false, 
				 "info": false,  
				 "responsive": true,
				 "oLanguage": {
					 		 "sEmptyTable":  "<?php echo Lang::txt("KontakteView_showContactTable.sEmptyTable"); ?>",
							 "sInfoEmpty":  "<?php echo Lang::txt("KontakteView_showContactTable.sInfoEmpty"); ?>",
							 "sZeroRecords":  "<?php echo Lang::txt("KontakteView_showContactTable.sZeroRecords"); ?>",
        					 "sSearch": "<?php echo Lang::txt("KontakteView_showContactTable.sSearch"); ?>"
		       }
			});
	});
		</script>
		<?php
	}
	
	function addEntity() {		
		$form = new Form(Lang::txt($this->getAddEntityName()), $this->modePrefix() . "add");		
		// just add all custom and regular fields
		$form->autoAddElementsNew($this->getData()->getFields());		
		$form->removeElement("id");
		$form->removeElement("status");
		
		// instrument
		$form->setForeign("instrument", "instrument", "id", "name", -1);
		$form->addForeignOption("instrument", Lang::txt("KontakteView_addEntity.noinstrument"), 0);
		
		// address
		$form->removeElement("address");
		$this->addAddressFieldsToForm($form);
		
		// contact group selection
		$groups = $this->getData()->getGroups();
		$gs = new GroupSelector($groups, array(), "group");
		$form->addElement(Lang::txt("KontakteView_addEntity.group"), $gs);
		
		$form->write();
	}
	
	function add() {
		$this->groupSelectionCheck();
		
		// do as usual
		parent::add();
	}
	
	function addOptions() {
		$this->backToStart();
		
		$addMore = new Link($this->modePrefix() . "addEntity", Lang::txt("KontakteView_addOptions.addEntity"));
		$addMore->addIcon("plus");
		$addMore->write();
	}
	
	function viewTitle() {
		$contact = $this->getData()->getContact($_GET["id"]);
		return $contact["name"] . " " . $contact["surname"];
	}
	
	function view() {
		// fetch contact and user details
		$contact = $this->getData()->getContact($_GET["id"]);
		
		// the contact is a member of these groups
		$groups = $this->getData()->getContactGroups($_GET["id"]);
		$groups = str_replace(",", ", ", $groups);
		
		// custom field handling
		$customFields = $this->getData()->getCustomFields("c");
		
		// build output
		?>
		<div class="contactdetail_section">
			<div class="contactdetail_section_header"><?php echo Lang::txt("KontakteView_view.title_1"); ?></div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.id"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["id"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.status"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["status"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.name"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["name"] . " " . $contact["surname"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.nickname"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["nickname"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.company"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["company"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.birthday"); ?></label>
				<div class="contactdetail_entry_value"><?php echo Data::convertDateFromDb($contact["birthday"]); ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.adresse"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $this->formatAddress($contact, TRUE, "", TRUE); ?></div>
			</div>
		</div>
		
		<div class="contactdetail_section">
			<div class="contactdetail_section_header"><?php echo Lang::txt("KontakteView_view.title_2"); ?></div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.phone"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["phone"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.business"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["business"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.mobile"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["mobile"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.fax"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["fax"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.email"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["email"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.web"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["web"]; ?></div>
			</div>
		</div>
		
		<div class="contactdetail_section">
			<div class="contactdetail_section_header"><?php echo Lang::txt("KontakteView_view.title_3"); ?></div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.instrumentname"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["instrumentname"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.is_conductor"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $contact["is_conductor"] == 0 ? Lang::txt("KontakteView_view.no") : Lang::txt("KontakteView_view.yes"); ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo Lang::txt("KontakteView_view.groups"); ?></label>
				<div class="contactdetail_entry_value"><?php echo $groups; ?></div>
			</div>
			<?php
			for($i = 1; $i < count($customFields); $i++) {
				$field = $customFields[$i];
			?>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo $field['txtdefsingle'] ?></label>
				<div class="contactdetail_entry_value"><?php
				$val = $contact[$field["techname"]];
				if($field["fieldtype"] == "BOOLEAN") {
					echo $val == 1 ? Lang::txt("KontakteView_view.yes") : Lang::txt("KontakteView_view.no");
				}
				else {
					echo $val;
				}
				?></div>
			</div>
			<?php
			} 
			?>
		</div>
		<?php
	}
	
	function additionalViewButtons() {
		// only show when it doesn't already exist
		if(!$this->getData()->hasContactUserAccount($_GET["id"])) {
			// show button
			$btn = new Link($this->modePrefix() . "createUserAccount&id=" . $_GET["id"], Lang::txt("KontakteView_additionalViewButtons.user"));
			$btn->addIcon("person");
			$btn->write();
		}
		
		// GDPR report
		$gdpr = new Link($this->modePrefix() . "gdprReport&id=" . $_GET["id"], Lang::txt("KontakteView_additionalViewButtons.question"));
		$gdpr->addIcon("person-rolodex");
		$gdpr->write();
	}
	
	function editEntityForm($write=true) {
		$contact = $this->getData()->getContact($_GET["id"]);
		
		$form = new Form(Lang::txt("KontakteView_editEntityForm.Form"), $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"], array("company"));
		$form->removeElement("id");
		
		// instrument
		$form->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);
		$form->addForeignOption("instrument", Lang::txt("KontakteView_addEntity.noinstrument"), 0);
		$form->renameElement("instrument", Lang::txt("KontakteData_construct.instrument"));
		
		// address
		$form->removeElement("address");
		$this->addAddressFieldsToForm($form, $this->getData()->getAddress($contact["address"]));
		
		$form->removeElement("status");
		
		// custom fields
		$fields = $this->getData()->getCustomFields('c');
		for($i = 1; $i < count($fields); $i++) {
			$field = $fields[$i];
			$techname = $field["techname"];
			$form->addElement($field['txtdefsingle'], new Field($techname, $contact[$techname], 
					$this->getData()->fieldTypeFromCustom($field['fieldtype'])));
		}
		
		// group selection
		$groups = $this->getData()->getGroups();
		$userGroups = $this->getData()->getContactGroupsArray($_GET["id"]);
		$gs = new GroupSelector($groups, $userGroups, "group");
		$form->addElement(Lang::txt("KontakteView_editEntityForm.group"), $gs);
		
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
			new BNoteError(Lang::txt("KontakteView_groupSelectionCheck.error"));
		}
	}
	
	function selectPrintGroups() {
		Writing::h2(Lang::txt("KontakteView_selectPrintGroups.title"));
		Writing::p(Lang::txt("KontakteView_selectPrintGroups.message"));
		
		$form = new Form(Lang::txt("KontakteView_selectPrintGroups.form"), $this->modePrefix() . "printMembers");
		
		// custom field selection
		$fields = $this->getData()->getCustomFields('c');
		$fieldSelector = new GroupSelector($fields, array(), "custom");
		$fieldSelector->setNameColumn("txtdefsingle");
		$form->addElement(Lang::txt("KontakteView_selectPrintGroups.txtdefsingle"), $fieldSelector);
				
		// group filter
		$groups = $this->getData()->getGroups();
		$gs = new GroupSelector($groups, array(), "group");
		$form->addElement(Lang::txt("KontakteView_selectPrintGroups.group"), $gs);
		
		$form->changeSubmitButton(Lang::txt("KontakteView_selectPrintGroups.submit"));
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
			new Message(Lang::txt("KontakteView_printMembers.message_1"), Lang::txt("KontakteView_printMembers.message_2"));
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
		$header = array (
				Lang::txt("KontakteView_formatMemberPrintTable.Name"),
				Lang::txt("KontakteView_formatMemberPrintTable.Nickname"),
				Lang::txt("KontakteView_formatMemberPrintTable.Instrument"),
				Lang::txt("KontakteView_formatMemberPrintTable.Phone"),
				Lang::txt("KontakteView_formatMemberPrintTable.Mobil"),
				Lang::txt("KontakteView_formatMemberPrintTable.Business"),
				Lang::txt("KontakteView_formatMemberPrintTable.Email"),
				Lang::txt("KontakteView_formatMemberPrintTable.Adresse") 
		);
		// add selected custom fields
		$fields = $this->getData()->getCustomFields('c');
		$fieldInfo = $this->getData()->compileCustomFieldInfo($fields);
		$customFields = GroupSelector::getPostSelection($fields, "custom");		
		$selectedCustomFields = array();
		foreach($fieldInfo as $techname => $info) {
			if(in_array($info[2], $customFields)) {
				array_push($header, $info[0]);
				$selectedCustomFields[$techname] = $info;
			}
		}
		array_push($formatted, $header);
		
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
			foreach($selectedCustomFields as $techname => $info) {
				if(isset($row[$techname])) {
					$fRow[$info[0]] = $row[$techname];
				}
				else {
					$fRow[$info[0]] = '-';
				}
			}
			array_push($formatted, $fRow);
		}
		
		return $formatted;
	}
	
	function printMembersOptions() {
		$this->backToStart();
		
		$prt = new Link("javascript:window.print();", Lang::txt("KontakteView_printMembersOptions.print"));
		$prt->addIcon("printer");
		$prt->write();
	}
	
	function userCreatedAndMailed($username, $email) {
		$m = Lang::txt("KontakteView_userCreatedAndMailed.Message_1") . " $email " . Lang::txt("KontakteView_userCreatedAndMailed.Message_2");
		new Message(Lang::txt("KontakteView_userCreatedAndMailed.Message_3") . " $username " . Lang::txt("KontakteView_userCreatedAndMailed.Message_4"), $m);
	}
	
	function userCredentials($username, $password) {
		$m = Lang::txt("KontakteView_userCredentials.Message_1");
		$m .= Lang::txt("KontakteView_userCredentials.Message_2");
		$m .= Lang::txt("KontakteView_userCredentials.Message_3");
		$m .= Lang::txt("KontakteView_userCredentials.Message_4");
		$m .= Lang::txt("KontakteView_userCredentials.Message_5") . "$username</strong><br />";
		$m .= Lang::txt("KontakteView_userCredentials.Message_6") . "<strong>$password</strong>";
		new Message(Lang::txt("KontakteView_userCredentials.Message_7") . "$username" . Lang::txt("KontakteView_userCredentials.Message_8"), $m);
	}
	
	function integration() {
		Writing::h2(Lang::txt("KontakteView_integration.title"));
		Writing::p(Lang::txt("KontakteView_integration.text"));
		?>
		<form method="POST" action="<?php echo $this->modePrefix(); ?>integration_process">
		<div class="start_box_table">
			<div class="start_box_row">
				<div class="start_box">
					<div class="start_box_heading"><?php echo Lang::txt("KontakteView_integration.member"); ?></div>
					<div class="start_box_content">
						<?php
						$grpFilter = isset($_GET["group"]) ? $_GET["group"] : null;
						$members = $this->getData()->getMembers($grpFilter);
						$group = new GroupSelector($members, array(), "member");
						$group->setNameColumns(array("name", "surname"));
						echo $group->write();
						?>
					</div>
				</div>
				<div class="start_box">
					<div class="start_box_heading"><?php echo Lang::txt("KontakteView_integration.rehearsal"); ?></div>
					<div class="start_box_content">
						<?php
						$rehearsals = $this->getData()->adp()->getFutureRehearsals();
						$group = new GroupSelector($rehearsals, array(), "rehearsal");
						$group->setNameColumn("begin");
						$group->setCaptionType(FieldType::DATE);
						echo $group->write();
						?>
					</div>
					<div class="start_box_heading"><?php echo Lang::txt("KontakteView_integration.rehearsalphase"); ?></div>
					<div class="start_box_content">
						<?php 
						$phases = $this->getData()->getPhases();
						$group = new GroupSelector($phases, array(), "rehearsalphase");
						echo $group->write();
						?>
					</div>
				</div>
				<div class="start_box">
					<div class="start_box_heading"><?php echo Lang::txt("KontakteView_integration.concert"); ?></div>
					<div class="start_box_content">
						<?php
						$concerts = $this->getData()->adp()->getFutureConcerts();
						$group = new GroupSelector($concerts, array(), "concert");
						$group->setNameColumn("begin");
						$group->setCaptionType(FieldType::DATE);
						echo $group->write();
						?>
					</div>
					<div class="start_box_heading"><?php echo Lang::txt("KontakteView_integration.vote"); ?></div>
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
		
		<input type="hidden" name="group" value="<?php echo isset($_GET["group"]) ? $_GET["group"] : ""; ?>" />
		<input type="submit" value=<?php echo Lang::txt("KontakteView_integration.save"); ?> />
		</form>
		<?php
	}
	
	function contactImport() {
		$form = new Form(Lang::txt("KontakteView_contactImport.form"), $this->modePrefix() . "contactImportProcess");
		$form->setMultipart();
		$groups = $this->getData()->adp()->getGroups();
		$form->addElement(Lang::txt("KontakteView_contactImport.group"), new GroupSelector($groups, array(), "group"));
		$form->addElement(Lang::txt("KontakteView_contactImport.vcdfile"), new Field("vcdfile", "", FieldType::FILE));
		$form->write();
	}
	
	function importVCardSuccess($message) {
		new Message(Lang::txt("KontakteView_importVCardSuccess.message"), $message);
	}
	
	function gdprReport() {
		Writing::h1(Lang::txt("KontakteView_gdprReport.title_1"));
		
		// fetch contact and user details
		$contact = $this->getData()->getContact($_GET["id"]);
		$cid = $contact["id"];
		$user = $this->getData()->adp()->getUserForContact($cid);
		$uid = $user["id"];
		
		// the contact is a member of these groups
		$groups = $this->getData()->getContactGroups($cid);
		
		// report creation line
		Writing::p(Lang::txt("KontakteView_gdprReport.message_1") . date("d.m.Y H:i:s") . Lang::txt("KontakteView_gdprReport.message_2") . $contact["surname"] . ", " . $contact["name"]);
		
		// personal information
		Writing::h2(Lang::txt("KontakteView_gdprReport.title_2"));
		$dv = new Dataview();
		$dv->autoAddElements($contact);
		$dv->autoRename($this->getData()->getFields());
		$dv->removeElement("Adresse");
		$dv->removeElement("instrument");
		$dv->renameElement("instrumentname", Lang::txt("KontakteView_gdprReport.instrumentname"));
		$dv->renameElement("street", Lang::txt("KontakteView_gdprReport.street"));
		$dv->renameElement("city", Lang::txt("KontakteView_gdprReport.city"));
		$dv->renameElement("zip", Lang::txt("KontakteView_gdprReport.zip"));
		$dv->write();
		
		if($uid != NULL && $uid > 0) {
			// Votes: participation
			Writing::h2(Lang::txt("KontakteView_gdprReport.title_3"));
			Writing::p(Lang::txt("KontakteView_gdprReport.message_3"));
			
			$votes = $this->getData()->adp()->getUsersVotesAll($uid);
			$voteList = new Plainlist($votes);
			$voteList->write();
			Writing::p(Lang::txt("KontakteView_gdprReport.message_4"));
			
			// Tasks
			Writing::h2(Lang::txt("KontakteView_gdprReport.title_4"));
			Writing::p(Lang::txt("KontakteView_gdprReport.message_5"));
			$tasks = $this->getData()->adp()->getUserTasks($uid);
			$taskList = new Plainlist($tasks);
			$taskList->setNameField("title");
			$taskList->write();
			Writing::p(Lang::txt("KontakteView_gdprReport.message_6"));
		}
		
		// Concerts: participation
		Writing::h2(Lang::txt("KontakteView_gdprReport.title_5"));
		/*
		 * concert_user: participation
		 * concert_contact: invitation
		 */
		Writing::p(Lang::txt("KontakteView_gdprReport.message_7"));
		$concertInvitations = $this->getData()->getConcertInvitations($cid);
		$concertInvitationList = new Plainlist($concertInvitations);
		$concertInvitationList->setNameField("title");
		$concertInvitationList->write();
		
		if($uid != NULL && $uid > 0) {
			Writing::p(Lang::txt("KontakteView_gdprReport.message_8"));
			$concertParticipation = $this->getData()->getConcertParticipation($uid);
			$concertParticipationList = new Plainlist($concertParticipation);
			$concertParticipationList->setNameField("title");
			$concertParticipationList->write();
		}
		
		Writing::p(Lang::txt("KontakteView_gdprReport.message_9"));
		
		// Rehearsals: participation
		Writing::h2(Lang::txt("KontakteView_gdprReport.title_6"));
		/*
		 * rehearsal_contact: invitation
		 * rehearsal_user: participation
		 * rehearsalphase_contact: invitation
		 */
		Writing::p(Lang::txt("KontakteView_gdprReport.message_10"));
		$rehearsalInvitations = $this->getData()->getRehearsalInvitations($cid);
		$rehearsalInvitationsList = new Plainlist($rehearsalInvitations);
		$rehearsalInvitationsList->setNameField("begin");
		$rehearsalInvitationsList->write();
		
		if($uid != NULL && $uid > 0) {
			Writing::p(Lang::txt("KontakteView_gdprReport.message_11"));
			$rehearsalParticipation = $this->getData()->getRehearsalParticipation($uid);
			$rehearsalParticipationList = new Plainlist($rehearsalParticipation);
			$rehearsalParticipationList->setNameField("begin");
			$rehearsalParticipationList->write();
		}
		
		Writing::p(Lang::txt("KontakteView_gdprReport.message_12"));
		$phaseInvitations = $this->getData()->getRehearsalphaseInvitations($cid);
		$phaseInvitationsList = new Plainlist($phaseInvitations);
		$phaseInvitationsList->write();
		
		// Tours: participation
		Writing::h2(Lang::txt("KontakteView_gdprReport.title_7"));
		Writing::p(Lang::txt("KontakteView_gdprReport.message_13"));
		$tourInvitations = $this->getData()->getTourInvitations($cid);
		$tourInvitationsList = new Plainlist($tourInvitations);
		$tourInvitationsList->write();
	}
	
	function gdprReportOptions() {
		$this->backToViewButton($_GET["id"]);
		
		$prt = new Link("javascript:window.print();", Lang::txt("KontakteView_gdprReportOptions.print"));
		$prt->addIcon("printer");
		$prt->write();
	}
	
	function gdprOk() {
		Writing::h1(Lang::txt("KontakteView_gdprOk.title"));
		Writing::p(Lang::txt("KontakteView_gdprOk.message"));
		
		$contacts = $this->getData()->getContactGdprStatus();
		$table = new Table($contacts);
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("gdpr_ok", Lang::txt("KontakteView_gdprOk.gdpr_ok"));
		$table->setColumnFormat("gdpr_ok", "BOOLEAN");
		$table->removeColumn("contact_id");
		$table->removeColumn("user_id");
		$table->write();
	}
	
	function gdprOkOptions() {
		$this->backToStart();
	
		$get = new Link($this->modePrefix() . "getGdprOk", Lang::txt("KontakteView_gdprOkOptions.getGdprOk"));
		$get->addIcon("envelope-exclamation");
		$get->write();
	
		$del = new Link($this->modePrefix() . "gdprNOK", Lang::txt("KontakteView_gdprOkOptions.gdprNOK"));
		$del->addIcon("person-x-fill");
		$del->write();
	}
	
	function getGdprOk() {
		/*
		 * Users should login and accept - otherwise not usable
		 * External contacts get an email with a link to accept showing a nice "thanks" page
		 */
		Writing::h1(Lang::txt("KontakteView_getGdprOk.title"));
		?>
		<p><?php echo Lang::txt("KontakteView_getGdprOk.message"); ?></p>
		
		<div style="margin: 10px 20px;">
		<?php
		require_once "data/gdpr_mail.php";
		
		echo $this->getData()->getSysdata()->getCompany();
		?>
		</div>
		<?php
	}
	
	function getGdprOkOptions() {
		$this->backToStart();
		
		$send = new Link($this->modePrefix() . "gdprSendMail", Lang::txt("KontakteView_getGdprOkOptions.gdprSendMail"));
		$send->addIcon("send");
		$send->write();
	}
	
	function gdprNOK() {
		Writing::p(Lang::txt("KontakteView_gdprNOK.message"));
	}
}

?>