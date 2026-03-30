<?php
/**
 * Password reset: opaque tokens, SHA-256 at rest, 1h TTL, invalidate prior rows per user.
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

require_once BNOTE_ROOT . '/src/logic/modules/logincontroller.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/nextgen_password_policy.php';
require_once __DIR__ . '/password_reset_schema.php';

final class NextGenPasswordReset {
    const TOKEN_BYTES = 32;
    const TTL_SECONDS = 3600;

    /**
     * @return array{userId:int,email:string}|null
     */
    public static function resolveActiveUserForReset(string $identifier, LoginData $loginData, $system_data): ?array {
        $trim = trim($identifier);
        if ($trim === '') {
            return null;
        }

        if (strpos($trim, '@') !== false) {
            $uid = (int) $loginData->getUserIdForEMail($trim);
        } else {
            $uid = (int) $loginData->getUserIdForLogin($trim);
        }

        if ($uid < 1) {
            return null;
        }

        if (!$loginData->isUserActive($uid)) {
            return null;
        }

        $contact = $system_data->getUsersContact($uid);
        $email = is_array($contact) ? trim((string) ($contact['email'] ?? '')) : '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return ['userId' => $uid, 'email' => $email];
    }

    /**
     * @return array{plainToken:string,tokenHash:string,expiresAt:string}
     */
    public static function newTokenRow(int $userId, $db): array {
        if (!PasswordResetSchema::ensureTable($db)) {
            throw new RuntimeException('password_reset_schema_failed');
        }
        $plain = bin2hex(random_bytes(self::TOKEN_BYTES));
        $tokenHash = hash('sha256', $plain, false);

        $db->execute('DELETE FROM password_reset_token WHERE user_id = ?', [['i', $userId]]);
        $ttl = (int) self::TTL_SECONDS;
        // Use MySQL clock for expiry so NOW() in SELECT matches INSERT (avoids PHP vs DB timezone skew).
        $db->execute(
            'INSERT INTO password_reset_token (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . $ttl . ' SECOND))',
            [['i', $userId], ['s', $tokenHash]]
        );

        return ['plainToken' => $plain, 'tokenHash' => $tokenHash, 'expiresAt' => ''];
    }

    /**
     * @return int user id
     */
    public static function loadValidTokenUserId(string $plainToken, $db): int {
        $plainToken = strtolower(trim($plainToken));
        if ($plainToken === '' || strlen($plainToken) !== 64 || !ctype_xdigit($plainToken)) {
            Response::error('password_reset_invalid', 400);
        }

        if (!PasswordResetSchema::ensureTable($db)) {
            Response::error('password_reset_invalid', 400);
        }

        $tokenHash = hash('sha256', $plainToken, false);

        $row = $db->fetchRow(
            'SELECT user_id FROM password_reset_token WHERE token_hash = ? AND expires_at > NOW() LIMIT 1',
            [['s', $tokenHash]]
        );

        if (!is_array($row) || empty($row['user_id'])) {
            Response::error('password_reset_invalid', 400);
        }

        return (int) $row['user_id'];
    }

    public static function deleteTokensForUser(int $userId, $db): void {
        if (!PasswordResetSchema::ensureTable($db)) {
            return;
        }
        $db->execute('DELETE FROM password_reset_token WHERE user_id = ?', [['i', $userId]]);
    }

    public static function applyNewPassword(int $userId, string $pw1, string $pw2, LoginData $loginData): void {
        if (!NextGenPasswordPolicy::passwordsValid($pw1, $pw2)) {
            Response::error('register_validation', 400);
        }

        $passwordEnc = crypt($pw1, LoginController::ENCRYPTION_HASH);
        $loginData->saveNewPassword($userId, $passwordEnc);
    }
}
