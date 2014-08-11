<?php

/**
 * View for news module.
 * @author matti
 *
 */
class NachrichtenView extends AbstractView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	public function start() {
		$content = $this->getData()->fetchContent();
		$form = new Form("Nachrichten", $this->modePrefix() . "save");
		$form->addElement("", new Field("news", $content, FieldType::TEXT));
		$form->changeSubmitButton("speichern");
		$form->write();
	}
	
	public function save() {
		$this->getData()->storeContent($_POST["news"]);
		new Message("Nachricht gespeichert", "Die eingegebene Nachricht wurde gespeichert.");
		$this->backToStart();
	}
}


?>