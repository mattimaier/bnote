<?php
declare(strict_types=1);

require_once __DIR__ . '/MailEnv.php';
require_once __DIR__ . '/MailI18n.php';
require_once __DIR__ . '/MailEntityColors.php';
require_once __DIR__ . '/MailEntityIcons.php';
require_once __DIR__ . '/MailLocaleDateTime.php';

final class MailPreviewFixtures {
    public static function systemData(): object {
        return new class () {
            public function getCompany(): string {
                return 'Demo Band';
            }

            public function getLang(): string {
                return 'en';
            }

            /** @param mixed $key */
            public function getDynamicConfigParameter($key) {
                if ($key === 'allow_participation_maybe') {
                    return 1;
                }
                return 0;
            }
        };
    }

    /** @return array{userId:int,contactId:int,name:string,surname:string,email:string,login:string,autoUserActivation:bool} */
    public static function newUserCtx(): array {
        return [
            'userId' => 42,
            'contactId' => 7,
            'name' => 'Jana',
            'surname' => 'Example',
            'email' => 'jana@example.com',
            'login' => 'jana@example.com',
            'autoUserActivation' => false,
        ];
    }

    public static function previewToEmail(): string {
        return 'preview@example.com';
    }

    /** First name for mail previews (admin / comment / long-demo salutation). */
    public static function recipientPreviewFirstName(): string {
        return 'Sam';
    }

    public static function demoResetUrl(): string {
        $base = MailEnv::nextgenPublicBaseUrl();
        if ($base !== '') {
            return $base . '/reset-password/confirm/?token=demo-token-preview';
        }
        return 'https://example.org/bnote-next-generation/reset-password/confirm/?token=demo-token-preview';
    }

    /**
     * @return array{otype:string,oid:int,entityTitle:string,authorLine:string,thread:list<array{author:string,message:string,created_at:string,is_new:bool}>,entityCard:array}
     */
    public static function commentDiscussionRehearsalShort(string $locale = 'en'): array {
        $acc = MailEntityColors::commentDiscussionCardAccents('rehearsal');

        return [
            'otype' => 'R',
            'oid' => 101,
            'entityTitle' => 'Rehearsal 02.04.2026',
            'authorLine' => 'River P.',
            'entityCard' => array_merge($acc, [
                'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('rehearsal'),
                'icon_char' => "\u{266B}",
                'title' => 'Haus der Vereine',
                'badge_label' => MailI18n::t('js.event.rehearsal', $locale),
                'meta_line' => MailLocaleDateTime::formatEventMetaLine('2026-04-02 03:30:00', '2026-04-02 05:09:00', $locale),
                'location_line' => 'Haus der Vereine',
            ]),
            'thread' => [
                ['author' => 'Alex M.', 'message' => 'Can we move the warm-up to 18:30?', 'created_at' => '2026-03-28 10:00:00', 'is_new' => false],
                ['author' => 'Sam K.', 'message' => "Works for me.\nSee https://maps.example.com/rehearsal", 'created_at' => '2026-03-28 11:15:00', 'is_new' => false],
                ['author' => 'River P.', 'message' => 'Thanks — I added the room code in the calendar.', 'created_at' => '2026-03-30 09:20:00', 'is_new' => true],
            ],
        ];
    }

    /**
     * @return array{otype:string,oid:int,entityTitle:string,authorLine:string,thread:list<array{author:string,message:string,created_at:string,is_new:bool}>,entityCard:array}
     */
    public static function commentDiscussionRehearsalLong(string $locale = 'en'): array {
        $thread = [];
        for ($i = 1; $i <= 14; $i++) {
            $thread[] = [
                'author' => 'Member ' . $i,
                'message' => $i === 14
                    ? "Final note: please confirm attire — details at https://example.org/outfits\nThanks everyone!"
                    : 'Short update #' . $i . ' on the rehearsal plan.',
                'created_at' => '2026-03-' . str_pad((string) (10 + (int) ($i / 2)), 2, '0', STR_PAD_LEFT) . ' 12:00:00',
                'is_new' => $i === 14,
            ];
        }
        $acc = MailEntityColors::commentDiscussionCardAccents('rehearsal');

        return [
            'otype' => 'R',
            'oid' => 202,
            'entityTitle' => 'Rehearsal 15.04.2026',
            'authorLine' => 'Member 14',
            'entityCard' => array_merge($acc, [
                'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('rehearsal'),
                'icon_char' => "\u{266B}",
                'title' => 'Studio A',
                'badge_label' => MailI18n::t('js.event.rehearsal', $locale),
                'meta_line' => MailLocaleDateTime::formatEventMetaLine('2026-04-15 19:00:00', '2026-04-15 21:30:00', $locale),
                'location_line' => 'Studio A',
            ]),
            'thread' => $thread,
        ];
    }

    /**
     * Concert entity with a short thread (otype C) — for layout + DE intro/subject checks.
     *
     * @return array{otype:string,oid:int,entityTitle:string,authorLine:string,thread:list<array{author:string,message:string,created_at:string,is_new:bool}>,entityCard:array}
     */
    public static function commentDiscussionConcert(string $locale = 'en'): array {
        $acc = MailEntityColors::commentDiscussionCardAccents('concert');

        return [
            'otype' => 'C',
            'oid' => 303,
            'entityTitle' => 'Concert 20.05.2026',
            'authorLine' => 'Jordan W.',
            'entityCard' => array_merge($acc, [
                'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('concert'),
                'icon_char' => "\u{266A}",
                'title' => 'Summer Open Air',
                'badge_label' => MailI18n::t('js.event.performance', $locale),
                'meta_line' => MailLocaleDateTime::formatEventMetaLine('2026-05-20 18:00:00', '2026-05-20 22:30:00', $locale),
                'location_line' => 'Parkbühne, Musterstadt',
            ]),
            'thread' => [
                ['author' => 'Alex M.', 'message' => 'Load-in is 14:00 — who can help with the risers?', 'created_at' => '2026-03-28 09:00:00', 'is_new' => false],
                ['author' => 'Sam K.', 'message' => 'I can be there from 13:30.', 'created_at' => '2026-03-29 16:20:00', 'is_new' => false],
                ['author' => 'Jordan W.', 'message' => 'Rain plan: we move to the hall — I will update the calendar entry.', 'created_at' => '2026-03-30 08:05:00', 'is_new' => true],
            ],
        ];
    }

