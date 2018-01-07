<?php

/**
 * View for vote module.
 * @author matti
 *
 */
class AbstimmungView extends CrudView {
	
	private $entityName_option;
	private $entityName_options;
	
	/**
	 * Create the locations view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("vote_entity"));
		$this->entityName_option = Lang::txt("vote_option");
		$this->entityName_options = Lang::txt("vote_options");
	}
	
	function writeTitle() {
		Writing::h2(Lang::txt("vote_yourVotes"));
	}

	function startOptions() {
		parent::startOptions();
		$this->buttonSpace();
		
		$arc = new Link($this->modePrefix() . "archive", Lang::txt("vote_archive"));
		$arc->addIcon("archive");
		$arc->write();
	}
	
	function showAllTable() {
		$votes = $this->getData()->getVotesForUser();
		$table = new Table($votes);
		$table->setEdit("id");
		$table->changeMode("view&resultview=true");
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("id");
		$table->setColumnFormat("end", "DATE");		
		$table->write();
	}
	
	function addEntityForm() {
		$form = new Form(Lang::txt("add_entity", array($this->getEntityName())), $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->removeElement("author");
		$form->removeElement("is_finished");
		
		$groups = $this->getData()->adp()->getGroups(true, true);
		$gs = new GroupSelector($groups, array(), "group");
		$gs->setNameColumn("name_member");
		$form->addElement(Lang::txt("vote_voters"), $gs);
		
		$form->write();
	}
	
	function view() {
		$this->checkID();
		if(isset($_GET["resultview"]) && $_GET["resultview"] == "true"
				|| (!$this->getData()->isUserAuthorOfVote($_SESSION["user"], $_GET["id"])
				&& !$this->getData()->getSysdata()->isUserSuperUser())) {
			$this->result();
		}
		else {		
			// heading
			Writing::h2(Lang::txt("vote_details_header"));
			
			// show the details
			$this->viewDetailTable();
		}
	}
	
	function viewOptions() {		
		if(isset($_GET["resultview"]) && $_GET["resultview"] == "true"
				|| (!$this->getData()->isUserAuthorOfVote($_SESSION["user"], $_GET["id"])
				&& !$this->getData()->getSysdata()->isUserSuperUser())) {
			// back
			if(isset($_GET["from"]) && $_GET["from"] == "history") {
				$lnk = new Link($this->modePrefix() . "archive", Lang::txt("back"));
				$lnk->addIcon("arrow_left");
				$lnk->write();
			}
			else {
				$this->backToStart();
			}
			$this->buttonSpace();
			
			// in case the user is the author or a superuser, he/she can edit the vote
			if($this->getData()->isUserAuthorOfVote($_SESSION["user"], $_GET["id"])
					|| $this->getData()->getSysdata()->isUserSuperUser()) {
				$editBtn = new Link($this->modePrefix() . "view&id=" . $_GET["id"], Lang::txt("vote_edit"));
				$editBtn->addIcon("edit");
				$editBtn->write();
				$this->buttonSpace();
				$hasButtons = true;
			}
			
			// in case vote isn't over yet, show button to view
			if($this->getData()->isVoteActive($_GET["id"])) {
				$voteBtn = new Link("?mod=1&mode=voteOptions&id=" . $_GET["id"], Lang::txt("vote_now"));
				$voteBtn->addIcon("checkmark");
				$voteBtn->write();
				$hasButtons = true;
			}
		}
		else {
			$this->backToStart();
			$this->buttonSpace();
			
			// show buttons to edit and close
			$edit = new Link($this->modePrefix() . "edit&id=" . $_GET["id"], Lang::txt("vote_edit"));
			$edit->addIcon("edit");
			$edit->write();
			$this->buttonSpace();
				
			$del = new Link($this->modePrefix() . "delete_confirm&id=" . $_GET["id"], Lang::txt("vote_finish"));
			$del->addIcon("stop");
			$del->write();
			$this->buttonSpace();
				
			// additional buttons
			$this->additionalViewButtons();
		}
	}
	
	function add() {
		// validate
		$this->getData()->validate($_POST, true);
		
		// process
		$vid = $this->getData()->create($_POST);
		
		// write success
		new Message(Lang::txt("saved_entity", array($this->getEntityName())),
				Lang::txt("vote_saved_message"));
		
		// show options link
		$lnk = new Link($this->modePrefix() . "options&id=$vid", Lang::txt("vote_add_options"));
		$lnk->addIcon("plus");
		$lnk->write();
		$this->buttonSpace();
	}
	
	function options() {
		$this->checkID();
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// add a new element if posted
		if(isset($_POST["name"]) || isset($_POST["odate"])) {
			$this->getData()->addOption($_GET["id"]);
		}
		else if(isset($_POST["odate_from"]) && isset($_POST["odate_to"])) {
			$this->getData()->addOptions($_GET["id"], $_POST["odate_from"], $_POST["odate_to"]);
		}
		
		// show options that are already present
		Writing::h2($vote["name"] . " - " . Lang::txt("vote_options"));
		$options = $this->getData()->getOptions($_GET["id"]);
		
		Writing::p(Lang::txt("vote_remove_option_tip"));
		
		echo "<ul>";
		for($i = 1; $i < count($options); $i++) {
			$href = $this->modePrefix() . "delOption&oid=" . $options[$i]["id"] . "&id=" . $_GET["id"];
			if($vote["is_date"] == 1) {
				$val = Data::convertDateFromDb($options[$i]["odate"]);
			}
			else {
				$val = $options[$i]["name"];
			}
			echo " <li><a href=\"$href\">$val</a></li>";
		}
		echo "</ul>";
		if(count($options) < 2) {
			Writing::p("<i>" . Lang::txt("vote_no_options_yet") . "</i>");
		}
		
		// show add options form
		$form = new Form(Lang::txt("add_entity", array($this->entityName_option)), $this->modePrefix() . "options&id=" . $_GET["id"]);
		if($vote["is_date"] == 1) {
			/* DATE VOTE -> show 2 Forms:
			 * a) add single datetimes
			 * b) add multiple datetimes (in between start and end)
			 */
			echo "<table>\n";
			echo " <tr>\n";
			echo "  <td>" . Lang::txt("vote_addSingleOption") . "</td>\n";
			echo "  <td>" . Lang::txt("vote_addMultipleOptions") . "</td>\n";
			echo " </tr>\n";
			echo " <tr>\n";
			echo "  <td>\n";
			
