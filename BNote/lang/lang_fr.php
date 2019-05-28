<?php
require_once 'lang_base.php';

/**
 * German translation of BNote.
 * @author mattimaier
 *
 */
class Translation extends BNoteTranslation {
	
	protected $texts = array(
			
			// Installation *************************************************************************************************************************************************

			// Installation
			"Installation_welcome.title" => "Bienvenue",
			"Installation_welcome.message_1" => "Merci d'avoir choisi BNote. Tu te rends un grand service à toi-même et à ton groupe.
					Il ne vous reste plus qu'à terminer l'installation et vous êtes prêt à partir !",
			"Installation_welcome.message_2" => "Vous avez déjà fait le premier pas vers BNote.
					Vous avez décompressé BNote, l'avez téléchargé sur votre serveur web et démarré l'installation.   
					Pour terminer l'installation avec succès, procédez comme suit :",
			"Installation_welcome.message_3" => "Créez un nouvel utilisateur de base de données MySQL avec mot de passe et attribuez-lui une nouvelle base de données vide.",
			"Installation_welcome.message_4" => "Assurez-vous que ce script peut écrire dans le sous-répertoire /config de BNote.<br/>
				Habituellement, vous devez modifier les permissions du dossier pour le rendre accessibles à l'utilisateur du serveur web ou
				demander à votre hébergeur de permettre à ce que les scripts puissent écrire.",
			"Installation_welcome.message_5" => "Préparez le nom et l'adresse de votre groupe.",
			"Installation_companyConfig.message_1" => "Vous avez déjà créé une configuration pour un groupe de musique.
					Vous pouvez donc sauter cette étape. Pour modifier les données de base, veuillez ajuster le fichier BNote/config/company.xml.",		
			"Installation_companyConfig.title" => "Votre groupe de musique",
			"Installation_companyConfig.message_2" => "Veuillez entrer les coordonnées de votre groupe.",
			"Installation_companyConfig.Form" => "Configuration de votre groupe",
			"Installation_companyConfig.Name" => "Nom du groupe",
			"Installation_companyConfig.Street" => "Rue",
			"Installation_companyConfig.Zip" => "Code postale",
			"Installation_companyConfig.City" => "Ville",
			"Installation_companyConfig.Country" => "Pays",
			"Installation_companyConfig.Phone" => "Téléphone",
			"Installation_companyConfig.Mail" => "Courriel du chef d'orchestre",
			"Installation_companyConfig.Web" => "Site Internet",
			"Installation_companyConfig.submit" => "Envoyer",
			"Installation_companyConfig.Error" => "La configuration n'a pas pu être écrite. Veuillez vous assurer que BNote peut écrire dans le répertoire config/.",
			"Installation_write_appConfig.Error" => "La configuration n'a pas pu être écrite. Veuillez vous assurer que BNote peut écrire dans le répertoire config/.",
			"Installation_databaseConfig.title" => "Configuration de la base de données",
			"Installation_databaseConfig.message_1" => "La configuration de la base de données existe déjà", "Il a été reconnu que vous avez déjà créé une configuration de base de données.
					Vous pouvez donc sauter cette étape.",
			"Installation_databaseConfig.message_2" => "Veuillez saisir les données d'accès à la base de données BNote.",
			"Installation_databaseConfig.Form" => "Configuration de la base de données",
			"Installation_databaseConfig.Server" => "Serveur",
			"Installation_databaseConfig.Port" => "Port",
			"Installation_databaseConfig.Name" => "Nom de base de données",
			"Installation_databaseConfig.User" => "Nom d'utilisateur",
			"Installation_databaseConfig.Password" => "Mot de passe",
			"Installation_databaseConfig.Submit" => "Envoyer",
			"Installation_process_databaseConfig.error" => "La configuration n'a pas pu être écrite. Veuillez vous assurer que BNote peut écrire dans le répertoire config/.",
			"Installation_adminUser.title" => "Créer un utilisateur",
			"Installation_adminUser.form" => "Nouvel utilisateur",
			"Installation_adminUser.login" => "Nom d'utilisateur",
			"Installation_adminUser.password" => "Mot de passe",
			"Installation_adminUser.name" => "Prénom",
			"Installation_adminUser.surname" => "Nom de famille",
			"Installation_adminUser.company" => "Organisation",
			"Installation_adminUser.phone" => "Téléphone",
			"Installation_adminUser.mobile" => "Téléphone portable",
			"Installation_adminUser.email" => "Adresse de courriel",
			"Installation_adminUser.street" => "Rue",
			"Installation_adminUser.zip" => "Code postale",
			"Installation_adminUser.city" => "Ville",
			"Installation_adminUser.state" => "Région",
			"Installation_adminUser.country" => "Pays",
			"Installation_process_adminUser.error" => "Mot de passe invalide. Veuillez vous assurer que le mot de passe comporte au moins 6 caractères et qu'il n'est pas vide.",
			"Installation_finalize.title" => "Ce qu'il reste à faire.....",
			"Installation_finalize.message" => "Vous l'avez fait : L'installation est maintenant terminée !
					Vous commencez donc correctement avec BNote :",
			"Installation_finalize.message_1" => "Rapport à l'intention du ",
			"Installation_finalize.message_2" => " pour.",
			"Installation_finalize.message_3" => "Vous êtes maintenant administrateur et avez accès à l'ensemble du système. Fais attention avec ça !",
			"Installation_finalize.message_4" => "Allez dans le module des coordonnées et complétez vos coordonnées.",
			"Installation_finalize.message_5" => "Supprimer le script install.php du dossier BNote
				pour empêcher tout accès non autorisé !",

			// Export *************************************************************************************************************************************************

			// AbstractBNA
			"AbstractBNA_sendMail.error" => "Aucun destinataire n'a été trouvé.",
			
			// BNote Interface (BNI)			
			"bni_getThumbPath.error" => "ID non défini.",
			"bni_getImagePath.error" => "ID non défini.",
			"bni_getGallery.error" => "ID non défini.",
			"bni_getImagesForGallery.error" => "ID non défini.",
			"bni_getImage.error" => "ID non défini.",
			
			// BNote Interface (BNI) - XML
			"bnixml_getImagePath.error" => "ID non défini.",
			"bnixml_getThumbPath.error" => "ID non défini.",
			"bnixml_getGallery.error" => "ID non défini.",
			"bnixml_getImagesForGallery.error" => "ID non défini.",
			"bnixml_getImage.error" => "ID non défini.",

			// Gigcard			
			"gigcard_concert.deniedMsg" => "Vous n'avez pas la permission pour exporter le concert !",
			"gigcard_concert.event" => "Evénement",
			"gigcard_concert.organizer" => "Organisateur",
			"gigcard_concert.address" => "lieu",
			"gigcard_concert.contact" => "Contact",
			"gigcard_concert.times" => "Périodes",
			"gigcard_concert.period" => "Date/Heure",
			"gigcard_concert.meetingtime" => "Heure de rendez-vous",
			"gigcard_concert.approve_until" => "Engagement jusqu'au",
			"gigcard_concert.organisation" => "Organisation",
			"gigcard_concert.groupNames" => "Distribution",
			"gigcard_concert.program" => "Programme",
			"gigcard_concert.outfit" => "Tenue",
			"gigcard_concert.equipment" => "Equipment",
			"gigcard_concert.details" => "Détails",
			"gigcard_concert.accommodation" => "Hébergement",
			"gigcard_concert.payment" => "Cachet",
			"gigcard_concert.conditions" => "Modalités et conditions",

			// Notify			
			"Notifier_sendRehearsalNotification.message_1" => "Répétition",
			"Notifier_sendRehearsalNotification.message_2" => "- souvenir",
			"Notifier_sendRehearsalNotification.message_3" => "Veuillez indiquer votre participation à la répétition à l'Assemblée générale annuelle de l",
			"Notifier_sendRehearsalNotification.message_4" => "pour.<br/>",
			"Notifier_sendRehearsalNotification.message_5" => "Appeler BNote",
			"Notifier_sendRehearsalNotification.message_6" => "Merci!",
			"Notifier_sendConcertNotification.message_1" => " - mémoire",
			"Notifier_sendConcertNotification.message_2" => "Veuillez inscrire votre participation pour ",
			"Notifier_sendConcertNotification.message_3" => " pour:<br/>",
			"Notifier_sendConcertNotification.message_4" => "Appeler BNote",
			"Notifier_sendConcertNotification.message_5" => "Merci!",
			"Notifier_sendVoteNotification.message_1" => " - mémoire",
			"Notifier_sendVoteNotification.message_2" => "Merci de voter pour ",
			"Notifier_sendVoteNotification.message_3" => " à partir de:",
			"Notifier_sendVoteNotification.message_4" => "Appeler BNote",
			"Notifier_sendVoteNotification.message_5" => "Merci!",
			
			// Program_csv				
			"program_csv_Notifier_start.deniedMsg" => "Vous n'avez pas la permission d'exporter les contacts !",
			"program_csv_Notifier_start.error" => "Veuillez entrer le numéro du programme que vous voulez exporter.",
			"program_csv_Notifier_start.title" => "Titre",
			"program_csv_Notifier_start.composer" => "Compositeur/arrangeur",
			"program_csv_Notifier_start.duration" => "Durée",
			"program_csv_Notifier_start.bpm" => "BPM",
			"program_csv_Notifier_start.key" => "Tonalité",		
			"program_csv_Notifier_start.gender" => "Genre",
			"program_csv_Notifier_start.status" => "Statut",
			"program_csv_Notifier_start.notes" => "Notes",

			// Repertoire				
			"repertoire_start.deniedMsg" => "Vous n'avez pas la permission d'exporter le répertoire.!",
			
			// Repertoire_files				
			"repertoire_files_start.deniedMsg" => "Vous n'avez pas la permission d'exporter le répertoire.!",
			"repertoire_files_start.message" => "Au moins 3 caractères requis.",
			
			// TriggerServiceClient
			"TriggerServiceClient_createTrigger.error" => "Les avis ne peuvent pas être créés.",

			// useractivation	
			"useractivation_input.printError" => "Saisie incorrecte.",
			"useractivation_validate.printError" => "ID utilisateur introuvable.",
			"useractivation_user_email.printError" => "Adresse courriel invalide.",
			"useractivation_update.message" => "<p><b>Compte utilisateur activé!</b><br/>Votre compte a été activé avec succès. Vous pouvez maintenant vous connecter.</p>",
			"useractivation_update.error_1" => "<p><b>Erreurs!</b><br/>L'activation n'a pas réussi. Veuillez contacter votre responsable.<br/>",
			"useractivation_update.error_2" => "<i>Message d'erreur:",
			"useractivation_update.error_3" => "</i></p>",

			// Vcard			
			"vcard_input.deniedMsg" => "Vous n'avez pas la permission d'exporter les contacts.!",
			
			// Export *************************************************************************************************************************************************
			
			// Memberlist	
			"MembersPDF_construct.title" => "Liste des membres",
			"MembersPDF_writeTable.surname" => "Nom",
			"MembersPDF_writeTable.title" => "prénom",
			"MembersPDF_writeTable.phone" => "Privé",
			"MembersPDF_writeTable.mobile" => "Mobile",
			"MembersPDF_writeTable.occupation" => "Profession",
			"MembersPDF_writeTable.email" => "Courriel",
			"MembersPDF_writeTable.street" => "Rue",
			"MembersPDF_writeTable.city" => "Ville",
			"MembersPDF_writeTable.zip" => "Code postal",
			"MembersPDF_writeTable.instrument" => "Instrument",
			
			// MemberlistPDF	
			"MemberlistPDF_Header.title" => "Liste de contacts",
			
			// PartlistPDF				
			"MembersPDF_PartlistPDF.title" => "Liste des participants",
			"MembersPDF_contents.from" => "Échantillon sur",
			"MembersPDF_contents.hour" => "horloge",
			"MembersPDF_contents.location" => "lieu",
			"MembersPDF_contents.notes" => "Notes",
			"MembersPDF_contents.name" => "Nom",
			"MembersPDF_contents.Instrument" => "Instrument",
			"MembersPDF_addSignatureCol.contact" => "Signature",
			
			// PDFTable				
			"PDFTable_write.novalue" => "Aucune entrée disponible.",

			// ProgramPDF	
			"ProgramPDF_writeTable.title" => "Titre",
			"ProgramPDF_writeTable.arranger" => "Arrangeur",
			"ProgramPDF_writeTable.notes" => "Notes",
			"ProgramPDF_writeTable.length" => "Durée",
			"ProgramPDF_writeTable.total_length" => "Durée totale",
						
			// General *************************************************************************************************************************************************

			// General: AbstractData *********************************************	   

			// AbstractData			
			"AbstractData_adp.message" => "Application Data Provider n'est pas paramétré ! Appeler init() !",
			"AbstractData_validate.error" => "Veuillez fournir suffisamment d'informations.",
			
			// General: AbstractLocation *********************************************	
			
			// AbstractLocationData
			"AbstractLocationData_getAddressViewFields.street" => "Rue",
			"AbstractLocationData_getAddressViewFields.city" => "Ville",
			"AbstractLocationData_getAddressViewFields.zip" => "Code postale",
			"AbstractLocationData_getAddressViewFields.state" => "Région",
			"AbstractLocationData_getAddressViewFields.country" => "Pays",

			// General: Abstract *********************************************																		  
			
			// AbstractView
			"AbstractView_backToStart.back" => "Retour",
			"AbstractView_deleteConfirmationMessage.delete" => "Supprimer",
			"AbstractView_deleteConfirmationMessage.reallyDeleteQ" => "Voulez-vous vraiment supprimer cette entrée ?",
			"AbstractView_deleteConfirmationMessage.back" => "Retour",
			"AbstractView_checkID.noUserId" => "Veuillez entrer un nom d'utilisateur.",

			// General: banner *********************************************																		
			
			// banner
			"banner_Logout.welcome" => "Bienvenue",
			"banner_Logout.Logout" => "Déconnexion",

			// General: Crud *********************************************	

			// CrudRefView			
			"CrudRefView_addEntityForm.getEntityName" => " ajouter",
			
			// CrudView
			"CrudView_start.showAllTable" => "Veuillez sélectionner une entrée pour la visualiser ou la modifier.",
			"CrudView_add.Message_1" => "Enregistrée %p",
			"CrudView_add.Message_2" => "L'entrée a été sauvegardée avec succès.",
			"CrudView_view.Message" => "Détails %p",
			"CrudView_viewOptions.edit" => "Editer %p",
			"CrudView_viewOptions.delete_confirm" => "Supprimer %p",
			"CrudView_editEntityForm.delete_edit" => "Editer",
			"CrudView_edit_process.delete_changed" => "Modifié",
			"CrudView_edit_process.delete_changed" => "L'entrée a été modifiée avec succès.",
			"CrudView_delete.deleted_entity" => "Supprimé %p",
			"CrudView_delete.entryDeleted" => "L'entrée a été supprimée avec succès.",
			"CrudView_backToViewButton.back" => "Retour",

			// General: CrudRefLocation *********************************************	
								
			// CrudRefLocationView
			"CrudRefLocationView_renameTableAddressColumns.street" => "Rue",
			"CrudRefLocationView_renameTableAddressColumns.city" => "Ville",
			"CrudRefLocationView_renameTableAddressColumns.zip" => "Code postale",
			"CrudRefLocationView_renameTableAddressColumns.state" => "Région",
			"CrudRefLocationView_renameTableAddressColumns.country" => "Pays",
			"CrudRefLocationView_renameDataViewFields.street" => "Rue",
			"CrudRefLocationView_renameDataViewFields.city" => "Ville",
			"CrudRefLocationView_renameDataViewFields.zip" => "Code postale",
			"CrudRefLocationView_renameDataViewFields.state" => "région",
			"CrudRefLocationView_renameDataViewFields.country" => "Pays",
			"CrudRefLocationView_replaceDataViewFieldWithAddress.address" => "Adresse",

			// General: navigation *********************************************	
					
			// navigation
			"navigation_Login" => "Connexion et inscription",
			"navigation_Start" => "Démarrage",
			"navigation_User" => "Utilisateur",
			"navigation_Kontakte" => "Contacts",
			"navigation_Konzerte" => "Concerts",
			"navigation_Proben" => "Répétitions",
			"navigation_Repertoire" => "Répertoire",
			"navigation_Kommunikation" => "Message",
			"navigation_Locations" => "Emplacement",
			"navigation_Kontaktdaten" => "Coordonnées de contact",
			"navigation_Hilfe" => "Aide",
			"navigation_Website" => "Website",
			"navigation_Share" => "partage",
			"navigation_Mitspieler" => "Membres",
			"navigation_Abstimmung" => "Votes",
			"navigation_Konfiguration" => "Paramétrage",
			"navigation_Aufgaben" => "Tâches",
			"navigation_Nachrichten" => "Actualités",
			"navigation_Probenphasen" => "Période d'essai",
			"navigation_Finance" => "Finance",
			"navigation_Calendar" => "Calendrier",
			"navigation_Passwort" => "Mot de passe oublié",
			"navigation_Warum BNote?" => "Pourquoi BNote ?",
			"navigation_Registrierung" => "Inscription",
			"navigation_Bedingungen" => "Conditions générales",
			"navigation_Impressum" => "Mentions légales",
			"navigation_Equipment" => "Équipement",
			"navigation_Tour" => "Voyage",
			"navigation_Outfits" => "Tenue",
			"navigation_Stats" => "Analyses",

			// General: Regex *********************************************	
			
			// Regex
			"Regex_fail.error" => "Une ou plusieurs zones contiennent des valeurs non valides. ",				
			
			// General: Systemdata *********************************************	
			
			// Systemdata			
			"Systemdata_getUserModulePermissions.error" => "Vous n'avez pas suffisamment de privilèges pour accéder à ce système. Veuillez contacter votre administrateur système.",
			
			// General: XmlData *********************************************	
			
			// XmlData			
			"XmlData_construct.filename" => " n'a pas pu être chargée",
			
			// Widget *********************************************************************************************************************

			// Widget: Dataview *********************************************	
			"Dataview_autoRename.yes" => "oui",
			"Dataview_autoRename.no" => "non",
			
			// Widget: Error *********************************************	
			"BNoteError_construct.error" => "Erreurs",
			
			// Widget: Filebrowser *********************************************	
			"Filebrowser_write.error" => "Accès refusé.",
			"Filebrowser_showOptions.addFolderForm" => "Ajouter un dossier",
			"Filebrowser_showOptions.addFileForm" => "Ajouter un fichier",
			"Filebrowser_showOptions.download" => "Télécharger le dossier sous forme d'archive zip",
			"Filebrowser_mainView.writeFavs" => "Favoris",
			"Filebrowser_mainView.addFile" => "Glisser les fichiers dans cette zone pour les ajouter au dossier",
			"Filebrowser_writeFavs.myFiles" => "Mes dossiers",
			"Filebrowser_writeFavs.commonShare" => "Dossiers d'échange",
			"Filebrowser_writeFavs.userFolder" => "Dossier utilisateur",
			"Filebrowser_writeFolderContent.message" => "Veuillez choisir un dossier.",
			"Filebrowser_addFolderForm.addFolder" => "Créer un dossier",
			"Filebrowser_addFolderForm.foldername" => "Nom du dossier",
			"Filebrowser_addFile.error_1" => "Vous n'avez pas la permission d'ajouter un fichier.",
			"Filebrowser_addFile.errorFileMaxSize" => "La taille maximale du fichier a été dépassée.",
			"Filebrowser_addFile.errorFileAbort" => "Le fichier n'a été que partiellement téléchargé. Veuillez vérifier votre connexion Internet.",
			"Filebrowser_addFile.errorNoFile" => "Aucun fichier n'a été téléchargé.",
			"Filebrowser_addFile.errorSavingFile" => "Erreur de serveur lors de l'enregistrement du fichier.",
			"Filebrowser_addFile.error_2" => "Le fichier n'a pas pu être téléchargé.",
			"Filebrowser_addFile.error_3" => "Vous n'avez pas la permission d'ajouter un fichier.",
			"Filebrowser_addFile.error_4" => "Erreur de serveur lors de l'enregistrement du fichier.",
			"Filebrowser_deleteFile.error_1" => "Vous n'avez pas la permission de supprimer un fichier.",
			"Filebrowser_deleteFile.error_2" => "Le fichier n'a pas pu être trouvé.",
			"Filebrowser_deleteFile.error_3" => "Vous n'avez pas la permission de supprimer un fichier.",
			"Filebrowser_addFolder.error_1" => "Vous n'avez pas la permission d'ajouter une commande.",
			"Filebrowser_addFolder.error_2" => "Le nouveau dossier ne doit pas s'appeler \"users\" ou \"groups\".",
			"Filebrowser_getFilesFromFolder.open" => "Ouverture",
			"Filebrowser_getFilesFromFolder.download" => "Télécharger",
			"Filebrowser_getFilesFromFolder.delete" => "supprimer",
			"Filebrowser_getFolderCaption.myFiles" => "Mes dossiers",
			"Filebrowser_getFolderCaption.userFolder" => "Dossier utilisateur",
			"Filebrowser_getFolderCaption.commonShare" => "Dossiers d'échange",
			"Filebrowser_download.archiveCreated" => "Les archives ont été créées et peuvent être téléchargées à partir du lien suivant.",
			"Filebrowser_download.downloadArchive" => "Télécharger l'archive",
			"Filebrowser_download.back" => "Retour",
			
			// Widget: Filterbox *********************************************			
			"Filterbox_write.showAllOption" => "Tout afficher",
			"Filterbox_write.yes" => "Oui",
			"Filterbox_write.no" => "Non",
			
			// Widget: Form *********************************************				
			"Form_setForeign.error" => "La référence n'a pu être trouvée.",
			"Form_write.message" => "* les champs marqués d'un astérisque sont obligatoires",

			// Widget: GroupSelector *********************************************				
			"GroupSelector_toString.no" => "Non",
			"GroupSelector_toString.yes" => "Oui",

			// Widget: SectionForm *********************************************					
			"SectionForm_write.message" => "* les champs marqués d'un astérisque sont obligatoires",
						
			// Widget: Plainlist *********************************************	
			"Plainlist_write.empty" => "[Aucune entrée]",
			
			// Widget: Table *********************************************	
			"Table_write.yes" => "Oui",
			"Table_write.yes" => "Non",
			"Table_write.table_no_entries" => "Aucune entrée n'a été trouvée.",
			"Table_write.prevpage" => "Entrées précédentes",
			"Table_write.nextpage" => "Entrées Suivantes",
			"Table_write.sEmptyTable" => "Aucune entrée n'a été trouvée.",
			"Table_write.sInfoEmpty" => "Aucune entrée n'a été trouvée.",
			"Table_write.sZeroRecords" => "Aucune entrée n'a été trouvée.",
			"Table_write.sSearch" => "Filtre :",
			
			// Module *********************************************************************************************************************
			
			// module: Abstimmung *********************************************

			// AbstimmungData
			"AbstimmungData_construct.id" => "Numéro de vote",
			"AbstimmungData_construct.name" => "Titre",
			"AbstimmungData_construct.author" => "créateur",
			"AbstimmungData_construct.end" => "Fin du vote",
			"AbstimmungData_construct.is_date" => "Réconciliation des dates",
			"AbstimmungData_construct.is_multi" => "Différentes options possibles",
			"AbstimmungData_construct.is_finished" => "Le vote est terminé",
			"AbstimmungData_getResult.yes" => " Oui",
			"AbstimmungData_getResult.no" => " Non",
			"AbstimmungData_getResult.maybe" => " Peut-être",
			"AbstimmungData_validate.BNoteError" => "Veuillez sélectionner un groupe pour le vote.",
			"AbstimmungData_getResult.odate" => " Heures",
			
			// AbstimmungView
			"AbstimmungView_construct.EntityName" => "Vote",
			"AbstimmungView_construct.addEntityName" => "Ajouter un vote",
			"AbstimmungView_construct.option" => "Option",
			"AbstimmungView_construct.options" => "Options ",
			"AbstimmungView_writeTitle_yourVotes" => "Vos votes",
			"AbstimmungView_startOptions.archive" => "Archive",
			"AbstimmungView_addEntityForm.add_entity" => "Abstimmung hinzufügen",
			"AbstimmungView_addEntityForm.voters" => "Ayant droit de vote",
			"AbstimmungView_view.header" => "détails du vote",
			"AbstimmungView_viewOptions.back" => "Retour",
			"AbstimmungView_viewOptions.edit" => "Traiter le rapprochement",
			"AbstimmungView_viewOptions.now" => "Votez maintenant",
			"AbstimmungView_viewOptions.finish" => "Fin du vote",
			"AbstimmungView_add.saved_entity" => "%p sauvegarder",			
			"AbstimmungView_add.saved_message" => "Le vote a été sauvé avec succès.",
			"AbstimmungView_add.add_options" => "Ajouter des options",
			"AbstimmungView_options.options" => "Options",
			"AbstimmungView_options.remove_option_tip" => "Cliquez sur une option pour la supprimer de la liste.",
			"AbstimmungView_options.no_options_yet" => "Ce vote n'a pas encore d'options.",
			"AbstimmungView_options.add_entity" => "Abstimmung hinzufügen",
			"AbstimmungView_options.addSingleOption" => "Ajoutez une option",
			"AbstimmungView_options.addMultipleOptions" => "Ajouter plusieurs options",
			"AbstimmungView_options.add_date" => "Rendez-vous",
			"AbstimmungView_options.del_date" => "Rendez-vous",
			"AbstimmungView_options.firstDay" => "Premier jour",
			"AbstimmungView_options.lastDay" => "Dernier jour",
			"AbstimmungView_options.multiform_add_date" => "Rendez-vous",
			"AbstimmungView_options.name" => "Nom",
			"AbstimmungView_viewDetailTable.title" => "Titre",
			"AbstimmungView_viewDetailTable.end" => "Fin du vote",
			"AbstimmungView_viewDetailTable.fields_is_date" => "rapprochement des dates",
			"AbstimmungView_viewDetailTable.fields_is_multi" => "Différentes options possibles",
			"AbstimmungView_additionalViewButtons.voters" => "Ayant droit de vote",
			"AbstimmungView_additionalViewButtons.notification" => "avis de vote",
			"AbstimmungView_additionalViewButtons.result" => "Résultat",
			"AbstimmungView_additionalViewButtons.edit_entity" => "%p éditer",
			"AbstimmungView_group.voters" => "Ayant droit de vote",
			"AbstimmungView_group.clickToRemoveUser" => "Cliquez sur un utilisateur pour le supprimer de la liste.",
			"AbstimmungView_group.noVotersYet" => "Ce vote n'a pas encore de droit de vote.",
			"AbstimmungView_group.addVoter" => "Ajouter une personne habilitée à voter",
			"AbstimmungView_group.voter" => "Personne habilitée à voter",
			"AbstimmungView_result.multipleAnswersPossible" => "Plusieurs réponses possibles.",
			"AbstimmungView_result.singleOnlyPossible" => "Chaque personne habilitée à voter ne pouvait exprimer qu'une seule voix.",
			"AbstimmungView_result.votes" => "Voix",
			"AbstimmungView_result.voters" => "Ayant droit de vote",
			"AbstimmungView_archive.archive" => "Archives des votes",
						
			// module: Appointment *********************************************

			// AppointmentView			
			"AppointmentView_changeDefaultAddEntityForm.group" => "Invitations",
			"AppointmentView_backToStart.back" => "Retour",
			"AppointmentView_changeDefaultEditEntityForm.group" => "Invitations",
			
			// module: Aufgaben *********************************************

			// AufgabenData
			"AufgabenData_construct.id" => "ID",
			"AufgabenData_construct.title" => "Titre",
			"AufgabenData_construct.description" => "Description",
			"AufgabenData_construct.created_at" => "Créé à",
			"AufgabenData_construct.created_by" => "Créé par",
			"AufgabenData_construct.due_at" => "Échues au",
			"AufgabenData_construct.assigned_to" => "Attribué à",
			"AufgabenData_construct.is_complete" => "Terminé",
			"AufgabenData_construct.completed_at" => "Terminé à",
			
			// AufgabenController
			"AufgabenController_informUser.title_1" => "Nouvelle tâche: ",
			"AufgabenController_informUser.body_1" => "Une nouvelle tâche a été créée pour vous. Veuillez vous connecter à BNote pour voir plus de détails.\n\n",
			"AufgabenController_informUser.body_2" => "Description de la tâche :\n\n",
			"AufgabenController_informUser.title_2" => "La tâche a été modifiée : ",
			"AufgabenController_informUser.body_3" => "La tâche en l'objet a changé.",
			"AufgabenController_informUser.body_4" => " Veuillez vous connecter à BNote pour voir la tâche.",
			
			// AufgabenView
			"AufgabenView_construct.EntityName" => "Tâche",					
			"AufgabenView_construct.addEntityName" => "Nouvelle Tâche",
			"AufgabenView_startOptions.addGroupTask" => "Ajouter une tâche de groupe",
			"AufgabenView_startOptions.open" => "Afficher les tâches ouvertes",
			"AufgabenView_startOptions.completed" => "Afficher les tâches terminées",
			"AufgabenView_showAllTable.creator" => "Créé par",
			"AufgabenView_showAllTable.assignee" => "Personne en charge",
			"AufgabenView_process_addGroupTask.Message1" => "Tâche ajoutée", 
			"AufgabenView_process_addGroupTask.Message2" => "La tâche a été ajoutée à tous les membres des groupes sélectionnés.",	
			"AufgabenView_additionalViewButtons.open" => "Marquer comme ouvert",
			"AufgabenView_additionalViewButtons.complete" => "Marquer comme terminé",
			
			// module: Accommodation *********************************************
			
			//AccommodationData
			"AccommodationData_construct.id" => "Id",
			"AccommodationData_construct.tour" => "Tournée",
			"AccommodationData_construct.location" => "Lieu",
			"AccommodationData_construct.checkin" => "Enregistrement",
			"AccommodationData_construct.checkout" => "Départ",
			"AccommodationData_construct.breakfast" => "Petit-déjeuner",
			"AccommodationData_construct.lunch" => "Déjeuner",
			"AccommodationData_construct.dinner" => "Dîner",
			"AccommodationData_construct.planned_cost" => "Prix (pré-budgété)",
			"AccommodationData_construct.notes" => "Notes",
			
			// AccommodationView
			"AccommodationView_construct.EntityName" => "Hébergement",				
			"AccommodationView_viewDetailTable.locationname" => "Nom du lieu",
			"AccommodationView_viewDetailTable.tourname" => "Tournée",	
			"AccommodationView_showAllTable.locationname" => "Hébergement",
			
			// module: Appointment *********************************************
			
			// AppointmentData
			"AppointmentData_construct.id" => "ID",
			"AppointmentData_construct.begin" => "Début",
			"AppointmentData_construct.end" => "Fin",
			"AppointmentData_construct.name" => "Nom",
			"AppointmentData_construct.location" => "Lieu",
			"AppointmentData_construct.contact" => "Contact",
			"AppointmentData_construct.notes" => "Notes",
			
			// AppointmentView
			"AppointmentView_construct.EntityName" => "Date prévue",
			"AppointmentView_changeDefaultAddEntityForm.group" => "Invitations",
			"AppointmentView_startOptions.back" => "Retour",
			"AppointmentView_startOptions.addEntity" => "Ajouter un rendez-vous",
			"AppointmentView_view.begin" => "Période",
			"AppointmentView_view.locationname" => "Lieu",
			"AppointmentView_view.contactname" => "Interlocuteur",
			"AppointmentView_view.techname" => "Détails",
			"AppointmentView_view.notes" => "Notes",
			"AppointmentView_view.groups" => "Groupes invités",
			"AppointmentView_backToStart.back" => "Retour",
			"AppointmentView_changeDefaultEditEntityForm.group" => "Invitations",

			// module: Calendar *********************************************

			// CalendarData
			"CalendarData_construct.id" => "ID",
			"CalendarData_construct.begin" => "Début",
			"CalendarData_construct.end" => "Fin",
			"CalendarData_construct.name" => "Nom",
			"CalendarData_construct.location" => "lieu",
			"CalendarData_construct.contact" => "Contact",
			"CalendarData_construct.notes" => "Notes",
			"CalendarData_getEvents.rehearsal" => "Répétition",
			"CalendarData_getEvents.concert" => "Concert",
			"CalendarData_getEvents.end_vote" => "Fin du vote :",
			"CalendarData_getEvents.birthday" => "Anniversaire :",
			"CalendarData_getEvents.reservation" => "Réservation :",
			"CalendarData_getEvents.appointment" => "Rendez-vous",
			
			// CalendarView
			"CalendarView_construct.EntityName" => "Réservation",
			"CalendarView_viewDetailTable.id" => "N° de réservation",
			"CalendarView_viewDetailTable.contact" => "Contact",
			"CalendarView_startOptions.addEntity" => "Ajouter une réservation",
			"CalendarView_startOptions.appointments" => "Ajouter un rendez-vous",

			// module: CustomFields *********************************************

			// CustomFieldsData
			"CustomFieldsData_fieldTypes.BOOLEAN" => "Oui/Non",
			"CustomFieldsData_fieldTypes.INT" => "Entier",
			"CustomFieldsData_fieldTypes.DOUBLE" => "Décimale",
			"CustomFieldsData_fieldTypes.DATE" => "Date",
			"CustomFieldsData_fieldTypes.DATETIME" => "Date et heure ",
			"CustomFieldsData_fieldTypes.STRING" => "Chaîne de caractères",
			"CustomFieldsData_construct.id" => "ID",
			"CustomFieldsData_construct.techname" => "Nom technique",
			"CustomFieldsData_construct.txtdefsingle" => "Nom Singulier",
			"CustomFieldsData_construct.txtdefplural" => "Nom Pluriel",
			"CustomFieldsData_construct.fieldtype" => "Plage de valeurs",
			"CustomFieldsData_construct.otype" => "Référence de l'objet",
			"CustomFieldsData_construct.public_field" => "Publié",
			"CustomFieldsData_validate.BNoteError" => "Le nom technique existe déjà. Veuillez choisir un autre nom technique.",
			
			// CustomFieldsView
			"CustomFieldsView_construct.EntityName" => "Champs personnalisé",
			"CustomFieldsView_construct.addEntityName" => "Ajouter un champs personnalisé",
			"CustomFieldsView_start.Title" => "Champs personnalisés",
			"CustomFieldsView_start.back" => "Retour",
			"CustomFieldsView_backToStart.back" => "Retour",
			"CustomFieldsView_addEntityForm.fieldtype" => "Plage de valeurs",
			"CustomFieldsView_addEntityForm.otype" => "Référence de l'objet",			
			"CustomFieldsView_editEntityForm.fieldtype" => "Plage de valeurs",	
			"CustomFieldsView_editEntityForm.otype" => "Référence de l'objet",

			// module: Equipment *********************************************
			
			// EquipmentData
			"EquipmentData_construct.id" => "ID",
			"EquipmentData_construct.model" => "Modèle",
			"EquipmentData_construct.make" => "Marque",
			"EquipmentData_construct.name" => "Nom",				
			"EquipmentData_construct.purchase_price" => "Prix d'achat",
			"EquipmentData_construct.current_value" => "Valeur actuelle",
			"EquipmentData_construct.quantity" => "Quantité",
			"EquipmentData_construct.notes" => "Notes",	
											
			// EquipmentView
			"EquipmentView_construct.EntityName" => "Equipement",			
			"EquipmentView_construct.addEntityName" => "Ajouter un équipement",

			// module: Finance *********************************************

			// FinanceData
			"FinanceData_construct.id" => "N° de compte",
			"FinanceData_construct.name" => "Intitulé de compte",
			"FinanceData_getBookingTypes.type_0" => "Achat",
			"FinanceData_getBookingTypes.type_1" => "Dépense",
			"FinanceData_findBookingsMetrics.sum" => "Montant",
			"FinanceData_findBookingsMetrics.income" => "Recettes",
			"FinanceData_findBookingsMetrics.expenses" => "Dépenses",
			"FinanceData_transfer_same_account" => "Les comptes doivent être différents.",
			"FinanceData_transfer_note" => "Transférer %p à",
			
			// FinanceView
			"FinanceView_construct.EntityName" => "Finance",			
			"FinanceView_construct.addEntityName" => "Ajouter un nouveau compte",			
			"FinanceView_startOptions.recpay" => "Paiements courants",
			"FinanceView_startOptions.transfer" => "Transfert",
			"FinanceView_startOptions.multireporting" => "Rapport",
			"FinanceView_finance_filter_box.filter_items" => "Filtrer les affichages",
			"FinanceView_finance_filter_box.date_from" => "Date du",
			"FinanceView_finance_filter_box.date_to" => "au",
			"FinanceView_finance_filter_row.otype" => "Références",
			"FinanceView_finance_filter_row.bookings_filter" => "Filtre",
			"FinanceView_Table_booking.id" => "Nr.",
			"FinanceView_Table_booking.bdate" => "Type",
			"FinanceView_Table_booking.subject" => "Objet",
			"FinanceView_Table_booking.amount_net" => "Net",
			"FinanceView_Table_booking.amount_tax" => "Taxe",
			"FinanceView_Table_booking.amount_total" => "Brut",
			"FinanceView_Table_booking.booking_btype" => "Date",
			"FinanceView_Table_booking.otype" => "Type de référence",
			"FinanceView_Table_booking.oid" => "Références",
			"FinanceView_Table_booking.notes" => "Note",
			"FinanceView_Table_metrics.header" => "Résultats",
			"FinanceView_Table_metrics.btype" => "Type",
			"FinanceView_Table_metrics.amount_net" => "Net",
			"FinanceView_Table_metrics.amount_tax" => "Taxe",
			"FinanceView_Table_metrics.amount_total" => "Brut",
			"FinanceView_additionalViewButtons.addbooking" => "Ajouter une réservation",
			"FinanceView_additionalViewButtons.print" => "Imprimer",
			"FinanceView_addBooking.Form" => "Ajouter une réservation",
			"FinanceView_addBooking.btype" => "Type",
			"FinanceView_addBooking.otype" => "Type de référence",
			"FinanceView_addBooking.bdate" => "Date",
			"FinanceView_addBooking.subject" => "Objet",
			"FinanceView_addBooking.amount_net" => "Net",
			"FinanceView_addBooking.amount_tax" => "Taxe",
			"FinanceView_addBooking.notes" => "Notes",
			"FinanceView_addBookingProcess.title" => "Réservation sauvegardée.",
			"FinanceView_addBookingProcess.saved" => "L'inscription a été sauvegardée avec succès.",
			"FinanceView_transfer.Form" => "Transfert",
			"FinanceView_transfer.from" => "Du compte",
			"FinanceView_transfer.to" => "Par compte",
			"FinanceView_transfer.bdate" => "Date",
			"FinanceView_transfer.subject" => "objet",
			"FinanceView_transfer.amount_net" => "Net",
			"FinanceView_transfer.amount_tax" => "Taxe",
			"FinanceView_processTransfer.title" => "Transfert réussi",
			"FinanceView_processTransfer.message" => "Le report a été comptabilisé avec succès.",
			"FinanceView_multireport.items" => "Filtrer les affichages",
			"FinanceView_multireport.from" => "Date du",
			"FinanceView_multireport.to" => "Au",
			"FinanceView_multireport.oid" => "Références",
			"FinanceView_multireport.accounts" => "Comptes",
			"FinanceView_multireport.submit" => "Créer un rapport",
			"FinanceView_multireportResult.sum" => "Total",
			"FinanceView_multireportResult.title" => "Récapitulatif",
			"FinanceView_multireportResult.account" => "Compte",
			"FinanceView_multireportResult.in_total_net" => "Résultat net",
			"FinanceView_multireportResult.in_total_tax" => "TVA",
			"FinanceView_multireportResult.in_total" => "Revenu brut",
			"FinanceView_multireportResult.out_total_net" => "Charges Net",
			"FinanceView_multireportResult.out_total_tax" => "Taxe sur les intrants",
			"FinanceView_multireportResult.out_total" => "Dépenses Montant brut",
			"FinanceView_multireportResult.sum_net" => "∑ Revenus",
			"FinanceView_multireportResult.sum_tax" => "∑ Impôts et taxes",
			"FinanceView_multireportResult.sum_gross" => "∑ Brut",
			"FinanceView_multireportResultOptions.back" => "Retour",
			"FinanceView_multireportResultOptions.print" => "Imprimer",

			// module: Genre *********************************************

			// GenreData
			"GenreData_construct.id" => "ID",
			"GenreData_construct.name" => "Nom",
			"GenreData_delete.BNoteError" => "Le genre est utilisé dans une ou plusieurs chansons et ne peut donc pas être supprimé.",
			
			// GenreView
			"GenreView_construct.EntityName" => "Genre",
			"GenreView_construct.addEntityName" => "Ajouter un Genre",
			"GenreView_backToStart.Back" => "Retour",
			"GenreView_startOptions.Back" => "Retour",

			// module: Gruppen *********************************************

			// GruppenData
			"GruppenData_construct.id" => "ID",
			"GruppenData_construct.name" => "Nom",
			"GruppenData_construct.is_active" => "Aktiv",
			"GruppenData_delete.BNoteError_1" => "Dans ce groupe se trouvent ",
			"GruppenData_delete.BNoteError_2" => " contacts qui ne sont assignés à aucun autre groupe.
					   Veuillez modifier leur appartenance au groupe avant de pouvoir supprimer le groupe.",
			"GruppenData_delete.BNoteError_3" => "Le répertoire de groupe contient toujours des fichiers. Veuillez les supprimer de l'annuaire
					   pour qu'il puisse être effacé.",
						
			// GruppenView
			"GruppenView_construct.EntityName" => "Groupe",
			"GruppenView_construct.addEntityName" => "Ajouter un nouveau groupe",
			"GruppenView_start.Title" => "Groupes",
			"GruppenView_start.explanation" => "Sur cette page, vous gérez les groupes de votre groupe.
		                Les groupes \"Administrateurs\" et \"Membres\" ne peuvent pas être supprimés.
						D'autres groupes sont possibles, par exemple groupe de rythme, combo, etc.",
			"GruppenView_startOptions.Back" => "Retour",
			"GruppenView_startOptions.addEntity" => "Ajouter un groupe",
			"GruppenView_backToStart.Back" => "Retour",
			"GruppenView_view.Title" => "Groupe: ",
			"GruppenView_view.GroupMembers" => "Membres du groupe",
			"GruppenView_viewOptions.edit" => " éditer",
			"GruppenView_viewOptions.remove" => " supprimer",
			
			// module: Hilfe *********************************************	

			// HilfeData
			
			// HilfeView ***** Trouble with translation label ******
			"HilfeView_introPages.video" => "Vidéo",
			"HilfeView_introPages.bnote_news" => "Nouveautés dans BNote",
			"HilfeView_introPages.sicherheit" => "Instructions de sécurité",
			"HilfeView_introPages.support" => "Support / Contact",
			"HilfeView_helpPages.abstimmung" => "Module Vote",
			"HilfeView_helpPages.aufgaben" => "Module Tâches",
			"HilfeView_helpPages.equipment" => "Module Equipment",
			"HilfeView_helpPages.finance" => "Module Finance",
			"HilfeView_helpPages.konfiguration" => "Module Configuration",
			"HilfeView_helpPages.calendar" => "Module Calendrier",
			"HilfeView_helpPages.kontakte" => "Module Contact (et protection des données)",
			"HilfeView_helpPages.mitspieler" => "Module Membres",
			"HilfeView_helpPages.nachrichten" => "Module Message",
			"HilfeView_helpPages.proben" => "Module Répétitions",
			"HilfeView_helpPages.probenphase" => "Module Phase de Répétition",
			"HilfeView_helpPages.repertoire" => "Module Répertoire",
			"HilfeView_helpPages.share" => "Module Partage",
			"HilfeView_helpPages.tour" => "Module Voyage",

			// module: Instrumente *********************************************	
			
			// InstrumenteData
			"InstrumenteData_construct.id" => "ID",
			"InstrumenteData_construct.name" => "Nom",
			"InstrumenteData_construct.category" => "Catégorie",
			"InstrumenteData_delete.BNoteError" => "L'instrument ne peut pas être effacé car il est affecté à au moins un contact.",
			"InstrumenteView_viewDetailTable.categoryname" => "Catégorie",
			
			// InstrumenteView
			"InstrumenteView_construct.EntityName" => "Instrument",
			"InstrumenteView_start.Title" => "Configuration des instruments",
			"InstrumenteView_startOptions.start" => "Retour",
			"InstrumenteView_startOptions.addEntity" => "Ajouter un instrument",
			"InstrumenteView_startOptions.activeInstrumentGroups" => "Filtrer les instruments",
			"InstrumenteView_activeInstrumentGroups.Title" => "Instrument",
			"InstrumenteView_activeInstrumentGroups.Message" => "Vous pouvez définir ici les groupes d'instruments qui peuvent être affichés dans le registre.",
			"InstrumenteView_activeInstrumentGroups.Form" => "Groupe d'instruments actifs",						
			"InstrumenteView_activeInstrumentGroups.addElement" => "Catégorie",
			"InstrumenteView_activeInstrumentGroups.SubmitButton" => "Sauvegarder",
			"InstrumenteView_backToStart.back" => "Retour",		
			"InstrumenteView_process_activeInstrumentGroups.Message_1" => "Enregistrement des groupes d'instruments actifs",
			"InstrumenteView_process_activeInstrumentGroups.Message_2" => "Les nouveaux groupes d'instruments actifs ont été enregistrés.",		
			
			// module: Kommunikation *********************************************
			
			// KommunikationController
			"KommunikationController_prepareMail.begin" => " Heure",
			"KommunikationController_prepareMail.songs" => "<p>Veuillez répéter les morceaux suivants :</p><ul>\n",
			"KommunikationController_prepareMail.rehearsalSerie" => "Exemple de répétition : ",
			"KommunikationController_prepareMail.concert" => "Concert du",
			"KommunikationController_prepareMail.message_1" => "Am ",
			"KommunikationController_prepareMail.message_2" => " que ",
			"KommunikationController_prepareMail.message_3" => " Heure du concert ",
			"KommunikationController_prepareMail.message_4" => "de ",
			"KommunikationController_prepareMail.message_5" => " statt.\n",
			"KommunikationController_prepareMail.message_6" => "Vous trouverez plus de détails dans BNote.",
			"KommunikationController_prepareMail.subject" => "Vote: ",
			"KommunikationController_prepareMail.vote_message" => "Veuillez voter pour le vote sur BNote dans l'objet du message..",
			"KommunikationController_sendMail.error" => "Aucun destinataire trouvé.",

			// KommunikationView
			"KommunikationView_start.title" => "Message",
			"KommunikationView_startOptions.rehearsalMail" => "Message pour une répétitions",
			"KommunikationView_startOptions.rehearsalSerieMail" => "Message d'une série de répétitions",
			"KommunikationView_startOptions.concertMail" => "Message de concert",
			"KommunikationView_startOptions.voteMail" => "Message de vote",
			"KommunikationView_rehearsalMail.Title" => "Message pour une répétitions",
			"KommunikationView_rehearsalMail.addElement" => "Répétition",
			"KommunikationView_rehearsalSerieMail.Title" => "Message d'une série de répétitions",
			"KommunikationView_rehearsalSerieMail.addElement" => "Série de répétition",		
			"KommunikationView_concertMail.Title" => "Notification de Concert",
			"KommunikationView_concertMail.concert" => "Concert",
			"KommunikationView_voteMail.Title" => "Avis de vote",
			"KommunikationView_voteMail.Vote" => "Vote",
			"KommunikationView_createMailForm.recipient" => "Destinataire",
			"KommunikationView_createMailForm.subject" => "Objet",
			"KommunikationView_createMailForm.Message" => "Message",
			"KommunikationView_createMailForm.Submit" => "ENVOYER",
			"KommunikationView_start.message" => "L'envoi d'e-mails a été désactivé à des fins de démonstration. Vous pouvez cliquer sur 'ENVOYER'.",
			"KommunikationView_rehearsalMail.hour" => " Heure ",
			"KommunikationView_reportMailError.message_1" => "<strong>Erreur de courrier:</strong> Le courriel à <strong>",
			"KommunikationView_reportMailError.message_2" => "</strong> n'a pas pu être envoyé.",
			"KommunikationView_messageSent.message_1" => "Courriels envoyés",
			"KommunikationView_messageSent.message_2" => "Tous les courriels ont été envoyés avec succès.",
			"KommunikationView_concertMail.begin_1" => " Heure (",
			"KommunikationView_concertMail.begin_2" => " Heure",
			
			// module: Konfiguration *********************************************
			
			// KonfigurationData
			"KonfigurationData_construct.param" => "Paramètre",
			"KonfigurationData_construct.value" => "Valeur",
			"KonfigurationData_construct.is_active" => "Actif",
			"KonfigurationData_construct.rehearsal_start" => "Exemple au démarrage",
			"KonfigurationData_construct.rehearsal_duration" => "Durée de l'échantillon en min.",
			"KonfigurationData_construct.default_contact_group" => "Groupe par défaut",
			"KonfigurationData_construct.auto_activation" => "Activation automatique du compte utilisateur",
			"KonfigurationData_construct.share_nonadmin_viewmode" => "Mode de lecture partagée pour les non-administrateurs",
			"KonfigurationData_construct.rehearsal_show_length" => "Affichage de la longueur de l'échantillon",
			"KonfigurationData_construct.allow_participation_maybe" => "Peut-être que le nom de la partie est autorisé",
			"KonfigurationData_construct.allow_zip_download" => "Autoriser le téléchargement zip pour les dossiers",
			"KonfigurationData_construct.rehearsal_show_max" => "Nombre d'échantillons sur la page d'accueile",
			"KonfigurationData_construct.discussion_on" => "Autoriser les discussions",
			"KonfigurationData_construct.updates_show_max" => "Nombre de mises à jour sur la page d'accueil",
			"KonfigurationData_construct.language" => "Langue",
			"KonfigurationData_construct.default_country" => "Pays",
			"KonfigurationData_construct.google_api_key" => "Clé de l'API Google Maps",
			"KonfigurationData_construct.trigger_key" => "Clé d'interface de notification",
			"KonfigurationData_construct.trigger_cycle_days" => "Cycle de rappel (tous les X jours)",
			"KonfigurationData_construct.trigger_repeat_count" => "Nombre de rappels",
			"KonfigurationData_construct.enable_trigger_service" => "Notifications activées",
			"KonfigurationData_construct.default_conductor" => "Conducteur standard",			
			"KonfigurationData_replaceParameterValue.yes" => "Oui",
			"KonfigurationData_replaceParameterValue.no" => "Non",
			
			// KonfigurationView
			"KonfigurationView_construct.EntityName" => "Paramétrage",
			"KonfigurationView_start.warning" => "Veuillez cliquer sur une ligne pour modifier sa valeur.",
			"KonfigurationView_start.caption" => "Paramètre",
			"KonfigurationView_start.value" => "Valeur",
			"KonfigurationView_start.group" => "Valeur",	
			"KonfigurationView_start.conductor" => "Valeur",			
			"KonfigurationView_start.country" => "Pays",			
			"KonfigurationView_start.instruments" => "Instruments",
			"KonfigurationView_start.customfields" => "Champs personnalisées",
			"KonfigurationView_showWarnings.Warnings" => "Clé de l'API Google Maps non définie.",
			"KonfigurationView_edit.header" => "Paramétrage",
			"KonfigurationView_editEntityForm.message" => "Chaque nouvel utilisateur enregistré est affecté à ce groupe.",
			"KonfigurationView_edit_process.message_1" => " modifié",
			"KonfigurationView_edit_process.message_2" => "L'entrée a été modifiée avec succès.",
			
			// module: Kontaktdaten *********************************************
			
			// KontaktdatenData
			"KontaktdatenData_updatePassword.BNoteError" => "Les mots de passe ne correspondent pas !",
			
			// KontaktdatenView
			"KontaktdatenView_start.message" => "Aucun contact n'a été attribué à votre utilisateur.",			
			"KontaktdatenView_start.Form" => "Modifier les données personnelles",
			"KontaktdatenView_startOptions.changePassword" => "Modifier le mot de passe",
			"KontaktdatenView_startOptions.settings" => "Mes paramètres",
			"KontaktdatenView_savePD.Message_1" => "Données sauvegardées",
			"KontaktdatenView_savePD.Message_2" => "Les modifications ont été sauvegardées.",
			"KontaktdatenView_changePassword.Message" => "Veuillez entrer au moins 6 caractères et aucun espace pour changer votre mot de passe.",
			"KontaktdatenView_changePassword.Form" => "<br Changer de mot de passe<br/>",
			"KontaktdatenView_changePassword.New" => "Nouveau mot de passe",
			"KontaktdatenView_changePassword.Repeat" => "Confirmer le mot de passe",
			"KontaktdatenView_password.Message_1" => "Le mot de passe a été changé",
			"KontaktdatenView_password.Message_2" => "Le mot de passe a été modifié.<br />A partir de maintenant, veuillez vous connecter avec un nouveau mot de passe.",
			"KontaktdatenView_settings.saveSettings" => "Modifier les paramètres",
			"KontaktdatenView_settings.email_notification" => "Notification par courriel à",
			"KontaktdatenView_saveSettings.Message_1" => "Paramètres sauvegardés",
			"KontaktdatenView_saveSettings.Message_2" => "Vos Paramètres ont été verrouillés.",	
			
			// module: Kontakte *********************************************	
			
			// KontakteData
			"KontakteData_construct.id" => "ID",
			"KontakteData_construct.surname" => "Nom",
			"KontakteData_construct.name" => "Prénom",
			"KontakteData_construct.nickname" => "Surnom",
			"KontakteData_construct.company" => "Organisation",
			"KontakteData_construct.phone" => "Téléphone",
			"KontakteData_construct.fax" => "Fax",
			"KontakteData_construct.mobile" => "Portable",
			"KontakteData_construct.business" => "Entreprises",
			"KontakteData_construct.email" => "Courriel",
			"KontakteData_construct.web" => "Web",
			"KontakteData_construct.notes" => "Notes",
			"KontakteData_construct.address" => "Adresse",
			"KontakteData_construct.instrument" => "Instrument",
			"KontakteData_construct.is_conductor" => "Chef d'orchestre",
			"KontakteData_construct.birthday" => "Anniversaire",
			"KontakteData_construct.status" => "Statut",	

			// KontakteController				
			"KontakteController_createUserAccount.subject" => "Information de connexion ",
			"KontakteController_createUserAccount.message_1" => "Vous pouvez maintenant aller à ",
			"KontakteController_createUserAccount.message_2" => " s'inscrire.\n\n",
			"KontakteController_createUserAccount.message_3" => "Votre nom d'utilisateur est ",
			"KontakteController_createUserAccount.message_4" => " et votre ",
			"KontakteController_createUserAccount.message_5" => "Le mot de passe est ",
			"KontakteController_integrate.message_1" => "Echec de la relation",
			"KontakteController_integrate.message_2" => "La relation R",
			"KontakteController_integrate.message_3" => "ne peut pas être défini.",
			"KontakteController_integrate.message_4" => "La relation RP",
			"KontakteController_integrate.message_5" => "La relation C",
			"KontakteController_integrate.message_6" => "La relation V",
			"KontakteController_integrate.message_7" => "Tâches sauvegardées",
			"KontakteController_integrate.message_8" => "Les affectations ont été sauvegardées..",
			"KontakteController_importVCard.cards" => " Les notices ont été importées.",
			"KontakteController_gdprSendMail.subject" => "Déclaration de consentement DSGVO",
			"KontakteController_gdprSendMail.message" => "Examiner et approuver",
			"KontakteController_gdprSendMail.error" => "Le(s) message(s) n'a (n'ont) pas pu être envoyé(s). Veuillez contacter l'administrateur.",
			"KontakteController_gdprSendMail.newmessage_1" => "Courriels envoyés",
			"KontakteController_gdprSendMail.newmessage_2" => "Des messages ont été transmis aux contacts.",

			// KontakteView		
			"KontakteView_construct.EntityName" => "Contact",
			"KontakteView_construct.addEntityName" => "Ajouter un nouveaux contact",
			"KontakteView_startOptions.integration" => "Intégration progressive",
			"KontakteView_startOptions.players" => "Groupes",
			"KontakteView_startOptions.selectPrintGroups" => "Imprimer la liste",
			"KontakteView_startOptions.contactImport" => "Import (vCard)",
			"KontakteView_startOptions.contactExport" => "Export (vCard)",
			"KontakteView_startOptions.gdprOk" => "Confidentialité des données",			
			"KontakteView_showContactTable.sEmptyTable" => "Aucune entrée n'a été trouvée.",
			"KontakteView_showContactTable.sInfoEmpty" => "Aucune entrée n'a été trouvée.",
			"KontakteView_showContactTable.sZeroRecords" => "Aucune entrée n'a été trouvée.",
			"KontakteView_showContactTable.sSearch" => "Filtre :",
			"KontakteView_addOptions.addEntity" => "Ajouter un autre contact",
			"KontakteView_printMembersOptions.print" => "Imprimer",
			"KontakteView_userCreatedAndMailed.Message_1" => "Les informations de connexion ont été envoyées à ",
			"KontakteView_userCreatedAndMailed.Message_2" => " .",
			"KontakteView_integration.title" => "L'arrivée progressive de nouveaux membres",
			"KontakteView_integration.text" => "Sélectionnez d'abord les membres que vous souhaitez introduire progressivement.
				Si vous ne voyez pas tous les contacts, sélectionnez-les dans la synthèse principale (écran précédent),
				sélectionnez d'abord le groupe <i>correct</i> puis sélectionnez <i>phase</i>.
				Cliquez sur toutes les entrées que vous voulez attribuer à ces membres.
				Enfin, cliquez sur le bouton Enregistrer pour enregistrer les affectations.",
			"KontakteView_integration.member" => "Membres",
			"KontakteView_integration.rehearsal" => "Répétitions",
			"KontakteView_integration.rehearsalphase" => "Phase de répétitions",
			"KontakteView_integration.concert" => "Concert",
			"KontakteView_integration.vote" => "Vote",
			"KontakteView_integration.save" => "Sauvegarder",
			"KontakteView_gdprReportOptions.print" => "Imprimer",
			"KontakteView_gdprReport.title_1" => "Extrait des données",
			"KontakteView_gdprReport.message_1" => "Créé le: ",
			"KontakteView_gdprReport.message_2" => " par ",
			"KontakteView_gdprReport.title_2" => "Informations personnelles",
			"KontakteView_gdprReport.title_3" => "Votes",
			"KontakteView_gdprReport.message_3" => "La personne a pris part aux votes suivants :",
			"KontakteView_gdprReport.message_4" => "Les données ont été collectées et traitées dans le but d'évaluer le résultat du vote.",
			"KontakteView_gdprReport.message_5" => "La personne était responsable des tâches suivantes :",
			"KontakteView_gdprReport.title_4" => "Tâches",
			"KontakteView_gdprReport.message_6" => "Les données ont été collectées et traitées dans le but d'assigner des tâches aux membres.",
			"KontakteView_gdprReport.title_5" => "Concert",
			"KontakteView_gdprReport.message_7" => "La personne a été invitée pour les représentations suivantes :",
			"KontakteView_gdprReport.message_8" => "La personne a enregistré sa présence pour les représentations suivantes :",
			"KontakteView_gdprReport.message_9" => "Aux fins de l'organisation des représentations, l'enquête sur les présences et l'invitation aux représentations ont été enregistrées et traitées.",
			"KontakteView_gdprReport.title_6" => "Répétition",
			"KontakteView_gdprReport.message_10" => "La personne a été invitée aux répétitions suivantes :",
			"KontakteView_gdprReport.message_11" => "La personne a indiqué sa participation aux répétitions suivantes :",
			"KontakteView_gdprReport.message_12" => "La personne a été invitée aux phases de répétition suivantes :",
			"KontakteView_gdprReport.title_7" => "Voyage",
			"KontakteView_gdprReport.message_13" => "La personne a été invitée à participer aux visites suivantes :",
			"KontakteView_gdprOk.title" => "Déclaration de consentement",
			"KontakteView_gdprOk.message" => "Le tableau suivant montre les contacts et leur statut de consentement :",
			"KontakteView_getGdprOk.title" => "Consentement des contacts",
			"KontakteView_getGdprOk.message" => "Tous les utilisateurs qui peuvent se connecter au système doivent être invités à ouvrir une session dans un courriel interne.
				Après s'être connecté, les utilisateurs doivent accepter la politique de confidentialité. Les contacts externes sont notifiés par le message suivant
				a demandé leur consentement au traitement des données à caractère personnel. Le message est :",
			"KontakteView_getGdprOkOptions.gdprSendMail" => "Envoyer des mails à des contacts externes",
			"KontakteView_gdprNOK.message" => "Les données des utilisateurs sans leur consentement ont été effacées.",
			"KontakteView_gdprOkOptions.getGdprOk" => "Demande de consentement",
			"KontakteView_gdprOkOptions.gdprNOK" => "Supprimer des contacts sans autorisation",
			"KontakteView_showContactTable.all" => "Tous les contacts",
			"KontakteView_showContactTable.name" => "Nom, prénom",
			"KontakteView_showContactTable.music" => "Musique",
			"KontakteView_showContactTable.adress" => "Adresse",
			"KontakteView_showContactTable.phone" => "Téléphone",
			"KontakteView_showContactTable.online" => "En ligne",
			"KontakteView_showContactTable.no" => "non",
			"KontakteView_showContactTable.yes" => "oui",
			"KontakteView_showContactTable.instrumentname" => "Instrument: ",
			"KontakteView_showContactTable.is_conductor" => "Chef d'orchestre: ",
			"KontakteView_showContactTable.no_entries" => "Aucune information de contact disponible.",
			"KontakteView_view.title_1" => "Données principales",
			"KontakteView_view.id" => "ID du contact",
			"KontakteView_view.status" => "statut",
			"KontakteView_view.name" => "Prénom et nom de famille",
			"KontakteView_view.nickname" => "Surnom",
			"KontakteView_view.company" => "Organisation",
			"KontakteView_view.birthday" => "Date de naissance",
			"KontakteView_view.adresse" => "Adresse",
			"KontakteView_view.title_2" => "Communication",
			"KontakteView_view.phone" => "Téléphone personnel",
			"KontakteView_view.business" => "Téléphone professionnel",
			"KontakteView_view.mobile" => "Portable",
			"KontakteView_view.fax" => "Fax",
			"KontakteView_view.email" => "Adresse de courriel",
			"KontakteView_view.web" => "Page Internet",
			"KontakteView_view.title_3" => "Informations musicales",
			"KontakteView_view.instrumentname" => "Instrument",
			"KontakteView_view.is_conductor" => "Chef d'orchestre",
			"KontakteView_view.no" => "non",
			"KontakteView_view.yes" => "oui",
			"KontakteView_view.groups" => "Groupes",
			"KontakteView_additionalViewButtons.user" => "Créer un compte utilisateur",
			"KontakteView_additionalViewButtons.question" => "extrait de données",
			"KontakteView_editEntityForm.Form" => "Editer le contact",
			"KontakteView_editEntityForm.group" => "Groupes",
			"KontakteView_groupSelectionCheck.error" => "Veuillez assigner au moins un groupe au contact.",
			"KontakteView_addEntity.group" => "Groupes",
			"KontakteView_gdprReport.instrumentname" => "Instrument",
			"KontakteView_gdprReport.street" => "Rue",
			"KontakteView_gdprReport.city" => "Ville",
			"KontakteView_gdprReport.zip" => "Code postale",
			"KontakteView_showContactTable.phone" => "Tél: ",
			"KontakteView_showContactTable.mobile" => "Portable: ",
			"KontakteView_showContactTable.business" => "Professionnel: ",
			"KontakteView_addEntity.noinstrument" => "[non spécifié]",
			"KontakteView_selectPrintGroups.title" => "Imprimer la liste des membres",
			"KontakteView_selectPrintGroups.message" => "Tous les membres sont triés par groupes. Veuillez sélectionner les groupes dont vous souhaitez imprimer les membres.",
			"KontakteView_selectPrintGroups.form" => "Sélection de pression",
			"KontakteView_selectPrintGroups.txtdefsingle" => "Afficher le champ",
			"KontakteView_selectPrintGroups.group" => "Filtre : Groupe",
			"KontakteView_selectPrintGroups.submit" => "Afficher l'aperçu avant impression",
			"KontakteView_printMembers.message_1" => "Erreur lors de la sélection de groupe",
			"KontakteView_printMembers.message_2" => "Sélectionnez au moins un groupe à imprimer.",
			"KontakteView_userCreatedAndMailed.Message_3" => "Utilisateur",
			"KontakteView_userCreatedAndMailed.Message_4" => "créé",
			"KontakteView_userCredentials.Message_1" => "<br />Les données d'accès n'ont pas pu être fournies à l'utilisateur. ",
			"KontakteView_userCredentials.Message_2" => "parce qu'il n'y a pas d'adresse e-mail stockée ou que l'e-mail n'est pas ",
			"KontakteView_userCredentials.Message_3" => "pourrait être expédié. Veuillez dire à l'utilisateur ce qui suit ",
			"KontakteView_userCredentials.Message_4" => "Accédez aux données avec:<br /><br />",
			"KontakteView_userCredentials.Message_5" => "Nom d'utilisateur <strong>",
			"KontakteView_userCredentials.Message_6" => "Mot de passe",
			"KontakteView_userCredentials.Message_7" => "Utilisateur ",
			"KontakteView_userCredentials.Message_8" => " créé",
			"KontakteView_contactImport.form" => "Importation des données de contact",
			"KontakteView_contactImport.group" => "Importer dans le groupe",
			"KontakteView_contactImport.vcdfile" => "Fichier VCard",
			"KontakteView_importVCardSuccess.message" => "Importation VCard",
			"KontakteView_gdprOk.gdpr_ok" => "Autorisation",
			
			// module: Konzerte *********************************************

			// KonzerteData
			"KonzerteData_construct.id" => "ID de Concert",
			"KonzerteData_construct.title" => "Titre",
			"KonzerteData_construct.begin" => "Début",
			"KonzerteData_construct.end" => "Fin",
			"KonzerteData_construct.approve_until" => "Engagements jusqu'au",
			"KonzerteData_construct.meetingtime" => "Heure de rendez-vous",
			"KonzerteData_construct.organizer" => "Organisateur",
			"KonzerteData_construct.location" => "Lieu",
			"KonzerteData_construct.accommodation" => "Hébergement",
			"KonzerteData_construct.program" => "Programme",
			"KonzerteData_construct.contact" => "Contact",
			"KonzerteData_construct.outfit" => "Tenue",
			"KonzerteData_construct.notes" => "Notes",
			"KonzerteData_construct.payment" => "Cachet",
			"KonzerteData_construct.conditions" => "Conditions générales",
			"KonzerteData_create.error" => "Au moins un groupe (casting) doit être sélectionné.",
			
			// KonzerteView
			"KonzerteView_construct.addEntityName" => "Ajouter un nouveaux concert",
			"KonzerteView_start.Message" => "Pour visualiser ou éditer un concert, veuillez cliquer sur la performance correspondante.",
			"KonzerteView_start.Next" => "Prochain Concert",
			"KonzerteView_start.More" => "Concert planifié",
			"KonzerteView_startOptions.programs" => "Gestion des programmes",
			"KonzerteView_startOptions.history" => "Historique",
			"KonzerteView_viewInvitations.title" => "Contacts invités",
			"KonzerteView_viewInvitations.fullname" => "Nom",
			"KonzerteView_viewInvitations.nickname" => "Surnom",
			"KonzerteView_viewInvitations.phone" => "Téléphone",
			"KonzerteView_viewInvitations.mobile" => "Portable",
			"KonzerteView_showParticipants.title_1" => "Participant",
			"KonzerteView_showParticipants.participate" => "Participe",
			"KonzerteView_showParticipants.reason" => "Motif",
			"KonzerteView_showParticipants.category" => "Groupe",
			"KonzerteView_showParticipants.nickname_1" => "Surnom",
			"KonzerteView_showParticipants.title_2" => "Engagements/rejets en cours",
			"KonzerteView_showParticipants.nickname_2" => "Surnom",
			"KonzerteView_showParticipants.fullname" => "Nom",
			"KonzerteView_showParticipants.phone" => "Téléphone",
			"KonzerteView_showParticipants.mobile" => "Portable",
			"KonzerteView_addEntityForm.flash_1" => "Avant de créer un nouveau concert, veuillez créer toutes les données de contact (contacts) et les emplacements.",
			"KonzerteView_addEntityForm.flash_2" => "Info",
			"KonzerteView_history.from" => "Période du",
			"KonzerteView_history.to" => "Période jusqu'au",
			"KonzerteView_history.id" => "N°",
			"KonzerteView_history.location_name" => "Lieu de représentation",
			"KonzerteView_history.location_city" => "Ville",
			"KonzerteView_history.contact_name" => "Point de contact",
			"KonzerteView_history.program_name" => "Programme",
			"KonzerteView_view.title" => "Evénement",
			"KonzerteView_view.organizer" => "Organisateur",			
			"KonzerteView_view.location" => "Lieu",
			"KonzerteView_view.contact" => "Contact",			
			"KonzerteView_view.periods" => "Période",			
			"KonzerteView_view.date" => "Date/Heure",
			"KonzerteView_view.place" => "Lieu de rendez-vous",
			"KonzerteView_view.till" => "Engagement jusqu'au",
			"KonzerteView_view.organisation" => "Organisation",
			"KonzerteView_view.occupation" => "Occupation",
			"KonzerteView_view.program" => "Programme",
			"KonzerteView_view.Outfit" => "Tenue",
			"KonzerteView_view.equipment" => "Equipement",
			"KonzerteView_view.details" => "Détails",
			"KonzerteView_view.accommodation" => "Hébergement",
			"KonzerteView_view.payment" => "Cachet",
			"KonzerteView_view.conditions" => "Modalités et conditions",
			"KonzerteView_addEntityForm.title" => "Titre",
			"KonzerteView_addEntityForm.begin" => "Début",
			"KonzerteView_addEntityForm.copyDateTarget" => "Fin",
			"KonzerteView_addEntityForm.meetingtime_from" => "Heure du rendez-vous",
			"KonzerteView_addEntityForm.meetingtime_to" => "Engagements jusqu'au",
			"KonzerteView_addEntityForm.notes" => "Notes",						
			"KonzerteView_addEntityForm.title" => "Concert",						
			"KonzerteView_addEntityForm.location" => "Lieu",
			"KonzerteView_addEntityForm.organizer" => "Organisateur",
			"KonzerteView_addEntityForm.contact" => "Contact",
			"KonzerteView_addEntityForm.accommodation" => "Hébergement",
			"KonzerteView_addEntityForm.title_location" => "Emplacement et organisateur",						
			"KonzerteView_addEntityForm.group" => "Occupation",
			"KonzerteView_addEntityForm.group" => "Programme à partir du modèle",
			"KonzerteView_addEntityForm.equipment" => "Equipement",
			"KonzerteView_addEntityForm.outfit" => "Tenue",
			"KonzerteView_addEntityForm.group_title" => "Organisation",						
			"KonzerteView_addEntityForm.payment" => "Cachet",
			"KonzerteView_addEntityForm.conditions" => "Conditions générales",
			"KonzerteView_addEntityForm.details_title" => "Détails",						
			"KonzerteView_viewPhases.title" => "phase de répétition",
			"KonzerteView_viewPhases.Begin" => "de",
			"KonzerteView_viewPhases.end" => "jusqu'à",
			"KonzerteView_viewPhases.notes" => "Notes",
			"KonzerteView_writeConcert.title" => " Hour / ",
			
			// module: Locations *********************************************

			// LocationsData
			"LocationsData_construct.id" => "ID",
			"LocationsData_construct.name" => "Nom",
			"LocationsData_construct.notes" => "Notes",
			"LocationsData_construct.address" => "Adresse",
			"LocationsData_construct.location_type" => "Lieu Type d'emplacement",
			
			// LocationsView
			"LocationsView_construct.addEntityName" => "Ajouter un nouveau Lieu",	
			"LocationsView_showAllTable.title" => "Tous",
			
			// module: Login *********************************************

			// LoginData
			"LoginData_construct.id" => "ID utilisateur",
			"LoginData_construct.login" => "Identifiant",
			"LoginData_construct.password" => "Mot de passe",
			"LoginData_construct.realname" => "Nom",
			"LoginData_construct.lastlogin" => "Dernière connexion",

			// LoginController
			"LoginController_doLogin.message" => "Le mot de passe saisi est haché comme suit ",
			"LoginController_doLogin.error" => "Veuillez vérifier vos informations d'identification.<br />
						Si ce message réapparaît, veuillez contacter votre chef d'orchestre.<br />>
						<a href=\"?mod=login\">Retour</a><br />",
			"LoginController_pwForgot.error" => "Votre adresse e-mail n'est pas connue du système ou existe plusieurs fois. Veuillez contacter votre chef d'orchestre.",
			"LoginController_pwForgot.subject" => "Nouveau mot de passe",
			"LoginController_pwForgot.body_1" => "Votre nom d'utilisateur est: ",
			"LoginController_pwForgot.body_2" => "Votre nouveau mot de passe est: ",
			"LoginController_pwForgot.sendMailerror" => "Malheureusement, l'e-mail n'a pas pu vous être envoyé. <br />>
					Veuillez contacter votre chef.",
			"LoginController_pwForgot.message_1" => "Le mot de passe a été changé",
			"LoginController_pwForgot.message_2" => "Le mot de passe vient de vous être envoyé.",
			"LoginController_register.error_1" => "Veuillez accepter les conditions d'utilisation.",
			"LoginController_register.error_2" => "Le nom d'utilisateur est déjà utilisé.",
			"LoginController_register.error_3" => "Veuillez vérifier votre mot de passe.",
			"LoginController_register.outMsg" => "Vous vous êtes inscrit(e) avec succès",
			"LoginController_register.writeOutput" => "Inscription terminée",
			"LoginController_register.subject" => "Activation BNote",
			"LoginController_register.message_1" => "Veuillez cliquer sur le lien suivant pour activer votre compte:\n",
			"LoginController_register.message_2" => "Activation",
			"LoginController_register.message_3" => "Malheureusement, un <b>Erreur</b> s'est produit pendant l'activation. Veuillez contacter votre chef d'orchestre pour l'activation.<br/>",
			"LoginController_register.message_4" => "Veuillez vérifier vos courriels. Cliquez sur le lien d'activation pour confirmer votre compte. Ensuite, vous pouvez vous connecter.<br/>",
			"LoginController_register.message_5" => "Veuillez contacter votre chef d'orchestre et attendre que votre compte soit activé.<br/>",	
			
			// LoginView
			"LoginView_showOptions.BNote-App" => "Version portable",
			"LoginView_showOptions.login" => "Identifiant",
			"LoginView_showOptions.forgotPassword" => "Mot de passe oublié ?",
			"LoginView_showOptions.registration" => "Inscription",
			"LoginView_showOptions.terms" => "Protection de la vie privée",
			"LoginView_showOptions.impressum" => "Mentions légales",
			"LoginView_login.message_1" => "Veuillez vous connecter pour utiliser BNote. Si vous n'avez pas de
				de compte veuillez vous <a href=\"?mod=registration\">enregistrer</a>.",
			"LoginView_login.message_2" => "Si vous ne pouvez pas vous connecter à plusieurs reprises,
				alors votre compte n'est peut-être pas encore activé. S'il vous plaît
				réessayer plus tard.",	
			"LoginView_login.Form" => "Connexion et inscription",
			"LoginView_login.login" => "Identifiant <br/> ou adresse de courriel",
			"LoginView_login.password" => "Mot de passe",
			"LoginView_forgotPassword.title" => "Mot de passe oublié ?",
			"LoginView_forgotPassword.message" => "Veuillez entrer votre adresse de courriel et le système vous enverra un nouveau mot de passe par e-mail.",
			"LoginView_forgotPassword.email" => "Adresse de courriel",
			"LoginView_registration.title" => "Inscription",
			"LoginView_registration.logintext" => "Veuillez remplir ce formulaire pour vous inscrire en tant que membre. Les données fournies seront traitées confidentiellement et ne seront pas transmises à des tiers.",
			"LoginView_registration.first_name" => "* Prénom",
			"LoginView_registration.surname" => "* Nom de famille",
			"LoginView_registration.phone" => "Téléphone",
			"LoginView_registration.email" => "* Courriel",
			"LoginView_registration.street" => "* Rue",
			"LoginView_registration.zip" => "* Code postal",
			"LoginView_registration.city" => "* Ville",
			"LoginView_registration.instrument" => "Instrument",
			"LoginView_registration.login" => "* Identifiant",
			"LoginView_registration.login_text" => "Caractères autorisés : Lettres, chiffres, points,
				trait d'union, tiret",
			"LoginView_registration.pw1" => "* Mot de passe",
			"LoginView_registration.password_text" => "Veuillez entrer au moins 6 caractères et sans espaces.",
			"LoginView_registration.pw2" => "* Répéter le Mot de passe",
			"LoginView_registration.terms_1" => "* Je suis d'accord avec les ",
			"LoginView_registration.terms_2" => "conditions générales d'utilisation", 
			"LoginView_registration.terms_3" => ".", 
			"LoginView_registration.message" => "* Les champs marqués d'un astérisque sont obligatoires.",
			"LoginView_registration.register" => "S'inscrire",
			"LoginView_extGdpr.title" => "Ordonnance de base sur la protection des données (Ordonnance sur la protection des données) : autorise",
			"LoginView_extGdpr.error" => "Veuillez vous assurer que vous avez cliqué sur le bon lien.",
			"LoginView_extGdpr.message_1" => "Merci",
			"LoginView_extGdpr.message_2" => "C'est fait. Merci de votre accord !",
			"LoginView_extGdpr.codeerror" => "Code inconnu.",
			"LoginView_extGdpr.codemessage" => "Nous vous demandons votre consentement pour traiter les données suivantes conformément à <a href=\"?mod=terms\">notre politique de confidentialité </a> :",
			"LoginView_extGdpr.name" => "Nom",
			"LoginView_extGdpr.nickname" => "Surnom",
			"LoginView_extGdpr.phone" => "Téléphone",
			"LoginView_extGdpr.fax" => "Fax",
			"LoginView_extGdpr.mobile" => "Mobile",
			"LoginView_extGdpr.business" => "Professionnel",
			"LoginView_extGdpr.email" => "Courriel",
			"LoginView_extGdpr.web" => "Page internet",
			"LoginView_extGdpr.street" => "Addresse",
			"LoginView_extGdpr.birthday" => "Date de naissance",
			"LoginView_extGdpr.link" => "Accepté",
			
			// module: Mitspieler *********************************************

			// MitspielerData	
			"MitspielerData_construct.id" => "ID",
			"MitspielerData_construct.surname" => "Nom",
			"MitspielerData_construct.name" => "Prénom",
			"MitspielerData_construct.nickname" => "Surnom",
			"MitspielerData_construct.company" => "Organisation",
			"MitspielerData_construct.phone" => "Téléphone",
			"MitspielerData_construct.fax" => "Fax",
			"MitspielerData_construct.mobile" => "Portable",
			"MitspielerData_construct.business" => "Entreprises",
			"MitspielerData_construct.email" => "Courriel",
			"MitspielerData_construct.address" => "Adresse",
			"MitspielerData_construct.instrument_name" => "Instrument",
			"MitspielerData_construct.birthday" => "Date de naissance",
			"MitspielerData_construct.status" => "Statut",
			"MitspielerData_construct.city" => "Ville",
			"MitspielerData_construct.zip" => "Code postale",
			
			// MitspielerView		
			"MitspielerView_start.title" => "Membres",
			"MitspielerView_start.message" => "Utilisez la combinaison de touches CTRL+F (Mac : CMD+F) pour rechercher sur cette page.",
			"MitspielerView_startOptions.print" => "Imprimer",
			
			// module: Nachrichten *********************************************

			// NachrichtenData				
			"NachrichtenData_check.error" => "Le contenu du message n'est pas sécurisé. Veuillez ne pas utiliser de cadres et de scripts.",
			
			// NachrichtenView	
			"NachrichtenView_start.form" => "Actualités",
			"NachrichtenView_start.Submit" => "Sauvegarder",
			"NachrichtenView_save.message_1" => "Message sauvegardé",
			"NachrichtenView_save.message_2" => "Le message saisi a été enregistré.",
			
			// module: Outfits *********************************************

			// OutfitsData
			"OutfitsData_construct.id" => "ID",
			"OutfitsData_construct.name" => "Nom",
			"OutfitsData_construct.description" => "Description",
			
			// OutfitsView
			"OutfitsView_construct.EntityName" => "Tenue",				
			"OutfitsView_construct.addEntityName" => "Nouvelle tenue",

			// module: Proben *********************************************

			// ProbenData			
			"ProbenData_construct.id" => "Numéro de répétition",
			"ProbenData_construct.begin" => "Début",
			"ProbenData_construct.end" => "Fin",
			"ProbenData_construct.approve_until" => "Engagements jusqu'au",
			"ProbenData_construct.location" => "Lieu",
			"ProbenData_construct.conductor" => "Chef d'orchestre",
			"ProbenData_construct.serie" => "Série de répétition",
			"ProbenData_construct.notes" => "Notes",
			"ProbenData_saveSerie.error" => "La dernière répétition est avant la première.",
			
			// ProbenView
			"ProbenView_construct.EntityName" => "Répétition",
			"ProbenView_construct.addEntityName" => "Ajouter une nouvelle répétition",	
			"ProbenView_start.text" => "Veuillez cliquer sur une répétition pour l'éditer.",
			"ProbenView_start.title" => "Prochaine répétition",
			"ProbenView_start.norehearsal" => "Aucune répétition annoncée.",
			"ProbenView_start.title_2" => "Autres répétition",
			"ProbenView_startOptions.overtime" => "Ajouter des occurences de répétition",
			"ProbenView_startOptions.edit" => "Modifier des occurences de répétition",
			"ProbenView_startOptions.mitspieler" => "Synthèse des participants",
			"ProbenView_startOptions.timer" => "Voir les répétitions précédentes",
			"ProbenView_addEntity.message_1" => "Aucun emplacement existant", 
			"ProbenView_addEntity.message_2" => "Avant de pouvoir créer une répétition, veuillez créer un emplacement.",
			"ProbenView_addEntity.begin" => "Début",
			"ProbenView_addEntity.end" => "Fin",
			"ProbenView_addEntity.duration" => "Durée en min.",
			"ProbenView_addEntity.location" => "Lieu",
			"ProbenView_addEntity.approve_until" => "Engagements jusqu'au",
			"ProbenView_addEntity.conductor" => "Chef d'orchestre",
			"ProbenView_addEntity.notes" => "Notes",
			"ProbenView_addEntity.groups" => "Répétition pour",
			"ProbenView_add.message_1" => " sauvegarder",
			"ProbenView_add.message_2" => "La répétition a été sauvegardé avec succès.",
			"ProbenView_add.rehearsalMail" => "Envoyer un avis de répétition aux membres",
			"ProbenView_addSerie.Form" => "Ajouter une série de répétition",
			"ProbenView_addSerie.name" => "Exemple de répétition",
			"ProbenView_addSerie.first_session" => "Première répétition",
			"ProbenView_addSerie.last_session" => "Dernière répétition le",
			"ProbenView_addSerie.cycle_1" => "Hebdomadaire",
			"ProbenView_addSerie.cycle_2" => "Bimensuel",
			"ProbenView_addSerie.cycle" => "Cycle",
			"ProbenView_addSerie.default_time" => "Heure",
			"ProbenView_addSerie.duration" => "Durée en min.",
			"ProbenView_addSerie.location" => "Lieu",
			"ProbenView_addSerie.Conductor" => "Chef d'orchestre",
			"ProbenView_addSerie.notes" => "Notes",
			"ProbenView_addSerie.group" => "Répétition pour",
			"ProbenView_processSerie.message_1" => "Exemple de chemin d'accès stocké",
			"ProbenView_processSerie.message_2" => "Toutes les répétitions ont été créés avec succès.",
			"ProbenView_processSerie.error" => "Les séries de répétition n'ont pas pu être traitées.",
			"ProbenView_editSerie.Form" => "Modifier la section des échantillons",
			"ProbenView_editSerie.serieSelector" => "Série",
			"ProbenView_editSerie.update_begin" => "Début de la mise à jour",
			"ProbenView_editSerie.begin" => "Début",
			"ProbenView_editSerie.update_location" => "Mise à jour de l'emplacement",
			"ProbenView_editSerie.locationSelector" => "Emplacement",
			"ProbenView_editSerie.delete" => "Distance et répétition <u>éteindre</u>",
			"ProbenView_processEditSerie.message_1" => "Mis à jour de la séries de répétition",
			"ProbenView_processEditSerie.message_2" => "La séries de répétition a été mis à jour avec succès.",
			"ProbenView_writeRehearsalList.message" => "Plus de répétitions prévues.",
			"ProbenView_participants.title_1" => "Instruments",
			"ProbenView_participants.title_2" => "Participation",
			"ProbenView_participants.nickname_1" => "Surnom",
			"ProbenView_participants.participate" => "Participe",
			"ProbenView_participants.reason" => "Motif",
			"ProbenView_participants.title_2" => "Engagements/rejets en suspens",
			"ProbenView_participants.nickname_2" => "Surnom",
			"ProbenView_participants.mobile" => "Portable",
			"ProbenView_participants.title_3" => "Synthèse",
			"ProbenView_practise.title" => "Sélection de pièces",
			"ProbenView_viewOptions.back" => "Retour",
			"ProbenView_viewDetailTable.period" => "Période",
			"ProbenView_viewDetailTable.approve_until" => "Engagements jusqu'au: ",
			"ProbenView_viewDetailTable.location" => "Lieu",
			"ProbenView_viewDetailTable.conductor" => "Chef d'orchestre",
			"ProbenView_viewDetailTable.serie_name" => "Exemple de chemin",
			"ProbenView_viewDetailTable.groups" => "Groupes invités",
			"ProbenView_viewDetailTable.notes" => "Notes",						
			"ProbenView_viewDetailTable.yes" => "Oui",
			"ProbenView_viewDetailTable.no" => "Non",
			"ProbenView_viewDetailTable.phases_title" => "Phases de répétition",
			"ProbenView_viewDetailTable.phases_name" => "Phases de répétition",
			"ProbenView_viewDetailTable.phases_begin" => "De",
			"ProbenView_viewDetailTable.phases_end" => "Jusqu'à",
			"ProbenView_viewDetailTable.phases_notes" => "Notes",
			"ProbenView_addContact.Form" => "Ajouter une invitation à la répétition",
			"ProbenView_invitations.mobile" => "Numéro de portable",
			"ProbenView_process_addContact.message_1" => "Contact ajouté",
			"ProbenView_process_addContact.message_2" => "Le contact a été ajouté à cette répéptition.",
			"ProbenView_practise.no_song" => "Aucune pièce sélectionnée.",
			"ProbenView_practise.Form" => "Ajouter une pièce",
			"ProbenView_practise.song" => "Pièce",
			"ProbenView_history.year" => "Année",
			"ProbenView_history.message" => "Cliquez sur une entrée pour l'afficher.",
			"ProbenView_history.street" => "Rue",
			"ProbenView_history.zip" => "Code postale",
			"ProbenView_history.city" => "Ville",
			"ProbenView_view.details" => "Détails de la répétition",
			"ProbenView_view.program" => "Pièces à pratiquer",
			"ProbenView_view.title" => "Pièces",
			"ProbenView_view.notes" => "Notes actuelles",
			"ProbenView_ProbenView_addContact.fullname" => "Invitation pour",
			"ProbenView_practise.save" => "sauvegarder",
			"ProbenView_practise.delete" => "supprimer ",
			"ProbenView_practise.notes" => "Remarques",
			"ProbenView_view.message_1" => "Répétition à ",
			"ProbenView_view.message_2" => " Heures",
			"ProbenView_view.details" => "Détails",
			"ProbenView_view.invitations" => "Invitations",
			"ProbenView_view.participants" => "Participants",
			"ProbenView_view.practise" => "Pièces à pratiquer",
			"ProbenView_viewOptions.remHref" => "Envoyer la notification",
			"ProbenView_viewOptions.addContact" => "Ajouter une invitation",
			"ProbenView_viewOptions.printPartlist" => "Imprimer la liste des participants",
			"ProbenView_overview.FutureRehearsals" => "Aucune répétition n'est actuellement prévue.",
			"ProbenView_overview.header_1" => "Répétition à ",
			"ProbenView_overview.header_2" => " Heures",
			"ProbenView_overview.unspecified" => "Non spécifié",
			"ProbenView_overview.cancel" => "Ne participe pas",
			"ProbenView_overview.yield" => "Peut participer",
			"ProbenView_overview.checked" => "Participe",
			"ProbenView_writeRehearsal.begin" => " Heures",
			
			// module: Probenphasen *********************************************
			
			// ProbenphasenData		
			"ProbenphasenData_construct.id" => "ID",
			"ProbenphasenData_construct.name" => "Nom",
			"ProbenphasenData_construct.begin" => "Début",
			"ProbenphasenData_construct.end" => "Fin",
			"ProbenphasenData_construct.notes" => "Notes",
			
			// ProbenphasenView
			"ProbenphasenView_construct.EntityName" => "Période de répétition",				
			"ProbenphasenView_construct.addEntityName" => "Ajouter une nouvelle période de répétition",		
			"ProbenphasenView_startOptions.timer" => "Phases de répétition antérieures",
			"ProbenphasenView_history.title" => "Phases de répétition antérieures",
			"ProbenphasenView_tab_rehearsals.id" => "ID",
			"ProbenphasenView_tab_rehearsals.begin" => "Début",
			"ProbenphasenView_tab_rehearsals.location" => "Lieu",
			"ProbenphasenView_tab_concerts.id" => "ID",
			"ProbenphasenView_tab_concerts.title" => "Titre",
			"ProbenphasenView_tab_concerts.begin" => "Début",
			"ProbenphasenView_tab_concerts.location" => "Lieu",
			"ProbenphasenView_tab_concerts.notes" => "Notes",
			"ProbenphasenView_tab_contacts.id" => "ID",
			"ProbenphasenView_tab_contacts.instrument" => "Instrument",
			"ProbenphasenView_tab_contacts.phone" => "Téléphone",
			"ProbenphasenView_tab_contacts.mobile" => "Portable",
			"ProbenphasenView_tab_contacts.email" => "Courriel",
			"ProbenphasenView_view.details" => "Détails",
			"ProbenphasenView_view.rehearsals" => "Répétition",
			"ProbenphasenView_view.contacts" => "Participants",
			"ProbenphasenView_view.concerts" => "Concerts",
			"ProbenphasenView_viewOptions.addRehearsal" => "Ajouter une répétition",
			"ProbenphasenView_viewOptions.addContact" => "Ajouter un contact",
			"ProbenphasenView_viewOptions.addMultipleContacts" => "Ajouter des contacts à un groupe",
			"ProbenphasenView_viewOptions.addConcert" => "Ajouter un concert",
			"ProbenphasenView_addRehearsal.form" => "Ajouter une répétition",
			"ProbenphasenView_addRehearsal.message" => "Ajouter une répétition à la série en cliquant dessus.",
			"ProbenphasenView_addRehearsal.begin" => "Répétition à venir",
			"ProbenphasenView_process_addRehearsal.message_1" => "Répétition ajoutés",
			"ProbenphasenView_process_addRehearsal.message_2" => "Les répétitions sélectionnés ont été ajoutés à la série de répétition.",
			"ProbenphasenView_addConcert.form" => "Ajouter un concert",
			"ProbenphasenView_addConcert.message" => "Ajoutez une ou plusieurs performances à la série en cliquant dessus.",
			"ProbenphasenView_addConcert.title" => "prochains concerts",
			"ProbenphasenView_process_addConcert.message_1" => "Concerts ajoutées",
			"ProbenphasenView_process_addConcert.message_2" => "Les concerts sélectionnées ont été ajoutées à la phase d'essai.",
			"ProbenphasenView_addContact.form" => "Ajouter un contact",
			"ProbenphasenView_addContact.message" => "Cliquez sur un ou plusieurs contacts pour les ajouter à la série de répétition.",
			"ProbenphasenView_addContact.contact" => "Contacts",
			"ProbenphasenView_process_addContact.message_1" => "Contacts ajoutés",
			"ProbenphasenView_process_addContact.message_2" => "Les contacts sélectionnés ont été ajoutés à la série de répétition.",
			"ProbenphasenView_addMultipleContacts.form" => "Ajouter tous les contacts d'un groupe à la série de répétition",
			"ProbenphasenView_addMultipleContacts.message" => "Ajoutez plusieurs contacts à la la série de répétition en cliquant sur un ou plusieurs groupes.",
			"ProbenphasenView_addMultipleContacts.name_member" => "Groupes",
			"ProbenphasenView_process_addMultipleContacts.message_1" => "Contacts ajoutés",
			"ProbenphasenView_process_addMultipleContacts.message_2" => "Les contacts sélectionnés ont été ajoutés à la série de répétition.",
			"ProbenphasenView_addRemoveColumnToTable.delete" => "Supprimer",
			
			// module: Program *********************************************
						
			// ProgramData	
			"ProgramData_construct.id" => "ID du programme",
			"ProgramData_construct.name" => "Nom",
			"ProgramData_construct.notes" => "Commentaires",
			"ProgramData_construct.isTemplate" => "Modèle",
			"ProgramData_addProgramWithTemplate.message_1" => "à partir du modèle",
			"ProgramData_addProgramWithTemplate.message_2" => " créé",
		
			// ProgramView	
			"ProgramView_construct.EntityName" => "Programme",
			"ProgramView_backToStart.back" => "Retour",
			"ProgramView_startOptions.back" => "Retour",
			"ProgramView_startOptions.addEntity" => "Ajouter un programme",
			"ProgramView_startOptions.addFromTemplate" => "Ajouter un programme à partir d'un modèle",
			"ProgramView_writeTitle.title" => "Programme",
			"ProgramView_writeTitle.message" => "Cliquez sur un programme pour voir les détails et éditer les pièces.",
			"ProgramView_addFromTemplate.form" => "Ajouter un programme à partir d'un modèle",
			"ProgramView_addFromTemplate.name" => "Nom",
			"ProgramView_addFromTemplate.template" => "Présentation",
			"ProgramView_addFromTemplate.ask" => "Présentation",
			"ProgramView_viewDetailTable.header" => "Liste des titres",
			"ProgramView_viewDetailTable.rank" => "Nr.",
			"ProgramView_viewDetailTable.title" => "Titre",
			"ProgramView_viewDetailTable.title" => "Compositeur/Arrangeur",
			"ProgramView_viewDetailTable.length" => "Durée",
			"ProgramView_viewDetailTable.notes" => "Notes",
			"ProgramView_writeProgramLength.message_1" => "Le programme a une durée totale de ",
			"ProgramView_writeProgramLength.message_2" => " Heures.",
			"ProgramView_additionalViewButtons.edit" => "Modifier la liste des titres",
			"ProgramView_additionalViewButtons.printer" => "Imprimer la liste des titres",
			"ProgramView_additionalViewButtons.export" => "Exporter la liste des titres (CSV)",
			"ProgramView_editList.message" => "Déplacez les titres dans l'ordre de votre choix.",
			"ProgramView_editListOptions.back" => "Retour",
			"ProgramView_printList.title" => "Pièce",
			"ProgramView_printList.notes" => "Notes",
			"ProgramView_printList.length" => "Durée",
			"ProgramView_printList.totalProgramLength" => "Durée du programme",
			"ProgramView_printListOptions.print" => "Imprimer",
			
			// module: Recpay *********************************************
			
			// RecpayData			
			"RecpayData_construct.id" => "ID",
			"RecpayData_construct.subject" => "Objet",
			"RecpayData_construct.account" => "Compte",
			"RecpayData_construct.amount_net" => "Net",
			"RecpayData_construct.amount_tax" => "Taxe",
			"RecpayData_construct.btype" => "Type",
			"RecpayData_construct.otype" => "Type de référence",
			"RecpayData_construct.oid" => "Référence",
			"RecpayData_construct.notes" => "Notes",
			"RecpayData_ref2val.location" => "Lieu",
			"RecpayData_ref2val.contact" => "Contact",
			"RecpayData_ref2val.concert" => "Concert",
			"RecpayData_ref2val.rehearsalphase" => "Phase de répétition",
			"RecpayData_ref2val.tour" => "Tournée",
			"RecpayData_ref2val.equipment" => "Equipement",
			"RecpayData_ref2val.no_otype" => "[aucune référence]",
			"RecpayData_getRecurringPayments.type_0" => "Achat",
			"RecpayData_getRecurringPayments.type_1" => "Dépense",
			
			// RecpayView
			"RecpayView_construct.EntityName" => "Paiement courant",
			"RecpayView_construct.addEntityName" => "Ajoutez un paiement courant",
			"RecpayView_showAllTableGenerator.accountname" => "Compte",
			"RecpayView_startOptions.back" => "Retour",
			"RecpayView_startOptions.book" => "Comptabiliser",
			"RecpayView_changeReference.otype" => "[aucune référence]",
			"RecpayView_changeReference.contact" => "Contact",
			"RecpayView_changeReference.concert" => "Concert",
			"RecpayView_changeReference.rehearsalphase" => "Série de répétition",
			"RecpayView_changeReference.location" => "Lieu",
			"RecpayView_changeReference.tour" => "Tournée",
			"RecpayView_changeReference.equipment" => "Equipment",
			"RecpayView_addEntityForm.form" => "Ajouter le paiement en cours",
			"RecpayView_addEntityForm.btype" => "Type",
			"RecpayView_addEntityForm.otype" => "Type de référence",
			"RecpayView_editEntityForm.btype" => "Type",
			"RecpayView_book.title" => "Comptabiliser les paiements courants",
			"RecpayView_book.bdate" => "Date",
			"RecpayView_book.book" => "Réservation",
			"RecpayView_book.submit" => "Réservation",
			"RecpayView_bookProcess.message_1" => "Réservation réussie",
			"RecpayView_bookProcess.message_2" => "Toutes les écritures ont été insérées avec succès.",
			"RecpayView_viewDetailTable.expense" => "Dépense",
			"RecpayView_viewDetailTable.income" => "Recette",
			"RecpayView_viewDetailTable.accountname" => "Compte",
			"RecpayView_objectReferenceTypeToText.H" => "Contact",
			"RecpayView_objectReferenceTypeToText.C" => "Concert",
			"RecpayView_objectReferenceTypeToText.P" => "Série de répétition",
			"RecpayView_objectReferenceTypeToText.L" => "Lieu",
			"RecpayView_objectReferenceTypeToText.T" => "Tournée",
			"RecpayView_objectReferenceTypeToText.E" => "Equipment",
			"RecpayView_viewOptions.edit" => "%p éditer",
			"RecpayView_viewOptions.delete_confirm" => "%p suppression",
			"RecpayView_backToStart.back" => "Retour",
			
			// module: Repertoire *********************************************
						
			// RepertoireData
			"RepertoireData_construct_id" => "ID du titre",
			"RepertoireData_construct_title" => "Titre",
			"RepertoireData_construct_length" => "Durée",
			"RepertoireData_construct_genre" => "Genre",
			"RepertoireData_construct_bpm" => "Tempo (bpm)",
			"RepertoireData_construct_music_key" => "Tonalité",
			"RepertoireData_construct_composer" => "Compositeur / Arrangeur",
			"RepertoireData_construct_status" => "Statut",
			"RepertoireData_construct_setting" => "Distribution",
			"RepertoireData_construct_notes" => "Notes",
			"RepertoireData_construct_is_active" => "Actuel",
			"RepertoireData_massUpdate_error" => "Veuillez sélectionner au moins un titre à mettre à jour.",
			
			// RepertoireController			
			"RepertoireController_xlsMapping.errorFileMaxSize" => "La taille maximale du fichier a été dépassée..",
			"RepertoireController_xlsMapping.errorFileAbort" => "Le fichier n'a été que partiellement téléchargé. Veuillez vérifier votre connexion Internet.",
			"RepertoireController_xlsMapping.errorNoFile" => "Aucun fichier téléchargé.",
			"RepertoireController_xlsMapping.errorSavingFile" => "Erreur de serveur lors de l'enregistrement du fichier.",
			"RepertoireController_xlsMapping.errorUploadingFile" => "Le fichier n'a pas pu être téléchargé..",
			"RepertoireController_xlsImport.error" => "Choisissez une colonne pour le titre de vos morceaux.",
			"RepertoireController_xlsMap.col_composer" => "non spécifié",
			
			// RepertoireView
			"RepertoireView_construct.addEntityName" => "Ajouter une nouvelle Partition",	
			"RepertoireView_startOptions.print" => "Imprimer",
			"RepertoireView_startOptions.massUpdate" => "Mise à jour en masse",
			"RepertoireView_startOptions.start" => "Gestion des genres",
			"RepertoireView_startOptions.xlsUpload" => "Import Excel",
			"RepertoireView_startOptions.repertoire" => "Export CSV",
			"RepertoireView_start.title" => "Titre",
			"RepertoireView_start.genre" => "Genre",
			"RepertoireView_start.music_key" => "Tonalité",
			"RepertoireView_start.solist" => "Soliste",
			"RepertoireView_start.status" => "Statut",
			"RepertoireView_start.composer" => "Compositeur / Arrangeur",
			"RepertoireView_start.is_active" => "Actuel",
			"RepertoireView_addEntityForm.Form" => "Ajouter une partition",
			"RepertoireView_addEntityForm.composer" => "Compositeur / arrangeur",
			"RepertoireView_addEntityForm.length" => "Durée",
			"RepertoireView_start.genrename" => "Genre",
			"RepertoireView_start.composername" => "Compositeur / arrangeur",
			"RepertoireView_start.statusname" => "Statut",
			"RepertoireView_view.music_key" => "Tonalité",
			"RepertoireView_view.length" => "Durée",
			"RepertoireView_view.bpm" => "Tempo",
			"RepertoireView_view.statusname" => "Statut",
			"RepertoireView_view.genrename" => "Genre",
			"RepertoireView_view.setting" => "Occupation",
			"RepertoireView_view.is_active" => "Actuel",
			"RepertoireView_view.notes" => "Notes",
			"RepertoireView_view.yes" => "Oui",
			"RepertoireView_view.no" => "Non",
			"RepertoireView_view.id" => "Références",
			"RepertoireView_view.rehearsals_song" => "Pièces de la répétition",
			"RepertoireView_view.concerts_song" => "Pièces du concert",
			"RepertoireView_view.solists" => "Solistes",
			"RepertoireView_view.nosolists" => "Aucun soliste n'est spécifié.",
			"RepertoireView_songFiles.doctype_name" => "Supprimer le lien",
			"RepertoireView_songFiles.addSongFile" => "Ajouter un fichier",
			"RepertoireView_songFiles.repertoire_filesearch" => "Spécifiez au moins 3 caractères d'un nom de fichier à partir de 'Partager' pour ajouter le fichier.",
			"RepertoireView_songFiles.submit" => "ajouter",
			"RepertoireView_additionalViewButtons.addSolist" => "Ajouter un soliste",
			"RepertoireView_editEntityForm.length" => "Durée en heures",
			"RepertoireView_editEntityForm.composer" => "Compositeur/arrangeur",
			"RepertoireView_addSolist.Form" => "Sélectionner les solistes",
			"RepertoireView_addSolist.selector" => "Solistes",
			"RepertoireView_process_addSolist.message_1" => "Soliste ajouté",
			"RepertoireView_process_addSolist.message_2" => "Le soliste a été ajouté à la pièce.",
			"RepertoireView_xlsUpload.Form" => "Téléchargement du fichiers Excel",
			"RepertoireView_xlsUpload.xlsfile" => "Fichier XLSX (Excel 2007+)",
			"RepertoireView_xlsUpload.submit" => "Télécharger et plus encore",
			"RepertoireView_xlsMapping.col_title" => "Titre",
			"RepertoireView_xlsMapping.col_composer" => "Compositeur/arrangeur",
			"RepertoireView_xlsMapping.col_key" => "Tonalité",
			"RepertoireView_xlsMapping.col_tempo" => "Tempo (BPM)",
			"RepertoireView_xlsMapping.col_notes" => "Notes",
			"RepertoireView_xlsMapping.col_genre" => "Genre",
			"RepertoireView_xlsMapping.dd_status" => "Statut",
			"RepertoireView_xlsMapping.submit" => "Envoyer ",
			"RepertoireView_columnSelector.import" => "- Ne pas importer",
			"RepertoireView_xlsImport.import" => "Importation",
			"RepertoireView_xlsImport.message_1" => "Les lignes peuvent être importées directement. ",
			"RepertoireView_xlsImport.message_2" => "Les lignes ne contiennent pas de titre et ont été marquées vides.",
			"RepertoireView_xlsImport.Form" => "Doublons",
			"RepertoireView_xlsImport.duplicate_id" => "Ecraser",
			"RepertoireView_xlsImport.duplicate_ignore" => "Ignorer",
			"RepertoireView_xlsImport.duplicates" => "Aucun doublon détecté",
			"RepertoireView_xlsProcessSuccess.message_1" => "Importation réussie",
			"RepertoireView_xlsProcessSuccess.message_2" => "Les pièces ont été créées avec succès.",
			"RepertoireView_xlsProcessSuccess.message_3" => "Les pièces ont été mises à jour.",
			"RepertoireView_massUpdate.form" => "Éditer des pièces",
			"RepertoireView_massUpdate.genre" => "[Ne pas modifier]",
			"RepertoireView_massUpdate.status" => "[Ne pas modifier]",
			"RepertoireView_massUpdate.songSelector" => "Pièces",
			"RepertoireView_process_massUpdate.message_1" => "Enregistrement des pièces",
			"RepertoireView_process_massUpdate.message_2" => "Les pièces ont été mises à jour avec succès.",
			
			// module: Share *********************************************

			// ShareData	
			"ShareData_construct.id" => "clefs",
			"ShareData_construct.name" => "Nom",
			"ShareData_construct.is_active" => "Actif",
			
			// ShareView			
			"ShareView_construct.EntityName" => "Type de document",
			"ShareView_startOptions.documenttype" => "Types de documents",
			"ShareView_docTypeOptions.addEntity" => "Ajouter un type de document",
			"ShareView_docType.error" => "Autorisation refusée.",
			
			// module: Stats *********************************************
						
			// StatsData
						
			// StatsView
			"StatsView_memberrehearsalperformanceChart.surname" => "Nom de famille",
			"StatsView_memberrehearsalperformanceChart.name" => "Nom",
			"StatsView_memberrehearsalperformanceChart.score" => "Nombre",
			"StatsView_memberrehearsalperformanceChart.rank" => "Classement",
			"StatsView_membervoteperformanceChart.surname" => "Nom de famille",
			"StatsView_membervoteperformanceChart.name" => "Nom",
			"StatsView_membervoteperformanceChart.score" => "Nombre",
			"StatsView_membervoteperformanceChart.rank" => "Classement",
			"StatsView_memberoptionperformanceChart.surname" => "Nom de famille",
			"StatsView_memberoptionperformanceChart.name" => "Nom",
			"StatsView_memberoptionperformanceChart.score" => "Nombre",
			"StatsView_memberoptionperformanceChart.rank" => "Classement",
			"StatsView_start.Rehearsal_title" => "Nombre de répétition au cours des 6 derniers mois",
			"StatsView_start.concertstat_title" => "Nombre de concerts au cours des 6 derniers mois",
			"StatsView_start.memberstat_title" => "Nombre de membres par groupe",
			"StatsView_start.memberrehearsalperformancestat_title" => "Principaux participants (1 an)",
			"StatsView_start.membervoteperformancestat_title" => "Principaux votants (1 an)",
			"StatsView_start.memberoptionperformancestat_title" => "Engagement maximum (1 an)",
			
			// module: Start *********************************************
		
			// StartData
			"StartData_construct.id" => "ID utilisateur",
			"StartData_construct.login" => "Identifiant",
			"StartData_construct.password" => "Mot de passe",
			"StartData_construct.realname" => "Nom",
			"StartData_construct.lastlogin" => "Dernière connexion",
			"StartData_getObjectTitle.Rehearsal" => "Répétition",
			"StartData_getObjectTitle.Concert" => "Concert",
			"StartData_getObjectTitle.Vote" => "Vote",
			"StartData_getObjectTitle.Task" => "Tâche",
			"StartData_getObjectTitle.Reservation" => "Réservation",

			// StartController
			"StartController_start.flash" => "Dommage. Donc vous ne pouvez pas utiliser BNote.",
			
			// StartView
			"StartView_startOptions.calendarExport" => "Exportation du calendrier",
			"StartView_startOptions.calendarSubscribe" => "S'abonner à l'agenda",
			"StartView_start.box_heading" => "Déclaration de consentement à la protection des données",
			"StartView_start.warning" => "Acceptez-vous que nous traitions vos données personnelles ?",
			"StartView_start.terms" => "Vers la politique de confidentialité",
			"StartView_start.checkmark" => "J'accepte",
			"StartView_start.cancel" => "Je refuse",
			"StartView_start_box.heading" => "Nouvelles",
			"StartView_start_box_content.warning_1" => "Il y a des utilisateurs inactifs ou non intégrés. Veuillez vous rendre à la rubrique contacts / phasage et vous en occuper.",
			"StartView_start_box_content.warning_2" => "L'enregistrement automatique est activé. Veuillez suivre les consignes de sécurité.",
			"StartView_start_box_Rehearsal.heading" => "Répétitions",
			"StartView_start_box_Concert.heading" => "Concerts",
			"StartView_start_box_Reservation.heading" => "Réservations",
			"StartView_start_box_Appointment.heading" => "Dates et événements",
			"StartView_start_box_Vote.heading" => "Votes",
			"StartView_start_box_Task.heading" => "Tâches",
			"StartView_askReason.Form" => "Veuillez donner une raison.",
			"StartView_writeRehearsalList.Form" => "Aucune répétition annoncée.",
			"StartView_writeRehearsalList.Rehearsal" => "Seuls les %p premiers échantillons sont affichés.",
			"StartView_writeRehearsalList.begin" => "Début",
			"StartView_writeRehearsalList.end" => "Fin",
			"StartView_writeRehearsalList.location" => "Emplacement",
			"StartView_writeRehearsalList.conductor" => "Chef d'Orchestre",
			"StartView_writeRehearsalList.Song" => "Pièces à pratiquer",
			"StartView_writeRehearsalList.Participants" => "Participants",
			"StartView_writeRehearsalList.yes" => "J'y prendrai part.",
			"StartView_writeRehearsalList.maybe" => "J'y assisterai peut-être.",
			"StartView_writeRehearsalList.no" => "Je ne pourrai pas.",
			"StartView_writeRehearsalList.setParticipation" => "Précisez votre participation",
			"StartView_writeRehearsalList.participationOver" => "Date limite de participation expirée",
			"StartView_writeRehearsalList.Participate" => "Vous participez à la répétition.",
			"StartView_writeRehearsalList.MaybeParticipate" => "vous pourrez peut-être assister à la répétition.",
			"StartView_writeRehearsalList.NotParticipate" => "Vous n'assisterez pas à la répétition.",
			"StartView_writeConcertList.noConcertsScheduled" => "Aucun concert annoncé.",
			"StartView_writeConcertList.yes" => "J'en serai",
			"StartView_writeConcertList.maybe" => "J'en serai peut-être.",
			"StartView_writeConcertList.no" => "Je n'en serai pas.",
			"StartView_writeConcertList.setParticipation" => "Précisez votre participation",
			"StartView_writeConcertList.participationOver" => "Date limite de participation expirée",
			"StartView_writeConcertList.Participate" => "Vous participez au concert.",
			"StartView_writeConcertList.MayParticipate" => "Vous participez peut-être au concert.",
			"StartView_writeConcertList.DontParticipate" => "Vous ne participez pas au concert.",
			"StartView_writeTaskList.title" => "Titre",
			"StartView_start_box_Task.writeUpdateList" => "Commentaires",
			"StartView_writeRehearsalList.groupNames" => "Groupes",
			"StartView_writeRehearsalList.comment" => "Remarque",
			"StartView_writeTaskList.description" => "Description",
			"StartView_writeTaskList.due_at" => "Échues le",
			"StartView_writeVoteList.name" => "Nom",
			"StartView_writeVoteList.end" => "Fin du vote",
			"StartView_writeVoteList.vote" => "Voter",
			"StartView_writeReservationList.name" => "Nom",
			"StartView_writeReservationList.yes" => "Oui",
			"StartView_writeReservationList.no" => "Non",
			"StartView_writeAppointmentList.name" => "Nom",
			"StartView_writeAppointmentList.locationname" => "Lieu",
			"StartView_writeAppointmentList.yes" => "Oui",
			"StartView_writeAppointmentList.no" => "Non",
			"StartView_writeBoxListItem.discussion_on" => "Commentaire",
			"StartView_writeBoxListItem.newDiscussion" => "Nouveau commentaire",
			"StartView_writeBoxListItem.participation" => "Participation",
			"StartView_voteOptions.error" => "Vous ne pouvez pas participer à ce vote.",
			"StartView_voteOptions.worksForMeNot" => "Je ne peux pas",
			"StartView_voteOptions.worksForMe" => "Je peux",
			"StartView_voteOptions.worksForMeMaybe" => "Peut-être",
			"StartView_voteOptions.noOptionsYet" => "Aucune option n'a encore été spécifiée. Revérifiez plus tard.",
			"StartView_saveVote.selectionSavedTitle" => "Sélection sauvegardée",
			"StartView_saveVote.selectionSavedMsg" => "Votre sélection a été sauvegardée.",
			"StartView_taskComplete.taskCompletedTitle" => "Tâche terminée",
			"StartView_taskComplete.taskCompletedMsg" => "La tâche a été marquée comme terminée.",
			"StartView_viewProgram.ProgramTitles" => "Programme",
			"StartView_viewProgram.rank" => "Nr.",
			"StartView_viewProgram.title" => "Titre",
			"StartView_viewProgram.composer" => "Compositeur/Arrangeur",
			"StartView_viewProgram.notes" => "Notes",
			"StartView_rehearsalParticipants.participantsOfRehearsal" => "Participants à la répétition à %p heure",
			"StartView_rehearsalParticipants.name" => "prénom",
			"StartView_rehearsalParticipants.surname" => "Nom de famille",
			"StartView_rehearsalParticipants.nickname" => "Surnom",
			"StartView_concertParticipants.participantsOfConcert" => "Participants au concert à %p heure",
			"StartView_concertParticipants.name" => "Prénom",
			"StartView_concertParticipants.surname" => "Nom de famille",
			"StartView_concertParticipants.nickname" => "Surnom",
			"StartView_writeUpdateList.noNews" => "Aucune nouvelle",
			"StartView_discussion.Deactivated" => "Les commentaires sont désactivés dans cette application.",
			"StartView_discussion.Reason" => "Veuillez indiquer la référence du commentaire.",
			"StartView_discussion.discussion" => "Commentaire",
			"StartView_discussion.noComments" => "Aucun commentaire",
			"StartView_discussion.noCommentsInDiscussion" => "Aucun commentaire disponible.",
			"StartView_discussion.addComment" => "Ajouter un commentaire",
			"StartView_discussion.Submit" => "Envoyer un commentaire",
			"StartView_gigcard.date" => "Date/Heure",
			"StartView_gigcard.meetingtime" => "Lieu de rendez-vous",
			"StartView_gigcard.approve_until" => "Engagement jusqu'au",
			"StartView_gigcard.organizer" => "Organisateur",
			"StartView_gigcard.address" => "Lieu",
			"StartView_gigcard.contact" => "Contact",
			"StartView_gigcard.accommodation" => "Hébergement",
			"StartView_gigcard.organisation" => "Organisation",
			"StartView_gigcard.groups" => "Groupe",
			"StartView_gigcard.program" => "Programme",
			"StartView_gigcard.outfit" => "Tenue",
			"StartView_gigcardOptions.print" => "Imprimer",
			"StartView_gigcardOptions.concertParticipants" => "Participants",
			"StartView_writeVoteList.noVotes" => "Aucun vote n'est ouvert.",
			"StartView_writeTaskList.noTasks" => "Aucune tâche disponible.",
			
			// module: Tour *********************************************

			// TourData
			"TourData_construct.id" => "ID",
			"TourData_construct.name" => "Nom",
			"TourData_construct.start" => "De",
			"TourData_construct.end" => "Jusqu'à",
			"TourData_construct.notes" => "Notes",
			
			// TourView
			"TourView_construct.addEntityName" => "Tournée",				
			"TourView_construct.addEntityName" => "Ajouter une nouvelle Tournée",					
			"TourView_view.details" => "Détails",
			"TourView_view.rehearsals" => "Répétition",
			"TourView_view.contacts" => "Participants",
			"TourView_view.concerts" => "Concerts",
			"TourView_view.accommodation" => "Nuitées",
			"TourView_view.travel" => "Voyage",
			"TourView_view.checklist" => "Aide-mémoire",
			"TourView_view.equipment" => "Equipement",
			"TourView_additionalViewButtons.summarysheet" => "Feuille de route",
			"TourView_additionalViewButtons.addRehearsal" => "Ajouter une répétition",
			"TourView_additionalViewButtons.addContacts" => "Ajouter un participant",
			"TourView_additionalViewButtons.addConcert" => "Ajouter un concert",
			"TourView_additionalViewButtons.addTask" => "Ajouter une tâche",
			"TourView_tab_rehearsals.begin" => "Début de la répétition",
			"TourView_tab_rehearsals.rehearsal_notes" => "Notes de répétition",
			"TourView_tab_rehearsals.name" => "nom",
			"TourView_tab_rehearsals.location_notes" => "Notes sur le lieu",
			"TourView_tab_rehearsals.street" => "Rue",
			"TourView_tab_rehearsals.city" => "Ville",
			"TourView_addRehearsalProcess.message_1" => "Répétition ajouté",
			"TourView_addRehearsalProcess.message_2" => "La répétition a été ajouté avec succès à la tournée.",
			"TourView_tab_contacts.surname" => "Nom de famille",
			"TourView_tab_contacts.name" => "Nom",
			"TourView_tab_contacts.phone" => "Téléphone",
			"TourView_tab_contacts.mobile" => "Mobile",
			"TourView_tab_contacts.birthday" => "Date de naissance",
			"TourView_tab_contacts.instrumentname" => "Instrument",
			"TourView_addContacts.form" => "Sélectionner un participant pour la visite",
			"TourView_addContactsProcess.message_1" => "Les participants ont ajouté",
			"TourView_addContactsProcess.message_2" => "Les participants ont été ajoutés avec succès à la visite.",
			"TourView_tab_concerts.title" => "Titre",
			"TourView_tab_concerts.begin" => "Début",
			"TourView_tab_concerts.end" => "Fin",
			"TourView_tab_concerts.notes" => "Notes",
			"TourView_tab_concerts.locationname" => "Auftrittsort",
			"TourView_tab_concerts.program" => "program",
			"TourView_tab_concerts.approve_until" => "Teilnahme angeben bis",
			"TourView_tab_checklist.todos" => "Ausstehende Aufgaben",
			"TourView_tab_checklist.completed_tasks" => "Abgeschlossene Aufgaben",
			"TourView_tab_checklist.title" => "Aufgabe",
			"TourView_tab_checklist.description" => "Beschreibung",
			"TourView_tab_checklist.assigned_to" => "Verantwortlicher",
			"TourView_tab_checklist.due_at" => "Fälligkeit",
			"TourView_tab_equipment.form" => "Tour-Equipment",
			"TourView_tab_equipment.model" => "Modell",
			"TourView_tab_equipment.make" => "Marke",
			"TourView_tab_equipment.tour_quantity" => "Menge",
			"TourView_tab_equipment.equipment_notes" => "Equipment Beschreibung",
			"TourView_tab_equipment.eq_tour_notes" => "Notizen für die Tour",
			"TourView_addEquipmentProcess.tour" => "Die Equipmentliste für die Tour wurde gespeichert.",
			"TourView_addEquipmentProcess.message_1" => "Equipment gespeichert",
			"TourView_addEquipmentProcess.message_2" => "Die Equipmentliste für die Tour wurde gespeichert.",
			"TourView_summarySheet.tab_contacts" => "Teilnehmer",
			"TourView_summarySheet.tab_concerts" => "Auftritte",
			"TourView_summarySheet.tab_rehearsals" => "Proben",
			"TourView_summarySheet.TravelView" => "Transfers",
			"TourView_summarySheet.AccommodationView" => "Übernachtungen",
			"TourView_summarySheet.equipment" => "Equipment",
			"TourView_summarySheetOptions.print" => "Drucken",
			
			// module: Travel *********************************************

			// TravelView	
			"TravelView_construct.EntityName" => "Voyage",
			"TravelView_viewDetailTable.tourname" => "voyage",
			
			// TravelData			
			"TravelData_construct.id" => "ID",
			"TravelData_construct.tour" => "Voyage",
			"TravelData_construct.num" => "Numéro de voyage",
			"TravelData_construct.departure" => "Départ",
			"TravelData_construct.departure_location" => "de",
			"TravelData_construct.arrival" => "Arrivée",
			"TravelData_construct.arrival_location" => "à",
			"TravelData_construct.planned_cost" => "Frais de voyage planifié",
			"TravelData_construct.notes" => "Notes",
			
			// module: User *********************************************

			// UserData
			"UserData_construct.id" => "ID utilisateur",
			"UserData_construct.isActive" => "Utilisateur actif",
			"UserData_construct.login" => "Identifiant",
			"UserData_construct.password" => "Mot de passe",
			"UserData_construct.contact" => "Contact",
			"UserData_construct.lastlogin" => "Dernière connexion",
			"UserData_create.error_1" => "Le nom d'utilisateur spécifié n'est pas valide.",
			"UserData_create.error_2" => "Le mot de passe spécifié n'est pas valide (au moins 6 caractères requis).",
			"UserData_create.error_3" => "Veuillez sélectionner un contact.",
			"UserData_create.error_4" => "Le nom d'utilisateur est déjà utilisé !",
			"UserData_create.error_5" => "Le mot de passe spécifié n'est pas suffisant.",
			"UserData_update.error" => "Accès refusé.",
			"UserData_delete.error" => "Accès refusé.",
			"UserData_updatePrivileges.error" => "Accès refusé.",
			"UserData_changeUserStatus.error" => "Accès refusé.",
			
			// UserController
			"UserController_activate.message_1" => "Compte utilisateur activé.",
			"UserController_activate.message_2" => "votre ",
			"UserController_activate.message_3" => " Le compte utilisateur a été activé. ",
			"UserController_activate.message_4" => "Vous pouvez maintenant aller à ",
			"UserController_activate.message_5" => " s'inscrire.",
			"UserController_activate.message_6" => "Echec de l'activation de l'email d'activation",
			"UserController_activate.message_7" => "L'envoi du courriel d'activation n'a pas réussi. Veuillez en informer vous-même l'utilisateur.",
			
			// UserView
			"UserView_construct.EntityName" => "Utilisateur",
			"UserView_construct.addEntityName" => "Ajouter un nouvel utilisateur",
			"UserView_start.message" => "Les utilisateurs qui peuvent se connecter au système sont gérés ici.",
			"UserView_start.contactsurname" => "Nom de famille",
			"UserView_start.contactname" => "Prénom",
			"UserView_start.isactive" => "Utilisateur actif",
			"UserView_start.lastlogin" => "DATE",
			"UserView_startOptions.question" => "Protection des données",
			"UserView_addEntity.contactDropdown" => "Contact",
			"UserView_view.error" => "Accès refusé.",
			"UserView_view.contactname" => "Prénom",
			"UserView_view.contactsurname" => "Nom de famille",
			"UserView_additionalViewButtons.privileges" => "Droits d'édition",
			"UserView_editEntityForm.login" => "Identifiant",
			"UserView_editEntityForm.password" => "Mot de passe",
			"UserView_editEntityForm.contact" => "Contact",
			"UserView_editEntityForm.message" => "Si le champ du mot de passe reste vide, le mot de passe actuel reste valide.",
			"UserView_privileges.form" => "Privilèges pour ",
			"UserView_privileges_process.message_1" => "Modifications sauvegardées.",
			"UserView_privileges_process.message_2" => "Les données utilisateur ont été sauvegardées avec succès.",
			"UserView_privileges_processOptions.back" => "Retour",
			"UserView_deleteConfirmationMessage.message_1" => "Supprimer",
			"UserView_deleteConfirmationMessage.message_2" => "Voulez-vous vraiment supprimer cet utilisateur avec tous ses fichiers ?",
			"UserView_deleteConfirmationMessage.linkDelete" => " SUPPRIMER",
			"UserView_deleteConfirmationMessage.back" => "Retour",
			"UserView_gdpr.title" => "Protection des données",
			"UserView_gdpr.message_1" => "Les utilisateurs suivants n'ont pas utilisé ce système au cours des 24 derniers mois :",
			"UserView_gdpr.message_2" => "Conformément au règlement européen sur la protection des données (EU DSGVO), vous êtes tenu de supprimer les données des utilisateurs. Ceci inclut les enregistrements de données suivants :",
			"UserView_gdpr.account" => "Compte utilisateur",
			"UserView_gdpr.contact_details" => "Coordonnées de contact",
			"UserView_gdpr.participation" => "Contributions",
			"UserView_gdpr.Invitations " => "Invitations aux représentations, répétitions et tournées",
			"UserView_gdpr.date" => "SUPPRIMER LES DONNÉES",
			"UserView_gdprDelete.date" => "Effacer les données",
			"UserView_gdprDelete.message" => "Les données des utilisateurs suivants ont été supprimées :",
			"UserView_contactDropdown.no_contact" => "[aucun contact]",
			"UserView_additionalViewButtons.no_entry" => "Désactiver l'utilisateur",
			"UserView_additionalViewButtons.no_entry" => "Activer l'utilisateur",
			"UserView_view.user" => "Utilisateur ",
			"UserView_editEntityForm.edit_process" => " éditer",
			
			// module: Website *********************************************

			// WebsiteData		
			"WebsiteData_construct.id" => "ID",
			"WebsiteData_construct.author" => "Auteur",
			"WebsiteData_construct.createdOn" => "créé le",
			"WebsiteData_construct.editedOn" => "traitées le",
			"WebsiteData_construct.id" => "Titre",
			"WebsiteData_addImageToGallery.error_1" => "Une erreur s'est produite pendant le téléchargement. Veuillez réessayer.",
			"WebsiteData_addImageToGallery.error_2" => "Le fichier n'a pas pu être téléchargé.",
			"WebsiteData_addImageToGallery.error_3" => "Le fichier téléchargé n'est pas une image.",
			
			// WebsiteView			
			"WebsiteView_construct.title" => "Contenu du site Internet",
			"WebsiteView_construct.message" => "Cliquez sur une page pour modifier son contenu.",
			"WebsiteView_startOptions.gallery" => "Éditer les galeries",
			"WebsiteView_startOptions.copy_link" => "Pages spéciales",
			"WebsiteView_pageEditor.select_page" => "Veuillez sélectionner une page à modifier.",			
			"WebsiteView_pageEditor.pages" => "Pages",
			"WebsiteView_editPage.filename_1" => "Le fichier HTML ",
			"WebsiteView_editPage.filename_2" => " n'existe pas.",
			"WebsiteView_editPage.filename_3" => " éditer",
			"WebsiteView_editPage.submit" => "Sauvegarder",
			"WebsiteView_save.Error_1" => "Veuillez sélectionner une page à modifier!",
			"WebsiteView_save.Error_2" => "La page n'a pas pu être sauvegardée.",
			"WebsiteView_infos.title" => "Pages spéciales",
			"WebsiteView_infos.message" => "Cliquez sur une page pour l'éditer.",
			"WebsiteView_infos.id" => "ID",
			"WebsiteView_infos.createdon" => "Créé le",
			"WebsiteView_infos.editedon" => "Dernière modification le",
			"WebsiteView_infos.title" => "Titre",
			"WebsiteView_infosOptions.addInfo" => "Ajouter une page",
			"WebsiteView_addInfo.Form" => "Ajouter une page d'information",
			"WebsiteView_addInfo.title" => "Titre",
			"WebsiteView_addInfo.page" => "Texte",
			"WebsiteView_processAddInfo.error" => "La page d'information n'a pas pu être sauvegardée..",
			"WebsiteView_processAddInfo.message_1" => "Page sauvegardée",
			"WebsiteView_processAddInfo.message_2" => "La page a été sauvegardée avec succès.",
			"WebsiteView_editInfo.author" => "Rédacteur",
			"WebsiteView_editInfo.createdOn" => "Créé le:",
			"WebsiteView_editInfo.editedOn" => "Dernière modification le:",
			"WebsiteView_editInfo.processEditInfo" => "Modifier le contenu de la page",
			"WebsiteView_editInfo.processEditInfo" => "Sauvegarder",
			"WebsiteView_editInfoOptions.deleteInfo" => "Supprimer la page",
			"WebsiteView_processEditInfo.error" => "La page d'information n'a pas pu être sauvegardée..",
			"WebsiteView_processEditInfo.message" => "La page a été sauvegardée avec succès.",
			"WebsiteView_deleteInfo.message" => "La page a été supprimée avec succès.",
			"WebsiteView_backToInfos.message" => "Retour",
			"WebsiteView_gallery.title" => "Galerie",
			"WebsiteView_gallery.message" => "Pour éditer une galerie, cliquez dessus.",
			"WebsiteView_gallery.Form" => "Ajouter une galerie.",
			"WebsiteView_gallery.name" => "Nom",
			"WebsiteView_gallery_addgallery.message_1" => "Galerie créée",
			"WebsiteView_gallery_addgallery.message_2" => "La galerie a été créée avec succès.",
			"WebsiteView_gallery_viewgallery.title" => "Galerie ",
			"WebsiteView_gallery_viewgallery.message" => "Pour éditer une image, cliquez sur cette image.",
			"WebsiteView_galleryOptions.addImageForm" => "Ajouter une image",
			"WebsiteView_galleryOptions.editgallery" => "Modifier le nom de la galerie",
			"WebsiteView_galleryOptions.deletegallery" => "Supprimer la galerie",
			"WebsiteView_galleryOptions.setimageasgallerydefault" => "Définir comme vignette",
			"WebsiteView_galleryOptions.editimage" => "Modifier la description de l'image",
			"WebsiteView_galleryOptions.deleteimage" => "Supprimer l'image",
			"WebsiteView_galleryOptions.viewimage" => "Retour",
			"WebsiteView_gallery_addImageForm.form" => "Ajouter une image",
			"WebsiteView_gallery_addImageForm.name" => "Nom",
			"WebsiteView_gallery_addImageForm.description" => "Description de la",
			"WebsiteView_gallery_addImageForm.file" => "Image",
			"WebsiteView_gallery_addImageForm.editgalleryprocess" => "Changer le nom de la galerie",
			"WebsiteView_gallery_editgallery.name" => "Name",
			"WebsiteView_gallery_editgalleryprocess.message_1" => "Galerie modifiée",
			"WebsiteView_gallery_editgalleryprocess.message_2" => "La galerie a été modifiée avec succès.",
			"WebsiteView_gallery_deletegallery.message_1" => "Etes-vous sûr, que c'est vous qui allez ",
			"WebsiteView_gallery_deletegallery.message_2" => " supprimer?",
			"WebsiteView_gallery_deletegallery.message_3" => "La suppression d'une galerie supprime toutes les images et données relatives à la galerie.!",
			"WebsiteView_gallery_deletegallery.message_4" => "Galerie",
			"WebsiteView_gallery_deletegallery.message_5" => "supprimer",
			"WebsiteView_gallery_deletegallery.deletegalleryprocess" => "SUPPRIMER LA GALERIE",
			"WebsiteView_gallery_deletegalleryprocess.message_1" => "Galerie supprimée",
			"WebsiteView_gallery_deletegalleryprocess.message_2" => "La galerie a été supprimée avec succès.",
			"WebsiteView_gallery_addimage.message_1" => "Image ajoutée",
			"WebsiteView_gallery_addimage.message_2" => "L'image a été ajoutée avec succès dans la galerie.",
			"WebsiteView_gallery_editimage.Form" => "Changer l'image",
			"WebsiteView_gallery_editimage.name" => "Nom",
			"WebsiteView_gallery_editimage.description" => "Description de la",
			"WebsiteView_gallery_editimageprocess.message_1" => "Image changée",
			"WebsiteView_gallery_editimageprocess.message_2" => "Les descriptions ont été modifiées.",
			"WebsiteView_gallery_deleteimage.message_1" => "Voulez-vous vraiment effacer l'image ?",
			"WebsiteView_gallery_deleteimage.message_2" => "Voulez-vous vraiment effacer l'image ?",
			"WebsiteView_gallery_deleteimage.deleteimageprocess" => "Supprimer une image",
			"WebsiteView_gallery_deleteimage.viewimage" => "Retour",
			"WebsiteView_gallery_deleteimageprocess.message_1" => "L'image a été supprimée",
			"WebsiteView_gallery_deleteimageprocess.message_2" => "L'image a été supprimée.",
			"WebsiteView_gallery_setimageasgallerydefault.message_1" => "Image enregistrée sous forme de vignette",
			"WebsiteView_gallery_setimageasgallerydefault.message_2" => "L'image a été sauvegardée en avant-première pour cette galerie.",
			"WebsiteView_backToGallery.gallery" => "Retour",
			"WebsiteView_backToGalleryView.viewgallery" => "Retour",
	);
	
