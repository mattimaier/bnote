<?php
require_once 'lang_base.php';
		
/**
 * Spanish translation of BNote.
 */
		
class Translation extends BNoteTranslation {
		
    protected $texts = array(
		
        // Installation *************************************************************************************************************************************************
		
        // Installation
        "Installation_welcome.title" => "Bienvenido",
        "Installation_welcome.message_1" => "Gracias por elegir BNote. Estás haciendo un gran favor a ti y a tu banda.
                Ahora solo necesitas completar la instalación y estarás listo para comenzar!",
        "Installation_welcome.message_2" => "Ya has dado el primer paso hacia BNote.
                Has descomprimido BNote, lo has cargado en tu servidor web y has iniciado la instalación.
                Para completar la instalación con éxito, sigue estos pasos:",
        "Installation_welcome.message_3" => "Crea un nuevo usuario de base de datos MySQL con contraseña y asígnale una nueva base de datos vacía.",
        "Installation_welcome.message_4" => "Asegúrate de que este script pueda escribir en la carpeta config/ de BNote.<br/>
                Normalmente, debes hacer que la carpeta sea accesible para el usuario del servidor web o
                decirle a tu proveedor de hosting que permita que los scripts escriban.",
        "Installation_welcome.message_5" => "Ten a mano el nombre y la dirección de tu banda.",
        "Installation_companyConfig.message_1" => "Configuración de la banda ya existente", "Se detectó que ya has creado una configuración de banda.
                Por lo tanto, puedes omitir este paso. Para cambiar los datos principales, edita el archivo BNote/config/company.xml.",
        "Installation_companyConfig.title" => "Tu Banda",
        "Installation_companyConfig.message_2" => "Por favor, introduce los datos de contacto de tu banda.",
        "Installation_companyConfig.Form" => "Configuración de la Banda",
        "Installation_companyConfig.Name" => "Nombre de la Banda",
        "Installation_companyConfig.Street" => "Calle",
        "Installation_companyConfig.Zip" => "Código Postal",
        "Installation_companyConfig.City" => "Ciudad",
        "Installation_companyConfig.Country" => "País",
        "Installation_companyConfig.Phone" => "Teléfono",
        "Installation_companyConfig.Mail" => "Correo del Líder de la Banda",
        "Installation_companyConfig.Web" => "Sitio Web",
        "Installation_companyConfig.submit" => "Continuar",
        "Installation_companyConfig.Error" => "No se pudo escribir la configuración. Asegúrate de que BNote pueda escribir en el directorio config/.",
        "Installation_write_appConfig.Error" => "No se pudo escribir la configuración. Asegúrate de que BNote pueda escribir en el directorio config/.",
        "Installation_databaseConfig.title" => "Configuración de la Base de Datos",
        "Installation_databaseConfig.message_1" => "Configuración de la base de datos ya existente", "Se detectó que ya has creado una configuración de base de datos.
                Por lo tanto, puedes omitir este paso.",
        "Installation_databaseConfig.message_2" => "Por favor, introduce los datos de acceso a la base de datos de BNote.",
        "Installation_databaseConfig.Form" => "Configuración de la Base de Datos",
        "Installation_databaseConfig.Server" => "Servidor",
        "Installation_databaseConfig.Port" => "Puerto",
        "Installation_databaseConfig.Name" => "Nombre de la Base de Datos",
        "Installation_databaseConfig.User" => "Nombre de Usuario",
        "Installation_databaseConfig.Password" => "Contraseña",
        "Installation_databaseConfig.Submit" => "Continuar",
        "Installation_process_databaseConfig.error" => "No se pudo escribir la configuración. Asegúrate de que BNote pueda escribir en el directorio config/.",
        "Installation_adminUser.title" => "Crear Usuario",
        "Installation_adminUser.form" => "Nuevo Usuario",
        "Installation_adminUser.login" => "Nombre de Usuario",
        "Installation_adminUser.password" => "Contraseña",
        "Installation_adminUser.name" => "Nombre",
        "Installation_adminUser.surname" => "Apellido",
        "Installation_adminUser.company" => "Organización",
        "Installation_adminUser.phone" => "Teléfono",
        "Installation_adminUser.mobile" => "Móvil",
        "Installation_adminUser.email" => "Correo Electrónico",
        "Installation_adminUser.street" => "Calle",
        "Installation_adminUser.zip" => "Código Postal",
        "Installation_adminUser.city" => "Ciudad",
        "Installation_adminUser.state" => "Estado",
        "Installation_adminUser.country" => "País",
        "Installation_process_adminUser.error" => "Contraseña inválida. Asegúrate de que la contraseña tenga al menos 6 caracteres y no esté vacía.",
        "Installation_finalize.title" => "Qué más hacer...",
        "Installation_finalize.message" => "¡Lo lograste: La instalación está completa!
                Así es como comienzas con BNote:",
        "Installation_finalize.message_1" => "Inicia sesión en ",
        "Installation_finalize.message_2" => ".",
        "Installation_finalize.message_3" => "Ahora eres administrador y tienes acceso a todo el sistema. ¡Úsalo con precaución!",
        "Installation_finalize.message_4" => "Ve al módulo de datos de contacto y completa tus datos de contacto.",
        "Installation_finalize.message_5" => "Elimina el script install.php de la carpeta de BNote
                para evitar accesos no autorizados.",
        "Installation_finalize.login" => "Iniciar sesión",
        "Installation_next.next" => "Continuar",
		
        // Export *************************************************************************************************************************************************
		
        // AbstractBNA
        "AbstractBNA_sendMail.error" => "No se encontraron destinatarios.",
		
        // BNote Interface (BNI)
        "bni_getThumbPath.error" => "ID no establecida.",
        "bni_getImagePath.error" => "ID no establecida.",
        "bni_getGallery.error" => "ID no establecida.",
        "bni_getImagesForGallery.error" => "ID no establecida.",
        "bni_getImage.error" => "ID no establecida.",
		
        // BNote Interface (BNI) - XML
        "bnixml_getImagePath.error" => "ID no establecida.",
        "bnixml_getThumbPath.error" => "ID no establecida.",
        "bnixml_getGallery.error" => "ID no establecida.",
        "bnixml_getImagesForGallery.error" => "ID no establecida.",
        "bnixml_getImage.error" => "ID no establecida.",
		
        // Gigcard
        "gigcard_concert.deniedMsg" => "¡No tienes permiso para exportar la actuación!",
        "gigcard_concert.event" => "Evento",
        "gigcard_concert.organizer" => "Organizador",
        "gigcard_concert.address" => "Dirección",
        "gigcard_concert.contact" => "Contacto",
        "gigcard_concert.times" => "Horarios",
        "gigcard_concert.period" => "Fecha/Hora",
        "gigcard_concert.meetingtime" => "Hora de encuentro",
        "gigcard_concert.approve_until" => "Aprobación hasta",
        "gigcard_concert.organisation" => "Organización",
        "gigcard_concert.groupNames" => "Conjunto",
        "gigcard_concert.program" => "Programa",
        "gigcard_concert.outfit" => "Vestimenta",
        "gigcard_concert.equipment" => "Equipo",
        "gigcard_concert.details" => "Detalles",
        "gigcard_concert.accommodation" => "Alojamiento",
        "gigcard_concert.payment" => "Pago",
        "gigcard_concert.conditions" => "Condiciones",
		
        // Notify
        "Notifier_sendRehearsalNotification.message_1" => "Ensayo el",
        "Notifier_sendRehearsalNotification.message_2" => "- Recordatorio",
        "Notifier_sendRehearsalNotification.message_3" => "Por favor, confirma tu participación en el ensayo del",
        "Notifier_sendRehearsalNotification.message_4" => "en.<br/>",
        "Notifier_sendRehearsalNotification.message_5" => "Abrir BNote",
        "Notifier_sendRehearsalNotification.message_6" => "¡Gracias!",
        "Notifier_sendConcertNotification.message_1" => " - Recordatorio",
        "Notifier_sendConcertNotification.message_2" => "Por favor, confirma tu participación en ",
        "Notifier_sendConcertNotification.message_3" => " en:<br/>",
        "Notifier_sendConcertNotification.message_4" => "Abrir BNote",
        "Notifier_sendConcertNotification.message_5" => "¡Gracias!",
        "Notifier_sendVoteNotification.message_1" => " - Recordatorio",
        "Notifier_sendVoteNotification.message_2" => "Por favor, vota para ",
        "Notifier_sendVoteNotification.message_3" => " :<br/>",
        "Notifier_sendVoteNotification.message_4" => "Abrir BNote",
        "Notifier_sendVoteNotification.message_5" => "¡Gracias!",
		
        // Program_csv
        "program_csv_Notifier_start.deniedMsg" => "¡No tienes permiso para exportar los contactos!",
        "program_csv_Notifier_start.error" => "Por favor, indica el número del programa que deseas exportar.",
        "program_csv_Notifier_start.title" => "Título",
        "program_csv_Notifier_start.composer" => "Compositor/Arreglista",
        "program_csv_Notifier_start.duration" => "Duración",
        "program_csv_Notifier_start.bpm" => "BPM",
        "program_csv_Notifier_start.key" => "Tonalidad",
        "program_csv_Notifier_start.gender" => "Género",
        "program_csv_Notifier_start.status" => "Estado",
        "program_csv_Notifier_start.notes" => "Notas",
		
        // Repertoire
        "repertoire_start.deniedMsg" => "¡No tienes permiso para exportar el repertorio!",
		
        // Repertoire_files
        "repertoire_files_start.deniedMsg" => "¡No tienes permiso para exportar el repertorio!",
        "repertoire_files_start.message" => "Se necesitan al menos 3 caracteres.",
		
        // TriggerServiceClient
        "TriggerServiceClient_createTrigger.error" => "No se pueden crear notificaciones.",
		
        // useractivation
        "useractivation_input.printError" => "Entrada no válida.",
        "useractivation_validate.printError" => "ID de usuario no encontrada.",
        "useractivation_user_email.printError" => "Correo electrónico no válido.",
        "useractivation_update.message" => "<p><b>¡Cuenta de usuario activada!</b><br/>Tu cuenta de usuario ha sido activada con éxito. Ahora puedes iniciar sesión.</p>",
        "useractivation_update.error_1" => "<p><b>¡Error!</b><br/>La activación no fue exitosa. Por favor, contacta a tu líder.<br/>",
        "useractivation_update.error_2" => "<i>Mensaje de error:",
        "useractivation_update.error_3" => "</i></p>",
		
        // Vcard
        "vcard_input.deniedMsg" => "¡No tienes permiso para exportar los contactos!",
		
        // Export *************************************************************************************************************************************************
		
        // Memberlist
        "MembersPDF_construct.title" => "Lista de Miembros",
        "MembersPDF_writeTable.surname" => "Apellido",
        "MembersPDF_writeTable.title" => "Nombre",
        "MembersPDF_writeTable.phone" => "Privado",
        "MembersPDF_writeTable.mobile" => "Móvil",
        "MembersPDF_writeTable.occupation" => "Ocupación",
        "MembersPDF_writeTable.email" => "Correo Electrónico",
        "MembersPDF_writeTable.street" => "Calle",
        "MembersPDF_writeTable.city" => "Ciudad",
        "MembersPDF_writeTable.zip" => "Código Postal",
        "MembersPDF_writeTable.instrument" => "Instrumento",
		
        // MemberlistPDF
        "MemberlistPDF_Header.title" => "Lista de Contactos",
		
        // PartlistPDF
        "MembersPDF_PartlistPDF.title" => "Lista de Participantes",
        "MembersPDF_contents.from" => "Ensayo el",
        "MembersPDF_contents.hour" => "Hora",
        "MembersPDF_contents.location" => "Lugar",
        "MembersPDF_contents.notes" => "Notas",
        "MembersPDF_contents.name" => "Nombre",
        "MembersPDF_contents.Instrument" => "Instrumento",
        "MembersPDF_addSignatureCol.contact" => "Firma",
		
        // PDFTable
        "PDFTable_write.novalue" => "No hay entradas disponibles.",
		
        // ProgramPDF
        "ProgramPDF_writeTable.title" => "Título",
        "ProgramPDF_writeTable.arranger" => "Arreglista",
        "ProgramPDF_writeTable.notes" => "Notas",
        "ProgramPDF_writeTable.length" => "Duración",
        "ProgramPDF_writeTable.total_length" => "Duración Total",
		
        // Logic *************************************************************************************************************************************************
		
        // ProgramPDF
        "Mailing_sendMail.BNoteError_1" => "El sistema está en modo de demostración y, por lo tanto, no envía correos electrónicos.",
        "Mailing_sendMail.BNoteError_2" => "Por favor, indica BCC o AN o ambos campos.",
        "Mailing_sendMail.BNoteError_3" => "No se ha especificado ningún mensaje.",
        "Mailing_sendMail.BNoteError_4" => "No se ha especificado ningún asunto.",
        "Mailing_sendMail.BNoteError_5" => "El mensaje no pudo ser enviado. Error del correo:",
        "Mailing_sendMail.BNoteError_6" => "No se pudo enviar el correo electrónico.",
		
        // General *************************************************************************************************************************************************
		
        // General: AbstractData
        "AbstractData_adp.message" => "¡Proveedor de datos de la aplicación no configurado! Llama a init()!",
        "AbstractData_validate.error" => "Por favor, proporciona suficiente información.",
		
        // General: AbstractLocation
        "AbstractLocationData_getAddressViewFields.street" => "Calle",
        "AbstractLocationData_getAddressViewFields.city" => "Ciudad",
        "AbstractLocationData_getAddressViewFields.zip" => "Código Postal",
        "AbstractLocationData_getAddressViewFields.state" => "Estado",
        "AbstractLocationData_getAddressViewFields.country" => "País",
		
        // General: Abstract
        "AbstractView_backToStart.back" => "Atrás",
        "AbstractView_deleteConfirmationMessage.delete" => "Eliminar",
        "AbstractView_deleteConfirmationMessage.reallyDeleteQ" => "¿Realmente deseas eliminar esta entrada?",
        "AbstractView_deleteConfirmationMessage.back" => "Atrás",
        "AbstractView_checkID.noUserId" => "Por favor, proporciona una ID de usuario.",
		
        // General: banner
        "banner_Logout.welcome" => "Bienvenido",
        "banner_Logout.Logout" => "Cerrar sesión",
		
        // General: Crud
        "CrudRefView_addEntityForm.getEntityName" => " añadir",
        "CrudView_start.showAllTable" => "Por favor, selecciona una entrada para mostrarla o editarla.",
        "CrudView_add.Message_1" => "%p guardado",
        "CrudView_add.Message_2" => "La entrada ha sido guardada con éxito.",
        "CrudView_view.Message" => "Detalles de %p",
        "CrudView_viewOptions.edit" => "Editar %p",
        "CrudView_viewOptions.delete_confirm" => "Eliminar %p",
        "CrudView_editEntityForm.delete_edit" => "editar",
        "CrudView_edit_process.delete_changed" => "cambiado",
        "CrudView_edit_process.delete_changed" => "La entrada ha sido cambiada con éxito.",
        "CrudView_delete.deleted_entity" => "%p eliminado",
        "CrudView_delete.entryDeleted" => "La entrada ha sido eliminada con éxito.",
        "CrudView_backToViewButton.back" => "Atrás",
		
        // General: CrudRefLocation
        "CrudRefLocationView_renameTableAddressColumns.street" => "Calle",
        "CrudRefLocationView_renameTableAddressColumns.city" => "Ciudad",
        "CrudRefLocationView_renameTableAddressColumns.zip" => "Código Postal",
        "CrudRefLocationView_renameTableAddressColumns.state" => "Estado",
        "CrudRefLocationView_renameTableAddressColumns.country" => "País",
        "CrudRefLocationView_renameDataViewFields.street" => "Calle",
        "CrudRefLocationView_renameDataViewFields.city" => "Ciudad",
        "CrudRefLocationView_renameDataViewFields.zip" => "Código Postal",
        "CrudRefLocationView_renameDataViewFields.state" => "Estado",
        "CrudRefLocationView_renameDataViewFields.country" => "País",
        "CrudRefLocationView_replaceDataViewFieldWithAddress.address" => "Dirección",
		
        // General: navigation
        "navigation_Login" => "Iniciar sesión",
        "navigation_Start" => "Inicio",
        "navigation_User" => "Usuario",
        "navigation_Kontakte" => "Contactos",
        "navigation_Konzerte" => "Actuaciones",
        "navigation_Proben" => "Ensayos",
        "navigation_Repertoire" => "Repertorio",
        "navigation_Kommunikation" => "Comunicación",
        "navigation_Locations" => "Ubicaciones",
        "navigation_Kontaktdaten" => "Datos de Contacto",
        "navigation_Hilfe" => "Ayuda",
        "navigation_Website" => "Sitio Web",
        "navigation_Share" => "Compartir",
        "navigation_Mitspieler" => "Miembros",
        "navigation_Abstimmung" => "Votaciones",
        "navigation_Konfiguration" => "Configuración",
        "navigation_Aufgaben" => "Tareas",
        "navigation_Nachrichten" => "Mensajes",
        "navigation_Probenphasen" => "Fases de Ensayo",
        "navigation_Finance" => "Finanzas",
        "navigation_Calendar" => "Calendario",
        "navigation_Passwort" => "Olvidé mi Contraseña",
        "navigation_Warum BNote?" => "¿Por qué BNote?",
        "navigation_Registrierung" => "Registro",
        "navigation_Bedingungen" => "Condiciones",
        "navigation_Impressum" => "Aviso Legal",
        "navigation_Equipment" => "Equipo",
        "navigation_Tour" => "Gira",
        "navigation_Outfits" => "Vestimenta",
        "navigation_Stats" => "Estadísticas",
        "navigation_Home" => "Bienvenido",
        "navigation_Login" => "Iniciar sesión",
        "navigation_" => ""
	);
}
?>