			// single form
			$form->setTitle("");
			$form->addElement(Lang::txt("date"), new Field("odate", "", FieldType::DATETIME));
			$form->write();
			
			echo "  </td>\n";
			echo "  <td>\n";
			
			// multiform
			$form->setTitle("");
			$form->removeElement(Lang::txt("date"));
			$form->addElement(Lang::txt("vote_firstDay"), new Field("odate_from", "", FieldType::DATETIME));
			$form->addElement(Lang::txt("vote_lastDay"), new Field("odate_to", "", FieldType::DATE));
			$form->write();
			
			echo "  </td>\n";
			echo " </tr>\n";
			echo "</table>\n";
			
			$form->addElement(Lang::txt("date"), new Field("odate", "", FieldType::DATETIME));
		}
		else {
			$form->addElement(Lang::txt("name"), new Field("name", "", FieldType::CHAR));
			$form->write();
		}
	}
	
	function optionsOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function delOption() {
		$this->checkID();
		$this->getData()->deleteOption($_GET["oid"]);
		$this->options();
	}
	
	function viewDetailTable() {
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		$dv = new Dataview();
		$dv->addElement(Lang::txt("title"), $vote["name"]);
		$dv->addElement(Lang::txt("vote_end"), Data::convertDateFromDb($vote["end"]));
		$checked = ($vote["is_date"] == 1) ? "checked" : "";
		$dv->addElement(Lang::txt("vote_fields_is_date"), "<input type=\"checkbox\" disabled $checked/>");
		$checked = ($vote["is_multi"] == 1) ? "checked" : "";
		$dv->addElement(Lang::txt("vote_fields_is_multi"), "<input type=\"checkbox\" disabled $checked/>");
		$dv->write();
	}
	
	function additionalViewButtons() {
		// options
		$opt = new Link($this->modePrefix() . "options&id=" . $_GET["id"], $this->entityName_options);
		$opt->addIcon("setlist");
		$opt->write();
		$this->buttonSpace();
		
		// users
		$grp = new Link($this->modePrefix() . "group&id=" . $_GET["id"], Lang::txt("vote_voters"));
		$grp->addIcon("user");
		$grp->write();
		$this->buttonSpace();
		
		// notifications
		$emLink = "?mod=" . $this->getData()->getSysdata()->getModuleId("Kommunikation");
		$emLink .= "&mode=voteMail&preselect=" . $_GET["id"];
		$em = new Link($emLink, Lang::txt("vote_notification"));
		$em->addIcon("email");
		$em->write();
		$this->buttonSpace();
		
		// result
		$res = new Link($this->modePrefix() . "result&id=" . $_GET["id"], Lang::txt("vote_result"));
		$res->addIcon("abstimmung");
		$res->write();
		$this->buttonSpace();
	}
	
	function editEntityForm() {
		$form = new Form(Lang::txt("edit_entity", $this->getEntityName()),
				$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
				$this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->removeElement("author");
		$form->removeElement("is_finished");
		$form->removeElement("is_date");
		$form->removeElement("is_multi");
		$form->write();
	}
	
	function group() {
		$this->checkID();
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// add a set of users when requested
		if(isset($_GET["func"]) && $_GET["func"] == "addAllMembers") {
			$this->getData()->addAllMembersAndAdminsToGroup($_GET["id"]);
		}
		
		// add a new element if posted
		if(isset($_POST["user"])) {
			$this->getData()->addToGroup($_GET["id"], $_POST["user"]);
		}
		
		// show options that are already present
		Writing::h2($vote["name"] . " - " . Lang::txt("vote_voters"));
		$group = $this->getData()->getGroup($_GET["id"]);
		
		Writing::p(Lang::txt("vote_clickToRemoveUser"));
		
		echo "<ul>";
		for($i = 1; $i < count($group); $i++) {
			$href = $this->modePrefix() . "delFromGroup&uid=" . $group[$i]["id"] . "&id=" . $_GET["id"];
			$val = $group[$i]["name"] . " " . $group[$i]["surname"];
			echo " <li><a href=\"$href\">$val</a></li>";
		}
		echo "</ul>";
		if(count($group) < 2) {
			Writing::p("<i>" . Lang::txt("vote_noVotersYet") . "</i>");
		}
			
		// show add users form
		$form = new Form(Lang::txt("vote_addVoter"), $this->modePrefix() . "group&id=" . $_GET["id"]);
		$users = $this->getData()->getUsers();
		$dd = new Dropdown("user");
		$amIinUsers = false;
		for($i = 1; $i < count($users); $i++) {
			$dd->addOption($users[$i]["name"] . " " . $users[$i]["surname"], $users[$i]["id"]);
			if($users[$i]["id"] == $_SESSION["user"]) {
				$amIinUsers = true;
			}
		}
		if(!$amIinUsers) {
			$contact = $this->getData()->getSysdata()->getUsersContact();
			$dd->addOption($contact["name"] . " " . $contact["surname"], $_SESSION["user"]);
		}
		$form->addElement(Lang::txt("vote_voter"), $dd);
		$form->write();
		$this->verticalSpace();
	}
	
	function groupOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function delFromGroup() {
		$this->checkID();
		$this->getData()->deleteFromGroup($_GET["id"], $_GET["uid"]);
		$this->group();
	}
	
	function result() {
		$this->checkID();
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h2($vote["name"] . " - Ergebnis");
		
		if($vote["is_multi"] == 1) {
			Writing::p(Lang::txt("vote_multipleAnswersPossible"));
		}
		else {
			Writing::p(Lang::txt("vote_singleOnlyPossible"));
		}
		
		// Javascript
		?>
		<script type="text/javascript" src="lib/jquery/plugins/jqplot.barRenderer.min.js"></script>
		<script type="text/javascript" src="lib/jquery/plugins/jqplot.categoryAxisRenderer.min.js"></script>
		<script type="text/javascript" src="lib/jquery/plugins/jqplot.pointLabels.min.js"></script>
		<script>
		$(document).ready(function() {
			// load result over BNA
			$.ajax({
				url: "src/export/bna-json.php?pin=<?php echo $this->getData()->getUserPin(); ?>&func=getVoteResult&id=<?php echo $_GET["id"]; ?>",
				dataType: "json",
				success: function(data) {
					graphData = convertVoteResult(data);
					
					$.jqplot('voteResult', graphData.data, {
						stackSeries: true,
						seriesDefaults: {
					            renderer:$.jqplot.BarRenderer,
					            rendererOptions: {
						            barDirection: 'horizontal'
					            }/*,
					            pointLabels: {
						            show: true,
						            location: 'e',
						            edgeTolerance: 10
						        }*/
					    },
						series: [
						         { label: "yes",   color: "#4EE330" },
						         { label: "maybe", color: "#EBEB50" },
						         { label: "no",    color: "#EB5A50" }
						],
						axes: {
							yaxis: {
								renderer: $.jqplot.CategoryAxisRenderer,
				                ticks: graphData.options
							}
						}
					});
				},
				error: function(x,y,z) {
					$("#voteResult").html("Fehler. " + z);
				}
			});

			function convertVoteResult(data) {
				//TODO convert
				var options = new Array();
				var yes = new Array();
				var no = new Array();
				var may = new Array(); 
				for(var o = 0; o < data.options.length; o++) {
					var option = data.options[o];
					options.push(option.name);
					yes.push(parseInt(option.choice[1]));
					no.push(parseInt(option.choice[0]));
					may.push(parseInt(option.choice[2]));
				}

				// complex sorting by yes, (yes+may)
				// idea: sort object of structure { optionIndex: weightValue } by weightValue
				// weightValue = Yes [+ Maybe]
				var weightObject = {};
				for(var i = 0; i < data.options.length; i++) {
					weightObject[i] = yes[i]+ may[i];
				}
				//TODO fix this
				var sortArray = [];
				for(var index in weightObject) {
					sortArray.push([index, weightObject[index]]);
				}
				sortArray.sort(function(a,b) {
					return a[1] - b[1];
				});

				var sortOptions = new Array();
				var sortYes = new Array();
				var sortNo = new Array();
				var sortMay = new Array();

				for(var i = 0; i < sortArray.length; i++) {
					var index = sortArray[i][0];
					sortOptions[i] = options[index];
					sortYes[i] = yes[index];
					sortNo[i] = no[index];
					sortMay[i] = may[index];
				}
				
				// target structure
				/* [ [yes_optionA, yes_optionB]
				 *   [ no_optionA,  no_optionB]
				 *   [may_optoinA, may_optionB] ]
				 */
				return {
					options: sortOptions,
					data: [ sortYes, sortMay, sortNo ]
				};
			}
		});
		</script>
		<div id="voteResult" style="height: 600px;"></div>
		
		<?php
		// Test Results
		$result = $this->getData()->getResult($_GET["id"]);
		$table = new Table($result);
		$table->removeColumn("id");
		$table->renameHeader("votes", Lang::txt("vote_votes"));
		$table->renameHeader("voters", Lang::txt("vote_voters"));
		if($vote["is_multi"] == 1) {
			$table->setDataRowSpan(3);
		}
		$table->write();
	}
	
	function archive() {
		Writing::h2(Lang::txt("vote_archive"));
		
		$votes = $this->getData()->getVotesForUser(false);
		$table = new Table($votes);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("id");
		$table->setColumnFormat("end", "DATE");
		$table->changeMode("result&from=history");
		$table->write();
	}
}

?>