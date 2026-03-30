/**
 * BNote Next Generation - Data privacy / Datenschutz text (from legacy data/terms.html)
 *
 * Copyright (C) 2026 BNote Contributors
 */

export function PrivacyLegalContent() {
  return (
    <div className="space-y-6 text-sm leading-6 text-base-content/85">
      <p>
        BNote speichert und verarbeitet personenbezogene Daten, daher müssen nach EU Datenschutzgrundverordnung
        (DSGVO) die Betreiber von BNote Auskunft über den Datenschutz von BNote geben. Diese Seite soll hier
        Erleichterung schaffen.
      </p>

      <section className="rounded-lg border border-base-300 p-5">
        <h2 className="mb-2 text-base font-semibold text-base-content">Verantwortlicher</h2>
        <p>
          Der Betrieber dieser Plattform ist für den Inhalt verantwortlich. Die Matti Maier und Stefan Kreminski
          BNote Software GbR stellt lediglich die Software zur Verfügung, jedoch ohne personenbezogene Inhalte.
          Diese werden im Laufe des Installations- und Einrichtungsvorgang durch den Betreiber ergänzt.
        </p>
      </section>

      <section className="rounded-lg border border-base-300 p-5">
        <h2 className="mb-2 text-base font-semibold text-base-content">Allgemein</h2>
        <p>
          Innerhalb des Internetangebotes besteht die Möglichkeit zur Eingabe persönlicher oder geschäftlicher
          Daten (Emailadressen, Namen, Anschriften). Die Preisgabe dieser Daten seitens des Nutzers erfolgt auf
          ausdrücklich freiwilliger Basis; wobei diese Daten aber auch ausnahmslos vertraulich und sorgsam
          behandelt werden. Die Nutzung der im Rahmen des Impressums oder vergleichbarer Angaben veröffentlichten
          Kontaktdaten wie Postanschriften, Telefon- und Faxnummern sowie Emailadressen durch Dritte zur
          Übersendung von nicht ausdrücklich angeforderten Informationen ist nicht gestattet. Rechtliche Schritte
          gegen die Versender von sogenannten Spam-Mails bei Verstößen gegen dieses Verbot sind ausdrücklich
          vorbehalten.
        </p>
      </section>

      <section className="rounded-lg border border-base-300 p-5">
        <h2 className="mb-3 text-base font-semibold text-base-content">
          Welche personenbezogenen Daten werden gespeichert und verarbeitet?
        </h2>
        <p className="mb-3">
          Bei der Installation von BNote kann der Administrator selbst entscheiden wie viel Daten er preisgibt.
          Es können folgende Daten über ein <strong>Ensemble</strong> (z.B. Personengruppe) erfasst werden:
        </p>
        <ul className="mb-4 list-disc space-y-1 pl-6">
          <li>Name</li>
          <li>Anschrift: Straße, PLZ, Ort</li>
          <li>Telefonnummer, Website/URL, E-Mail-Adresse</li>
        </ul>
        <p className="mb-3">
          Zusätzlich kann jeder Benutzer (auch der Administrator) persönliche Daten in BNote erfassen. Folgende
          Felder stehen hierfür zur Verfügung:
        </p>
        <ul className="mb-4 list-disc space-y-1 pl-6">
          <li>Vorname, Nachname, Spitzname</li>
          <li>Telefonnummern: Telefon, Fax, Mobil, Geschäftlich</li>
          <li>E-Mail-Adresse und Website-URL</li>
          <li>Weitere Anmerkungen zum Kontakt/zur Person</li>
          <li>Instrument</li>
          <li>Anschrift: Straße, PLZ, Ort</li>
          <li>Geburtstag</li>
          <li>Gruppenzugehörigkeit innerhalb der in BNote verwalteten Gruppen</li>
          <li>Benutzerkonto: Benutzernamen, Passwort</li>
          <li>
            Außerdem kann der Administrator weitere Felder definieren (ab BNote 3.3.0), sodass weitere Daten über
            die Personen gespeichert werden können.
          </li>
        </ul>
        <p>
          Neben Daten über die Benutzer von BNote, können auch andere Kontakte wie z.B. Veranstalter, Aushilfen,
          etc. erfasst werden. Welche Daten erfasst werden obliegt dem Betreiber von BNote selbst.
        </p>
      </section>

      <section className="rounded-lg border border-base-300 p-5">
        <h3 className="mb-2 text-base font-semibold text-base-content">Zweck der Datenverarbeitung</h3>
        <p>
          Daten zu Benutzern werden benötigt, sodass Gruppenmitglieder untereinander Kontakt aufnehmen können um
          beispielsweise vor einer Veranstaltung den Treffpunkt auszumachen. Daten von anderen Kontakten werden
          für Direktmarketing und für Rückfragen zu Veranstaltungen, Aushilfen und Agenten benötigt.
        </p>
      </section>

      <section className="rounded-lg border border-base-300 p-5">
        <h3 className="mb-2 text-base font-semibold text-base-content">Dauer der Datenverarbeitung</h3>
        <p>Für die Dauer des Betriebs von BNote werden die Daten auf einem Datenbankserver gespeichert.</p>
      </section>

      <section className="rounded-lg border border-base-300 p-5">
        <h2 className="mb-3 text-base font-semibold text-base-content">Wer hat Einsicht in die Daten?</h2>
        <p className="mb-3">
          Ein Datenbankadministrator hat auf alle Daten Zugriff mit Ausnahme der Benutzerpasswörter. Diese werden
          verschlüsselt in der Datenbank aufbewahrt.
        </p>
        <p className="mb-3">
          Ein BNote-Administrator hat Zugriff auf alle Daten in BNote und kann Passwörter zurücksetzen und sich
          damit Zugriff auf ein Benutzerkonto verschaffen. Außerdem kann ein BNote-Administrator Benutzerkonten
          sperren und Daten löschen.
        </p>
        <p className="mb-3">
          Ein regulärer BNote-Benutzer (auch &quot;Mitglied&quot; genannt) kann nur auf seine eigenen
          persönlichen Daten zugreifen und diese auch ändern. Des weiteren hat er Zugriff auf folgende
          personenbezogenen Daten anderer Mitgliedern sofern diese der gleichen Gruppe zugeordnet sind,
          mindestens eine Probenphase, eine Tour, ein Konzert oder eine Probe teilen:
        </p>
        <ul className="mb-4 list-disc space-y-1 pl-6">
          <li>Vorname, Nachname, Spitzname</li>
          <li>Telefon-, Mobil-, Fax und geschäftliche Rufnummer</li>
          <li>Anschrift: Straße, PLZ, Stadt</li>
          <li>Instrument</li>
          <li>Geburtstag</li>
        </ul>
        <p>
          Ein BNote-Administrator kann Benutzern weitere Rechte einräumen, sodass diese weitere Kontaktdaten
          einsehen und verändern können. Hierfür existiert ein gesondertes Berechtigungssystem.
        </p>
      </section>

      <section className="rounded-lg border border-base-300 p-5">
        <h2 className="mb-2 text-base font-semibold text-base-content">
          Wie werden die Daten geschützt? Welche Sicherheitsmaßnahmen sind zu beachten?
        </h2>
        <p>
          Prinzipiell sollte der BNote-Betreiber die Sicherheitshinweise (im System, nach Anmeldung des
          Administrators unter Hilfe &gt; Sicherheitshinweise) beachten und umsetzen. Danach wird die Kommunikation
          zwischen dem BNote Server und den Endgeräten über HTTPS geschützt. Außerdem werden die Passwörter mit
          einem individuellen Schlüssel/Salt verschlüsselt. Innerhalb von BNote ist in den Hilfeseiten dokumentiert
          wie das Berechtigungs- und Sicherheitssystem von BNote funktioniert und konfiguriert wird.
        </p>
      </section>

      <section className="rounded-lg border border-base-300 p-5">
        <h2 className="mb-2 text-base font-semibold text-base-content">
          Wie kann jedes Ensemble-Mitglied seine Daten bearbeiten/einsehen/löschen?
        </h2>
        <p>
          Jeder Benutzer hat nach Anmeldung über seinen Namen bzw. die Schaltfläche &quot;Kontaktdaten&quot; die
          Möglichkeit zu entscheiden welche Daten er teilen möchte. Im Extremfall ist nur ein Name zu vergeben.
          Wird dies nicht getan, ist der Zweck von BNote - eine gemeinsame Plattform für die Ensembleorganisation zu
          schaffen - nichtig.
        </p>
      </section>
    </div>
  );
}
