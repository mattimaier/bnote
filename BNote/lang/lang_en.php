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
			
			// navigation
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
			"news" => "News",
			"nonIntegratedUsers" => 'Non integrated users have been detected. Please change to Contacts/Integration to integrate them into your ensemble.',
			"rehearsals" => "Rehearsals"
	);
	
	
}


?>