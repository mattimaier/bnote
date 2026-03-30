/**
 * BNote Next Generation - Imprint legal text (shared)
 *
 * Copyright (C) 2026 BNote Contributors
 */

const legalSections = [
  {
    title: "Urheber- und Kennzeichenrecht",
    body:
      "Der Autor ist bestrebt, in allen Publikationen die Urheberrechte der verwendeten Grafiken, Tondokumente, Videosequenzen und Texte zu beachten und anzugeben bzw. möglichst auf lizenzfreie Grafiken, Tondokumente, Videosequenzen und Texte zurückzugreifen. Es wurde sich bemüht, alle bekannten Quellen und Herkunftsorte der dargestellten Bild- oder Textdokumente kenntlich zu machen. Sollten sich Personen oder Interessengruppen trotz der Quellenangabe in ihrem Copyright oder Urheberrecht verletzt fühlen, wird um eine kurze und problemlose Mail gebeten, nach dessen Eingang unverzüglich alle zu beanstandeten Dateien entfernt werden. Alle innerhalb des Internetangebotes genannten und ggf. durch Dritte geschützten Marken- und Warenzeichen unterliegen uneingeschränkt den Bestimmungen des jeweils gültigen Kennzeichenrechts und den Besitzrechten der jeweiligen eingetragenen Eigentümer. Allein aufgrund der Verwendung auf dieser Internetpräsenz ist nicht der Schluß zu ziehen, daß die verwendeten Dateien nicht durch entsprechende Rechte Dritter geschützt sind!",
  },
  {
    title: "Inhalt des Onlineangebotes",
    body:
      "Der Autor übernimmt keinerlei Gewähr für die Aktualität, Korrektheit, Vollständigkeit oder Qualität der bereitgestellten Informationen. Haftungsansprüche gegen den Autor, welche sich auf Schäden materieller oder ideeller Art beziehen, die durch die Nutzung oder Nichtnutzung der dargebotenen Informationen bzw. durch die Nutzung fehlerhafter und unvollständiger Informationen verursacht wurden, sind grundsätzlich ausgeschlossen, sofern seitens des Autors kein nachweislich vorsätzliches oder grob fahrlässiges Verschulden vorliegt. Alle Angebote sind freibleibend und unverbindlich. Der Autor behält es sich ausdrücklich vor, Teile der Seiten oder das gesamte Angebot ohne gesonderte Ankündigung zu verändern, zu ergänzen, zu löschen oder die Veröffentlichung zeitweise oder endgültig einzustellen.",
  },
  {
    title: "Verweise und Links",
    body:
      "Bei direkten oder indirekten Verweisen auf fremde Webseiten (\"Hyperlinks\"), die außerhalb des Verantwortungsbereiches des Autors liegen, würde eine Haftungsverpflichtung ausschließlich in dem Fall in Kraft treten, in dem der Autor von den Inhalten Kenntnis hat und es ihm technisch möglich und zumutbar wäre, die Nutzung im Falle rechtswidriger Inhalte zu verhindern. Der Autor erklärt hiermit ausdrücklich, dass zum Zeitpunkt der Linksetzung keine illegalen Inhalte auf den zu verlinkenden Seiten zu erkennbar waren. Auf die aktuelle und zukünftige Gestaltung, die Inhalte oder die Urheberschaft der gelinkten/verknüpften Seiten hat der Autor keinerlei Einfluß. Deshalb distanziert er sich hiermit ausdrücklich von allen Inhalten aller gelinkten/verknüpften Seiten, die nach der Linksetzung verändert wurden. Diese Feststellung gilt für alle innerhalb des eigenen Internetangebotes gesetzten Links und Verweise sowie für Fremdeinträge in vom Autor eingerichteten Gästebüchern, Diskussionsforen und Mailinglisten. Für illegale, fehlerhafte oder unvollständige Inhalte und insbesondere für Schäden, die aus der Nutzung oder Nichtnutzung solcherart dargebotener Informationen entstehen, haftet allein der Anbieter der Seite, auf welche verwiesen wurde, nicht derjenige, der über Links auf die jeweilige Veröffentlichung lediglich verweist.",
  },
  {
    title: "Rechtswirksamkeit dieses Haftungsausschlusses",
    body:
      "Dieser Haftungsausschluß ist als Teil des Internetangebotes zu betrachten, von dem aus auf diese Seite verwiesen wurde. Sofern Teile oder einzelne Formulierungen dieses Textes der geltenden Rechtslage nicht, nicht mehr oder nicht vollständig entsprechen sollten, bleiben die übrigen Teile des Dokumentes in ihrem Inhalt und ihrer Gültigkeit davon unberührt.",
  },
];

export function ImprintLegalContent() {
  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-base-300 bg-base-200 p-5">
        <h2 className="mb-2 text-lg font-semibold text-base-content">Software Entwickler</h2>
        <p className="text-sm leading-6 text-base-content/85">
          Matti Maier und Stefan Kreminski
          <br />
          E-Mail: mail [at] bnote.info
          <br />
          Web:{" "}
          <a
            href="https://www.bnote.info"
            target="_blank"
            rel="noopener noreferrer"
            className="link link-primary"
          >
            www.bnote.info
          </a>
        </p>
      </section>

      <div className="space-y-5">
        {legalSections.map((section) => (
          <section key={section.title} className="rounded-lg border border-base-300 p-5">
            <h2 className="mb-2 text-base font-semibold text-base-content">{section.title}</h2>
            <p className="text-sm leading-6 text-base-content/80">{section.body}</p>
          </section>
        ))}
      </div>
    </div>
  );
}
