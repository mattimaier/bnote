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
			"rehearsal" => "Rehearsal",
			"rehearsalphase" => "Rehearsal Phase",
			"rehearsalphases" => "Rehearsal Phases",
			"concerts" => "Concerts",
			"concert" => "Concert",
			"votes" => "Votes",
			"vote" => "Vote",
			"tasks" => "Tasks",
			"task" => "Task",
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
			"surname" => "Lastname",
			"name" => "Name",
			"firstname" => "Firstname",
			"discussion" => "Discussion",
			"add_entity" => "Add %p",
			"edit_entity" => "Edit %p",
			"delete_entity" => "Delete %p",
			"saved_entity" => "%p saved",
			"details_entity" => "Details of %p",
			"deleted_entity" => "%p deleted",
			"user" => "User",
			"date" => "Date",
			"mail_footerText" => "This e-mail was automatically sent by BNote:",
			"error" => "Error",
			"street" => "Street",
			"city" => "City",
			"phone" => "Phone",
			"mobile" => "Mobile",
			"birthday" => "Birthday",
			"print" => "Print",
			"notes" => "Notes",
			"nickname" => "Nick",
			"fullname" => "Name",
			"length" => "Length",
			"show_all" => "Show all",
			"outfit" => "Outfit",
			
			// navigation
			"mod_Login" => "Login",
			"mod_Start" => "Start",
			"mod_User" => "Users",
			"mod_Kontakte" => "Contacts",
			"mod_Konzerte" => "Gigs",
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
			"mod_Finance" => "Finance",
			"mod_Calendar" => "Calendar",
			"mod_Passwort" => "Forgot password",
			"mod_Warum BNote?" => "Why BNote?",
			"mod_Registrierung" => "Registration",
			"mod_Bedingungen" => "Terms and Conditions",
			"mod_Impressum" => "Impressum",
			"mod_Equipment" => "Equipment",
			"mod_Tour" => "Tour",
			"mod_Outfits" => "Outfits",
			
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
			"noEntries" => "No entries present.",
			"table_no_entries" => "No entries found.",
			"table_search" => "Filter:",
			"sum" => "Sum",
			"equipment" => "Equipment",
			"tour" => "Tour",

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
			"start_participation" => "Participation",
			"start_youCannotParticipateVote" => "You cannot participate in this vote.",
			"start_worksForMeNot" => "Doesn't work for me",
			"start_worksForMe" => "Works for me",
			"start_worksForMeMaybe" => "May work for me",
			"start_noOptionsYet" => "No vote options. Please check again later.",
			"start_selectionSavedTitle" => "Selection saved",
			"start_selectionSavedMsg" => "Your selection has been saved.",
			"start_taskCompletedTitle" => "Task completed",
			"start_taskCompletedMsg" => "The task has been marked as completed.",
			"start_editProgram" => "Edit Program",
			"start_rank" => "No.",
			"start_title" => "Title",
			"start_composer" => "Composer",
			"start_notes" => "Notes",
			"start_participantsOfRehearsal" => "Participants of the rehearsal on %p",
			"start_noNews" => "No news",
			"start_discussionsDeactivated" => "Discussions are deactivated.",
			"start_giveDiscussionReason" => "Please enter a discussion matter.",
			"start_noComments" => "No comments found",
			"start_noCommentsInDiscussion" => "This discussion has no comments.",
			"start_addComment" => "Add comment",
			"start_sendComment" => "Send comment",
			
			// module: vote
			"vote_yourVotes" => "Your Votes",
			"vote_archive" => "Archive",
			"vote_voters" => "Voters",
			"vote_fields_id" => "Vote No.",
			"vote_fields_name" => "Title",
			"vote_fields_author" => "Creator",
			"vote_fields_end" => "End of Vote",
			"vote_fields_is_date" => "Date Vote",
			"vote_fields_is_multi" => "Multiple Options Selectable",
			"vote_fields_is_finished" => "Vote Finished",
			"vote_details_header" => "Vote Details",
			"vote_edit" => "Edit Vote",
			"vote_now" => "Vote Now",
			"vote_finish" => "Finish Vote",
			"vote_saved_message" => "The vote has been saved successfully.",
			"vote_add_options" => "Add Options",
			"vote_remove_option_tip" => "Click on an option to remove it from the list.",
			"vote_options" => "Options",
			"vote_no_options_yet" => "This vote has no options yet.",
			"vote_option" => "Option",
			"vote_addSingleOption" => "Add Single Option",
			"vote_addMultipleOptions" => "Add Multiple Options",
			"vote_firstDay" => "First Day",
			"vote_lastDay" => "Last Day",
			"vote_end" => "End of Vote",
			"vote_notification" => "Notification for Vote",
			"vote_result" => "Vote Result",
			"vote_clickToRemoveUser" => "Click to remove a user from the list.",
			"vote_noVotersYet" => "This vote has no voters yet.",
			"vote_addVoter" => "Add a voter",
			"vote_voter" => "Voter",
			"vote_multipleAnswersPossible" => "Multiple options were selectable.",
			"vote_singleOnlyPossible" => "Only one option could be selected.",
			"vote_votes" => "Votes",
			"vote_archive" => "Vote Archive",

			// module: finance
			"finance_account" => "Account",
			"finance_account_id" => "Account ID",
			"finance_account_name" => "Account Name",
			"finance_filter_items" => "Filter Bookings",
			"finance_date_from" => "Date from",
			"finance_date_to" => "to",
			"finance_date_filter" => "Filter",
			"finance_add_booking" => "Add Booking",
			"finance_booking_bdate" => "Date",
			"finance_booking_id" => "Booking ID",
			"finance_booking_subject" => "Subject",
			"finance_booking_amount_net" => "Net",
			"finance_booking_amount_tax" => "Tax",
			"finance_booking_amount_total" => "Gross",
			"finance_booking_notes" => "Notes",
			"finance_booking_btype" => "Type",
			"finance_booking_type_0" => "Income",
			"finance_booking_type_1" => "Expense",
			"finance_bookings_filter" => "Filter",
			"finance_booking_saved_title" => "Booking saved",
			"finance_booking_saved" => "The booking was successfully saved.",
			"finance_metrics_header" => "Summary",
			"finance_metrics_income" => "Income",
			"finance_metrics_expenses" => "Expenses",
			"finance_metrics_total" => "Total",
			"finance_metrics_margin" => "Margin",
			"finance_metrics_sum" => "Sum",
			
			"finance_recpay" => "Recurring Payments",
			"recurringpayment" => "Recurring Payment",
			"recpay_account" => "Account",
			"recpay_otype" => "Reference Type",
			"recpay_oid" => "Reference",
			"recpay_accountname" => "Account",
			"recpay_book" => "Book",
			"recpay_book_title" => "Book Recurring Payment",
			"recpay_add_form_title" => "Add Recurring Payment",
			"recpay_no_otype" => "[No Reference]",
			"recpay_book_success_title" => "Booking inserted",
			"recpay_book_success_msg" => "All bookings have been inserted successfully.",
			
			"calendar_rehearsal" => "Rehearsal",
			"calendar_concert" => "Concert",
			"calendar_end_vote" => "Vote End:",
			"calendar_birthday" => "Bday:",
			"calendar_reservation" => "Res.:",
			
			"equipment_model" => "Model",
			"equipment_make" => "Make",
			"equipment_purchase_price" => "Purchase Price",
			"equipment_current_value" => "Current Value",
			"equipment_quantity" => "Quantity",
			
			"tour_details" => "Details",
			"tour_heading" => "Tour Planning",
			"tour_summarysheet" => "Toursheet",
			"tour_transfers" => "Transfers",
			"tour_checklist" => "Checklist",
			"accommodation" => "Accommodation",
			"accommodation_price" => "Price (planned)",
			"accommodation_locationname" => "Location Name",
			"accommodation_tourname" => "Tour",
			"tour_add_rehearsal" => "Add Rehearsal",
			"tour_rehearsal_created" => "Rehearsal added",
			"tour_rehearsal_created_msg" => "Successfully added the rehearsal to the tour.",
			"tour_rehearsal_tab_begin" => "Rehearsal Begin",
			"tour_rehearsal_tab_notes" => "Rehearsal Notes",
			"tour_rehearsal_tab_location" => "Location",
			"tour_rehearsal_tab_location_notes" => "Location Notes",
			"tour_add_contacts" => "Add Participant",
			"add_contacts_form_title" => "Select Tour Participants",
			"tour_add_contacts_success_title" => "Participant added",
			"tour_add_contacts_success_msg" => "The participants were successfully added to the tour.",
			"tour_contact_remove_ref" => "Remove Participant",
			"tour_add_concert" => "Add Concert",
			"travel" => "Transfer",
			"travel_num" => "Trip No.",
			"travel_departure_datetime" => "Departure",
			"travel_departure_location" => "From",
			"travel_arrival_datetime" => "Arrival",
			"travel_arrival_location" => "To",
			"travel_planned_cost" => "Planned Cost",
			"tour_add_task" => "Add Task",
			"tour_task_title" => "Task",
			"tour_task_assigned_to" => "Assignee",
			"tour_task_due_at" => "Due At",
			"tour_task_is_complete" => "Completed",
			"tour_todos" => "ToDos",
			"tour_completed_tasks" => "Completes Tasks",
			"tour_concert_location" => "Concert Venue",
			"tour_concert_approve_until" => "Approve participation until",
			"tour_summary_show_checklist" => "Show Checklist",
			"tour_summary_hide_checklist" => "Hide Checklist",
			"tour_add_equipment" => "Add Equipment",
			"add_equipment_form_title" => "Tour-Equipment",
			"tour_equipment_general_notes" => "Equipment Notes",
			"tour_equipment_notes" => "Notes for the Tour",
			"tour_equipment_saved" => "Equipment Saved",
			"tour_equipment_saved_msg" => "The list of equipment for this tour has been saved.",
			
			"reservation" => "Reservation",
			"reservations" => "Reservations",
			"reservation_id" => "Reservation No."
	);
	
	protected $regex = array(
			"positive_amount" => '/^\d{1,12}$/',
			"positive_decimal" => '/^\d{0,8}\.\d{0,2}$/',
			"signed_amount" => '/^-?\d{1,12}$/',
			"date" => '/^\d{4}\/\d{1,2}\/\d{1,2}$/',
			"datetime" => '/^\d{4}\/\d{1,2}\/\d{1,2}\ \d{2}:\d{2}$/'
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
		else if(strlen($formattedDate) > 5) {
			// standard conversion
			$dot1 = strpos($formattedDate, "/");
			$dot2 = strpos($formattedDate, "/", $dot1+1);
			$year = substr($formattedDate, 0, 4);
			$month = substr($formattedDate, $dot1+1, $dot2-$dot1-1);
			$day = substr($formattedDate, $dot2+1, 2);
			return $year . "-" . $month . "-" . $day;
		}
		else {
			return $formattedDate;
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
	
	public function getRegex($patternCode) {
		if(!isset($this->regex[$patternCode])) {
			return null;
		}
		return $this->regex[$patternCode];
	}
	
	public function decimalToDb($decimal) {
		return str_replace(",", "", $decimal);
	}
	
	public function formatDecimal($dbDecimal) {
		return $dbDecimal;
	}
}


?>