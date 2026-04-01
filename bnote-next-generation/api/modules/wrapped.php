<?php
/**
 * BNote Next Generation - Wrapped API Module
 *
 * Personal yearly summary ("Spotify Wrapped"-style) for members,
 * with optional band-wide aggregates for admins.
 */

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class WrappedModule {
    public function __construct() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        $enabled = strval($system_data->getDynamicConfigParameter('wrapped_module_enabled')) === '1';
        if (!$enabled) {
            Response::error('Wrapped module is disabled', 403);
        }
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'year';
        switch ($action) {
            case 'year':
                return $this->getYearWrapped();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    /**
     * @param array<mixed> $selection
     * @return array<int, array<string, mixed>>
     */
    private function rows(array $selection): array {
        $rows = [];
        for ($i = 1; $i < count($selection); $i++) {
            if (is_array($selection[$i])) {
                $rows[] = $selection[$i];
            }
        }
        return $rows;
    }

    private function resolveYear(): int {
        $requested = intval($_GET['year'] ?? $_POST['year'] ?? 0);
        $current = intval(date('Y'));
        if ($requested < 2000 || $requested > $current + 1) {
            return $current;
        }
        return $requested;
    }

    /**
     * @return array{start:string,end:string}
     */
    private function yearRange(int $year): array {
        return [
            'start' => sprintf('%04d-01-01 00:00:00', $year),
            'end' => sprintf('%04d-12-31 23:59:59', $year),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPersonalSummary(int $userId, int $year): array {
        global $system_data;
        $range = $this->yearRange($year);
        $params = [['i', $userId], ['s', $range['start']], ['s', $range['end']]];

        $responseSql = "SELECT
                SUM(CASE WHEN x.participate IS NOT NULL THEN 1 ELSE 0 END) as totalResponses,
                SUM(CASE WHEN x.participate = 1 THEN 1 ELSE 0 END) as yesResponses,
                SUM(CASE WHEN x.participate = 2 THEN 1 ELSE 0 END) as maybeResponses,
                SUM(CASE WHEN x.participate = 0 THEN 1 ELSE 0 END) as noResponses,
                AVG(CASE WHEN x.replyon IS NOT NULL AND x.approve_until IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, x.replyon, x.approve_until) END) as avgLeadHours
            FROM (
                SELECT ru.participate, ru.replyon, r.approve_until
                FROM rehearsal_user ru
                JOIN rehearsal r ON r.id = ru.rehearsal
                WHERE ru.user = ? AND r.begin >= ? AND r.begin <= ?
                UNION ALL
                SELECT cu.participate, cu.replyon, c.approve_until
                FROM concert_user cu
                JOIN concert c ON c.id = cu.concert
                WHERE cu.user = ? AND c.begin >= ? AND c.begin <= ?
            ) x";

        $responseRows = $this->rows($system_data->dbcon->getSelection(
            $responseSql,
            array_merge($params, $params)
        ));
        $response = count($responseRows) > 0 ? $responseRows[0] : [];

        $eventsSql = "SELECT
                SUM(CASE WHEN x.otype = 'rehearsal' THEN 1 ELSE 0 END) as rehearsals,
                SUM(CASE WHEN x.otype = 'concert' THEN 1 ELSE 0 END) as concerts,
                COUNT(*) as total
            FROM (
                SELECT DISTINCT ru.rehearsal as oid, 'rehearsal' as otype
                FROM rehearsal_user ru
                JOIN rehearsal r ON r.id = ru.rehearsal
                WHERE ru.user = ? AND r.begin >= ? AND r.begin <= ?
                UNION
                SELECT DISTINCT cu.concert as oid, 'concert' as otype
                FROM concert_user cu
                JOIN concert c ON c.id = cu.concert
                WHERE cu.user = ? AND c.begin >= ? AND c.begin <= ?
            ) x";
        $eventRows = $this->rows($system_data->dbcon->getSelection(
            $eventsSql,
            array_merge($params, $params)
        ));
        $events = count($eventRows) > 0 ? $eventRows[0] : [];

        $monthSql = "SELECT month_name, month_count
            FROM (
                SELECT DATE_FORMAT(r.begin, '%Y-%m') as month_name, COUNT(*) as month_count
                FROM rehearsal_user ru
                JOIN rehearsal r ON r.id = ru.rehearsal
                WHERE ru.user = ? AND r.begin >= ? AND r.begin <= ?
                GROUP BY month_name
                UNION ALL
                SELECT DATE_FORMAT(c.begin, '%Y-%m') as month_name, COUNT(*) as month_count
                FROM concert_user cu
                JOIN concert c ON c.id = cu.concert
                WHERE cu.user = ? AND c.begin >= ? AND c.begin <= ?
                GROUP BY month_name
            ) t
            ORDER BY month_count DESC, month_name ASC
            LIMIT 0, 1";
        $monthRows = $this->rows($system_data->dbcon->getSelection(
            $monthSql,
            array_merge($params, $params)
        ));
        $topMonth = count($monthRows) > 0 ? (string)($monthRows[0]['month_name'] ?? '') : '';

        $totalResponses = intval($response['totalResponses'] ?? 0);
        $yesResponses = intval($response['yesResponses'] ?? 0);
        $rehearsals = intval($events['rehearsals'] ?? 0);
        $concerts = intval($events['concerts'] ?? 0);
        $totalEvents = intval($events['total'] ?? 0);
        $yesRate = $totalResponses > 0 ? round(($yesResponses / $totalResponses) * 100, 1) : 0.0;

        return [
            'responses' => [
                'total' => $totalResponses,
                'yes' => $yesResponses,
                'maybe' => intval($response['maybeResponses'] ?? 0),
                'no' => intval($response['noResponses'] ?? 0),
                'yesRate' => $yesRate,
            ],
            'events' => [
                'total' => $totalEvents,
                'rehearsals' => $rehearsals,
                'concerts' => $concerts,
            ],
            'avgLeadHours' => round(floatval($response['avgLeadHours'] ?? 0), 1),
            'topMonth' => $topMonth,
            'funFacts' => [
                'favoriteType' => $rehearsals >= $concerts ? 'rehearsal' : 'concert',
                'responseStyle' => $yesRate >= 70 ? 'committed' : ($yesRate >= 40 ? 'balanced' : 'selective'),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function getBandSummaryIfAdmin(int $year): array {
        global $system_data;
        $userId = Auth::getUserId();
        $isAdmin = $userId && ($system_data->isUserSuperUser($userId) || $system_data->isUserMemberGroup(1, $userId));
        if (!$isAdmin) {
            return [];
        }

        $range = $this->yearRange($year);
        $params = [['s', $range['start']], ['s', $range['end']]];

        $overviewSql = "SELECT
                SUM(CASE WHEN x.otype = 'rehearsal' THEN 1 ELSE 0 END) as rehearsals,
                SUM(CASE WHEN x.otype = 'concert' THEN 1 ELSE 0 END) as concerts,
                COUNT(*) as totalEvents,
                SUM(CASE WHEN x.participate = 1 THEN 1 ELSE 0 END) as yesResponses,
                COUNT(*) as totalResponses
            FROM (
                SELECT 'rehearsal' as otype, ru.participate
                FROM rehearsal_user ru
                JOIN rehearsal r ON r.id = ru.rehearsal
                WHERE r.begin >= ? AND r.begin <= ?
                UNION ALL
                SELECT 'concert' as otype, cu.participate
                FROM concert_user cu
                JOIN concert c ON c.id = cu.concert
                WHERE c.begin >= ? AND c.begin <= ?
            ) x";
        $overviewRows = $this->rows($system_data->dbcon->getSelection(
            $overviewSql,
            array_merge($params, $params)
        ));
        $overview = count($overviewRows) > 0 ? $overviewRows[0] : [];

        $topSql = "SELECT c.name, COUNT(*) as score
            FROM (
                SELECT ru.user as user_id
                FROM rehearsal_user ru
                JOIN rehearsal r ON r.id = ru.rehearsal
                WHERE r.begin >= ? AND r.begin <= ?
                UNION ALL
                SELECT cu.user as user_id
                FROM concert_user cu
                JOIN concert c2 ON c2.id = cu.concert
                WHERE c2.begin >= ? AND c2.begin <= ?
            ) x
            JOIN user u ON u.id = x.user_id
            JOIN contact c ON c.id = u.contact
            GROUP BY x.user_id, c.name
            ORDER BY score DESC, c.name ASC
            LIMIT 0, 5";
        $topRows = $this->rows($system_data->dbcon->getSelection(
            $topSql,
            array_merge($params, $params)
        ));
        $topResponders = [];
        foreach ($topRows as $row) {
            $topResponders[] = [
                'firstName' => trim((string)($row['name'] ?? '')),
                'score' => intval($row['score'] ?? 0),
            ];
        }

        $totalResponses = intval($overview['totalResponses'] ?? 0);
        $yesRate = $totalResponses > 0
            ? round((intval($overview['yesResponses'] ?? 0) / $totalResponses) * 100, 1)
            : 0.0;

        return [
            'events' => [
                'rehearsals' => intval($overview['rehearsals'] ?? 0),
                'concerts' => intval($overview['concerts'] ?? 0),
                'total' => intval($overview['totalEvents'] ?? 0),
            ],
            'yesRate' => $yesRate,
            'topResponders' => $topResponders,
        ];
    }

    private function getYearWrapped(): array {
        global $system_data;

        $userId = intval(Auth::getUserId() ?? 0);
        if ($userId <= 0) {
            Response::error('Authentication required', 403);
        }

        $year = $this->resolveYear();
        $range = $this->yearRange($year);
        $userInfo = Auth::getUserInfo();
        $firstName = trim((string)($userInfo['name'] ?? ''));
        if ($firstName === '') {
            $firstName = trim((string)($userInfo['login'] ?? 'Member'));
        }

        return [
            'year' => $year,
            'yearRange' => $range,
            'profile' => [
                'firstName' => $firstName,
                'bandName' => (string)($system_data->getCompany() ?? 'BNote'),
            ],
            'personal' => $this->getPersonalSummary($userId, $year),
            'band' => $this->getBandSummaryIfAdmin($year),
        ];
    }
}

