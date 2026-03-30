<?php
/**
 * Magic-link tokens for email participation (rehearsal/concert). SHA-256 at rest.
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

declare(strict_types=1);

require_once __DIR__ . '/participation_response_token_schema.php';
require_once __DIR__ . '/mail/MailLocaleDateTime.php';

final class NextGenParticipationToken {
    private const TOKEN_BYTES = 32;

    /** Upper bound for participation magic-link lifetime (30 days). */
    public const MAX_TTL_SECONDS = 2592000;

    /**
     * Resolve user id for a contact, or 0.
     */
    public static function userIdForContact(int $contactId, $db): int {
        if ($contactId < 1) {
            return 0;
        }
        $row = $db->fetchRow(
            'SELECT id FROM user WHERE contact = ? LIMIT 1',
            [['i', $contactId]]
        );
        return is_array($row) && !empty($row['id']) ? (int) $row['id'] : 0;
    }

    /**
     * TTL in seconds until token expiry (MySQL DATE_ADD), capped; uses approve_until / begin when valid.
     */
    public static function ttlSecondsForEvent(?string $approveUntil, ?string $eventBegin): int {
        $now = time();
        $candidates = [];
        foreach ([$approveUntil, $eventBegin] as $dt) {
            if ($dt === null || $dt === '' || $dt === '-') {
                continue;
            }
            $ts = strtotime((string) $dt);
            if ($ts !== false && $ts > $now) {
                $candidates[] = $ts - $now;
            }
        }
        if (count($candidates) > 0) {
            return min(self::MAX_TTL_SECONDS, max(3600, min($candidates)));
        }

        return self::MAX_TTL_SECONDS;
    }

    /**
     * Human-readable “valid until” for mail (band timezone), ~DATE_ADD(NOW(), ttl) when the token is minted.
     */
    public static function formatApproxExpiryForMail(string $locale, int $ttlSeconds): string {
        $ttlSeconds = max(300, min(self::MAX_TTL_SECONDS, $ttlSeconds));
        $tz = new DateTimeZone(MailLocaleDateTime::defaultTimezone());
        $until = (new DateTimeImmutable('now', $tz))->add(new DateInterval('PT' . $ttlSeconds . 'S'));

        return MailLocaleDateTime::formatDateTimeShort($until, $locale);
    }

    /**
     * @return array{plainToken:string,tokenHash:string}
     */
    public static function newTokenRow(
        $db,
        string $eventType,
        int $eventId,
        int $contactId,
        int $ttlSeconds
    ): array {
        if (!ParticipationResponseTokenSchema::ensureTable($db)) {
            throw new RuntimeException('participation_token_schema_failed');
        }
        $eventType = strtoupper($eventType);
        if ($eventType !== 'R' && $eventType !== 'C') {
            throw new InvalidArgumentException('invalid_event_type');
        }
        if ($eventId < 1 || $contactId < 1) {
            throw new InvalidArgumentException('invalid_ids');
        }

        $userId = self::userIdForContact($contactId, $db);
        $ttlSeconds = max(300, min(self::MAX_TTL_SECONDS, $ttlSeconds));

        if ($userId > 0) {
            $db->execute(
                'DELETE FROM participation_response_token WHERE event_type = ? AND event_id = ? AND user_id = ?',
                [['s', $eventType], ['i', $eventId], ['i', $userId]]
            );
        } else {
            $db->execute(
                'DELETE FROM participation_response_token WHERE event_type = ? AND event_id = ? AND contact_id = ? AND user_id IS NULL',
                [['s', $eventType], ['i', $eventId], ['i', $contactId]]
            );
        }

        $plain = bin2hex(random_bytes(self::TOKEN_BYTES));
        $tokenHash = hash('sha256', $plain, false);

        if ($userId > 0) {
            $db->execute(
                'INSERT INTO participation_response_token (token_hash, event_type, event_id, user_id, contact_id, expires_at) VALUES (?, ?, ?, ?, NULL, DATE_ADD(NOW(), INTERVAL ' . (int) $ttlSeconds . ' SECOND))',
                [['s', $tokenHash], ['s', $eventType], ['i', $eventId], ['i', $userId]]
            );
        } else {
            $db->execute(
                'INSERT INTO participation_response_token (token_hash, event_type, event_id, user_id, contact_id, expires_at) VALUES (?, ?, ?, NULL, ?, DATE_ADD(NOW(), INTERVAL ' . (int) $ttlSeconds . ' SECOND))',
                [['s', $tokenHash], ['s', $eventType], ['i', $eventId], ['i', $contactId]]
            );
        }

        return ['plainToken' => $plain, 'tokenHash' => $tokenHash];
    }

    /**
     * @return array{event_type:string,event_id:int,user_id:int,contact_id:int,expiresAt:string}|null
     */
    public static function loadValidTokenRow(string $plainToken, $db): ?array {
        $plainToken = strtolower(trim($plainToken));
        if ($plainToken === '' || strlen($plainToken) !== 64 || !ctype_xdigit($plainToken)) {
            return null;
        }
        if (!ParticipationResponseTokenSchema::ensureTable($db)) {
            return null;
        }
        $tokenHash = hash('sha256', $plainToken, false);
        $row = $db->fetchRow(
            'SELECT event_type, event_id, user_id, contact_id, expires_at FROM participation_response_token WHERE token_hash = ? AND expires_at > NOW() LIMIT 1',
            [['s', $tokenHash]]
        );
        if (!is_array($row) || empty($row['event_id'])) {
            return null;
        }
        $uid = isset($row['user_id']) && $row['user_id'] !== null ? (int) $row['user_id'] : 0;
        $cid = isset($row['contact_id']) && $row['contact_id'] !== null ? (int) $row['contact_id'] : 0;
        $expiresAt = self::expiresAtIso8601($row['expires_at'] ?? null);

        return [
            'event_type' => strtoupper((string) ($row['event_type'] ?? '')),
            'event_id' => (int) $row['event_id'],
            'user_id' => $uid,
            'contact_id' => $cid,
            'expiresAt' => $expiresAt,
        ];
    }

    private static function expiresAtIso8601($mysqlRaw): string {
        if ($mysqlRaw === null) {
            return '';
        }
        $s = trim((string) $mysqlRaw);
        if ($s === '') {
            return '';
        }
        $tz = new DateTimeZone(MailLocaleDateTime::defaultTimezone());
        try {
            $dt = new DateTimeImmutable($s, $tz);

            return $dt->format(\DateTimeInterface::ATOM);
        } catch (Throwable $e) {
            return '';
        }
    }
}
