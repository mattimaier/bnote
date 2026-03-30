<?php
/**
 * Apply rehearsal/concert participation from a validated magic token (no session).
 * Next Gen only — uses StartData::saveParticipation (read-only legacy include).
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once __DIR__ . '/nextgen_participation_token.php';

final class ParticipationMagicApply {
    /**
     * @param array{event_type:string,event_id:int,user_id:int,contact_id:int} $row from NextGenParticipationToken::loadValidTokenRow
     * @return array{ok:bool,status:string}
     */
    public static function apply($system_data, array $row, string $status, string $reason = ''): array {
        $eventType = $row['event_type'];
        $eventId = $row['event_id'];
        $userId = (int) $row['user_id'];
        $contactId = (int) $row['contact_id'];

        if ($userId < 1 && $contactId > 0) {
            $userId = NextGenParticipationToken::userIdForContact($contactId, $system_data->dbcon);
        }
        if ($userId < 1) {
            return ['ok' => false, 'error' => 'participation_no_user_for_contact'];
        }

        if ($contactId < 1) {
            $urow = $system_data->dbcon->fetchRow('SELECT contact FROM user WHERE id = ? LIMIT 1', [['i', $userId]]);
            $contactId = is_array($urow) && !empty($urow['contact']) ? (int) $urow['contact'] : 0;
        }
        if ($contactId < 1) {
            return ['ok' => false, 'error' => 'participation_no_user_for_contact'];
        }

        if (!self::contactInvitedToEvent($system_data->dbcon, $eventType, $eventId, $contactId)) {
            return ['ok' => false, 'error' => 'participation_not_invited'];
        }

        $validStatuses = ['yes', 'maybe', 'no', 'undecided'];
        if (!in_array($status, $validStatuses, true)) {
            return ['ok' => false, 'error' => 'participation_invalid_status'];
        }

        if ($status === 'maybe' && (int) $system_data->getDynamicConfigParameter('allow_participation_maybe') !== 1) {
            return ['ok' => false, 'error' => 'participation_maybe_disabled'];
        }

        $deadline = null;
        $eventBegin = null;
        $startData = new StartData();
        if ($eventType === 'R') {
            $ev = $startData->getRehearsal($eventId);
            if (!is_array($ev) || empty($ev['id'])) {
                return ['ok' => false, 'error' => 'participation_event_not_found'];
            }
            $deadline = $ev['approve_until'] ?? null;
            $eventBegin = $ev['begin'] ?? null;
        } else {
            $ev = $startData->getConcert($eventId);
            if (!is_array($ev) || empty($ev['id'])) {
                return ['ok' => false, 'error' => 'participation_event_not_found'];
            }
            $deadline = $ev['approve_until'] ?? null;
            $eventBegin = $ev['begin'] ?? null;
        }

        if (self::isPastDeadline($deadline) || self::isPastBegin($eventBegin)) {
            return ['ok' => false, 'error' => 'participation_locked'];
        }

        $participate = self::mapStatusToParticipation($status);
        $startData->saveParticipation($eventType, $userId, $eventId, $participate, $reason);

        return ['ok' => true, 'status' => $status];
    }

    private static function contactInvitedToEvent($db, string $eventType, int $eventId, int $contactId): bool {
        if ($eventType === 'R') {
            $row = $db->fetchRow(
                'SELECT 1 FROM rehearsal_contact WHERE rehearsal = ? AND contact = ? LIMIT 1',
                [['i', $eventId], ['i', $contactId]]
            );
            return is_array($row);
        }
        $row = $db->fetchRow(
            'SELECT 1 FROM concert_contact WHERE concert = ? AND contact = ? LIMIT 1',
            [['i', $eventId], ['i', $contactId]]
        );
        return is_array($row);
    }

    private static function isPastDeadline($deadline): bool {
        if ($deadline === null || $deadline === '' || $deadline === '-') {
            return false;
        }
        if (strlen(trim((string) $deadline)) < 10) {
            return false;
        }
        $t = strtotime((string) $deadline);
        return $t !== false && $t < time();
    }

    private static function isPastBegin($eventBegin): bool {
        if ($eventBegin === null || $eventBegin === '' || $eventBegin === '-') {
            return false;
        }
        if (strlen(trim((string) $eventBegin)) < 10) {
            return false;
        }
        $t = strtotime((string) $eventBegin);
        return $t !== false && $t < time();
    }

    private static function mapStatusToParticipation(string $status): int {
        return match ($status) {
            'yes' => 1,
            'maybe' => 2,
            'no' => 0,
            default => -1,
        };
    }
}
