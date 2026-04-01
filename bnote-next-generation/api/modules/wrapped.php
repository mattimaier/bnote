<?php
/**
 * BNote Next Generation - Wrapped API Module
 *
 * Personal yearly summary ("Spotify Wrapped"-style) for members,
 * including playful achievements and band-visible rankings.
 */

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../module_provisioning.php';

class WrappedModule {
    private int $userId;
    private const VIBE_VARIANT_COUNT = 4;

    public function __construct() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }

        $this->userId = intval(Auth::getUserId() ?? 0);
        if ($this->userId <= 0) {
            Response::error('Authentication required', 403);
        }

        $this->assertAccess();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'year';
        switch ($action) {
            case 'year':
                return $this->getYearWrapped();
            case 'years':
                return $this->getAvailableYears();
            case 'canAccess':
                return ['canAccess' => true];
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function assertAccess(): void {
        global $system_data;

        $enabled = strval($system_data->getDynamicConfigParameter('wrapped_module_enabled')) === '1';
        if (!$enabled) {
            Response::error('Wrapped module is disabled', 403);
        }

        $moduleId = ModuleProvisioning::ensureModuleExists('Wrapped', 'cake', 'main');
        if ($moduleId <= 0 || !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Wrapped', 403);
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
        $start = sprintf('%04d-01-01 00:00:00', $year);
        $yearEnd = sprintf('%04d-12-31 23:59:59', $year);
        $now = date('Y-m-d H:i:s');
        $end = strcmp($yearEnd, $now) <= 0 ? $yearEnd : $now;

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function badgeLevelFromThresholds(float $value, float $gold, float $silver): string {
        if ($value >= $gold) {
            return 'gold';
        }
        if ($value >= $silver) {
            return 'silver';
        }
        return 'bronze';
    }

    private function badgeLevelFromInverseThresholds(float $value, float $goldMax, float $silverMax): string {
        if ($value <= $goldMax) {
            return 'gold';
        }
        if ($value <= $silverMax) {
            return 'silver';
        }
        return 'bronze';
    }

    private function clamp(float $value, float $min, float $max): float {
        if ($value < $min) return $min;
        if ($value > $max) return $max;
        return $value;
    }

    /**
     * @param array<string,mixed> $personal
     * @return array{id:string,score:float,variant:int,proof:array<string,mixed>}
     */
    private function buildVibePersona(array $personal, int $year): array {
        $totalEvents = intval($personal['events']['total'] ?? 0);
        $totalResponses = intval($personal['responses']['total'] ?? 0);
        $yesRate = floatval($personal['responses']['yesRate'] ?? 0);
        $responseCompletionRate = $totalEvents > 0
            ? ($totalResponses / $totalEvents) * 100.0
            : 0.0;
        $noResponses = max(0, $totalEvents - $totalResponses);
        $avgDeadlineGapHours = floatval($personal['responses']['deadlineGapHours'] ?? 0);

        $eventEnergyScore = $this->clamp(($totalEvents / 40.0) * 100.0, 0.0, 100.0);
        $reliableAnchorScore = $this->clamp($responseCompletionRate - min($noResponses * 4.0, 35.0), 0.0, 100.0);
        $earlyBirdScore = $this->clamp(70.0 - (($avgDeadlineGapHours / 24.0) * 12.0), 0.0, 100.0);
        $allInScore = $this->clamp($yesRate, 0.0, 100.0);

        $candidates = [
            [
                'id' => 'reliable_anchor',
                'score' => round($reliableAnchorScore, 1),
                'tieCompletion' => round($responseCompletionRate, 1),
                'tieEvents' => $totalEvents,
                'proof' => [
                    'label' => 'response_completion',
                    'value' => round($responseCompletionRate, 1),
                    'unit' => 'percent',
                    'direction' => 'higher_better',
                ],
            ],
            [
                'id' => 'early_bird',
                'score' => round($earlyBirdScore, 1),
                'tieCompletion' => round($responseCompletionRate, 1),
                'tieEvents' => $totalEvents,
                'proof' => [
                    'label' => 'deadline_gap',
                    'value' => round($avgDeadlineGapHours / 24.0, 1),
                    'unit' => 'days',
                    'direction' => 'lower_better',
                ],
            ],
            [
                'id' => 'all_in',
                'score' => round($allInScore, 1),
                'tieCompletion' => round($responseCompletionRate, 1),
                'tieEvents' => $totalEvents,
                'proof' => [
                    'label' => 'yes_rate',
                    'value' => round($yesRate, 1),
                    'unit' => 'percent',
                    'direction' => 'higher_better',
                ],
            ],
            [
                'id' => 'stage_beast',
                'score' => round($eventEnergyScore, 1),
                'tieCompletion' => round($responseCompletionRate, 1),
                'tieEvents' => $totalEvents,
                'proof' => [
                    'label' => 'events',
                    'value' => $totalEvents,
                    'unit' => 'count',
                    'direction' => 'higher_better',
                ],
            ],
        ];

        usort($candidates, function (array $a, array $b): int {
            $scoreCmp = floatval($b['score'] ?? 0) <=> floatval($a['score'] ?? 0);
            if ($scoreCmp !== 0) return $scoreCmp;

            $completionCmp = floatval($b['tieCompletion'] ?? 0) <=> floatval($a['tieCompletion'] ?? 0);
            if ($completionCmp !== 0) return $completionCmp;

            $eventsCmp = intval($b['tieEvents'] ?? 0) <=> intval($a['tieEvents'] ?? 0);
            if ($eventsCmp !== 0) return $eventsCmp;

            return strcmp(strval($a['id'] ?? ''), strval($b['id'] ?? ''));
        });

        $winner = $candidates[0];
        $variantSeed = crc32($this->userId . '|' . $year . '|' . strval($winner['id'] ?? ''));
        $variant = intval(abs(intval($variantSeed)) % self::VIBE_VARIANT_COUNT);

        return [
            'id' => strval($winner['id'] ?? 'reliable_anchor'),
            'score' => round(floatval($winner['score'] ?? 0), 1),
            'variant' => $variant,
            'proof' => is_array($winner['proof'] ?? null) ? $winner['proof'] : [],
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
                    THEN TIMESTAMPDIFF(HOUR, x.approve_until, x.replyon) END) as avgDeadlineGapHours,
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
        $avgDeadlineGapHours = round(floatval($response['avgDeadlineGapHours'] ?? 0), 1);

        $responses = [
            'total' => $totalResponses,
            'yes' => $yesResponses,
            'maybe' => intval($response['maybeResponses'] ?? 0),
            'no' => intval($response['noResponses'] ?? 0),
            'yesRate' => $yesRate,
            'deadlineGapHours' => $avgDeadlineGapHours,
        ];

        $personal = [
            'responses' => $responses,
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

        $personal['vibePersona'] = $this->buildVibePersona($personal, $year);

        return $personal;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getBandAttendanceRanking(int $year, int $minEvents, string $direction): array {
        global $system_data;

        $range = $this->yearRange($year);
        $params = [
            ['s', $range['start']],
            ['s', $range['end']],
            ['s', $range['start']],
            ['s', $range['end']],
            ['i', $minEvents],
        ];

        $order = $direction === 'asc'
            ? 'attendanceRate ASC, invited DESC, c.name ASC, c.surname ASC'
            : 'attendanceRate DESC, invited DESC, c.name ASC, c.surname ASC';

        $sql = "SELECT
                c.name as firstName,
                c.surname as surname,
                COUNT(*) as invited,
                SUM(CASE WHEN x.participate IN (1,2) THEN 1 ELSE 0 END) as attending,
                ROUND((SUM(CASE WHEN x.participate IN (1,2) THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as attendanceRate
            FROM (
                SELECT ru.user as user_id, ru.participate
                FROM rehearsal_user ru
                JOIN rehearsal r ON r.id = ru.rehearsal
                WHERE r.begin >= ? AND r.begin <= ?
                UNION ALL
                SELECT cu.user as user_id, cu.participate
                FROM concert_user cu
                JOIN concert c2 ON c2.id = cu.concert
                WHERE c2.begin >= ? AND c2.begin <= ?
            ) x
            JOIN user u ON u.id = x.user_id
            JOIN contact c ON c.id = u.contact
            WHERE IFNULL(u.isActive, 1) = 1
            GROUP BY x.user_id, c.name, c.surname
            HAVING COUNT(*) >= ?
            ORDER BY $order
            LIMIT 0, 5";

        $rows = $this->rows($system_data->dbcon->getSelection($sql, $params));

        $ranking = [];
        foreach ($rows as $row) {
            $ranking[] = [
                'firstName' => trim((string)($row['firstName'] ?? '')),
                'surname' => trim((string)($row['surname'] ?? '')),
                'eventCount' => intval($row['invited'] ?? 0),
                'attendanceCount' => intval($row['attending'] ?? 0),
                'attendanceRate' => round(floatval($row['attendanceRate'] ?? 0), 1),
            ];
        }

        return $ranking;
    }

    /**
     * @param array<string,mixed> $personal
     * @return array<string,mixed>
     */
    private function getAchievements(int $year, array $personal): array {
        $totalEvents = intval($personal['events']['total'] ?? 0);
        $totalResponses = intval($personal['responses']['total'] ?? 0);
        $yesRate = floatval($personal['responses']['yesRate'] ?? 0);
        $avgDeadlineGapHours = floatval($personal['responses']['deadlineGapHours'] ?? 0);
        $responseCompletionRate = $totalEvents > 0
            ? round(($totalResponses / $totalEvents) * 100, 1)
            : 0.0;

        $personalBadges = [
            [
                'id' => 'attendance_commitment',
                'level' => $this->badgeLevelFromThresholds($yesRate, 85, 70),
                'value' => round($yesRate, 1),
                'unit' => 'percent',
            ],
            [
                'id' => 'response_speed',
                'level' => $this->badgeLevelFromInverseThresholds($avgDeadlineGapHours, -72, -24),
                'value' => round($avgDeadlineGapHours, 1),
                'unit' => 'deadline_gap_hours',
            ],
            [
                'id' => 'response_reliability',
                'level' => $this->badgeLevelFromThresholds($responseCompletionRate, 95, 80),
                'value' => round($responseCompletionRate, 1),
                'unit' => 'percent',
            ],
            [
                'id' => 'event_energy',
                'level' => $this->badgeLevelFromThresholds(floatval($totalEvents), 40, 20),
                'value' => $totalEvents,
                'unit' => 'count',
            ],
        ];

        $minEvents = 5;

        return [
            'personalBadges' => $personalBadges,
            'bandLeaderboard' => [
                'minEvents' => $minEvents,
                'topAttendance' => $this->getBandAttendanceRanking($year, $minEvents, 'desc'),
                'lowestAttendance' => $this->getBandAttendanceRanking($year, $minEvents, 'asc'),
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

        $year = $this->resolveYear();
        $range = $this->yearRange($year);
        $userInfo = Auth::getUserInfo();
        $firstName = trim((string)($userInfo['name'] ?? ''));
        if ($firstName === '') {
            $firstName = trim((string)($userInfo['login'] ?? 'Member'));
        }

        $personal = $this->getPersonalSummary($this->userId, $year);

        return [
            'year' => $year,
            'yearRange' => $range,
            'profile' => [
                'firstName' => $firstName,
                'bandName' => (string)($system_data->getCompany() ?? 'BNote'),
            ],
            'personal' => $personal,
            'band' => $this->getBandSummaryIfAdmin($year),
            'achievements' => $this->getAchievements($year, $personal),
        ];
    }

    /**
     * @return array{years:array<int,int>,startYear:int,endYear:int}
     */
    private function getAvailableYears(): array {
        global $system_data;

        $currentYear = intval(date('Y'));
        $params = [['i', $this->userId], ['i', $this->userId]];
        $sql = "SELECT MIN(y) as minYear
            FROM (
                SELECT YEAR(r.begin) as y
                FROM rehearsal_user ru
                JOIN rehearsal r ON r.id = ru.rehearsal
                WHERE ru.user = ? AND r.begin IS NOT NULL
                UNION ALL
                SELECT YEAR(c.begin) as y
                FROM concert_user cu
                JOIN concert c ON c.id = cu.concert
                WHERE cu.user = ? AND c.begin IS NOT NULL
            ) years";

        $rows = $this->rows($system_data->dbcon->getSelection($sql, $params));
        $minYear = intval($rows[0]['minYear'] ?? $currentYear);
        if ($minYear < 2000 || $minYear > $currentYear) {
            $minYear = $currentYear;
        }

        $years = [];
        for ($y = $currentYear; $y >= $minYear; $y--) {
            $years[] = $y;
        }

        return [
            'years' => $years,
            'startYear' => $minYear,
            'endYear' => $currentYear,
        ];
    }
}
