<?php
/**
 * Reminder digest orchestration: recipient selection, summary aggregation, mail send, idempotency.
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/modules/abstimmungdata.php';
require_once __DIR__ . '/ReminderSchema.php';
require_once __DIR__ . '/ReminderConfig.php';
require_once __DIR__ . '/ReminderInboxSource.php';
require_once __DIR__ . '/builders/ReminderDigestMailBuilder.php';
require_once __DIR__ . '/NextGenMailer.php';
require_once __DIR__ . '/NextGenMailPolicy.php';
require_once __DIR__ . '/MailEnv.php';
require_once dirname(__DIR__) . '/nextgen_participation_token.php';

final class ReminderDigestService {
    /**
     * @param array{dryRun?:bool,force?:bool,mode?:string,onlyUserId?:int,ignoreLimits?:bool} $options
     * @return array<string,mixed>
     */
    public static function runScheduled($system_data, array $options = []): array {
        $dryRun = !empty($options['dryRun']);
        $force = !empty($options['force']);
        $mode = isset($options['mode']) && is_string($options['mode']) ? $options['mode'] : 'scheduled';
        $onlyUserId = isset($options['onlyUserId']) ? (int) $options['onlyUserId'] : 0;
        $ignoreLimits = !empty($options['ignoreLimits']);
        $db = $system_data->dbcon;

        if (!ReminderSchema::ensureTables($db)) {
            throw new RuntimeException('reminder_schema_unavailable');
        }
        $cfg = ReminderConfig::get($db);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (empty($cfg['enabled']) && !$force) {
            return [
                'status' => 'disabled',
                'runKey' => self::runKeyForNow($now),
                'dryRun' => $dryRun,
                'config' => $cfg,
            ];
        }

        if ($ignoreLimits) {
            $cfg['recipient_scope'] = 'all_opted_in';
            $cfg['event_window_days'] = 365;
            $cfg['max_events'] = 999;
            $cfg['include_votes'] = true;
            $cfg['max_votes'] = 999;
            $cfg['include_tasks'] = true;
            $cfg['max_tasks'] = 999;
        }
        if (!NextGenMailPolicy::shouldSendPublicMail($system_data) && !$dryRun) {
            return [
                'status' => 'mail_disabled',
                'runKey' => self::runKeyForNow($now),
                'dryRun' => $dryRun,
                'config' => $cfg,
            ];
        }

        $runKey = self::runKeyForNow($now);
        $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';
        $source = new ReminderInboxSource(new StartData(), new AbstimmungData(), $system_data);
        $recipients = self::loadRecipients($system_data, (string) ($cfg['recipient_scope'] ?? 'actionable_only'));
        if ($onlyUserId > 0) {
            $recipients = array_values(array_filter($recipients, static fn (array $r): bool => (int) ($r['id'] ?? 0) === $onlyUserId));
        }

        $result = [
            'status' => 'ok',
            'dryRun' => $dryRun,
            'runKey' => $runKey,
            'only_user_id' => $onlyUserId > 0 ? $onlyUserId : null,
            'users_scanned' => 0,
            'users_eligible' => 0,
            'emails_sent' => 0,
            'details' => [],
            'ignore_limits' => $ignoreLimits,
            'skipped' => [
                'no_items' => 0,
                'no_future_events' => 0,
                'already_sent' => 0,
                'invalid_email' => 0,
                'identity_conflict' => 0,
                'send_failed' => 0,
            ],
            'config' => $cfg,
        ];
        $identityConflictsByUserId = self::indexRecipientIdentityConflicts($recipients);

        foreach ($recipients as $recipient) {
            $result['users_scanned']++;
            $uid = (int) ($recipient['id'] ?? 0);
            $email = trim((string) ($recipient['email'] ?? ''));
            $contactId = (int) ($recipient['contact_id'] ?? 0);
            if ($uid < 1 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result['skipped']['invalid_email']++;
                continue;
            }
            if (isset($identityConflictsByUserId[$uid])) {
                $result['skipped']['identity_conflict']++;
                $result['details'][] = [
                    'user' => [
                        'id' => $uid,
                        'name' => (string) ($recipient['name'] ?? ''),
                        'email' => $email,
                    ],
                    'status' => 'identity_conflict',
                    'reason' => (string) $identityConflictsByUserId[$uid],
                ];
                continue;
            }

            $summary = $source->buildDigestSummaryForUser($uid, $cfg);
            $hasFutureEvents = ((int) ($summary['future_event_count'] ?? 0) > 0);
            if (!$ignoreLimits && !$hasFutureEvents) {
                $result['skipped']['no_future_events']++;
                continue;
            }
            $result['users_eligible']++;
            $detail = self::buildDetailEntry($recipient, $summary);

            $enforceWeeklyIdempotency = ($mode === 'scheduled');
            if (!$dryRun && $enforceWeeklyIdempotency && ReminderSchema::hasRunForUser($db, $runKey, $uid)) {
                $result['skipped']['already_sent']++;
                $detail['status'] = 'already_sent';
                $result['details'][] = $detail;
                continue;
            }

            if ($dryRun) {
                $detail['status'] = 'dry_run';
                $result['details'][] = $detail;
                continue;
            }

            $summary = self::attachParticipationActionUrls($system_data, $db, $summary, $contactId);

            $message = ReminderDigestMailBuilder::build(
                $system_data,
                $locale,
                (string) ($recipient['name'] ?? ''),
                $summary['events_upcoming'],
                $summary['events_pending_response'],
                $summary['votes'],
                $summary['tasks'],
                [$email],
                []
            );
            $ok = NextGenMailer::send($message);
            if ($ok) {
                $result['emails_sent']++;
                ReminderSchema::markRunForUser($db, $runKey, $uid, $mode);
                $detail['status'] = 'sent';
            } else {
                $result['skipped']['send_failed']++;
                $detail['status'] = 'send_failed';
            }
            $result['details'][] = $detail;
        }

        return $result;
    }

    /**
     * @param array{id:int,name:string,email:string,contact_id?:int} $recipient
     * @param array{
     *  events_upcoming:list<array<string,mixed>>,
     *  events_pending_response:list<array<string,mixed>>,
     *  votes:list<array<string,mixed>>,
     *  tasks:list<array<string,mixed>>,
     *  items:list<array<string,mixed>>,
     *  open_count:int,
     *  future_event_count:int
     * } $summary
     * @return array<string,mixed>
     */
    private static function buildDetailEntry(array $recipient, array $summary): array {
        $map = static function (array $items): array {
            $out = [];
            foreach ($items as $it) {
                $out[] = [
                    'otype' => (string) ($it['otype'] ?? ''),
                    'oid' => (int) ($it['oid'] ?? 0),
                    'title' => (string) ($it['title'] ?? ''),
                    'replyUntil' => isset($it['replyUntil']) ? (string) $it['replyUntil'] : null,
                    'participation' => isset($it['participation']) ? (int) $it['participation'] : null,
                ];
            }
            return $out;
        };

        return [
            'user' => [
                'id' => (int) ($recipient['id'] ?? 0),
                'name' => (string) ($recipient['name'] ?? ''),
                'email' => (string) ($recipient['email'] ?? ''),
            ],
            'open_count' => (int) ($summary['open_count'] ?? 0),
            'future_event_count' => (int) ($summary['future_event_count'] ?? 0),
            'counts' => [
                'events_upcoming' => count($summary['events_upcoming'] ?? []),
                'events_pending_response' => count($summary['events_pending_response'] ?? []),
                'votes' => count($summary['votes'] ?? []),
                'tasks' => count($summary['tasks'] ?? []),
            ],
            'events_upcoming' => $map($summary['events_upcoming'] ?? []),
            'events_pending_response' => $map($summary['events_pending_response'] ?? []),
            'votes' => $map($summary['votes'] ?? []),
            'tasks' => $map($summary['tasks'] ?? []),
        ];
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    public static function listRecipientsForAdmin($system_data): array {
        return self::loadRecipients($system_data, 'all_opted_in');
    }

    private static function runKeyForNow(DateTimeImmutable $now): string {
        return 'w' . $now->format('oW');
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    private static function loadRecipients($system_data, string $scope): array {
        $db = $system_data->dbcon;
        $where = 'u.isActive = 1 AND u.email_notification = 1';
        if ($scope !== 'all_opted_in') {
            // actionable_only and future modes both start from opted-in active users.
            $where = 'u.isActive = 1 AND u.email_notification = 1';
        }
        $sel = $db->getSelection(
            'SELECT u.id, u.contact AS contact_id, c.name, c.email
             FROM user u
             LEFT JOIN contact c ON c.id = u.contact
             WHERE ' . $where . '
             ORDER BY u.id',
            []
        );
        if (!is_array($sel) || count($sel) < 2) {
            return [];
        }
        $out = [];
        for ($i = 1; $i < count($sel); $i++) {
            $row = $sel[$i];
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'contact_id' => (int) ($row['contact_id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Safety guard against sending one user's digest to another recipient:
     * if active opted-in users share contact or email, skip until data is cleaned up.
     *
     * @param list<array{id:int,contact_id:int,name:string,email:string}> $recipients
     * @return array<int,string> map user_id => reason
     */
    private static function indexRecipientIdentityConflicts(array $recipients): array {
        $contactCounts = [];
        $emailCounts = [];
        foreach ($recipients as $r) {
            $contactId = (int) ($r['contact_id'] ?? 0);
            if ($contactId > 0) {
                $contactCounts[$contactId] = ($contactCounts[$contactId] ?? 0) + 1;
            }
            $emailKey = self::normalizeEmail((string) ($r['email'] ?? ''));
            if ($emailKey !== '') {
                $emailCounts[$emailKey] = ($emailCounts[$emailKey] ?? 0) + 1;
            }
        }

        $conflicts = [];
        foreach ($recipients as $r) {
            $uid = (int) ($r['id'] ?? 0);
            if ($uid < 1) {
                continue;
            }
            $reasons = [];
            $contactId = (int) ($r['contact_id'] ?? 0);
            $emailKey = self::normalizeEmail((string) ($r['email'] ?? ''));
            if ($contactId > 0 && ($contactCounts[$contactId] ?? 0) > 1) {
                $reasons[] = 'duplicate_contact_mapping';
            }
            if ($emailKey !== '' && ($emailCounts[$emailKey] ?? 0) > 1) {
                $reasons[] = 'duplicate_email_mapping';
            }
            if (count($reasons) > 0) {
                $conflicts[$uid] = implode(',', $reasons);
            }
        }
        return $conflicts;
    }

    private static function normalizeEmail(string $email): string {
        return strtolower(trim($email));
    }

    /**
     * @param array{
     *  events_upcoming:list<array<string,mixed>>,
     *  events_pending_response:list<array<string,mixed>>,
     *  votes:list<array<string,mixed>>,
     *  tasks:list<array<string,mixed>>,
     *  items:list<array<string,mixed>>,
     *  open_count:int,
     *  future_event_count:int
     * } $summary
     * @return array{
     *  events_upcoming:list<array<string,mixed>>,
     *  events_pending_response:list<array<string,mixed>>,
     *  votes:list<array<string,mixed>>,
     *  tasks:list<array<string,mixed>>,
     *  items:list<array<string,mixed>>,
     *  open_count:int,
     *  future_event_count:int
     * }
     */
    private static function attachParticipationActionUrls($system_data, object $db, array $summary, int $contactId): array {
        if ($contactId < 1) {
            return $summary;
        }
        $allowMaybe = (int) $system_data->getDynamicConfigParameter('allow_participation_maybe') === 1;
        $backupTtl = NextGenParticipationToken::maxTtlSecondsFromConfig($system_data);
        /** @var array<string,string> $tokenCache */
        $tokenCache = [];
        $enrich = static function (array $events) use ($allowMaybe, $backupTtl, $db, $contactId, &$tokenCache): array {
            $out = [];
            foreach ($events as $event) {
                $otype = strtoupper((string) ($event['otype'] ?? ''));
                $oid = (int) ($event['oid'] ?? 0);
                if (($otype !== 'R' && $otype !== 'C') || $oid < 1) {
                    $out[] = $event;
                    continue;
                }

                $deadline = isset($event['replyUntil']) ? (string) $event['replyUntil'] : null;
                $begin = isset($event['eventBegin']) ? (string) $event['eventBegin'] : null;
                $ttl = NextGenParticipationToken::ttlSecondsForEvent($deadline, $begin, $backupTtl);
                $cacheKey = $otype . ':' . $oid;
                try {
                    if (isset($tokenCache[$cacheKey]) && $tokenCache[$cacheKey] !== '') {
                        $plainToken = $tokenCache[$cacheKey];
                    } else {
                        $plainToken = NextGenParticipationToken::newTokenRow($db, $otype, $oid, $contactId, $ttl)['plainToken'];
                        $tokenCache[$cacheKey] = $plainToken;
                    }
                } catch (Throwable $e) {
                    $out[] = $event;
                    continue;
                }

                $urls = [
                    'yes' => MailEnv::nextgenParticipationRespondAbsoluteUrl($plainToken, 'yes'),
                    'no' => MailEnv::nextgenParticipationRespondAbsoluteUrl($plainToken, 'no'),
                ];
                if ($allowMaybe) {
                    $urls['maybe'] = MailEnv::nextgenParticipationRespondAbsoluteUrl($plainToken, 'maybe');
                }
                $event['traffic_urls'] = $urls;
                $event['allow_maybe'] = $allowMaybe;
                $out[] = $event;
            }
            return $out;
        };

        $summary['events_upcoming'] = $enrich($summary['events_upcoming'] ?? []);
        $summary['events_pending_response'] = $enrich($summary['events_pending_response'] ?? []);
        return $summary;
    }
}
