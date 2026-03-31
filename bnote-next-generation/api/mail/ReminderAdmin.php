<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../response.php';

final class ReminderAdmin {
    public static function isAdmin($system_data, ?int $uid = null): bool {
        $userId = $uid ?? (int) Auth::getUserId();
        if ($userId < 1) {
            return false;
        }
        return (method_exists($system_data, 'isUserSuperUser') && $system_data->isUserSuperUser($userId))
            || (method_exists($system_data, 'isUserMemberGroup') && $system_data->isUserMemberGroup(1, $userId));
    }

    public static function requireAdmin($system_data): int {
        $uid = (int) Auth::getUserId();
        if ($uid < 1) {
            Response::error('Not authenticated', 401);
        }
        if (!self::isAdmin($system_data, $uid)) {
            Response::error('Admin privileges required', 403);
        }
        return $uid;
    }
}
