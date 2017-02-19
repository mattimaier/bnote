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
		$form = new Form("Location hinzufügen", $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->removeElement("address");
		$form->renameElement("Notes", "Notizen");
		$form->addElement("Stra&szlig;e", new Field("street", "", FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", "", FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", "", FieldType::CHAR));
		$form->setForeign("location_type", "location_type", "id", array("name"), 1);
		
		$form->write();
	}
	
	protected function editEntityForm($write=true) {
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
		$form->setForeign("location_type", "location_type", "id", array("name"), $loc['location_type']);
		
		$form->write();
	}
	
	protected function showAllTable() {
		// get all types and display the locations per type
		$locTypes = $this->getData()->getLocationTypes();
		for($i = 1; $i < count($locTypes); $i++) {
			$locType = $locTypes[$i]['id'];
			$locTypeName = $locTypes[$i]['name'];
			Writing::h3($locTypeName);
			
			// show table rows
			$table = new Table($this->getData()->findAllJoinedWhere($this->getJoinedAttributes(), "location_type = $locType"));
			$table->setEdit("id");
			$table->renameAndAlign($this->getData()->getFields());
			$table->renameHeader("addressstreet", "Stra&szlig;e");
			$table->renameHeader("addresscity", "Stadt");
			$table->renameHeader("addresszip", "PLZ");
			$table->removeColumn("location_type");
			$table->write();
		}
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->resolveForeignElement("location_type", "location_type");		
		$details->autoRename($this->getData()->getFields());
		$details->renameElement("addressstreet", "Stra&szlig;e");
		$details->renameElement("addresszip", "PLZ");
		$details->renameElement("addresscity", "Stadt");
		$details->write();
		
		// show map
		Writing::h3("Karte");
		$addy = $entity['addressstreet'] . ", " . $entity["addresszip"] . " " . $entity["addresscity"];
		$google_api_key = $this->getData()->getSysdata()->getDynamicConfigParameter("google_api_key");
		
		if($google_api_key != "") {
			?>
			<div id="location_map" style="width: 1000px; height: 500px;"></div>
			<script>
			var map;
			function initMap() {
				var geocoder = new google.maps.Geocoder();
				geocoder.geocode( { 'address': '<?php echo $addy; ?>'}, function(results, status) {
				      if (status == google.maps.GeocoderStatus.OK) {
				        map.setCenter(results[0].geometry.location);
				        var marker = new google.maps.Marker({
				            map: map,
				            position: results[0].geometry.location
				        });
				      }
				    });
				
			  map = new google.maps.Map(document.getElementById('location_map'), {
			    center: {lat: 48.135125, lng: 11.574789},
			    zoom: 15
			  });
			}
			</script>
			<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_api_key; ?>&callback=initMap">
		    </script>
			<?php
		}
	}

}

?>