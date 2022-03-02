<?php
/**
 * Creates a word export from the concert with the given ID in the POST payload.
 * @author matti
 *
 */
session_start();

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");
require_once($dir_prefix . "lang.php");
require_once($GLOBALS["DIR_WIDGETS"] . "message.php");
require_once($GLOBALS["DIR_WIDGETS"] . "link.php");

require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractlocationdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "konzertedata.php");

require_once($GLOBALS["DIR_WIDGETS"] . "writing.php");
require_once($dir_prefix . $GLOBALS["DIR_PRESENTATION"] . "abstractview.php");
require_once($dir_prefix . $GLOBALS["DIR_PRESENTATION"] . "crudview.php");
require_once($dir_prefix . $GLOBALS["DIR_PRESENTATION"] . "crudrefview.php");
require_once($dir_prefix . $GLOBALS["DIR_PRESENTATION"] . "crudreflocationview.php");
require_once($dir_prefix . $GLOBALS["DIR_PRESENTATION_MODULES"] . "konzerteview.php");

require_once($dir_prefix . $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php");
require_once($dir_prefix . $GLOBALS["DIR_LOGIC_MODULES"] . "konzertecontroller.php");

// Build Database Connection
$system_data = new Systemdata($dir_prefix);
global $system_data;

// check whether a user is registered and has contact (mod=3) permission
$deniedMsg = Lang::txt("gigcard_concert.deniedMsg");
if(!$system_data->isUserAuthenticated()) {
	new BNoteError($deniedMsg);
}
else if(!$system_data->userHasPermission(4)) {
	new BNoteError($deniedMsg);
}

// get access to data
$concertData = new KonzerteData($dir_prefix);
$concertCtrl = new KonzerteController();
$concertCtrl->setData($concertData);
$concertView = new KonzerteView($concertCtrl);

$c = $concertData->findByIdNoRef($_GET["id"]);
$custom = $concertData->getCustomData($_GET["id"]);
$loc = $concertData->adp()->getLocation($c["location"]);

// set the return type
header("Content-Type: application/msword");

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html" charset="utf-8">
</head>

<body>

<?php 
// concert details
Writing::h1($c["title"]);

Writing::p($c["notes"]);
?>

<h1><?php echo Lang::txt("gigcard_concert.event"); ?></h1>
<table>
	<tbody>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.organizer"); ?></td>
			<td><?php 
			if($c["organizer"]) {
				echo $c["organizer"];
			}
			?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.address"); ?></td>
			<td><?php 
			echo $loc["name"] . "<br/>";
			echo $concertView->exportFormatAddress($concertData->getAddress($loc["address"]));
			?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.contact"); ?></td>
			<td><?php 
			if($c["contact"]) {
				$cnt = $concertData->getContact($c["contact"]);
				$cv = $concertView->exportFormatContact($cnt, 'NAME_COMM_LB');
			}
			else {
				$cv = "-";
			}
			echo $cv;
			?></td>
		</tr>
	</tbody>
</table>

<h1><?php echo Lang::txt("gigcard_concert.times"); ?></h1>
<table>
	<tbody>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.period"); ?></td>
			<td><?php 
			echo Data::convertDateFromDb($c["begin"]) . " - ";
			echo Data::convertDateFromDb($c["end"]);
			?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.meetingtime"); ?></td>
			<td><?php echo Data::convertDateFromDb($c["meetingtime"]); ?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.approve_until"); ?></td>
			<td><?php echo Data::convertDateFromDb($c["approve_until"]); ?></td>
		</tr>
	</tbody>
</table>

<h1><?php echo Lang::txt("gigcard_concert.organisation"); ?></h1>
<table>
	<tbody>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.groupNames"); ?></td>
			<td><?php 
			$groups = $concertData->getConcertGroups($c["id"]);
			$groupNames = Database::flattenSelection($groups, "name");
			echo join(", ", $groupNames);
			?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.program"); ?></td>
			<td><?php 
			if($c["program"]) {
				$prg = $concertData->getProgram($c["program"]);
				echo $prg["name"];
			}
			?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.outfit"); ?></td>
			<td><?php 
			if($c["outfit"]) {
				$outfit = $concertData->getOutfit($c["outfit"]);
				echo $outfit["name"];
			}
			?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.equipment"); ?></td>
			<td>
			<?php 
			$equipment = $concertData->getConcertEquipment($c["id"]);
			if(count($equipment) == 0) {
				echo '-';		
			}
			else {
				echo '<ul>';
				for($e = 1; $e < count($equipment); $e++) {
					echo '<li>' . $equipment[$e]["name"] . '</li>';
				}
				echo '</ul>';
			}
			?>
			</td>
		</tr>
	</tbody>
</table>

<h1><?php echo Lang::txt("gigcard_concert.details"); ?></h1>
<table>
	<tbody>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.accommodation"); ?></td>
			<td><?php 
			if($c["accommodation"] > 0) {
				$acc = $concertData->adp()->getAccommodationLocation($c["accommodation"]);
				echo $acc["name"] . "<br>" . $this->formatAddress($acc);
			}
			?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.payment"); ?></td>
			<td><?php echo Data::convertFromDb($c["payment"]); ?></td>
		</tr>
		<tr>
			<td><?php echo Lang::txt("gigcard_concert.conditions"); ?></td>
			<td><?php echo $c["conditions"]; ?></td>
		</tr>
		
		<?php 
		// custom data
		$customFields = $concertData->getCustomFields(KonzerteData::$CUSTOM_DATA_OTYPE);
		for($i = 1; $i < count($customFields); $i++) {
			$field = $customFields[$i];
			?>
			<tr>
				<td><?php echo $field["txtdefsingle"]; ?></td>
				<td><?php echo $custom[$field["techname"]]; ?></td>
			</tr>
			<?php 
		}
		?>
	</tbody>
</table>

</body>
</html>