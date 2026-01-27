<?php

/**
 * Handles participation in rehearsals and concerts.
 * @author matti
 *
 */
class ParticipationWidget implements iWriteable {
	
	/**
	 * URL that's call when the form is submitted.
	 * @var String
	 */
	private $formAction;
	
	/**
	 * Participation (-1 = not set, 0 = no, 1 = yes, 2 = maybe)
	 * @var Integer
	 */
	private $participation;
	
	/**
	 * In case the widget is used multiple times on a page, you can use a suffix to distinugish the field IDs
	 * @var string
	 */
	private $fieldIdSuffix = "";
	
	function __construct($formAction, $participation, $reason) {
		$this->formAction = $formAction;
		$this->participation = ($participation != 0 && $participation == "") ? -1 : intval($participation);
		$this->reason = $reason;
	}
	
	public function setFieldIdSuffix($suffix) {
		$this->fieldIdSuffix = $suffix;
	}
	
	public function getName() {
		return NULL;
	}

	public function write() {
		$fieldId = "participation" . $this->fieldIdSuffix;
		global $system_data;
		?>
		<div class="participation_widget">
			<form action="<?php echo $this->formAction; ?>" method="POST" class="row row-cols-lg-auto g-3 align-items-center p-2">
				<div class="col-12">
					<?php echo Lang::txt("ParticipationWidget_write.participation"); ?>
				</div>
				<div class="col-12">
					<input type="radio" class="btn-check" name="participation" value="1" id="<?php echo $fieldId . "_yes"; ?>" autocomplete="off" <?php echo $this->participation == 1 ? "checked" : ""; ?>/>
					<label class="btn btn-outline-success participation_button" for="<?php echo $fieldId . "_yes"; ?>"><i class="bi-check"></i></label>
					<?php
					if($system_data->getDynamicConfigParameter("allow_participation_maybe") == 1) {
					?>
					<input type="radio" class="btn-check" name="participation" value="2" id="<?php echo $fieldId . "_maybe"; ?>" autocomplete="off" <?php echo $this->participation == 2 ? "checked" : ""; ?>/>
					<label class="btn btn-outline-warning participation_button" for="<?php echo $fieldId . "_maybe"; ?>"><i class="bi-question"></i></label>
					<?php 
					}
					?>
					<input type="radio" class="btn-check" name="participation" value="0" id="<?php echo $fieldId . "_no"; ?>" autocomplete="off" <?php echo $this->participation == 0 ? "checked" : ""; ?>/>
					<label class="btn btn-outline-danger participation_button" for="<?php echo $fieldId . "_no"; ?>"><i class="bi-x"></i></label>
				</div>
				<div class="col-12">
					<input type="text" name="reason" placeholder="<?php echo Lang::txt("ParticipationWidget_write.reason"); ?>" class="form-control" value="<?php echo $this->reason; ?>" />
				</div>
				<div class="col-12">
					<input type="submit" class="btn btn-primary" value="<?php echo Lang::txt("ParticipationWidget_write.save"); ?>" />
				</div>
			</form>
		</div>
		<?php
	}
	
}