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
	
	/**
	 * @var Array Column names that are fixed valued based on the given URL parameter for each field.
	 */
	protected $internalReferenceFields = array();
	
	protected function addEntityForm() {
		$form = new Form($this->getEntityName() ." hinzuf&uuml;gen", $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement($this->idField);
		foreach($this->joinedAttributes as $field => $naming_cols) {
			$form->setForeign($field, $this->getData()->getReferencedTable($field), "id", $naming_cols, -1);
		}
		// remove internal reference fields
		foreach($this->internalReferenceFields as $field => $value) {
			$form->removeElement($field);
			$form->addHidden($field, $value);
		}
		$this->changeDefaultAddEntityForm($form);
		$form->write();
	}
	
	/**
	 * Change the default entity form rather than completely reimplementing it.
	 * @param Form $form Form to change.
	 */
	protected function changeDefaultAddEntityForm($form) {
		// leave blank by default
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
			$form->setForeign($joinField, $joinTable, $this->idField, $targetFields, $selectedId);
		}
		// remove internal reference fields
		foreach($this->internalReferenceFields as $field => $value) {
			$form->removeElement($field);
			$form->addHidden($field, $value);
		}
		// remoe id field
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