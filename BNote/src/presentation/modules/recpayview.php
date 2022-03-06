<?php


class RecpayView extends CrudRefView {

	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("RecpayView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("RecpayView_construct.addEntityName"));
		$this->setJoinedAttributes(array(
			"account" => array("name")
		));
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=recpay&sub=";
	}
	
	function getTitle() {
		if(isset($_GET["sub"])) {
			$mode = $_GET["sub"];
		}
		else {
			$mode = "start";
		}
		$titleFunc = $mode . "Title";
		if(method_exists($this, $titleFunc)) {
			return $this->$titleFunc();
		}
		return $this->getData()->getSysdata()->getModuleTitle($_GET["mod"]);
	}
	
	private function showAllTableGenerator($data) {
		$table = new Table($data);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("accountname", Lang::txt("RecpayView_showAllTableGenerator.accountname"));
		$table->changeMode("recpay&sub=view");
		return $table;
	}
	
	function showAllTable() {
		$table = $this->showAllTableGenerator($this->getData()->getRecurringPayments($this->getJoinedAttributes()));
		$table->write();
	}
	
	function startOptions() {
		$back = new Link("?mod=" . $this->getModId(), Lang::txt("RecpayView_startOptions.back"));
		$back->addIcon("arrow-left");
		$back->write();
		
		parent::startOptions();
		
		$book = new Link($this->modePrefix() . "book", Lang::txt("RecpayView_startOptions.book"));
		$book->addIcon("journal-plus");
		$book->write();
	}
	
	function objectReferenceForm($otype=NULL, $oid=NULL) {
		$contacts = $this->getData()->adp()->getContacts();
		$concerts = $this->getData()->adp()->getFutureConcerts();
		$phases = $this->getData()->getPhases();
		$locations = $this->getData()->adp()->getLocations();
		$tours = $this->getData()->adp()->getTours();
		$equipment = $this->getData()->adp()->getEquipment();
		
		$contacts = Data::dbSelectionToDict($contacts, "id", array("name", "surname"));
		$concerts = Data::dbSelectionToDict($concerts, "id", array("begin"));
		$phases = Data::dbSelectionToDict($phases, "id", array("name"));
		$locations = Data::dbSelectionToDict($locations, "id", array("name"));
		$tours = Data::dbSelectionToDict($tours, "id", array("name"));
		$equipment = Data::dbSelectionToDict($equipment, "id", array("name"));
		?>
		<script type="text/javascript">
		function changeReference(dd) {
			var options = "";
			var dict = {};
			var hide = false;

			var preset_otype = <?php if($otype != null) { echo '"' . $otype . '"'; } else { echo "null"; } ?>;
			var preset_oid = <?php if($oid != null) { echo $oid; } else { echo "null"; }?>;
			
			if(dd.value == "H") {
				dict = <?php echo json_encode($contacts); ?>;
			}
			else if(dd.value == "C") {
				dict = <?php echo json_encode($concerts); ?>;
			}
			else if(dd.value == "P") {
				dict = <?php echo json_encode($phases); ?>;
			}
			else if(dd.value == "L") {
				dict = <?php echo json_encode($locations); ?>;
			}
			else if(dd.value == "T") {
				dict = <?php echo json_encode($tours); ?>;
			}
			else if(dd.value == "E") {
				dict = <?php echo json_encode($equipment); ?>;
			}
			else {
				hide = true;
			}

			var numOptions = 0;
			for(var k in dict) {
				var sel = "";
				if(k == preset_oid) {
					sel = " selected";
				}
				options += "<option value=\"" + k + "\"" + sel + ">" + dict[k] + "</option>\n";
				numOptions++;
			}

			if($('#oref_id').length > 0) {
				if(hide) {
					$('#oref_id').hide();
				}
				else {
					$('#oref_id').show();
				}
				$('#oref_id').html(options);
			}
			else if(numOptions > 0) {
				var oref_parent = $('#oref').parent();
				oref_parent.append("<select id=\"oref_id\" class=\"form-select\" name=\"oid\">" + options + "</select>");
			}
		}

		// preset object reference value if set
		$(document).ready(function() {
			var val = $('#oref').val();
			if(val != null && val != "" && typeof changeReference == "function") {
				changeReference(document.getElementById("oref"));
			}
		});
		</script>
		<?php
		$objdd = new Dropdown("otype");
		$objdd->addOption(Lang::txt("RecpayView_changeReference.otype"), "0");
		$objdd->addOption(Lang::txt("RecpayView_changeReference.contact"), "H");
		$objdd->addOption(Lang::txt("RecpayView_changeReference.concert"), "C");
		$objdd->addOption(Lang::txt("RecpayView_changeReference.rehearsalphase"), "P");
		$objdd->addOption(Lang::txt("RecpayView_changeReference.location"), "L");
		$objdd->addOption(Lang::txt("RecpayView_changeReference.tour"), "T");
		$objdd->addOption(Lang::txt("RecpayView_changeReference.equipment"), "E");
		$objdd->setOnChange("changeReference(this)");
		$objdd->setId("oref");
		if($otype != null) {
			$objdd->setSelected($otype);
		}
		
		return $objdd;
	}
	