	protected $regex = array(
			"positive_amount" => '/^\d{1,12}$/',
			"positive_decimal" => '/^\d{0,8}\,\d{0,2}$/',
   			"signed_amount" => '/^-?\d{1,12}$/',
			"date" => '/^\d{1,2}.\d{1,2}.\d{4}$/',
			"datetime" => '/^\d{1,2}.\d{1,2}.\d{4}\ \d{1,2}:\d{2}$/'
	);
	
	public function formatDate($day, $month, $year, $hour, $minute) {
		$time = "";
		if($hour != null && $minute != null) {
			$time = " $hour:$minute";
		} 
		return "$day.$month.$year" . $time;
	}
	
	public function formatDateForDb($formattedDate) {
		if(strlen($formattedDate) > 10) {
			// datetime conversion
			$dot1 = strpos($formattedDate, ".");
			$dot2 = strpos($formattedDate, ".", $dot1+1);
				
			$time = substr($formattedDate, $dot2+6, 5) . ":00";
			$year = substr($formattedDate, $dot2+1, 4);
			$month = substr($formattedDate, $dot1+1, $dot2-$dot1-1);
			$day = substr($formattedDate, 0, $dot1);
			return $year . "-" . $month . "-" . $day . " $time";
		}
		else if(strlen($formattedDate) > 5) {
			// standard conversion
			$dot1 = strpos($formattedDate, ".");
			$dot2 = strpos($formattedDate, ".", $dot1+1);
			$year = substr($formattedDate, $dot2+1);
			$month = substr($formattedDate, $dot1+1, $dot2-$dot1-1);
			$day = substr($formattedDate, 0, $dot1);
			return $year . "-" . $month . "-" . $day;
		}
		else {
			return $formattedDate;
		}
	}
	
