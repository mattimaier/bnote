<?php

/**
 * Extension of CrudView. This class allows its subclasses to conduct all
 * CRUD operations on objects with references.
 * @author matti
 *
 */
abstract class CrudRefView extends CrudView {
	
	/**
	 * @var Array Column names of attribute replacements, e.g. "name".
	 */
	private $joinedAttributes;
	
	protected function addEntityForm() {
		$form = new Form($this->getEntityName() ." hinzuf&uuml;gen", $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement($this->idField);
		foreach($this->joinedAttributes as $field => $cols) {
			
			$caption = "";
			foreach($cols as $i => $col) {
				$caption .= $col . ", ";
			}
			$caption = substr($caption, 0, strlen($caption)-2);
			
			$form->setForeign($field, $this->getData()->getReferencedTable($field),
						"id", $caption, -1);
		}
		$form->write();
	}
	
	protected function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllJoined($this->joinedAttributes));
		$table->setEdit($this->idField);
		$table->renameAndAlign($this->getData()->getFields());
		$table->write();
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdJoined($_GET[$this->idParameter], $this->joinedAttributes);
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->write();
	}
	
	protected function editEntityForm() {
		$entityId = $_GET[$this->idParameter];
		$form = new Form(Lang::txt("edit", array($this->getEntityName())),
				$this->modePrefix() . "edit_process&" . $this->idParameter . "=" . $entityId);
		$form->autoAddElements($this->getData()->getFields(),
				$this->getData()->getTable(), $entityId);
		
		// replace the reference fields with object selection from join table
		$record = $this->getData()->findByIdNoRef($entityId);
		foreach($this->joinedAttributes as $joinField => $targetFields) {
			$joinTable = $this->getData()->getReferencedTable($joinField);
			$selectedId = $record[$joinField];
			$form->setForeign($joinField, $joinTable, $this->idField, $targetFields[0], $selectedId);
		}
		
		$form->removeElement($this->idField);
		$form->write();
	}
	
	/**
	 * Sets the columns which will be replaced for the foreign key.
	 * @param Array $jA Format of array: [foreign-key] => {col1, col2, ...}
	 */
	public function setJoinedAttributes($jA) {
		$this->joinedAttributes = $jA;
	}

	/**
	 * @return Array Format of array: [foreign-key] => {col1, col2, ...}
	 */
	public function getJoinedAttributes() {
		return $this->joinedAttributes;
	}
}

?>