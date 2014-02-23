<?php
/**
 * View for repertoire module.
 * @author matti
 *
 */
class RepertoireView extends CrudRefView {
	
	/**
	 * Create the repertoire view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Song");
		$this->setJoinedAttributes(array(
			"genre" => array("name"),
			"composer" => array("name"),
			"status" => array("name")
		));
	}
	
	protected function addEntityForm() {
		?>
		<script type="text/javascript">
		$(function() {
			var composers = [
			    <?php
			    echo $this->getData()->listComposers();
			    ?>
			];

			$("#composer").autocomplete({
				source: composers
			});
		});	
		</script>
		<?php
		$form = new Form("Song hinzuf&uuml;gen", $this->modePrefix() . "add&manualValid=true");
		$form->autoAddElementsNew($this->getData()->getFields());
		
		$form->removeElement("id");
		$form->setForeign("genre", "genre", "id", "name", -1);
		$form->setForeign("status", "status", "id", "name", -1);
		
		$form->removeElement("composer");
		$composer = "<input type=\"text\" name=\"composer\" id=\"composer\" size=\"30\" />";
		$form->addElement("Komponist / Arrangeur", new TextWriteable($composer));
		
		$form->removeElement("length");
		$length = "<input type=\"text\" name=\"length\" size=\"6\" />&nbsp;min";
		$form->addElement("L&auml;nge", new TextWriteable($length));
		
		$form->write();
	}
	
	protected function showAllTable() {
		$data = $this->getData()->findAllJoinedWhere($this->getJoinedAttributes(), "length >= 0 ORDER BY title");
		$table = new Table($data);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("genrename", "Genre");
		$table->renameHeader("composername", "Komponist/Arrangeur");
		$table->renameHeader("statusname", "Status");
		$table->removeColumn("id");
		$table->write();
		
		$tt = $this->getData()->totalRepertoireLength();
		Writing::p("Das Reperatoire hat eine Gesamtl&auml;nge von <strong>" . $tt . "</strong> Stunden.");
	}
	
	protected function viewDetailTable() {
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes()));
		$dv->autoRename($this->getData()->getFields());
		$dv->renameElement("genrename", "Genre");
		$dv->renameElement("composername", "Komponist / Arrangeur");
		$dv->renameElement("statusname", "Status");
		$dv->write();
	}
	
	protected function editEntityForm() {
		$song = $this->getData()->findByIdNoRef($_GET["id"]);
		
		$form = new Form("Song bearbeiten", $this->modePrefix() . "edit_process&manualValid=true&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->renameElement("length", "L&auml;nge in Stunden");
		$form->setForeign("genre", "genre", "id", "name", $song["genre"]);
		$form->setForeign("status", "status", "id", "name", $song["status"]);
		$form->removeElement("composer");
		$form->addElement("Komponist/Arrangeur", new Field("composer",
					$this->getData()->getComposerName($song["composer"]), FieldType::CHAR));
		$form->write();
	}
}

?>