	public function getMonths() {
		return array(
				1 => "Janvier",
				2 => "Février",
				3 => "Mars",
				4 => "Avril",
				5 => "Mai",
				6 => "Juin",
				7 => "Juillet",
				8 => "Août",
				9 => "Septembre",
				10 => "Octobre",
				11 => "Novembre",
				12 => "Décembre"
		);
	}
	
	public function convertEnglishWeekday($wd) {
		$res = "";
		switch($wd) {
			case "Mon": $res = "Lundi"; break;
			case "Monday": $res = "Lundi"; break;
			case "Tue": $res = "Mardi"; break;
			case "Tuesday": $res = "Mardi"; break;
			case "Wed": $res = "Mercredi"; break;
			case "Wednesday": $res = "Mercredi"; break;
			case "Thu": $res = "Jeudi"; break;
			case "Thursday": $res = "Jeudi"; break;
			case "Fri": $res = "Vendredi"; break;
			case "Friday": $res = "Vendredi"; break;
			case "Sat": $res = "Samedi"; break;
			case "Saturday": $res = "Samedi"; break;
			case "Sun": $res = "Dimanche"; break;
			case "Sunday": $res = "Dimanche"; break;
		}
		return $res;
	}
	
	public function getDateTimeFormatPattern() {
		return "d.m.Y H:i";
	}
	
	public function getDateFormatPattern() {
		return "d.m.Y";
	}
	
	public function getRegex($patternCode) {
		if(!isset($this->regex[$patternCode])) {
			return null;
		}
		return $this->regex[$patternCode];
	}
	
	public function decimalToDb($decimal) {
		$dec = str_replace(".", "", $decimal);  # remove thousand separator
		return str_replace(",", ".", $dec);  # remove decimal separator
	}
	
	public function formatDecimal($dbDecimal) {
		if($dbDecimal == null) return "";
		return number_format(doubleval($dbDecimal), 2, ',', '.');
	}
}

?>