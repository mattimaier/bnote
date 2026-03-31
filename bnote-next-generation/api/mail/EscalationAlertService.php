<?php
/**
 * Escalation alerts for at-risk rehearsals/concerts.
 */
declare(strict_types=1);

require_once __DIR__ . '/ReminderSchema.php';
require_once __DIR__ . '/ReminderConfig.php';
require_once __DIR__ . '/NextGenMailPolicy.php';
require_once __DIR__ . '/NextGenMailer.php';
require_once __DIR__ . '/MailEnv.php';
require_once __DIR__ . '/MailI18n.php';
require_once __DIR__ . '/builders/EscalationAlertMailBuilder.php';

final class EscalationAlertService {
    /**
     * @param array{
     *   dryRun?:bool,
     *   force?:bool,
     *   mode?:string,
     *   isTest?:bool,
     *   overrideRecipients?:list<string>,
     *   onlyEvent?:array{otype:string,oid:int},
     *   triggerKind?:string,
     *   dropoutSource?:string,
     *   dropoutContactId?:int
     * } $options
     * @return array<string,mixed>
     */
    public static function runScheduled($system_data, array $options = []): array {
        $dryRun = !empty($options['dryRun']);
        $force = !empty($options['force']);
        $mode = isset($options['mode']) ? (string) $options['mode'] : 'scheduled';
        $isTest = !empty($options['isTest']) || $dryRun;
        $overrideRecipients = self::normalizeEmails($options['overrideRecipients'] ?? []);
        $triggerKind = isset($options['triggerKind']) ? (string) $options['triggerKind'] : 'scheduled';
        $onlyEvent = (isset($options['onlyEvent']) && is_array($options['onlyEvent'])) ? $options['onlyEvent'] : null;
        $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';
        $dropoutMeta = null;
        if ($triggerKind === 'dropout') {
            $dropoutMeta = [
                'source' => (string) ($options['dropoutSource'] ?? 'unknown'),
                'contact_id' => (int) ($options['dropoutContactId'] ?? 0),
            ];
        }

        $db = $system_data->dbcon;
        if (!ReminderSchema::ensureTables($db)) {
            throw new RuntimeException('reminder_schema_unavailable');
        }

        $cfg = ReminderConfig::get($db);
        $esc = self::escalationCfg($cfg);
        if (empty($esc['enabled']) && !$force) {
            return ['status' => 'disabled', 'config' => $esc];
        }

        if (!NextGenMailPolicy::shouldSendPublicMail($system_data) && !$dryRun) {
            return ['status' => 'mail_disabled', 'config' => $esc];
        }

        $events = self::loadAtRiskEvents($system_data, $esc, $locale, $onlyEvent, $dropoutMeta);
        $result = [
            'status' => 'ok',
            'dryRun' => $dryRun,
            'events_scanned' => count($events),
            'alerts_sent' => 0,
            'details' => [],
            'config' => $esc,
        ];

        foreach ($events as $event) {
            $eligibility = self::resolveEligibleRecipients($system_data, $esc, $event);
            $recipientEmails = $overrideRecipients;
            if (count($recipientEmails) < 1) {
                $recipientEmails = array_values(array_unique(array_map(
                    static fn (array $r): string => (string) $r['email'],
                    $eligibility['included']
                )));
            }
            $recipientEmails = self::normalizeEmails($recipientEmails);

            $eventUrl = self::eventAbsoluteUrl((string) $event['otype'], (int) $event['oid']);
            $eventBegin = (string) ($event['begin'] ?? '');
            $urgency = self::urgencyForEvent($eventBegin, (array) ($esc['deadline_windows_hours'] ?? [48, 12]));
            $detail = [
                'event' => $event,
                'urgency' => $urgency,
                'eligibility' => $eligibility,
                'recipient_count' => count($recipientEmails),
                'override_recipients' => count($overrideRecipients) > 0,
                'status' => 'dry_run',
            ];

            if (!$dryRun && count($recipientEmails) > 0) {
                $messages = [];
                foreach ($recipientEmails as $email) {
                    $messages[] = EscalationAlertMailBuilder::build(
                        $system_data,
                        $locale,
                        (string) ($event['title'] ?? ''),
                        (string) ($event['otype'] ?? 'R'),
                        $eventBegin,
                        '',
                        '',
                        $urgency,
                        (array) ($event['reasons'] ?? []),
                        (array) ($event['instrument_gaps'] ?? []),
                        $eventUrl,
                        [$email],
                        [],
                        isset($event['counts']) && is_array($event['counts']) ? $event['counts'] : null
                    );
                }
                $sent = NextGenMailer::sendBulk($messages);
                $result['alerts_sent'] += $sent;
                $detail['status'] = $sent > 0 ? 'sent' : 'send_failed';
                $detail['emails_sent'] = $sent;
            } elseif (!$dryRun && count($recipientEmails) < 1) {
                $detail['status'] = 'no_recipients';
            }

            ReminderSchema::addEscalationAudit($db, [
                'is_test' => $isTest || count($overrideRecipients) > 0,
                'trigger_kind' => $triggerKind,
                'delivery_mode' => $mode,
                'otype' => (string) ($event['otype'] ?? ''),
                'oid' => (int) ($event['oid'] ?? 0),
                'event_title' => (string) ($event['title'] ?? ''),
                'reason_summary' => implode('; ', (array) ($event['reasons'] ?? [])),
                'event' => $event,
                'urgency' => $urgency,
                'override_recipients' => $overrideRecipients,
                'resolved_recipients' => $recipientEmails,
                'eligibility' => $eligibility,
                'result' => $detail['status'],
            ]);

            $result['details'][] = $detail;
        }

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    public static function triggerImmediateDropout(
        $system_data,
        string $otype,
        int $oid,
        int $contactId,
        string $source,
        bool $dryRun = false,
        array $overrideRecipients = []
    ): array {
        $otype = strtoupper(trim($otype));
        if (($otype !== 'R' && $otype !== 'C') || $oid < 1) {
            return ['status' => 'invalid_event'];
        }
        $cfg = ReminderConfig::get($system_data->dbcon);
        $esc = self::escalationCfg($cfg);
        $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';
        $event = self::buildDropoutEvent($system_data, $esc, $locale, $otype, $oid, $source, $contactId);
        if ($event === null) {
            return ['status' => 'event_not_found'];
        }
        $hoursToBegin = self::hoursUntil((string) ($event['begin'] ?? ''));
        if ($hoursToBegin !== null && $hoursToBegin > (int) ($esc['dropout_window_hours'] ?? 24)) {
            return ['status' => 'outside_dropout_window', 'hours_to_begin' => $hoursToBegin];
        }

        return self::runScheduled($system_data, [
            'dryRun' => $dryRun,
            'force' => true,
            'mode' => 'immediate_dropout',
            'isTest' => $dryRun || count($overrideRecipients) > 0,
            'overrideRecipients' => $overrideRecipients,
            'onlyEvent' => ['otype' => $otype, 'oid' => $oid],
            'triggerKind' => 'dropout',
            'dropoutSource' => $source,
            'dropoutContactId' => $contactId,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public static function getEligibilityForEvent($system_data, string $otype, int $oid): array {
        $cfg = ReminderConfig::get($system_data->dbcon);
        $esc = self::escalationCfg($cfg);
        $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';
        $event = self::buildRiskForEvent($system_data, $esc, $locale, strtoupper($otype), $oid);
        if ($event === null) {
            return ['event' => null, 'eligibility' => ['included' => [], 'excluded' => []]];
        }
        return [
            'event' => $event,
            'eligibility' => self::resolveEligibleRecipients($system_data, $esc, $event),
        ];
    }

    /**
     * @param array<string,mixed> $cfg
     * @return array<string,mixed>
     */
    private static function escalationCfg(array $cfg): array {
        $esc = (isset($cfg['escalation']) && is_array($cfg['escalation'])) ? $cfg['escalation'] : [];
        return [
            'enabled' => !empty($esc['enabled']),
            'deadline_windows_hours' => (isset($esc['deadline_windows_hours']) && is_array($esc['deadline_windows_hours']))
                ? array_values(array_map('intval', $esc['deadline_windows_hours']))
                : [48, 12],
            'dropout_window_hours' => max(1, (int) ($esc['dropout_window_hours'] ?? 24)),
            'pending_threshold_percent' => max(1, (int) ($esc['pending_threshold_percent'] ?? 20)),
            'escalation_target_group_id' => max(0, (int) ($esc['escalation_target_group_id'] ?? 0)),
            'include_event_organizer' => !empty($esc['include_event_organizer']),
        ];
    }

    /**
     * @param array<string,mixed> $esc
     * @param string $locale
     * @param null|array{otype:string,oid:int} $onlyEvent
     * @param null|array{source:string,contact_id:int} $dropoutMeta
     * @return list<array<string,mixed>>
     */
    private static function loadAtRiskEvents($system_data, array $esc, string $locale, ?array $onlyEvent = null, ?array $dropoutMeta = null): array {
        $events = [];
        if ($onlyEvent !== null) {
            $otype = strtoupper((string) ($onlyEvent['otype'] ?? ''));
            $oid = (int) ($onlyEvent['oid'] ?? 0);
            if ($dropoutMeta !== null) {
                $event = self::buildDropoutEvent(
                    $system_data,
                    $esc,
                    $locale,
                    $otype,
                    $oid,
                    (string) ($dropoutMeta['source'] ?? 'unknown'),
                    (int) ($dropoutMeta['contact_id'] ?? 0)
                );
                return $event ? [$event] : [];
            }
            $event = self::buildRiskForEvent($system_data, $esc, $locale, $otype, $oid);
            return $event ? [$event] : [];
        }

        foreach (['R', 'C'] as $otype) {
            $baseRows = self::loadFutureEventsBase($system_data, $otype);
            foreach ($baseRows as $row) {
                $oid = (int) ($row['id'] ?? 0);
                if ($oid < 1) {
                    continue;
                }
                $event = self::buildRiskForEvent($system_data, $esc, $locale, $otype, $oid, $row);
                if ($event !== null) {
                    $events[] = $event;
                }
            }
        }
        usort($events, static fn (array $a, array $b): int => strcmp((string) ($a['begin'] ?? ''), (string) ($b['begin'] ?? '')));
        return $events;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private static function loadFutureEventsBase($system_data, string $otype): array {
        $db = $system_data->dbcon;
        if ($otype === 'R') {
            // Rehearsal table has no stable `name` column in legacy schema.
            $query = "SELECT id, begin, approve_until, begin AS title FROM rehearsal WHERE begin >= NOW()";
        } else {
            $query = "SELECT id, begin, approve_until, title FROM concert WHERE begin >= NOW()";
        }
        $rows = $db->getSelection($query, []);
        if (!is_array($rows) || count($rows) < 2) {
            return [];
        }
        $out = [];
        for ($i = 1; $i < count($rows); $i++) {
            $out[] = $rows[$i];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $esc
     * @param string $locale
     * @param null|array<string,mixed> $base
     * @return null|array<string,mixed>
     */
    private static function buildRiskForEvent($system_data, array $esc, string $locale, string $otype, int $oid, ?array $base = null): ?array {
        if (($otype !== 'R' && $otype !== 'C') || $oid < 1) {
            return null;
        }
        $db = $system_data->dbcon;
        $base = $base ?? self::loadSingleEventBase($db, $otype, $oid);
        if ($base === null) {
            return null;
        }
        $deadline = (string) ($base['approve_until'] ?? '');
        $hoursToDeadline = self::hoursUntil($deadline);
        $windows = (array) ($esc['deadline_windows_hours'] ?? [48, 12]);
        $maxWindow = count($windows) > 0 ? max($windows) : 48;
        if ($hoursToDeadline !== null && $hoursToDeadline > $maxWindow) {
            return null;
        }

        $counts = self::participationCounts($db, $otype, $oid);
        $pendingPct = $counts['invited_users'] > 0
            ? (int) round(($counts['pending_users'] * 100) / $counts['invited_users'])
            : 0;
        $minimums = self::loadInstrumentMinimums($system_data);
        $gaps = self::instrumentGaps($db, $otype, $oid, $minimums);
        $reasons = [];
        if ($pendingPct >= (int) ($esc['pending_threshold_percent'] ?? 20)) {
            $reasons[] = MailI18n::interpolate(MailI18n::t('mail.escalation.reasonPendingThreshold', $locale), [
                'pending' => (string) $counts['pending_users'],
                'total' => (string) $counts['invited_users'],
                'percent' => (string) $pendingPct,
            ]);
        }
        if (count($gaps) > 0) {
            $reasons[] = MailI18n::interpolate(MailI18n::t('mail.escalation.reasonInstrumentGaps', $locale), [
                'count' => (string) count($gaps),
            ]);
        }
        if (count($reasons) < 1) {
            return null;
        }

        return [
            'otype' => $otype,
            'oid' => $oid,
            'title' => (string) ($base['title'] ?? ''),
            'begin' => (string) ($base['begin'] ?? ''),
            'approve_until' => $deadline,
            'hours_to_deadline' => $hoursToDeadline,
            'pending_threshold_percent' => (int) ($esc['pending_threshold_percent'] ?? 20),
            'counts' => $counts,
            'pending_percent' => $pendingPct,
            'instrument_gaps' => $gaps,
            'reasons' => $reasons,
        ];
    }

    /**
     * Build a forced event payload for dropout triggers.
     * Includes normal risk reasons when present, and always includes the dropout reason.
     *
     * @param array<string,mixed> $esc
     * @return null|array<string,mixed>
     */
    private static function buildDropoutEvent(
        $system_data,
        array $esc,
        string $locale,
        string $otype,
        int $oid,
        string $source,
        int $contactId
    ): ?array {
        if (($otype !== 'R' && $otype !== 'C') || $oid < 1) {
            return null;
        }
        $db = $system_data->dbcon;
        $base = self::loadSingleEventBase($db, $otype, $oid);
        if ($base === null) {
            return null;
        }
        $counts = self::participationCounts($db, $otype, $oid);
        $pendingPct = $counts['invited_users'] > 0
            ? (int) round(($counts['pending_users'] * 100) / $counts['invited_users'])
            : 0;
        $minimums = self::loadInstrumentMinimums($system_data);
        $gaps = self::instrumentGaps($db, $otype, $oid, $minimums);

        $reasons = [
            self::dropoutReasonText($db, $locale, $source, $contactId),
        ];
        if ($pendingPct >= (int) ($esc['pending_threshold_percent'] ?? 20)) {
            $reasons[] = MailI18n::interpolate(MailI18n::t('mail.escalation.reasonPendingThreshold', $locale), [
                'pending' => (string) $counts['pending_users'],
                'total' => (string) $counts['invited_users'],
                'percent' => (string) $pendingPct,
            ]);
        }
        if (count($gaps) > 0) {
            $reasons[] = MailI18n::interpolate(MailI18n::t('mail.escalation.reasonInstrumentGaps', $locale), [
                'count' => (string) count($gaps),
            ]);
        }

        return [
            'otype' => $otype,
            'oid' => $oid,
            'title' => (string) ($base['title'] ?? ''),
            'begin' => (string) ($base['begin'] ?? ''),
            'approve_until' => (string) ($base['approve_until'] ?? ''),
            'hours_to_deadline' => self::hoursUntil((string) ($base['approve_until'] ?? '')),
            'pending_threshold_percent' => (int) ($esc['pending_threshold_percent'] ?? 20),
            'counts' => $counts,
            'pending_percent' => $pendingPct,
            'instrument_gaps' => $gaps,
            'reasons' => $reasons,
        ];
    }

    /**
     * @return null|array<string,mixed>
     */
    private static function loadSingleEventBase(object $db, string $otype, int $oid): ?array {
        if ($otype === 'R') {
            $row = $db->fetchRow('SELECT id, begin, approve_until, begin AS title FROM rehearsal WHERE id = ?', [['i', $oid]]);
        } else {
            $row = $db->fetchRow('SELECT id, begin, approve_until, title FROM concert WHERE id = ?', [['i', $oid]]);
        }
        return is_array($row) ? $row : null;
    }

    /**
     * @return array{invited_users:int,pending_users:int,yes:int,maybe:int,no:int}
     */
    private static function participationCounts(object $db, string $otype, int $oid): array {
        $tblContact = $otype === 'R' ? 'rehearsal_contact' : 'concert_contact';
        $tblUser = $otype === 'R' ? 'rehearsal_user' : 'concert_user';
        $entityCol = $otype === 'R' ? 'rehearsal' : 'concert';
        $query = "SELECT
                    COUNT(DISTINCT u.id) AS invited_users,
                    SUM(CASE WHEN ev.participate IS NULL OR ev.participate < 0 THEN 1 ELSE 0 END) AS pending_users,
                    SUM(CASE WHEN ev.participate = 1 THEN 1 ELSE 0 END) AS yes_count,
                    SUM(CASE WHEN ev.participate = 2 THEN 1 ELSE 0 END) AS maybe_count,
                    SUM(CASE WHEN ev.participate = 0 THEN 1 ELSE 0 END) AS no_count
                  FROM {$tblContact} ec
                  JOIN contact c ON ec.contact = c.id
                  JOIN user u ON u.contact = c.id AND u.isActive = 1
                  LEFT JOIN {$tblUser} ev ON ev.user = u.id AND ev.{$entityCol} = ?
                  WHERE ec.{$entityCol} = ?";
        $row = $db->fetchRow($query, [['i', $oid], ['i', $oid]]);
        return [
            'invited_users' => (int) ($row['invited_users'] ?? 0),
            'pending_users' => (int) ($row['pending_users'] ?? 0),
            'yes' => (int) ($row['yes_count'] ?? 0),
            'maybe' => (int) ($row['maybe_count'] ?? 0),
            'no' => (int) ($row['no_count'] ?? 0),
        ];
    }

    /**
     * @return array<string,int>
     */
    private static function loadInstrumentMinimums($system_data): array {
        $val = (string) ($system_data->getDynamicConfigParameter('instrument_minimums') ?? '');
        if ($val === '') {
            return [];
        }
        $decoded = json_decode($val, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $id => $min) {
            $instId = is_numeric($id) ? (int) $id : 0;
            $instMin = is_numeric($min) ? max(0, (int) $min) : 0;
            if ($instId > 0 && $instMin > 0) {
                $out[(string) $instId] = $instMin;
            }
        }
        return $out;
    }

    /**
     * @param array<string,int> $minimums
     * @return list<array{instrument_name:string,current:int,minimum:int}>
     */
    private static function instrumentGaps(object $db, string $otype, int $oid, array $minimums): array {
        if (count($minimums) < 1) {
            return [];
        }
        $tblContact = $otype === 'R' ? 'rehearsal_contact' : 'concert_contact';
        $tblUser = $otype === 'R' ? 'rehearsal_user' : 'concert_user';
        $entityCol = $otype === 'R' ? 'rehearsal' : 'concert';
        $query = "SELECT i.id AS instrument_id, i.name AS instrument_name,
                    SUM(CASE WHEN ev.participate IN (1,2) THEN 1 ELSE 0 END) AS attending
                  FROM {$tblContact} ec
                  JOIN contact c ON ec.contact = c.id
                  LEFT JOIN instrument i ON c.instrument = i.id
                  LEFT JOIN user u ON u.contact = c.id AND u.isActive = 1
                  LEFT JOIN {$tblUser} ev ON ev.user = u.id AND ev.{$entityCol} = ?
                  WHERE ec.{$entityCol} = ?
                  GROUP BY i.id, i.name";
        $sel = $db->getSelection($query, [['i', $oid], ['i', $oid]]);
        if (!is_array($sel) || count($sel) < 2) {
            return [];
        }
        $gaps = [];
        for ($i = 1; $i < count($sel); $i++) {
            $row = $sel[$i];
            $instId = (int) ($row['instrument_id'] ?? 0);
            if ($instId < 1) {
                continue;
            }
            $min = $minimums[(string) $instId] ?? 0;
            if ($min < 1) {
                continue;
            }
            $attending = (int) ($row['attending'] ?? 0);
            if ($attending < $min) {
                $gaps[] = [
                    'instrument_name' => (string) ($row['instrument_name'] ?? ''),
                    'current' => $attending,
                    'minimum' => $min,
                ];
            }
        }
        return $gaps;
    }

    /**
     * @param array<string,mixed> $esc
     * @param array<string,mixed> $event
     * @return array{included:list<array<string,mixed>>,excluded:list<array<string,mixed>>}
     */
    private static function resolveEligibleRecipients($system_data, array $esc, array $event): array {
        $db = $system_data->dbcon;
        $included = [];
        $excluded = [];

        $groupId = (int) ($esc['escalation_target_group_id'] ?? 0);
        if ($groupId > 0) {
            $sel = $db->getSelection(
                'SELECT c.id AS contact_id, c.name, c.surname, c.email, u.id AS user_id, u.isActive, u.email_notification
                 FROM contact_group cg
                 JOIN contact c ON c.id = cg.contact
                 LEFT JOIN user u ON u.contact = c.id
                 WHERE cg.group = ?',
                [['i', $groupId]]
            );
            if (is_array($sel)) {
                for ($i = 1; $i < count($sel); $i++) {
                    $row = $sel[$i];
                    $decision = self::recipientDecision($row);
                    if ($decision['include']) {
                        $included[] = $decision['recipient'];
                    } else {
                        $excluded[] = $decision['recipient'];
                    }
                }
            }
        }

        if (!empty($esc['include_event_organizer'])) {
            $org = self::resolveEventOrganizer($db, (string) ($event['otype'] ?? ''), (int) ($event['oid'] ?? 0));
            if ($org !== null) {
                $decision = self::recipientDecision($org);
                if ($decision['include']) {
                    $included[] = $decision['recipient'];
                } else {
                    $excluded[] = $decision['recipient'];
                }
            }
        }

        $seen = [];
        $dedup = [];
        foreach ($included as $r) {
            $email = strtolower(trim((string) ($r['email'] ?? '')));
            if ($email === '' || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;
            $dedup[] = $r;
        }
        return ['included' => $dedup, 'excluded' => $excluded];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{include:bool,recipient:array<string,mixed>}
     */
    private static function recipientDecision(array $row): array {
        $email = trim((string) ($row['email'] ?? ''));
        $name = trim(((string) ($row['name'] ?? '')) . ' ' . ((string) ($row['surname'] ?? '')));
        $recipient = [
            'contact_id' => (int) ($row['contact_id'] ?? 0),
            'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'name' => $name,
            'email' => $email,
        ];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $recipient['reason'] = 'invalid_email';
            return ['include' => false, 'recipient' => $recipient];
        }
        $uid = isset($row['user_id']) ? (int) $row['user_id'] : 0;
        if ($uid < 1) {
            $recipient['reason'] = 'no_user_link';
            return ['include' => false, 'recipient' => $recipient];
        }
        if ((int) ($row['isActive'] ?? 0) !== 1) {
            $recipient['reason'] = 'inactive_user';
            return ['include' => false, 'recipient' => $recipient];
        }
        if ((int) ($row['email_notification'] ?? 0) !== 1) {
            $recipient['reason'] = 'email_notifications_off';
            return ['include' => false, 'recipient' => $recipient];
        }
        $recipient['reason'] = 'eligible';
        return ['include' => true, 'recipient' => $recipient];
    }

    /**
     * @return null|array<string,mixed>
     */
    private static function resolveEventOrganizer(object $db, string $otype, int $oid): ?array {
        if ($otype === 'C') {
            $row = $db->fetchRow(
                'SELECT c.id AS contact_id, c.name, c.surname, c.email, u.id AS user_id, u.isActive, u.email_notification
                 FROM concert e
                 LEFT JOIN contact c ON c.id = e.contact
                 LEFT JOIN user u ON u.contact = c.id
                 WHERE e.id = ?',
                [['i', $oid]]
            );
            return is_array($row) ? $row : null;
        }
        return null;
    }

    private static function eventAbsoluteUrl(string $otype, int $oid): string {
        $base = MailEnv::nextgenPublicBaseUrl();
        if ($base === '' || $oid < 1) {
            return '';
        }
        $type = strtoupper($otype) === 'C' ? 'concert' : 'rehearsal';
        return $base . '/entity?' . http_build_query(['type' => $type, 'id' => (string) $oid]);
    }

    /**
     * @param list<string> $emails
     * @return list<string>
     */
    private static function normalizeEmails(array $emails): array {
        $out = [];
        $seen = [];
        foreach ($emails as $e) {
            $email = strtolower(trim((string) $e));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;
            $out[] = $email;
        }
        return $out;
    }

    private static function hoursUntil(string $datetime): ?int {
        $raw = trim($datetime);
        if ($raw === '') {
            return null;
        }
        try {
            $target = new DateTimeImmutable($raw, new DateTimeZone('UTC'));
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            return (int) floor(($target->getTimestamp() - $now->getTimestamp()) / 3600);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param list<int> $windows
     */
    private static function urgencyForEvent(string $begin, array $windows): string {
        $hours = self::hoursUntil($begin);
        if ($hours === null) {
            return 'soon';
        }
        $criticalThreshold = count($windows) > 0 ? min($windows) : 12;
        return $hours <= $criticalThreshold ? 'critical' : 'soon';
    }

    private static function dropoutSourceLabel(string $locale, string $source): string {
        $key = match (trim($source)) {
            'participation_no' => 'mail.escalation.sourceParticipationNo',
            'contact_removed_from_event' => 'mail.escalation.sourceContactRemoved',
            'developer_simulation' => 'mail.escalation.sourceDeveloperSimulation',
            default => 'mail.escalation.sourceUnknown',
        };
        return MailI18n::t($key, $locale);
    }

    private static function dropoutReasonText(object $db, string $locale, string $source, int $contactId): string {
        $contactLabel = self::contactDisplayLabel($db, $contactId);
        return MailI18n::interpolate(MailI18n::t('mail.escalation.reasonDropoutDetected', $locale), [
            'source' => self::dropoutSourceLabel($locale, $source),
            'contact' => $contactLabel,
        ]);
    }

    private static function contactDisplayLabel(object $db, int $contactId): string {
        if ($contactId < 1) {
            return '#' . (string) max(0, $contactId);
        }
        $row = $db->fetchRow(
            'SELECT name, surname FROM contact WHERE id = ?',
            [['i', $contactId]]
        );
        if (!is_array($row)) {
            return '#' . (string) $contactId;
        }
        $display = trim(
            trim((string) ($row['name'] ?? '')) . ' ' . trim((string) ($row['surname'] ?? ''))
        );
        return $display !== '' ? $display : ('#' . (string) $contactId);
    }
}
