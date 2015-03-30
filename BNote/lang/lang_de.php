<?php

require_once 'lang_base.php';

/**
 * German translation of BNote.
 * @author mattimaier
 *
 */
class Translation extends BNoteTranslation {
	
	protected $texts = array(
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
			"showAll" => "Alle anzeigen",
			"begin" => "Beginn",
			"end" => "Ende",
			"location" => "Ort",
			"news" => "Nachrichten",
			"nonIntegratedUsers" => 'Es gibt inaktive oder nicht integrierte Nutzer. Bitte gehe auf Kontakte/Einphasung und kümmere dich darum.',
			"rehearsals" => "Proben",
			"rehearsal" => "Probe",
			"concerts" => "Konzerte",
			"concert" => "Konzert",
			"votes" => "Abstimmungen",
			"vote" => "Abstimmung",
			"tasks" => "Aufgaben",
			"task" => "Aufgabe",
			"discussions" => "Diskussionen",
			"comment" => "Anmerkung",
			"participants" => "Teilnehmer",
			"contact" => "Kontakt",
			"program" => "Programm",
			"title" => "Titel",
			"description" => "Beschreibung",
			"dueAt" => "Fällig am",
			"name" => "Name",
			"vote" => "Abstimmen",
			"discussion" => "Diskussion",
			"surname" => "Nachname",
			"name" => "Name",
			"firstname" => "Vorname",
			"discussion" => "Diskussion",
			
			// navigation
			"mod_Login" => "Anmeldung",
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
			"start_pleaseGiveReason" => "Bitte gebe einen Grund an.",
			"start_noRehearsalsScheduled" => "Keine Proben angesagt.",
			"start_showNumRehearsals" => "Es werden nur die ersten %p Proben angezeigt.",
			"start_songsToPractise" => "Stücke zum üben",
			"start_iParticipate" => "Ich nehme teil.",
			"start_iMightParticipate" => "Ich nehme vielleicht teil.",
			"start_iDoNotParticipate" => "Ich kann leider nicht.",
			"start_setParticipation" => "Teilnahme angeben",
			"start_participationOver" => "Teilnahmefrist abgelaufen",
			"start_rehearsalParticipate" => "Du nimmst an der Probe teil.",
			"start_rehearsalMaybeParticipate" => "Du nimmst an der Probe vielleicht teil.",
			"start_rehearsalNotParticipate" => "Du nimmst an der Probe nicht teil.",
			"start_noConcertsScheduled" => "Keine Konzerte angesagt.",
			"start_viewProgram" => "Programm ansehen",
			"start_iPlay" => "Ich werde mitspielen.",
			"start_iMayPlay" => "Ich werde vielleicht mitspielen.",
			"start_iDontPlay" => "Ich kann nicht mitspielen.",
			"start_youParticipate" => "Du nimmst am Konzert teil.",
			"start_youMayParticipate" => "Du nimmst am Konzert vielleicht teil.",
			"start_youDontParticipate" => "Du nimmst am Konzert nicht teil.",
			"start_noTasks" => "Keine Aufgaben vorhanden.",
			"start_markAsCompleted" => "Als abgeschlossen markieren",
			"start_noVotes" => "Keine Abstimmungen offen.",
			"start_endOfVote" => "Abstimmungsende",
			"start_newDiscussion" => "Neue Diskussion",
			"start_participation" => "Teilnahme",
			"start_youCannotParticipateVote" => "Sie können an dieser Abstimmung nicht teilnehmen.",
			"start_worksForMeNot" => "Geht nicht",
			"start_worksForMe" => "Geht",
			"start_worksForMeMaybe" => "Vielleicht",
			"start_noOptionsYet" => "Es wurden noch keine Optionen angegeben. Schau später noch einmal nach.",
			"start_selectionSavedTitle" => "Auswahl gespeichert", 
			"start_selectionSavedMsg" => "Deine Auswahl wurde gespeichert.",
			"start_taskCompletedTitle" => "Aufgabe abgeschlossen",
			"start_taskCompletedMsg" => "Die Aufgabe wurde als abgeschlossen markiert.",
			"start_editProgram" => "Programm bearbeiten",
			"start_rank" => "Nr.",
			"start_title" => "Titel",
			"start_composer" => "Komponist/Arrangeuer",
			"start_notes" => "Notizen",
			"start_participantsOfRehearsal" => "Teilnehmer der Probe am %p",
			"start_noNews" => "Keine Neuigkeiten",
			"start_discussionsDeactivated" => "Diskussionen sind in dieser Anwendung deaktiviert.",
			"start_giveDiscussionReason" => "Bitte geben Sie den Dikussionsgegenstand an.",
			"start_noComments" => "Keine Kommentare",
			"start_noCommentsInDiscussion" => "Keine Kommentare in dieser Diskussion.",
			"start_addComment" => "Kommentar hinzufügen",
			"start_sendComment" => "Kommentar senden"
	);
	
	public function formatDate($day, $month, $year, $hour, $minute) {
		$time = "";
		if($hour != null && $minute != null) {
			$time = " $hour:$minute Uhr";
		} 
		return "$day.$month.$year" . $time;
	}
	
	public function formatDateForDb($formattedDate) {
		if(strlen($formattedDate) > 10) {
			// datetime conversion
			$dot1 = strpos($formattedDate, ".");
			$dot2 = strpos($formattedDate, ".", $dot1+1);
				
			$time = substr($formattedDate, $dot2+6, 5);
			$year = substr($formattedDate, $dot2+1, 4);
			$month = substr($formattedDate, $dot1+1, $dot2-$dot1-1);
			$day = substr($formattedDate, 0, $dot1);
			return $year . "-" . $month . "-" . $day . " $time";
		}
		else {
			// standard conversion
			$dot1 = strpos($formattedDate, ".");
			$dot2 = strpos($formattedDate, ".", $dot1+1);
			$year = substr($formattedDate, $dot2+1);
			$month = substr($formattedDate, $dot1+1, $dot2-$dot1-1);
			$day = substr($formattedDate, 0, $dot1);
			return $year . "-" . $month . "-" . $day;
		}
	}
	
	public function getMonths() {
		return array(
				1 => "Januar",
				2 => "Februar",
				3 => "M&auml;rz",
				4 => "April",
				5 => "Mai",
				6 => "Juni",
				7 => "Juli",
				8 => "August",
				9 => "September",
				10 => "Oktober",
				11 => "November",
				12 => "Dezember"
		);
	}
	
	public function convertEnglishWeekday($wd) {
		$res = "";
		switch($wd) {
			case "Mon": $res = "Montag"; break;
			case "Monday": $res = "Montag"; break;
			case "Tue": $res = "Dienstag"; break;
			case "Tuesday": $res = "Dienstag"; break;
			case "Wed": $res = "Mittwoch"; break;
			case "Wednesday": $res = "Mittwoch"; break;
			case "Thu": $res = "Donnerstag"; break;
			case "Thursday": $res = "Donnerstag"; break;
			case "Fri": $res = "Freitag"; break;
			case "Friday": $res = "Freitag"; break;
			case "Sat": $res = "Samstag"; break;
			case "Saturday": $res = "Samstag"; break;
			case "Sun": $res = "Sonntag"; break;
			case "Sunday": $res = "Sonntag"; break;
		}
		return $res;
	}
	
	public function getDateTimeFormatPattern() {
		return "d.m.Y H:i";
	}
	
	public function getDateFormatPattern() {
		return "d.m.Y";
	}
}


?>