<?php

require_once 'lang_base.php';

/**
 * English translation of BNote.
 * @author mattimaier
 *
 */
class Translation extends BNoteTranslation {
	
	protected $texts = array(
			"welcome" => "Welcome",
			"autoActivation" => "The automated user activation is enabled. Please see the security notes for details.",
			"back" => "Back",
			"delete" => "Delete",
			"deleted" => "deleted",
			"reallyDeleteQ" => "Do you really want to delete this entry?",
			"noUserId" => "Please specify a user id.",
			"selectEntryText" => "Please select an entry to view or edit it.",
			"add" => "add",
			"saved" => "saved",
			"entrySaved" => "The entry has been saved.",
			"details" => "Details",
			"edit" => "edit",
			"changed" => "changed",
			"entryChanged" => "The entry has been changed.",
			"entryDeleted" => "the entry has been deleted.",
			"yes" => "yes",
			"no" => "no",
			"showAll" => "Show all",
			"begin" => "Begin",
			"end" => "End",
			"location" => "Location",
			"news" => "News",
			"nonIntegratedUsers" => 'Non integrated users have been detected. Please change to Contacts/Integration to integrate them into your ensemble.',
			"rehearsals" => "Rehearsals",
			"concerts" => "Concerts",
			"votes" => "Votes",
			"tasks" => "Tasks",
			"discussions" => "Discussions",
			"comment" => "Comment",
			"participants" => "Participant",
			"contact" => "Contact",
			"program" => "Program",
			"title" => "Title",
			"description" => "Description",
			"dueAt" => "Due at",
			"name" => "Name",
			"vote" => "Vote",
			"discussion" => "Discussion",
			
			// navigation
			"mod_Login" => "Login",
			"mod_Start" => "Start",
			"mod_User" => "Users",
			"mod_Kontakte" => "Contacts",
			"mod_Konzerte" => "Concerts",
			"mod_Proben" => "Rehearsals",
			"mod_Repertoire" => "Repertoire",
			"mod_Kommunikation" => "Communication",
			"mod_Locations" => "Locations",
			"mod_Kontaktdaten" => "My Data",
			"mod_Hilfe" => "Help",
			"mod_Website" => "Website",
			"mod_Share" => "Share",
			"mod_Mitspieler" => "Fellows",
			"mod_Abstimmung" => "Votes",
			"mod_Konfiguration" => "Configuration",
			"mod_Aufgaben" => "Tasks",
			"mod_Nachrichten" => "News",
			"mod_Probenphasen" => "Planning",
			
			// widgets
			"addFolder" => "Add Folder",
			"addFile" => "Add File",
			"favorites" => "Favorits",
			"myFiles" => "My Files",
			"commonShare" => "Share Folder",
			"userFolder" => "User Folder",
			"groupFolder" => "Group Share",
			"selectFolder" => "Please select a share folder.",
			"folderUp" => "Change one up",
			"folderAsZip" => "Download folder as zip-archive",
			"filename" => "Filename",
			"filesize" => "Filesize",
			"fileoptions" => "Options",
			"createFolder" => "Create Folder",
			"foldername" => "Foldername",
			"createFile" => "Create File",
			"file" => "File",
			"uploadFile" => "Upload file",
			"noFileAddPermission" => "You do not have permission to add files.",
			"errorWithFile" => "There was an error processing your file. Please try again.",
			"errorFileMaxSize" => "Die maximum filesize was exceeded.",
			"errorFileAbort" => "The file was uploaded partially. Please check your internet connection.",
			"errorNoFile" => "No file uploaded.",
			"errorSavingFile" => "Servererror when saving your file.",
			"errorUploadingFile" => "The file couldn't be uploaded.",
			"errorDeletingFile" => "You don't have permission to delete this file.",
			"errorFileNotFound" => "The file was not found.",
			"noFolderAddPermission" => "You do not have permission to add a folder.",
			"errorReservedFolderNames" => "The folder must not be called \"users\" or \"groups\".",
			"open" => "Open",
			"download" => "Download",
			"archiveCreated" => "The archive was created and can be downloaded now from the following link.",
			"downloadArchive" => "Download Archive",
			"noEntries" => "Keine Eintr&auml;ge vorhanden",

			// module: start
			"start_calendarExport" => "Export Calendar",
			"start_calendarSubscribe" => "Subscribe to Calendar",
			"start_pleaseGiveReason" => "Please give a reason.",
			"start_noRehearsalsScheduled" => "No rehearsals scheduled.",
			"start_showNumRehearsals" => "Only the first %p rehearsals are displayed.",
			"start_songsToPractise" => "Songs to practise",
			"start_iParticipate" => "I participate",
			"start_iMightParticipate" => "I may participate.",
			"start_iDoNotParticipate" => "I cannot participate.",
			"start_setParticipation" => "Set participation",
			"start_participationOver" => "Participation over",
			"start_rehearsalParticipate" => "You participate in this rehearsal.",
			"start_rehearsalMaybeParticipate" => "You may participate in this rehearsal.",
			"start_rehearsalNotParticipate" => "You don't participate in this rehearsal.",
			"start_noConcertsScheduled" => "No concerts scheduled.",
			"start_viewProgram" => "View program",
			"start_iPlay" => "I will play.",
			"start_iMayPlay" => "I may play.",
			"start_iDontPlay" => "I won't play.",
			"start_youParticipate" => "You participate in this concert.",
			"start_youMayParticipate" => "You may participate in this concert.",
			"start_youDontParticipate" => "You cannot participate in this concert.",
			"start_noTasks" => "No tasks present.",
			"start_markAsCompleted" => "Mark as completed",
			"start_noVotes" => "No votes present.",
			"start_endOfVote" => "End of vote",
			"start_newDiscussion" => "New discussion",
			"start_participation" => "Participation"
	);
	
	public function formatDate($day, $month, $year, $hour, $minute) {
		$time = "";
		if($hour != null && $minute != null) {
			$time = " $hour:$minute";
		}
		return "$year/$month/$day" . $time;
	}
	
	public function formatDateForDb($formattedDate) {
		if(strlen($formattedDate) > 10) {
			// datetime conversion
			$dot1 = strpos($formattedDate, "/");
			$dot2 = strpos($formattedDate, "/", $dot1+1);

			$time = substr($formattedDate, $dot2+2, 5);
			$year = substr($formattedDate, 0, 4);
			$month = substr($formattedDate, $dot1+1, $dot2-$dot1-1);
			$day = substr($formattedDate, $dot2+1, 2);
			return $year . "-" . $month . "-" . $day . " $time";
		}
		else {
			// standard conversion
			$dot1 = strpos($formattedDate, "/");
			$dot2 = strpos($formattedDate, "/", $dot1+1);
			$year = substr($formattedDate, 0, 4);
			$month = substr($formattedDate, $dot1+1, $dot2-$dot1-1);
			$day = substr($formattedDate, $dot2+1, 2);
			return $year . "-" . $month . "-" . $day;
		}
	}
	
	public function getMonths() {
		return array(
				1 => "January",
				2 => "February",
				3 => "March",
				4 => "April",
				5 => "May",
				6 => "June",
				7 => "July",
				8 => "August",
				9 => "September",
				10 => "October",
				11 => "November",
				12 => "December"
		);
	}
	
	public function convertEnglishWeekday($wd) {
		return $wd;
	}
	
	public function getDateTimeFormatPattern() {
		return "Y/m/d H:i";
	}
	
	public function getDateFormatPattern() {
		return "Y/m/d";
	}
}


?>