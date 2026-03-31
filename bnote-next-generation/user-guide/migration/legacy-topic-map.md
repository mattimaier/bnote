# Legacy Help Topic Map (old BNote -> Next Generation User Guide)

This map translates old BNote tutorial/help topics to the new non-technical user guide structure.

## Source Of Legacy Topics

- Legacy help index: `BNote/src/presentation/modules/hilfeview.php`
- Legacy pages: `BNote/data/help/de/*.html`, `BNote/data/help/fr/*.html`

## Topic Mapping

| Legacy topic ID | Old meaning | New guide destination |
| --- | --- | --- |
| `kontakte` | Contacts and people data | `user-guide/i18n/<lang>/features/contacts.md` |
| `mitspieler` | Members and participant handling | `user-guide/i18n/<lang>/features/participation.md` |
| `proben` | Rehearsals/events workflow | `user-guide/i18n/<lang>/features/events.md` |
| `probenphase` | Rehearsal-phase process | `user-guide/i18n/<lang>/features/events.md` |
| `calendar` | Calendar coordination | `user-guide/i18n/<lang>/features/calendar.md` |
| `nachrichten` | Messaging/updates | `user-guide/i18n/<lang>/features/communication.md` |
| `aufgaben` | Tasks and responsibilities | `user-guide/i18n/<lang>/features/tasks.md` |
| `abstimmung` | Votes/polls | `user-guide/i18n/<lang>/features/votes.md` |
| `share` | Shared resources/files | `user-guide/i18n/<lang>/features/share.md` |
| `repertoire` | Piece/repertoire management | `user-guide/i18n/<lang>/features/repertoire.md` |
| `equipment` | Equipment management | `user-guide/i18n/<lang>/features/equipment.md` |
| `finance` | Financial workflows | `user-guide/i18n/<lang>/features/finance.md` |
| `tour` | Tour/travel planning | `user-guide/i18n/<lang>/features/tour.md` |
| `konfiguration` | Settings/configuration | `user-guide/i18n/<lang>/admin/configuration.md` |
| `sicherheit` | Security and account safety | `user-guide/i18n/<lang>/admin/security.md` |

## Known Gaps To Fill

1. Feature pages listed above still need authoring for all languages.
2. Some legacy terms do not match the new UI wording one-to-one and need glossary alignment.
3. Legacy video links are historical; create new short, app-current walkthroughs where needed.

## Deprecation Notes

- Legacy wording should be supported temporarily in migration pages to reduce confusion.
- "What's New" updates should highlight renamed areas and changed navigation.