	function addEntityForm() {
		$form = new Form(Lang::txt("RecpayView_addEntityForm.form"), $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		foreach($this->getJoinedAttributes() as $field => $cols) {
				
			$caption = "";
			foreach($cols as $i => $col) {
				$caption .= $col . ", ";
			}
			$caption = substr($caption, 0, strlen($caption)-2);
				
			$form->setForeign($field, $this->getData()->getReferencedTable($field),
					"id", $caption, -1);
		}
		
		// adapt form
		$form->removeElement("btype");
		$dd = new Dropdown("btype");
		$btypes = FinanceData::getBookingTypes();
		foreach($btypes as $val => $capt) {
			$dd->addOption($capt, $val);
		}
		$dd->setSelected(1);
		$form->addElement(Lang::txt("RecpayView_addEntityForm.btype"), $dd);
		
		$form->removeElement("otype");
		$form->removeElement("oid");
		
		$objdd = $this->objectReferenceForm();
		$form->addElement(Lang::txt("RecpayView_addEntityForm.otype"), $objdd);
		
		$form->write();
	}
	
	function addEntityOptions() {
		$this->backToStart();
	}
	
	function addOptions() {
		$this->backToStart();
	}
	
	function editEntityForm($write=true) {
		$form = parent::editEntityForm(false);
		$record = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// adapt form
		$form->removeElement("btype");
		$dd = new Dropdown("btype");
		$btypes = FinanceData::getBookingTypes();
		foreach($btypes as $val => $capt) {
			$dd->addOption($capt, $val);
		}
		$dd->setSelected($record["btype"]);
		$form->addElement(Lang::txt("RecpayView_editEntityForm.btype"), $dd);
		
		$form->removeElement("otype");
		$form->removeElement("oid");
		
		$objdd = $this->objectReferenceForm($record["otype"], $record["oid"]);
		$form->addElement(Lang::txt("recpay_otype"), $objdd);
		
		$form->write();
	}
	
	function bookTitle() {
		return Lang::txt("RecpayView_book.title");
	}
	
	function book() {
		?>
		<form action="<?php echo $this->modePrefix() . "bookProcess"; ?>" method="POST">
		<?php
		echo "<label style=\"padding-right: 10px;\">" . Lang::txt("RecpayView_book.bdate") . "</label>";
		$f = new Field("bdate", "", FieldType::DATE);
		echo $f->write();
		$this->verticalSpace();
		
		$recpay = $this->getData()->getRecurringPayments($this->getJoinedAttributes());
		// add column with checkbox
		$displayData = array();
		foreach($recpay as $i => $row) {
			if($i == 0) {
				// header
				$row["book"] = Lang::txt("RecpayView_book.book"); 
			}
			else {
				// body
				$recpayId = $row["id"];
				$row["book"] = "<input type=\"checkbox\" name=\"recpay_$recpayId\" />";
			}
			array_push($displayData, $row);
		}
		$tab = $this->showAllTableGenerator($displayData);
		$tab->removeColumn("id");
		$tab->setOptionColumnNames(array("book"));
		$tab->disableForwardNav();
		$tab->write();
		?>
		<input type="submit" class="btn btn-primary" value="<?php echo Lang::txt("RecpayView_book.submit"); ?>" />
		</form>
		<?php
	}
	
	function bookOptions() {
		$this->backToStart();
	}
	
	function bookProcess() {
		$this->getData()->book();
		new Message(Lang::txt("RecpayView_bookProcess.message_1"), Lang::txt("RecpayView_bookProcess.message_2"));
	}
	
	function bookProcessOptions() {
		$this->backToStart();
	}
	
	function deleteOptions() {
		$this->backToStart();
	}
	
	function edit_processOptions() {
		$this->backToViewButton($_GET["id"]);
	}

	function viewDetailTable() {
		// get data
		$entity = $this->getData()->findByIdJoined($_GET[$this->idParameter], $this->getJoinedAttributes());
		
		// edit values to make them more readible
		$entity["btype"] = $entity["btype"] == 1 ? Lang::txt("RecpayView_viewDetailTable.expense") : Lang::txt("RecpayView_viewDetailTable.income");
		$otype = $entity["otype"];
		$entity["otype"] = $this->objectReferenceTypeToText($otype);
		if($entity["oid"] > 0) {
			$entity["oid"] = $this->resolveObjectReference($otype, $entity["oid"]);
		}
		else {
			$entity["oid"] = "-";
		}
		
		// show view
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->renameElement("accountname", Lang::txt("RecpayView_viewDetailTable.accountname"));
		$details->write();
	}
	
	protected function objectReferenceTypeToText($otype) {
		switch($otype) {
			case "H": return Lang::txt("RecpayView_objectReferenceTypeToText.H");
			case "C": return Lang::txt("RecpayView_objectReferenceTypeToText.C");
			case "P": return Lang::txt("RecpayView_objectReferenceTypeToText.P");
			case "L": return Lang::txt("RecpayView_objectReferenceTypeToText.L");
			case "T": return Lang::txt("RecpayView_objectReferenceTypeToText.T");
			case "E": return Lang::txt("RecpayView_objectReferenceTypeToText.E");
		}
		return $otype;
	}
	
	protected function resolveObjectReference($otype, $oid) {
		switch($otype) {
			case "H": return $this->getData()->getContactName($oid);
			case "C": return $this->getData()->getConcertName($oid);
			case "P": return $this->getData()->getPhaseName($oid);
			case "L": return $this->getData()->getLocationName($oid);
			case "T": return $this->getData()->getTourName($oid);
			case "E": return $this->getData()->getEquipmentName($oid);
		}
		return $oid;
	}
	
	function viewOptions() {
		// back button
		$this->backToStart();
		
		// show buttons to edit and delete
		$edit = new Link($this->modePrefix() . "edit&id=" . $_GET["id"],
				Lang::txt("RecpayView_viewOptions.edit"), array($this->getEntityName()));
		$edit->addIcon("pen");
		$edit->write();
		
		$del = new Link($this->modePrefix() . "delete_confirm&id=" . $_GET["id"],
				Lang::txt("RecpayView_viewOptions.delete_confirm"), array($this->getEntityName()));
		$del->addIcon("trash3");
		$del->write();
	}
	
	function backToStart() {
		$link = new Link("?mod=" . $this->getModId() . "&mode=recpay", Lang::txt("RecpayView_backToStart.back"));
		$link->addIcon("arrow-left");
		$link->write();
	}
}

?>