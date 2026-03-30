<?php
/**
 * Sends event invite mail when rehearsals/concerts are created or contacts are added.
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once __DIR__ . '/NextGenMailPolicy.php';
require_once __DIR__ . '/NextGenMailer.php';
require_once __DIR__ . '/builders/EventParticipantInviteMailBuilder.php';
require_once __DIR__ . '/CommentDiscussionEntitySummary.php';
require_once __DIR__ . '/../nextgen_participation_token.php';

final class EventParticipantNotifier {
    /**
     * @param 'R'|'C' $otype
     * @param list<int>|null $onlyContactIds null = all contacts on the event
     */
    public static function sendSafe($system_data, StartData $startData, string $otype, int $oid, ?array $onlyContactIds): void {
        try {
            if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
                return;
            }
            $otype = strtoupper($otype);
            if (($otype !== 'R' && $otype !== 'C') || $oid < 1) {
                return;
            }

            $db = $system_data->dbcon;
            $rows = self::loadEventContacts($db, $otype, $oid);
            if (count($rows) < 1) {
                return;
            }

            $onlySet = null;
            if ($onlyContactIds !== null && count($onlyContactIds) > 0) {
                $onlySet = [];
                foreach ($onlyContactIds as $cid) {
                    $onlySet[(int) $cid] = true;
                }
            }

            $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';
            $entityTitle = $startData->getObjectTitle($otype, $oid);
            if (!is_string($entityTitle)) {
                $entityTitle = '';
            }
            $entityCard = CommentDiscussionEntitySummary::load($otype, $oid, $system_data);
            $allowMaybe = (int) $system_data->getDynamicConfigParameter('allow_participation_maybe') === 1;

            $deadline = null;
            $begin = null;
            if ($otype === 'R') {
                $ev = $startData->getRehearsal($oid);
                $deadline = is_array($ev) ? ($ev['approve_until'] ?? null) : null;
                $begin = is_array($ev) ? ($ev['begin'] ?? null) : null;
            } else {
                $ev = $startData->getConcert($oid);
                $deadline = is_array($ev) ? ($ev['approve_until'] ?? null) : null;
                $begin = is_array($ev) ? ($ev['begin'] ?? null) : null;
            }
            $ttl = NextGenParticipationToken::ttlSecondsForEvent(
                is_string($deadline) ? $deadline : null,
                is_string($begin) ? $begin : null
            );

            $messages = [];
            foreach ($rows as $r) {
                $cid = (int) ($r['id'] ?? 0);
                if ($cid < 1) {
                    continue;
                }
                if ($onlySet !== null && !isset($onlySet[$cid])) {
                    continue;
                }
                if (!NextGenMailPolicy::contactAllowsTransactionalNotification($system_data, $cid)) {
                    continue;
                }
                $email = trim((string) ($r['email'] ?? ''));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $plainToken = '';
                if (NextGenParticipationToken::userIdForContact($cid, $db) > 0) {
                    try {
                        $plainToken = NextGenParticipationToken::newTokenRow($db, $otype, $oid, $cid, $ttl)['plainToken'];
                    } catch (Throwable $e) {
                        error_log('EventParticipantNotifier token: ' . $e->getMessage());
                        $plainToken = '';
                    }
                }

                $firstName = trim((string) ($r['name'] ?? ''));
                $messages[] = EventParticipantInviteMailBuilder::build(
                    $system_data,
                    $locale,
                    $otype,
                    $oid,
                    $entityTitle,
                    $entityCard,
                    $firstName !== '' ? $firstName : null,
                    $plainToken,
                    $allowMaybe,
                    [$email],
                    []
                );
            }

            if (count($messages) > 0) {
                NextGenMailer::sendBulk($messages);
            }
        } catch (Throwable $e) {
            error_log('EventParticipantNotifier: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    private static function loadEventContacts($db, string $otype, int $oid): array {
        if ($otype === 'R') {
            $sel = $db->getSelection(
                'SELECT c.id, c.name, c.email FROM rehearsal_contact rc JOIN contact c ON c.id = rc.contact WHERE rc.rehearsal = ? ORDER BY c.name',
                [['i', $oid]]
            );
        } else {
            $sel = $db->getSelection(
                'SELECT c.id, c.name, c.email FROM concert_contact cc JOIN contact c ON c.id = cc.contact WHERE cc.concert = ? ORDER BY c.name',
                [['i', $oid]]
            );
        }
        if (!is_array($sel) || count($sel) < 2) {
            return [];
        }
        $out = [];
        for ($i = 1; $i < count($sel); $i++) {
            $row = $sel[$i];
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
            ];
        }

        return $out;
    }
}
