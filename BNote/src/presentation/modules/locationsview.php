<?php

/**
 * View for location module.
 * @author matti
 *
 */
class LocationsView extends CrudRefLocationView {
	
	/**
	 * Create the locations view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Location");
		$this->setAddEntityName(Lang::txt("LocationsView_construct.addEntityName"));
		$this->setJoinedAttributes(array(
			"address" => array("street", "zip", "city", "state", "country")
		));
	}
	
	function addEntityForm() {
		$form = new Form(Lang::txt($this->getaddEntityName()), $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFieldsWithCustomFields(LocationsData::$CUSTOM_DATA_OTYPE));
		$form->removeElement("id");
		$form->removeElement("address");
		$form->renameElement("Notes", "Notizen");
		$form->setForeign("location_type", "location_type", "id", array("name"), 1);
		$this->addAddressFieldsToForm($form);
		
		$form->write();
	}
	
	protected function editEntityForm($write=true) {
		$loc = $this->getData()->findByIdNoRef($_GET["id"]);
		$address = $this->getData()->getAddress($loc["address"]);
		
		$form = new Form($this->getEntityName() . " bearbeiten",
				$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		
		$form->autoAddElements($this->getData()->getFieldsWithCustomFields(LocationsData::$CUSTOM_DATA_OTYPE),
				$this->getData()->getTable(), $_GET["id"]);
		
		$form->removeElement("id");
		$form->removeElement("address");
		
		$form->setForeign("location_type", "location_type", "id", array("name"), $loc['location_type']);
		$this->addAddressFieldsToForm($form, $address);
		$this->appendCustomFieldsToForm($form, LocationsData::$CUSTOM_DATA_OTYPE, $loc, false);
		
		$form->write();
	}
	
	protected function showAllTable() {
		// get all types and display the locations per type
		$locTypes = $this->getData()->getLocationTypes();
		
		$allActive = "";
		if(!isset($_GET["locationType"]) || (isset($_GET["locationType"]) && $_GET["locationType"] == "all")) {
			$allActive = "active";
		}
		?>
		<div class="row">
			<div class="nav nav-tabs">
				<div class="nav-item">
					<a class="nav-link <?php echo $allActive; ?>" href="<?php echo $this->modePrefix() . "start&locationType=all"; ?>"><?php echo Lang::txt("LocationsView_showAllTable.title"); ?></a>
				</div>
			<?php
			for($i = 1; $i < count($locTypes); $i++) {
				$locType = $locTypes[$i]['id'];
				$locTypeName = $locTypes[$i]['name'];
				$active = "";
				if(isset($_GET["locationType"]) && $_GET["locationType"] == $locType) {
					$active = "active";
				}
				?>
				<div class="nav-item">
					<a class="nav-link <?php echo $active; ?>" href="<?php echo $this->modePrefix() . "start&locationType=$locType"; ?>"><?php echo $locTypeName; ?></a>
				</div>
				<?php
			}
			?>
		</div>
		
		<div class="row">
		<?php 
		if($allActive) {
			$d = $this->getData()->findAllJoined($this->getJoinedAttributes());
		}
		else {
			$locType = $_GET["locationType"];
			$this->getData()->getSysdata()->regex->isPositiveAmount($locType);
			$d = $this->getData()->findAllJoinedWhere($this->getJoinedAttributes(), "location_type = $locType");
		}
		$table = new Table($d);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("id");
		$this->renameTableAddressColumns($table, "address");
		$table->removeColumn("location_type");
		$table->write();
		?>
		</div>
		
		</div>
		<?php
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->removeElement("id");
		$details->resolveForeignElement("location_type", "location_type");		
		$details->autoRename($this->getData()->getFieldsWithCustomFields(LocationsData::$CUSTOM_DATA_OTYPE));
		$this->replaceDataViewFieldWithAddress($details, "address");
		$details->write();
		
		// show map
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