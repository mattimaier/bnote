<?php
/**
 * View for location module.
 * @author matti
 *
 */
class LocationsView extends CrudRefView {
	
	/**
	 * Create the locations view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Location");
		$this->setJoinedAttributes(array(
			"address" => array("street", "zip", "city")
		));
	}
	
	function addEntityForm() {
		$form = new Form("Location hinzuf&uuml;gen", $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->removeElement("address");
		$form->renameElement("Notes", "Notizen");
		$form->addElement("Stra&szlig;e", new Field("street", "", FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", "", FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", "", FieldType::CHAR));
		$form->write();
	}
	
	protected function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllJoined($this->getJoinedAttributes()));
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("addressstreet", "Stra&szlig;e");
		$table->renameHeader("addresscity", "Stadt");
		$table->renameHeader("addresszip", "PLZ");
		$table->write();
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->renameElement("addressstreet", "Stra&szlig;e");
		$details->renameElement("addresszip", "PLZ");
		$details->renameElement("addresscity", "Stadt");
		$details->write();
	}
	
	protected function editEntityForm() {
		$loc = $this->getData()->findByIdNoRef($_GET["id"]);
		$address = $this->getData()->adp()->getEntityForId("address", $loc["address"]);
		$form = new Form($this->getEntityName() . " bearbeiten",
							$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
									$this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->removeElement("address");
		$form->addElement("Stra&szlig;e", new Field("street", $address["street"], FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", $address["zip"], FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", $address["city"], FieldType::CHAR));
		
		$form->write();
	}
}

?>