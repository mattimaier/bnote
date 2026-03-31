<?php
/**
 * Stable per-user token for calendar subscription/download links.
 */
declare(strict_types=1);

require_once __DIR__ . '/calendar_subscription_token_schema.php';

final class NextGenCalendarSubscriptionToken {
    private const TOKEN_BYTES = 32;

    /**
     * @return array{plainToken:string,tokenHash:string}
     */
    public static function getOrCreateForUser(int $userId, object $db): array {
        if ($userId < 1) {
            throw new InvalidArgumentException('invalid_user');
        }
        if (!CalendarSubscriptionTokenSchema::ensureTable($db)) {
            throw new RuntimeException('calendar_subscription_token_schema_failed');
        }
        if (!self::isActiveUser($userId, $db)) {
            throw new RuntimeException('calendar_subscription_user_inactive');
        }

        $row = $db->fetchRow(
            'SELECT token_plain, token_hash FROM calendar_subscription_token WHERE user_id = ? LIMIT 1',
            [['i', $userId]]
        );
        if (is_array($row) && !empty($row['token_plain']) && !empty($row['token_hash'])) {
            return [
                'plainToken' => (string) $row['token_plain'],
                'tokenHash' => (string) $row['token_hash'],
            ];
        }
        // Legacy/partial row exists but no reusable plaintext token available: rotate once.
        if (is_array($row) && (!empty($row['token_hash']) || array_key_exists('token_plain', $row))) {
            return self::regenerateForUser($userId, $db);
        }

        try {
            return self::insertFreshToken($userId, $db);
        } catch (Throwable $e) {
            // Race-safe fallback: another request may have inserted first.
            $row2 = $db->fetchRow(
                'SELECT token_plain, token_hash FROM calendar_subscription_token WHERE user_id = ? LIMIT 1',
                [['i', $userId]]
            );
            if (is_array($row2) && !empty($row2['token_plain']) && !empty($row2['token_hash'])) {
                return [
                    'plainToken' => (string) $row2['token_plain'],
                    'tokenHash' => (string) $row2['token_hash'],
                ];
            }
            throw $e;
        }
    }

    /**
     * @return array{plainToken:string,tokenHash:string}
     */
    public static function regenerateForUser(int $userId, object $db): array {
        if ($userId < 1) {
            throw new InvalidArgumentException('invalid_user');
        }
        if (!CalendarSubscriptionTokenSchema::ensureTable($db)) {
            throw new RuntimeException('calendar_subscription_token_schema_failed');
        }
        if (!self::isActiveUser($userId, $db)) {
            throw new RuntimeException('calendar_subscription_user_inactive');
        }
        $db->execute(
            'DELETE FROM calendar_subscription_token WHERE user_id = ?',
            [['i', $userId]]
        );
        return self::insertFreshToken($userId, $db);
    }

    public static function loadValidUserId(string $plainToken, object $db): int {
        $plainToken = strtolower(trim($plainToken));
        if ($plainToken === '' || strlen($plainToken) !== 64 || !ctype_xdigit($plainToken)) {
            return 0;
        }
        if (!CalendarSubscriptionTokenSchema::ensureTable($db)) {
            return 0;
        }
        $tokenHash = hash('sha256', $plainToken, false);
        $row = $db->fetchRow(
            'SELECT cst.user_id
             FROM calendar_subscription_token cst
             JOIN user u ON u.id = cst.user_id
             WHERE cst.token_hash = ? AND u.isActive = 1
             LIMIT 1',
            [['s', $tokenHash]]
        );
        return is_array($row) && !empty($row['user_id']) ? (int) $row['user_id'] : 0;
    }

    /**
     * @return array{plainToken:string,tokenHash:string}
     */
    private static function insertFreshToken(int $userId, object $db): array {
        $plain = bin2hex(random_bytes(self::TOKEN_BYTES));
        $tokenHash = hash('sha256', $plain, false);
        $db->execute(
            'INSERT INTO calendar_subscription_token (user_id, token_plain, token_hash) VALUES (?, ?, ?)',
            [['i', $userId], ['s', $plain], ['s', $tokenHash]]
        );
        return [
            'plainToken' => $plain,
            'tokenHash' => $tokenHash,
        ];
    }

    private static function isActiveUser(int $userId, object $db): bool {
        $row = $db->fetchRow(
            'SELECT isActive FROM user WHERE id = ? LIMIT 1',
            [['i', $userId]]
        );
        if (!is_array($row) || !array_key_exists('isActive', $row)) {
            return false;
        }
        return (int) $row['isActive'] === 1;
    }
}