    /**
     * @return array{otype:string,oid:int,entityTitle:string,authorLine:string,thread:list<array{author:string,message:string,created_at:string,is_new:bool}>,entityCard:array}
     */
    public static function commentDiscussionVote(string $locale = 'en'): array {
        $acc = MailEntityColors::commentDiscussionCardAccents('vote');

        return [
            'otype' => 'V',
            'oid' => 55,
            'entityTitle' => 'Vote: Spring concert program',
            'authorLine' => 'Casey L.',
            'entityCard' => array_merge($acc, [
                'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('vote'),
                'icon_char' => 'V',
                'title' => 'Spring concert program',
                'badge_label' => MailI18n::t('js.votes.active', $locale),
                'meta_line' => MailLocaleDateTime::formatVoteEndLine('2026-04-15 23:59:00', $locale),
                'location_line' => '',
            ]),
            'thread' => [
                ['author' => 'Jamie R.', 'message' => 'Option A has better flow for the second half.', 'created_at' => '2026-03-25 08:00:00', 'is_new' => false],
                ['author' => 'Morgan T.', 'message' => 'I prefer B — more rest between pieces.', 'created_at' => '2026-03-26 19:30:00', 'is_new' => false],
                ['author' => 'Casey L.', 'message' => 'We need a decision by Friday; please vote in the app.', 'created_at' => '2026-03-30 07:45:00', 'is_new' => true],
            ],
        ];
    }

    /**
     * @return array{otype:string,oid:int,entityTitle:string,authorLine:string,thread:list<array{author:string,message:string,created_at:string,is_new:bool}>,entityCard:array}
     */
    public static function commentDiscussionSingleNew(string $locale = 'en'): array {
        $acc = MailEntityColors::commentDiscussionCardAccents('concert');

        return [
            'otype' => 'C',
            'oid' => 88,
            'entityTitle' => 'Concert 12.04.2026',
            'authorLine' => 'Taylor N.',
            'entityCard' => array_merge($acc, [
                'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('concert'),
                'icon_char' => "\u{266A}",
                'title' => 'Spring Gala',
                'badge_label' => MailI18n::t('js.event.performance', $locale),
                'meta_line' => MailLocaleDateTime::formatEventMetaLine('2026-04-12 19:00:00', '2026-04-12 22:00:00', $locale),
                'location_line' => 'City Hall',
            ]),
            'thread' => [
                ['author' => 'Taylor N.', 'message' => 'Doors open at 19:00 — please arrive by 18:15 for sound check.', 'created_at' => '2026-03-30 14:00:00', 'is_new' => true],
            ],
        ];
    }

    /**
     * @return array{otype:string,oid:int,entityTitle:string,entityCard:array,recipientFirstName:string,plainToken:string,allowMaybe:bool}
     */
    public static function eventInviteRehearsal(string $locale = 'en'): array {
        $ctx = self::commentDiscussionRehearsalShort($locale);

        return [
            'otype' => 'R',
            'oid' => (int) $ctx['oid'],
            'entityTitle' => (string) $ctx['entityTitle'],
            'entityCard' => $ctx['entityCard'],
            'recipientFirstName' => self::recipientPreviewFirstName(),
            'plainToken' => str_repeat('b', 64),
            'allowMaybe' => true,
        ];
    }

    /**
     * @return array{otype:string,oid:int,entityTitle:string,entityCard:array,recipientFirstName:string,plainToken:string,allowMaybe:bool}
     */
    public static function eventInviteRehearsalNoMaybe(string $locale = 'en'): array {
        $x = self::eventInviteRehearsal($locale);
        $x['allowMaybe'] = false;

        return $x;
    }

    /**
     * @return array{otype:string,oid:int,entityTitle:string,entityCard:array,recipientFirstName:string,plainToken:string,allowMaybe:bool}
     */
    public static function eventInviteConcert(string $locale = 'en'): array {
        $ctx = self::commentDiscussionConcert($locale);

        return [
            'otype' => 'C',
            'oid' => (int) $ctx['oid'],
            'entityTitle' => (string) $ctx['entityTitle'],
            'entityCard' => $ctx['entityCard'],
            'recipientFirstName' => self::recipientPreviewFirstName(),
            'plainToken' => str_repeat('c', 64),
            'allowMaybe' => true,
        ];
    }

    /**
     * @return array{mode:string,title:string,description:string,taskId:int}
     */
    public static function taskNotifyCreate(): array {
        return [
            'mode' => 'create',
            'title' => 'Print posters for April concert',
            'description' => "Use the template in the shared drive.\nDeadline: Friday EOD.",
            'taskId' => 501,
        ];
    }

    /**
     * @return array{mode:string,title:string,description:string,taskId:int}
     */
    public static function taskNotifyUpdate(): array {
        return [
            'mode' => 'update',
            'title' => 'Print posters for April concert',
            'description' => 'Venue confirmed — use the updated address on the PDF.',
            'taskId' => 501,
        ];
    }
}
