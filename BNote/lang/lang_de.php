<?php

/**
 * German translation of BNote.
 * @author mattimaier
 *
 */
class Translation {
	
	private $texts = array(
			"welcome" => "Willkommen",
			"autoActivation" => "Die automatische Registrierung ist aktiviert. Bitte Sicherheitshinweise beachten.",
			"back" => "Zurück",
			"delete" => "löschen",
			"deleted" => "gelöscht",
			"reallyDeleteQ" => "Wollen sie diesen Eintrag wirklich l&ouml;schen?",
			"noUserId" => "Bitte geben Sie eine Benutzer ID an.",
			"selectEntryText" => "Bitte wählen Sie einen Eintrag um diesen anzuzeigen oder zu bearbeiten.",
			"add" => "hinzufügen",
			"saved" => "gespeichert",
			"entrySaved" => "Der Eintrag wurde erfolgreich gespeichert.",
			"details" => "Details",
			"edit" => "bearbeiten",
			"changed" => "geändert",
			"entryChanged" => "Der Eintrag wurde erfolgreich ge&auml;ndert.",
			"entryDeleted" => "Der Eintrag wurde erfolgreich gel&ouml;scht.",
			"yes" => "ja",
			"no" => "nein",
			
			// navigation
			"mod_Start" => "Start",
			"mod_User" => "Benutzer",
			"mod_Kontakte" => "Kontakte",
			"mod_Konzerte" => "Konzerte",
			"mod_Proben" => "Proben",
			"mod_Repertoire" => "Repertoire",
			"mod_Kommunikation" => "Kommunikation",
			"mod_Locations" => "Locations",
			"mod_Kontaktdaten" => "Kontaktdaten",
			"mod_Hilfe" => "Hilfe",
			"mod_Website" => "Website",
			"mod_Share" => "Share",
			"mod_Mitspieler" => "Mitspieler",
			"mod_Abstimmung" => "Abstimmungen",
			"mod_Konfiguration" => "Konfiguration",
			"mod_Aufgaben" => "Aufgaben",
			"mod_Nachrichten" => "Nachrichten",
			"mod_Probenphasen" => "Probephasen",
			
			// widgets
			"addFolder" => "Ordner hinzufügen",
			"addFile" => "Datei hinzufügen",
			"favorites" => "Favoriten",
			"myFiles" => "Meine Dateien",
			"commonShare" => "Tauschordner",
			"userFolder" => "Benutzerordner",
			"groupFolder" => "Gruppenorder",
			"selectFolder" => "Bitte w&auml;hle einen Ordner.",
			"folderUp" => "In Überordner wechseln",
			"folderAsZip" => "Ordner als Zip-Archiv herunterladen",
			"filename" => "Dateiname",
			"filesize" => "Dateigröße",
			"fileoptions" => "Optionen",
			"createFolder" => "Ordner erstellen",
			"foldername" => "Ordnername",
			"createFile" => "Datei hinzufügen",
			"file" => "Datei",
			"uploadFile" => "Datei hochladen",
			"noFileAddPermission" => "Du hast keine Berechtigung eine Datei hinzuzuf&uuml;gen.",
			"errorWithFile" => "Es trat ein Fehler beim verarbeiten der Datei auf. Bitte versuche es noch einmal.",
			"errorFileMaxSize" => "Die maximale Dateigr&ouml;&szlig;e wurde &uuml;berschritten.",
			"errorFileAbort" => "Die Datei wurde nur teilweise hochgeladen. Bitte &uuml;berpr&uuml;fe deine Internetverbindung.",
			"errorNoFile" => "Es wurde keine Datei hochgeladen.",
			"errorSavingFile" => "Serverfehler beim Speichern der Datei.",
			"errorUploadingFile" => "Die Datei konnte nicht hochgeladen werden.",
			"errorDeletingFile" => "Du hast keine Berechtigung eine Datei zu l&uml;schen.",
			"errorFileNotFound" => "Die Datei konnte nicht gefunden werden.",
			"noFolderAddPermission" => "Du hast keine Berechtigung einen Order hinzuzuf&uuml;gen.",
			"errorReservedFolderNames" => "Der neue Ordner darf nicht \"users\" oder \"groups\" heißen.",
			"open" => "Öffnen",
			"download" => "Download",
			"archiveCreated" => "Das Archiv wurde erstellt und kann unter folgendem Link heruntergeladen werden.",
			"downloadArchive" => "Archiv herunterladen",
			"noEntries" => "Keine Eintr&auml;ge vorhanden",
			
			// module: start
			"start_calendarExport" => "Kalender Export",
			"start_calendarSubscribe" => "Kalender abonnieren",
			"news" => "Nachrichten",
			"nonIntegratedUsers" => 'Es gibt inaktive oder nicht integrierte Nutzer. Bitte gehe auf Kontakte/Einphasung und kümmere dich darum.',
			"rehearsals" => "Proben"
	);
	
	public function getText($code) {
		if(!isset($this->texts[$code])) return null;
		return $this->texts[$code];
	}
}


?>