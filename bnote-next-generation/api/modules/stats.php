<?php
/**
 * BNote Next Generation - Statistics API Module
 *
 * Provides admin-oriented statistics dashboard data with legacy-compatible
 * module permission semantics (Stats/Auswertungen module privilege).
 */

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once BNOTE_ROOT . '/src/data/modules/aufgabendata.php';
require_once BNOTE_ROOT . '/src/data/modules/abstimmungdata.php';

class StatsModule {
    /** @var string[] */
    private array $statsModuleAliases = ['Stats', 'Auswertungen', 'Statistik', 'Statistics'];
    /** @var array<string, bool> */
    private array $columnCache = [];

    public function __construct() {
        $this->assertPermission();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

        switch ($action) {
            case 'dashboard':
                return $this->getDashboard();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    /**
     * @return array{scope:string,year:int,start:?string,end:?string,labels:array<int,string>,availableYears:array<int,int>}
     */
    private function resolvePeriodContext(): array {
        $scopeRaw = strtolower(trim((string)($_GET['scope'] ?? $_POST['scope'] ?? 'year')));
        $scope = $scopeRaw === 'all' ? 'all' : 'year';
        $currentYear = intval(date('Y'));
        $year = intval($_GET['year'] ?? $_POST['year'] ?? $currentYear);
        if ($year < 2000 || $year > ($currentYear + 1)) {
            $year = $currentYear;
        }

        $availableYears = $this->getAvailableYears();
        if ($scope === 'year' && !in_array($year, $availableYears, true)) {
            $year = count($availableYears) > 0 ? $availableYears[count($availableYears) - 1] : $currentYear;
        }

        if ($scope === 'all') {
            return [
                'scope' => 'all',
                'year' => $year,
                'start' => null,
                'end' => null,
                'labels' => array_map(static fn($y) => strval($y), $availableYears),
                'availableYears' => array_reverse($availableYears),
            ];
        }

        $labels = [];
        for ($month = 1; $month <= 12; $month++) {
            $labels[] = sprintf('%04d-%02d', $year, $month);
        }
        return [
            'scope' => 'year',
            'year' => $year,
            'start' => sprintf('%04d-01-01 00:00:00', $year),
            'end' => sprintf('%04d-12-31 23:59:59', $year),
            'labels' => $labels,
            'availableYears' => array_reverse($availableYears),
        ];
    }

    /**
     * @return array<int,int>
     */
    private function getAvailableYears(): array {
        global $system_data;
        $query = "SELECT MIN(begin) as min_begin FROM rehearsal
                  UNION ALL
                  SELECT MIN(begin) as min_begin FROM concert";
        $rows = $this->rows($this->getSelectionSafe($query, [], 'available-years'));
        $minYear = intval(date('Y'));
        foreach ($rows as $row) {
            $value = trim((string)($row['min_begin'] ?? ''));
            if ($value !== '') {
                $year = intval(substr($value, 0, 4));
                if ($year > 0 && $year < $minYear) {
                    $minYear = $year;
                }
            }
        }
        $maxYear = intval(date('Y'));
        $years = [];
        for ($y = $minYear; $y <= $maxYear; $y++) {
            $years[] = $y;
        }
        if (count($years) === 0) {
            $years[] = $maxYear;
        }
        return $years;
    }

    private function periodKeyExpression(string $column, array $context): string {
        $expr = $this->normalizeDateExpression($column);
        return $context['scope'] === 'all'
            ? "DATE_FORMAT($expr, '%Y')"
            : "DATE_FORMAT($expr, '%Y-%m')";
    }

    /**
     * @param array<int, array<int|string>> $params
     */
    private function applyDateFilter(string $column, array $context, array &$params): string {
        if ($context['scope'] === 'all') {
            return '';
        }
        $params[] = ['s', (string)$context['start']];
        $params[] = ['s', (string)$context['end']];
        $expr = $this->normalizeDateExpression($column);
        return " WHERE $expr IS NOT NULL AND $expr >= ? AND $expr <= ? ";
    }

    private function assertPermission(): void {
        global $system_data;

        $moduleId = $this->resolveStatsModuleId();
        if ($moduleId > 0) {
            if (!$system_data->userHasPermission($moduleId)) {
                Response::error('Access denied to statistics', 403);
            }
            return;
        }

        // Fallback when no dedicated stats module exists: allow admins only.
        $userId = Auth::getUserId();
        $isAdmin = $userId && ($system_data->isUserSuperUser($userId) || $system_data->isUserMemberGroup(1, $userId));
        if (!$isAdmin) {
            Response::error('Access denied to statistics', 403);
        }
    }

    private function resolveStatsModuleId(): int {
        global $system_data;

        foreach ($this->statsModuleAliases as $name) {
            $id = intval($system_data->getModuleId($name));
            if ($id > 0) {
                return $id;
            }
        }

        $aliases = array_map(
            static fn($v) => mb_strtolower($v),
            $this->statsModuleAliases
        );
        $modules = $system_data->getModuleArray();
        if (!is_array($modules)) {
            return 0;
        }

        foreach ($modules as $modId => $modRow) {
            if (!is_array($modRow)) {
                continue;
            }
            $name = trim(html_entity_decode((string)($modRow['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($name === '') {
                continue;
            }
            if (in_array(mb_strtolower($name), $aliases, true)) {
                $resolved = intval($modRow['id'] ?? $modId);
                if ($resolved > 0) {
                    return $resolved;
                }
            }
        }

        return 0;
    }

    /**
     * Convert legacy selection format (header row at index 0) to rows.
     *
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

    /**
     * @param array<int, array<int|string>> $params
     * @return array<mixed>
     */
    private function getSelectionSafe(string $query, array $params = [], string $label = ''): array {
        global $system_data;
        return $system_data->dbcon->getSelection($query, $params);
    }

    private function hasColumn(string $table, string $column): bool {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }
        global $system_data;
        $query = "SELECT COUNT(*) as cnt
                  FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?";
        $rows = $this->rows($this->getSelectionSafe($query, [['s', $table], ['s', $column]], 'has-column'));
        $has = count($rows) > 0 && intval($rows[0]['cnt'] ?? 0) > 0;
        $this->columnCache[$key] = $has;
        return $has;
    }

    private function normalizeDateExpression(string $column): string {
        return "COALESCE(STR_TO_DATE($column, '%Y-%m-%d %H:%i:%s'), STR_TO_DATE($column, '%Y-%m-%d'))";
    }

    /**
     * @return array<int, string>
     */
    private function monthSeries(int $months): array {
        $start = new DateTimeImmutable('first day of this month');
        $start = $start->modify('-' . max(0, $months - 1) . ' months');
        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->modify('+' . $i . ' months')->format('Y-m');
        }
        return $labels;
    }

    /**
     * @param array<int, string> $labels
     * @return array<int, array{month:string,rehearsals:int,concerts:int}>
     */
    private function getEventsByMonth(array $labels, array $context): array {
        $rehearsals = $this->monthCountMap('rehearsal', 'begin', $context);
        $concerts = $this->monthCountMap('concert', 'begin', $context);

        $series = [];
        foreach ($labels as $month) {
            $series[] = [
                'month' => $month,
                'rehearsals' => intval($rehearsals[$month] ?? 0),
                'concerts' => intval($concerts[$month] ?? 0),
            ];
        }
        return $series;
    }

    /**
     * @return array<string, int>
     */
    private function monthCountMap(string $table, string $dateColumn, array $context): array {
        global $system_data;
        $params = [];
        $query = "SELECT " . $this->periodKeyExpression($dateColumn, $context) . " as ym, COUNT(*) as cnt
                  FROM `$table`";
        $query .= $this->applyDateFilter($dateColumn, $context, $params);
        $query .= " GROUP BY ym";
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'month-count-map'));
        $map = [];
        foreach ($rows as $row) {
            $map[(string)($row['ym'] ?? '')] = intval($row['cnt'] ?? 0);
        }
        return $map;
    }

    /**
     * @return array<int, array{name:string,count:int}>
     */
    private function getMembersPerGroup(): array {
        global $system_data;

        $query = "SELECT g.name, COUNT(*) as num
                  FROM `contact_group` cg
                  JOIN `group` g ON cg.`group` = g.id
                  GROUP BY g.id, g.name
                  ORDER BY num DESC, g.name ASC";
        $rows = $this->rows($this->getSelectionSafe($query, [], 'members-per-group'));
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'name' => (string)($row['name'] ?? ''),
                'count' => intval($row['num'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array{name:string,surname:string,firstName:string,instrument:string,score:int,rank:int}>
     */
    private function getTopRehearsalParticipants(array $context): array {
        global $system_data;
        $params = [];
        $where = $this->applyDateFilter("r.begin", $context, $params);
        $where .= ($where === '' ? ' WHERE ' : ' AND ') . " ru.participate = 1 ";

        $query = "SELECT c.name, c.surname, COALESCE(i.name, '') as instrument, COUNT(*) as score
                  FROM `rehearsal_user` ru
                  JOIN `rehearsal` r ON ru.rehearsal = r.id
                  JOIN `user` u ON ru.user = u.id
                  JOIN `contact` c ON u.contact = c.id
                  LEFT JOIN `instrument` i ON c.instrument = i.id
                  $where
                  GROUP BY ru.`user`, c.name, c.surname, i.name
                  ORDER BY score DESC, c.surname ASC, c.name ASC
                  LIMIT 0, 8";
        return $this->rankedParticipants($this->rows($this->getSelectionSafe($query, $params, 'top-rehearsal-participants')));
    }

    /**
     * @return array<int, array{name:string,surname:string,firstName:string,instrument:string,score:int,rank:int}>
     */
    private function getTopVoteParticipants(array $context): array {
        global $system_data;
        $params = [];
        $where = $this->applyDateFilter("v.end", $context, $params);

        $query = "SELECT c.name, c.surname, COALESCE(i.name, '') as instrument, COUNT(*) as score
                  FROM `vote_option_user` vou
                  JOIN `vote_option` vo ON vou.vote_option = vo.id
                  JOIN `vote` v ON vo.vote = v.id
                  JOIN `user` u ON vou.user = u.id
                  JOIN `contact` c ON u.contact = c.id
                  LEFT JOIN `instrument` i ON c.instrument = i.id
                  $where
                  GROUP BY vou.`user`, c.name, c.surname, i.name
                  ORDER BY score DESC, c.surname ASC, c.name ASC
                  LIMIT 0, 8";
        return $this->rankedParticipants($this->rows($this->getSelectionSafe($query, $params, 'top-vote-participants')));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{name:string,surname:string,firstName:string,instrument:string,score:int,rank:int}>
     */
    private function rankedParticipants(array $rows): array {
        $out = [];
        $rank = 1;
        foreach ($rows as $row) {
            $firstName = trim((string)($row['name'] ?? ''));
            $out[] = [
                'name' => $firstName,
                'surname' => trim((string)($row['surname'] ?? '')),
                'firstName' => $firstName,
                'instrument' => trim((string)($row['instrument'] ?? '')),
                'score' => intval($row['score'] ?? 0),
                'rank' => $rank++,
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCriticalEvents(int $limit = 12): array {
        $rows = array_merge(
            $this->getCriticalRehearsals($limit),
            $this->getCriticalConcerts($limit)
        );

        usort($rows, static function ($a, $b) {
            $pendingCmp = intval($b['pendingUsers'] ?? 0) <=> intval($a['pendingUsers'] ?? 0);
            if ($pendingCmp !== 0) {
                return $pendingCmp;
            }
            return strcmp((string)($a['begin'] ?? ''), (string)($b['begin'] ?? ''));
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCriticalRehearsals(int $limit): array {
        global $system_data;

        $beginExpr = $this->normalizeDateExpression("r.begin");
        $query = "SELECT
                    r.id,
                    r.begin,
                    r.approve_until,
                    r.status,
                    l.name as location_name,
                    COUNT(DISTINCT u.id) as invited_users,
                    COUNT(DISTINCT ru.user) as replied_users,
                    SUM(CASE WHEN ru.user IS NULL THEN 1 ELSE 0 END) as pending_users
                  FROM `rehearsal` r
                  LEFT JOIN `location` l ON r.location = l.id
                  JOIN `rehearsal_contact` rc ON rc.rehearsal = r.id
                  JOIN `user` u ON u.contact = rc.contact
                  LEFT JOIN `rehearsal_user` ru ON ru.rehearsal = r.id AND ru.user = u.id
                  WHERE $beginExpr IS NOT NULL
                    AND $beginExpr >= NOW()
                    AND $beginExpr <= DATE_ADD(NOW(), INTERVAL 90 DAY)
                  GROUP BY r.id, r.begin, r.approve_until, r.status, l.name
                  HAVING pending_users > 0
                  ORDER BY pending_users DESC, r.begin ASC
                  LIMIT 0, ?";

        $rows = $this->rows($this->getSelectionSafe($query, [['i', $limit]], 'critical-rehearsals'));
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->formatCriticalEventRow('rehearsal', $row, 'Rehearsal #' . intval($row['id'] ?? 0));
        }
        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCriticalConcerts(int $limit): array {
        global $system_data;

        $beginExpr = $this->normalizeDateExpression("c.begin");
        $query = "SELECT
                    c.id,
                    c.title,
                    c.begin,
                    c.approve_until,
                    c.status,
                    l.name as location_name,
                    COUNT(DISTINCT u.id) as invited_users,
                    COUNT(DISTINCT cu.user) as replied_users,
                    SUM(CASE WHEN cu.user IS NULL THEN 1 ELSE 0 END) as pending_users
                  FROM `concert` c
                  LEFT JOIN `location` l ON c.location = l.id
                  JOIN `concert_contact` cc ON cc.concert = c.id
                  JOIN `user` u ON u.contact = cc.contact
                  LEFT JOIN `concert_user` cu ON cu.concert = c.id AND cu.user = u.id
                  WHERE $beginExpr IS NOT NULL
                    AND $beginExpr >= NOW()
                    AND $beginExpr <= DATE_ADD(NOW(), INTERVAL 90 DAY)
                  GROUP BY c.id, c.title, c.begin, c.approve_until, c.status, l.name
                  HAVING pending_users > 0
                  ORDER BY pending_users DESC, c.begin ASC
                  LIMIT 0, ?";

        $rows = $this->rows($this->getSelectionSafe($query, [['i', $limit]], 'critical-concerts'));
        $out = [];
        foreach ($rows as $row) {
            $fallbackTitle = 'Concert #' . intval($row['id'] ?? 0);
            $title = trim((string)($row['title'] ?? '')) ?: $fallbackTitle;
            $out[] = $this->formatCriticalEventRow('concert', $row, $title);
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatCriticalEventRow(string $type, array $row, string $title): array {
        $approveUntil = (string)($row['approve_until'] ?? '');
        $isDeadlineSoon = false;
        if ($approveUntil !== '') {
            $deadlineTs = strtotime($approveUntil);
            if ($deadlineTs !== false) {
                $isDeadlineSoon = $deadlineTs <= strtotime('+48 hours');
            }
        }

        return [
            'type' => $type,
            'id' => intval($row['id'] ?? 0),
            'title' => $title,
            'begin' => (string)($row['begin'] ?? ''),
            'approveUntil' => $approveUntil,
            'status' => (string)($row['status'] ?? ''),
            'locationName' => (string)($row['location_name'] ?? ''),
            'invitedUsers' => intval($row['invited_users'] ?? 0),
            'repliedUsers' => intval($row['replied_users'] ?? 0),
            'pendingUsers' => intval($row['pending_users'] ?? 0),
            'severity' => $isDeadlineSoon ? 'critical' : 'warning',
        ];
    }

    /**
     * @param array<int, string> $labels
     * @return array{
     *   series:array<int,array<string,mixed>>,
     *   overallRate:float,
     *   overallYes:int,
     *   overallTotal:int,
     *   byType:array{
     *     rehearsals:array{yes:int,total:int,rate:float},
     *     concerts:array{yes:int,total:int,rate:float}
     *   }
     * }
     */
    private function getParticipationTrend(array $labels, array $context): array {
        $rehearsal = $this->monthlyParticipationRows('rehearsal', 'rehearsal_user', 'rehearsal', $context);
        $concert = $this->monthlyParticipationRows('concert', 'concert_user', 'concert', $context);

        $series = [];
        $overallYes = 0;
        $overallTotal = 0;
        $rehearsalYes = 0;
        $rehearsalTotal = 0;
        $concertYes = 0;
        $concertTotal = 0;

        foreach ($labels as $month) {
            $reYes = intval($rehearsal[$month]['yes'] ?? 0);
            $reTotal = intval($rehearsal[$month]['total'] ?? 0);
            $coYes = intval($concert[$month]['yes'] ?? 0);
            $coTotal = intval($concert[$month]['total'] ?? 0);
            $yes = $reYes + $coYes;
            $total = $reTotal + $coTotal;
            $rate = $total > 0 ? round(($yes / $total) * 100, 1) : 0.0;
            $series[] = [
                'month' => $month,
                'rate' => $rate,
                'yes' => $yes,
                'total' => $total,
            ];
            $overallYes += $yes;
            $overallTotal += $total;
            $rehearsalYes += $reYes;
            $rehearsalTotal += $reTotal;
            $concertYes += $coYes;
            $concertTotal += $coTotal;
        }

        $overallRate = $overallTotal > 0 ? round(($overallYes / $overallTotal) * 100, 1) : 0.0;
        return [
            'series' => $series,
            'overallRate' => $overallRate,
            'overallYes' => $overallYes,
            'overallTotal' => $overallTotal,
            'byType' => [
                'rehearsals' => [
                    'yes' => $rehearsalYes,
                    'total' => $rehearsalTotal,
                    'rate' => $rehearsalTotal > 0 ? round(($rehearsalYes / $rehearsalTotal) * 100, 1) : 0.0,
                ],
                'concerts' => [
                    'yes' => $concertYes,
                    'total' => $concertTotal,
                    'rate' => $concertTotal > 0 ? round(($concertYes / $concertTotal) * 100, 1) : 0.0,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{yes:int,total:int}>
     */
    private function monthlyParticipationRows(string $eventTable, string $responseTable, string $fk, array $context): array {
        global $system_data;
        $params = [];

        $query = "SELECT
                    " . $this->periodKeyExpression("e.begin", $context) . " as ym,
                    SUM(CASE WHEN r.participate = 1 THEN 1 ELSE 0 END) as yes_count,
                    COUNT(*) as total_count
                  FROM `$responseTable` r
                  JOIN `$eventTable` e ON r.`$fk` = e.id";
        $query .= $this->applyDateFilter("e.begin", $context, $params);
        $query .= "
                  GROUP BY ym";
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'monthly-participation-rows'));
        $map = [];
        foreach ($rows as $row) {
            $map[(string)($row['ym'] ?? '')] = [
                'yes' => intval($row['yes_count'] ?? 0),
                'total' => intval($row['total_count'] ?? 0),
            ];
        }
        return $map;
    }

    /**
     * @return array<string, array{invited:int,replied:int}>
     */
    private function monthlyInvitationRows(
        string $eventTable,
        string $inviteTable,
        string $responseTable,
        string $fk,
        array $context
    ): array {
        global $system_data;
        $params = [];

        $query = "SELECT
                    " . $this->periodKeyExpression("e.begin", $context) . " as ym,
                    COUNT(*) as invited_count,
                    SUM(CASE WHEN r.user IS NOT NULL THEN 1 ELSE 0 END) as replied_count
                  FROM `$eventTable` e
                  JOIN `$inviteTable` i ON i.`$fk` = e.id
                  JOIN `user` u ON u.contact = i.contact
                  LEFT JOIN `$responseTable` r ON r.`$fk` = e.id AND r.user = u.id";
        $query .= $this->applyDateFilter("e.begin", $context, $params);
        $query .= "
                  GROUP BY ym";
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'monthly-invitation-rows'));
        $map = [];
        foreach ($rows as $row) {
            $map[(string)($row['ym'] ?? '')] = [
                'invited' => intval($row['invited_count'] ?? 0),
                'replied' => intval($row['replied_count'] ?? 0),
            ];
        }
        return $map;
    }

    /**
     * @param array<int, string> $labels
     * @return array{
     *   series:array<int,array{month:string,rate:float,replied:int,invited:int}>,
     *   overallRate:float,
     *   replied:int,
     *   invited:int
     * }
     */
    private function getResponseCompletionTrend(array $labels, array $context): array {
        $rehearsal = $this->monthlyInvitationRows('rehearsal', 'rehearsal_contact', 'rehearsal_user', 'rehearsal', $context);
        $concert = $this->monthlyInvitationRows('concert', 'concert_contact', 'concert_user', 'concert', $context);

        $series = [];
        $overallInvited = 0;
        $overallReplied = 0;

        foreach ($labels as $month) {
            $reInvited = intval($rehearsal[$month]['invited'] ?? 0);
            $reReplied = intval($rehearsal[$month]['replied'] ?? 0);
            $coInvited = intval($concert[$month]['invited'] ?? 0);
            $coReplied = intval($concert[$month]['replied'] ?? 0);
            $invited = $reInvited + $coInvited;
            $replied = $reReplied + $coReplied;
            $series[] = [
                'month' => $month,
                'rate' => $invited > 0 ? round(($replied / $invited) * 100, 1) : 0.0,
                'replied' => $replied,
                'invited' => $invited,
            ];
            $overallInvited += $invited;
            $overallReplied += $replied;
        }

        return [
            'series' => $series,
            'overallRate' => $overallInvited > 0 ? round(($overallReplied / $overallInvited) * 100, 1) : 0.0,
            'replied' => $overallReplied,
            'invited' => $overallInvited,
        ];
    }

    /**
     * @return array<string, array{invited:int,yes:int,maybe:int,no:int,pending:int}>
     */
    private function monthlyResponseMixRows(
        string $eventTable,
        string $inviteTable,
        string $responseTable,
        string $fk,
        array $context
    ): array {
        global $system_data;
        $params = [];

        $query = "SELECT
                    " . $this->periodKeyExpression("e.begin", $context) . " as ym,
                    COUNT(*) as invited_count,
                    SUM(CASE WHEN r.participate = 1 THEN 1 ELSE 0 END) as yes_count,
                    SUM(CASE WHEN r.participate = 2 THEN 1 ELSE 0 END) as maybe_count,
                    SUM(CASE WHEN r.participate = 0 THEN 1 ELSE 0 END) as no_count,
                    SUM(CASE WHEN r.participate IS NULL OR r.participate < 0 THEN 1 ELSE 0 END) as pending_count
                  FROM `$eventTable` e
                  JOIN `$inviteTable` i ON i.`$fk` = e.id
                  JOIN `user` u ON u.contact = i.contact
                  LEFT JOIN `$responseTable` r ON r.`$fk` = e.id AND r.user = u.id";
        $query .= $this->applyDateFilter("e.begin", $context, $params);
        $query .= "
                  GROUP BY ym";

        $rows = $this->rows($this->getSelectionSafe($query, $params, 'monthly-response-mix'));
        $map = [];
        foreach ($rows as $row) {
            $map[(string)($row['ym'] ?? '')] = [
                'invited' => intval($row['invited_count'] ?? 0),
                'yes' => intval($row['yes_count'] ?? 0),
                'maybe' => intval($row['maybe_count'] ?? 0),
                'no' => intval($row['no_count'] ?? 0),
                'pending' => intval($row['pending_count'] ?? 0),
            ];
        }
        return $map;
    }

    /**
     * @param array<string, array{invited:int,yes:int,maybe:int,no:int,pending:int}> $map
     * @return array{invited:int,yes:int,maybe:int,no:int,pending:int}
     */
    private function summarizeResponseMix(array $map): array {
        $summary = [
            'invited' => 0,
            'yes' => 0,
            'maybe' => 0,
            'no' => 0,
            'pending' => 0,
        ];
        foreach ($map as $row) {
            $summary['invited'] += intval($row['invited'] ?? 0);
            $summary['yes'] += intval($row['yes'] ?? 0);
            $summary['maybe'] += intval($row['maybe'] ?? 0);
            $summary['no'] += intval($row['no'] ?? 0);
            $summary['pending'] += intval($row['pending'] ?? 0);
        }
        return $summary;
    }

    /**
     * @return array{medianHours:float,p90Hours:float,avgHours:float,sampleSize:int}
     */
    private function getResponseLeadTimeStats(array $context): array {
        global $system_data;
        $params = [];

        $query = "SELECT TIMESTAMPDIFF(HOUR, x.reply_dt, x.approve_dt) as lead_hours
                  FROM (
                    SELECT
                      COALESCE(STR_TO_DATE(ru.replyon, '%Y-%m-%d %H:%i:%s'), STR_TO_DATE(ru.replyon, '%Y-%m-%d')) as reply_dt,
                      COALESCE(STR_TO_DATE(r.approve_until, '%Y-%m-%d %H:%i:%s'), STR_TO_DATE(r.approve_until, '%Y-%m-%d')) as approve_dt,
                      COALESCE(STR_TO_DATE(r.begin, '%Y-%m-%d %H:%i:%s'), STR_TO_DATE(r.begin, '%Y-%m-%d')) as event_begin_dt
                    FROM rehearsal_user ru
                    JOIN rehearsal r ON r.id = ru.rehearsal
                    UNION ALL
                    SELECT
                      COALESCE(STR_TO_DATE(cu.replyon, '%Y-%m-%d %H:%i:%s'), STR_TO_DATE(cu.replyon, '%Y-%m-%d')) as reply_dt,
                      COALESCE(STR_TO_DATE(c.approve_until, '%Y-%m-%d %H:%i:%s'), STR_TO_DATE(c.approve_until, '%Y-%m-%d')) as approve_dt,
                      COALESCE(STR_TO_DATE(c.begin, '%Y-%m-%d %H:%i:%s'), STR_TO_DATE(c.begin, '%Y-%m-%d')) as event_begin_dt
                    FROM concert_user cu
                    JOIN concert c ON c.id = cu.concert
                  ) x
                  WHERE x.reply_dt IS NOT NULL
                    AND x.approve_dt IS NOT NULL
                    AND x.event_begin_dt IS NOT NULL
                    AND x.reply_dt <= x.approve_dt";
        if ($context['scope'] !== 'all') {
            $expr = "x.event_begin_dt";
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $query .= " AND $expr IS NOT NULL AND $expr >= ? AND $expr <= ? ";
        }

        $rows = $this->rows($this->getSelectionSafe($query, $params, 'lead-time'));
        $values = [];
        foreach ($rows as $row) {
            $value = $row['lead_hours'] ?? null;
            if ($value === null) {
                continue;
            }
            $values[] = floatval($value);
        }

        if (count($values) === 0) {
            return [
                'medianHours' => 0.0,
                'p90Hours' => 0.0,
                'avgHours' => 0.0,
                'sampleSize' => 0,
            ];
        }

        sort($values, SORT_NUMERIC);
        $median = $this->percentile($values, 50);
        $p90 = $this->percentile($values, 90);
        $avg = array_sum($values) / count($values);
        return [
            'medianHours' => round($median, 1),
            'p90Hours' => round($p90, 1),
            'avgHours' => round($avg, 1),
            'sampleSize' => count($values),
        ];
    }

    /**
     * @param array<int, float> $values
     */
    private function percentile(array $values, float $percentile): float {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }
        $rank = ($percentile / 100) * ($count - 1);
        $lower = (int) floor($rank);
        $upper = (int) ceil($rank);
        if ($lower === $upper) {
            return $values[$lower];
        }
        $weight = $rank - $lower;
        return ($values[$lower] * (1 - $weight)) + ($values[$upper] * $weight);
    }

    /**
     * @param array<int, float> $values
     */
    private function variance(array $values): float {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }
        $mean = array_sum($values) / $count;
        $sum = 0.0;
        foreach ($values as $v) {
            $sum += ($v - $mean) ** 2;
        }
        return $sum / $count;
    }

    /**
     * @param array<int, array{month:string,rate:float,yes:int,total:int}> $series
     * @return array{variance:float,stdDev:float}
     */
    private function getParticipationStabilityIndex(array $series): array {
        $values = [];
        foreach ($series as $row) {
            if (intval($row['total'] ?? 0) <= 0) {
                continue;
            }
            $values[] = floatval($row['rate'] ?? 0);
        }
        $variance = $this->variance($values);
        return [
            'variance' => round($variance, 2),
            'stdDev' => round(sqrt($variance), 2),
        ];
    }

    /**
     * @param array<int, string> $labels
     * @return array{series:array<int,array{month:string,rate:float,active:int,total:int}>,overallRate:float,active:int,total:int}
     */
    private function getActiveMemberTrend(array $labels, array $context): array {
        global $system_data;
        $totalSel = $this->getSelectionSafe(
            "SELECT COUNT(*) as cnt FROM user WHERE isActive = 1",
            [],
            'active-members-total'
        );
        $totalRows = $this->rows($totalSel);
        $totalActive = count($totalRows) > 0 ? intval($totalRows[0]['cnt'] ?? 0) : 0;

        $params = [];
        $ruReplyExpr = $this->normalizeDateExpression("ru.replyon");
        $cuReplyExpr = $this->normalizeDateExpression("cu.replyon");
        $query = "SELECT " . $this->periodKeyExpression("x.replyon", $context) . " as ym,
                         COUNT(DISTINCT x.user) as cnt
                  FROM (
                    SELECT ru.user, ru.replyon FROM rehearsal_user ru WHERE $ruReplyExpr IS NOT NULL
                    UNION ALL
                    SELECT cu.user, cu.replyon FROM concert_user cu WHERE $cuReplyExpr IS NOT NULL
                  ) x";
        $query .= $this->applyDateFilter("x.replyon", $context, $params);
        $query .= " GROUP BY ym";
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'active-members-trend'));
        $map = [];
        foreach ($rows as $row) {
            $map[(string)($row['ym'] ?? '')] = intval($row['cnt'] ?? 0);
        }

        $series = [];
        $overallActive = 0;
        foreach ($labels as $month) {
            $active = intval($map[$month] ?? 0);
            $rate = $totalActive > 0 ? round(($active / $totalActive) * 100, 1) : 0.0;
            $series[] = [
                'month' => $month,
                'rate' => $rate,
                'active' => $active,
                'total' => $totalActive,
            ];
            $overallActive += $active;
        }

        $overallRate = $totalActive > 0 ? round((($overallActive / max(1, count($labels))) / $totalActive) * 100, 1) : 0.0;
        return [
            'series' => $series,
            'overallRate' => $overallRate,
            'active' => $overallActive,
            'total' => $totalActive,
        ];
    }

    /**
     * @return array{buckets:array<int,array{label:string,count:int}>,totalUsers:int}
     */
    private function getResponseConsistencyStreaks(array $context): array {
        global $system_data;
        $params = [];
        $replyExpr = $this->normalizeDateExpression("x.replyon");
        $approveExpr = $this->normalizeDateExpression("x.approve_until");
        $beginExpr = $this->normalizeDateExpression("x.event_begin");
        $query = "SELECT x.user, x.event_begin, x.replyon, x.approve_until
                  FROM (
                    SELECT ru.user, r.begin as event_begin, ru.replyon, r.approve_until
                    FROM rehearsal_user ru
                    JOIN rehearsal r ON r.id = ru.rehearsal
                    UNION ALL
                    SELECT cu.user, c.begin as event_begin, cu.replyon, c.approve_until
                    FROM concert_user cu
                    JOIN concert c ON c.id = cu.concert
                  ) x
                  WHERE $replyExpr IS NOT NULL
                    AND $approveExpr IS NOT NULL
                    AND $beginExpr IS NOT NULL";
        if ($context['scope'] !== 'all') {
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $query .= " AND $beginExpr >= ? AND $beginExpr <= ? ";
        }
        $query .= " ORDER BY x.user ASC, x.event_begin ASC";
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'response-streaks'));

        $maxStreakByUser = [];
        $currentUser = null;
        $currentStreak = 0;
        $maxStreak = 0;
        foreach ($rows as $row) {
            $uid = intval($row['user'] ?? 0);
            if ($currentUser !== $uid) {
                if ($currentUser !== null) {
                    $maxStreakByUser[$currentUser] = max($maxStreak, $currentStreak);
                }
                $currentUser = $uid;
                $currentStreak = 0;
                $maxStreak = 0;
            }
            $replyon = (string)($row['replyon'] ?? '');
            $approveUntil = (string)($row['approve_until'] ?? '');
            if ($replyon !== '' && $approveUntil !== '' && strtotime($replyon) <= strtotime($approveUntil)) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }
        if ($currentUser !== null) {
            $maxStreakByUser[$currentUser] = max($maxStreak, $currentStreak);
        }

        $buckets = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5+' => 0,
        ];
        foreach ($maxStreakByUser as $streak) {
            if ($streak >= 5) {
                $buckets['5+']++;
            } elseif ($streak >= 4) {
                $buckets['4']++;
            } elseif ($streak >= 3) {
                $buckets['3']++;
            } elseif ($streak >= 2) {
                $buckets['2']++;
            } elseif ($streak >= 1) {
                $buckets['1']++;
            }
        }

        $bucketRows = [];
        foreach ($buckets as $label => $count) {
            $bucketRows[] = ['label' => $label, 'count' => $count];
        }

        return [
            'buckets' => $bucketRows,
            'totalUsers' => count($maxStreakByUser),
        ];
    }

    /**
     * @param array<int, string> $labels
     * @return array{series:array<int,array{month:string,medianHours:float,count:int}>,overallMedian:float,available:bool}
     */
    private function getTaskCompletionLatency(array $labels, array $context): array {
        global $system_data;
        $completionColumn = null;
        foreach (['completed_at', 'done_at', 'finished_at', 'closed_at'] as $col) {
            if ($this->hasColumn('task', $col)) {
                $completionColumn = $col;
                break;
            }
        }
        if ($completionColumn === null || !$this->hasColumn('task', 'created_at')) {
            $series = [];
            foreach ($labels as $month) {
                $series[] = ['month' => $month, 'medianHours' => 0.0, 'count' => 0];
            }
            return ['series' => $series, 'overallMedian' => 0.0, 'available' => false];
        }

        $params = [];
        $completedExpr = $this->normalizeDateExpression("t.$completionColumn");
        $createdExpr = $this->normalizeDateExpression("t.created_at");
        $query = "SELECT " . $this->periodKeyExpression("t.$completionColumn", $context) . " as ym,
                         TIMESTAMPDIFF(HOUR, $createdExpr, $completedExpr) as hours
                  FROM task t
                  WHERE $completedExpr IS NOT NULL
                    AND $createdExpr IS NOT NULL";
        if ($context['scope'] !== 'all') {
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $query .= " AND $completedExpr >= ? AND $completedExpr <= ? ";
        }
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'task-latency'));
        $byMonth = [];
        foreach ($rows as $row) {
            $month = (string)($row['ym'] ?? '');
            $hours = $row['hours'] ?? null;
            if ($month === '' || $hours === null) {
                continue;
            }
            $byMonth[$month][] = floatval($hours);
        }

        $series = [];
        $allValues = [];
        foreach ($labels as $month) {
            $values = $byMonth[$month] ?? [];
            sort($values, SORT_NUMERIC);
            $median = count($values) > 0 ? $this->percentile($values, 50) : 0.0;
            foreach ($values as $v) {
                $allValues[] = $v;
            }
            $series[] = [
                'month' => $month,
                'medianHours' => round($median, 1),
                'count' => count($values),
            ];
        }
        sort($allValues, SORT_NUMERIC);
        $overallMedian = count($allValues) > 0 ? $this->percentile($allValues, 50) : 0.0;
        return [
            'series' => $series,
            'overallMedian' => round($overallMedian, 1),
            'available' => true,
        ];
    }

    /**
     * @param array<int, string> $labels
     * @return array{series:array<int,array{month:string,rate:float,votes:int,eligible:int}>,overallRate:float}
     */
    private function getVoteParticipationTrend(array $labels, array $context): array {
        global $system_data;
        $params = [];
        $endExpr = $this->normalizeDateExpression("v.end");
        $query = "SELECT v.id, v.end,
                         COALESCE(eligible.cnt, 0) as eligible,
                         COALESCE(voted.cnt, 0) as voted
                  FROM vote v
                  LEFT JOIN (
                    SELECT vote, COUNT(DISTINCT user) as cnt
                    FROM vote_group
                    GROUP BY vote
                  ) eligible ON eligible.vote = v.id
                  LEFT JOIN (
                    SELECT vo.vote, COUNT(DISTINCT vou.user) as cnt
                    FROM vote_option vo
                    JOIN vote_option_user vou ON vo.id = vou.vote_option
                    GROUP BY vo.vote
                  ) voted ON voted.vote = v.id
                  WHERE $endExpr IS NOT NULL";
        if ($context['scope'] !== 'all') {
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $query .= " AND $endExpr >= ? AND $endExpr <= ? ";
        }
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'vote-participation'));
        $byMonth = [];
        $overallEligible = 0;
        $overallVoted = 0;
        foreach ($rows as $row) {
            $end = (string)($row['end'] ?? '');
            if ($end === '') continue;
            $key = $context['scope'] === 'all'
                ? date('Y', strtotime($end))
                : date('Y-m', strtotime($end));
            $eligible = intval($row['eligible'] ?? 0);
            $voted = intval($row['voted'] ?? 0);
            $byMonth[$key][] = ['eligible' => $eligible, 'voted' => $voted];
            $overallEligible += $eligible;
            $overallVoted += $voted;
        }

        $series = [];
        foreach ($labels as $month) {
            $items = $byMonth[$month] ?? [];
            $eligibleSum = 0;
            $votedSum = 0;
            foreach ($items as $it) {
                $eligibleSum += $it['eligible'];
                $votedSum += $it['voted'];
            }
            $rate = $eligibleSum > 0 ? round(($votedSum / $eligibleSum) * 100, 1) : 0.0;
            $series[] = [
                'month' => $month,
                'rate' => $rate,
                'votes' => $votedSum,
                'eligible' => $eligibleSum,
            ];
        }
        $overallRate = $overallEligible > 0 ? round(($overallVoted / $overallEligible) * 100, 1) : 0.0;
        return [
            'series' => $series,
            'overallRate' => $overallRate,
        ];
    }

    /**
     * @return array{beforeCount:int,afterCount:int,upliftRate:float,escalations:int}
     */
    private function getReminderEffectiveness(array $context): array {
        global $system_data;
        $params = [];
        $escalationExpr = $this->normalizeDateExpression("e.created_at");
        $rehearsalReplyExpr = $this->normalizeDateExpression("ru.replyon");
        $concertReplyExpr = $this->normalizeDateExpression("cu.replyon");
        $query = "SELECT
                    SUM(CASE WHEN r.replyon BETWEEN e.created_at AND DATE_ADD(e.created_at, INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as after_count,
                    SUM(CASE WHEN r.replyon BETWEEN DATE_SUB(e.created_at, INTERVAL 24 HOUR) AND e.created_at THEN 1 ELSE 0 END) as before_count,
                    COUNT(DISTINCT e.id) as escalation_count
                  FROM nextgen_escalation_audit e
                  JOIN (
                    SELECT 'R' as otype, ru.rehearsal as oid, ru.replyon
                    FROM rehearsal_user ru
                    WHERE $rehearsalReplyExpr IS NOT NULL
                    UNION ALL
                    SELECT 'C' as otype, cu.concert as oid, cu.replyon
                    FROM concert_user cu
                    WHERE $concertReplyExpr IS NOT NULL
                  ) r ON r.otype = e.otype AND r.oid = e.oid
                  WHERE e.is_test = 0
                    AND $escalationExpr IS NOT NULL";
        if ($context['scope'] !== 'all') {
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $query .= " AND $escalationExpr >= ? AND $escalationExpr <= ? ";
        }
        $rows = $this->rows($this->getSelectionSafe($query, $params, 'reminder-effectiveness'));
        $row = count($rows) > 0 ? $rows[0] : [];
        $after = intval($row['after_count'] ?? 0);
        $before = intval($row['before_count'] ?? 0);
        $total = $after + $before;
        $uplift = $total > 0 ? round((($after - $before) / $total) * 100, 1) : 0.0;
        return [
            'beforeCount' => $before,
            'afterCount' => $after,
            'upliftRate' => $uplift,
            'escalations' => intval($row['escalation_count'] ?? 0),
        ];
    }

    /**
     * @return array{rehearsal:array<string,int>,concert:array<string,int>}
     */
    private function getInstrumentMinimumsFromConfig(): array {
        global $system_data;
        $sectionCoverageEnabled = $this->isSectionCoverageEnabled();
        $json = $system_data->getDynamicConfigParameter('instrument_minimums');
        if (!$json) {
            return ['mode' => 'instrument', 'rehearsal' => [], 'concert' => []];
        }
        $parsed = json_decode((string)$json, true);
        if (!is_array($parsed)) {
            return ['mode' => 'instrument', 'rehearsal' => [], 'concert' => []];
        }
        $mode = (($parsed['mode'] ?? 'instrument') === 'section' && $sectionCoverageEnabled) ? 'section' : 'instrument';
        $normalize = function ($input) use ($mode): array {
            $out = [];
            if (!is_array($input)) {
                return $out;
            }
            foreach ($input as $instId => $min) {
                $key = trim((string) $instId);
                $val = is_numeric($min) ? intval($min) : 0;
                if ($val < 1 || $key === '') {
                    continue;
                }
                if ($mode === 'section') {
                    if (str_starts_with($key, 'section:')) {
                        $sectionId = trim(substr($key, 8));
                        if ($sectionId !== '') {
                            $out['section:' . $sectionId] = $val;
                        }
                    }
                }
                if (is_numeric($key) && intval($key) > 0) {
                    $out[(string) intval($key)] = $val;
                }
            }
            return $out;
        };
        if (isset($parsed['rehearsal']) || isset($parsed['concert'])) {
            $reh = $normalize($parsed['rehearsal'] ?? []);
            $con = $normalize($parsed['concert'] ?? []);
            if (count($con) < 1) {
                $con = $reh;
            }
            return ['mode' => $mode, 'rehearsal' => $reh, 'concert' => $con];
        }
        $legacy = ($mode === 'section') ? [] : $normalize($parsed);
        return ['mode' => 'instrument', 'rehearsal' => $legacy, 'concert' => $legacy];
    }

    private function getInstrumentSections(): array {
        global $system_data;
        if (!$this->isSectionCoverageEnabled()) {
            return [];
        }
        $raw = (string) ($system_data->getDynamicConfigParameter('nextgen_instrument_sections') ?? '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isSectionCoverageEnabled(): bool {
        global $system_data;
        return (string) ($system_data->getDynamicConfigParameter('beta_section_coverage_enabled') ?? '') === '1';
    }

    private function getResolvedSectionsForCoverage(): array {
        $sections = $this->getInstrumentSections();
        $out = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $sectionId = trim((string) ($section['id'] ?? ''));
            $sectionName = trim((string) ($section['name'] ?? ''));
            if ($sectionId === '' || $sectionName === '') {
                continue;
            }
            $ids = [];
            foreach (($section['instrument_ids'] ?? []) as $rawId) {
                $iid = (int) $rawId;
                if ($iid > 0) {
                    $ids[] = $iid;
                }
            }
            $targetMap = [];
            foreach (($section['concert_instrument_targets'] ?? []) as $target) {
                if (!is_array($target)) {
                    continue;
                }
                $targetInstrumentId = (int) ($target['instrument_id'] ?? 0);
                $targetRequired = max(0, (int) ($target['required'] ?? 0));
                if ($targetInstrumentId > 0 && $targetRequired > 0) {
                    $targetMap[$targetInstrumentId] = $targetRequired;
                    $ids[] = $targetInstrumentId;
                }
            }
            $rehearsalMinTotal = max(0, (int) ($section['rehearsal_min_total'] ?? 0));
            $concertMinTotal = max(0, (int) ($section['concert_min_total'] ?? 0));
            $out[] = [
                'id' => $sectionId,
                'name' => $sectionName,
                'instrument_ids' => array_values(array_unique($ids)),
                'rehearsal_min_total' => $rehearsalMinTotal,
                'concert_min_total' => $concertMinTotal,
                'concert_instrument_targets' => $targetMap,
            ];
        }
        return $out;
    }

    private function getInstrumentGapsForRehearsal($rid, $minimums, $mode = 'instrument') {
        global $system_data;
        $query = "SELECT i.id as instrument_id, i.name as instrument_name,
                  SUM(CASE WHEN ru.participate IN (1,2) THEN 1 ELSE 0 END) as attending
                  FROM rehearsal_contact rc
                  JOIN contact ct ON rc.contact = ct.id
                  LEFT JOIN instrument i ON ct.instrument = i.id
                  LEFT JOIN user u ON u.contact = ct.id
                  LEFT JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = ?
                  WHERE rc.rehearsal = ?
                  GROUP BY i.id, i.name";
        $rows = $this->getSelectionSafe($query, [['i', $rid], ['i', $rid]], 'instrument-gaps-rehearsal');
        $gaps = [];
        $attendingByInstrument = [];
        $instrumentNamesById = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $instId = (int) ($row['instrument_id'] ?? 0);
                if ($instId <= 0) continue;
                $attending = (int) ($row['attending'] ?? 0);
                $attendingByInstrument[$instId] = $attending;
                $instrumentNamesById[$instId] = (string) ($row['instrument_name'] ?? '');
                $min = isset($minimums[(string) $instId]) ? (int) $minimums[(string) $instId] : null;
                if ($min === null || $min <= 0) continue;
                if ($attending < $min) {
                    $gaps[] = [
                        'instrument_id' => (string) $instId,
                        'instrument_name' => $instrumentNamesById[$instId] ?? '',
                        'current' => $attending,
                        'minimum' => $min,
                    ];
                }
            }
        }
        if ($mode === 'section') {
            $assignedInstrumentIds = [];
            foreach ($this->getResolvedSectionsForCoverage() as $section) {
                $sectionId = (string) ($section['id'] ?? '');
                if ($sectionId === '') {
                    continue;
                }
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $assignedInstrumentIds[$iid] = true;
                    }
                }
                $min = max(
                    (int) ($section['rehearsal_min_total'] ?? 0),
                    isset($minimums['section:' . $sectionId]) ? (int) $minimums['section:' . $sectionId] : 0
                );
                if ($min < 1) {
                    continue;
                }
                $current = 0;
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $current += (int) ($attendingByInstrument[$iid] ?? 0);
                    }
                }
                if ($current < $min) {
                    $gaps[] = [
                        'instrument_id' => 'section:' . $sectionId,
                        'instrument_name' => (string) ($section['name'] ?? $sectionId),
                        'current' => $current,
                        'minimum' => $min,
                    ];
                }
            }
            foreach ($minimums as $instrumentId => $minRaw) {
                if (!is_numeric($instrumentId)) {
                    continue;
                }
                $iid = (int) $instrumentId;
                if ($iid < 1 || isset($assignedInstrumentIds[$iid])) {
                    continue;
                }
                $min = (int) $minRaw;
                $current = (int) ($attendingByInstrument[$iid] ?? 0);
                if ($min > 0 && $current < $min) {
                    $gaps[] = [
                        'instrument_id' => (string) $iid,
                        'instrument_name' => $instrumentNamesById[$iid] ?? '',
                        'current' => $current,
                        'minimum' => $min,
                    ];
                }
            }
            return $gaps;
        }
        foreach ($minimums as $instrumentId => $minRaw) {
            if (!is_numeric($instrumentId)) {
                continue;
            }
            $iid = (int) $instrumentId;
            if ($iid < 1) {
                continue;
            }
            $min = (int) $minRaw;
            if ($min < 1) {
                continue;
            }
            $current = (int) ($attendingByInstrument[$iid] ?? 0);
            if ($current < $min) {
                $gaps[] = [
                    'instrument_id' => (string) $iid,
                    'instrument_name' => $instrumentNamesById[$iid] ?? '',
                    'current' => $current,
                    'minimum' => $min,
                ];
            }
        }
        return $gaps;
    }

    private function getInstrumentGapsForConcert($cid, $minimums, $mode = 'instrument') {
        global $system_data;
        $query = "SELECT i.id as instrument_id, i.name as instrument_name,
                  SUM(CASE WHEN cu.participate IN (1,2) THEN 1 ELSE 0 END) as attending
                  FROM concert_contact cc
                  JOIN contact ct ON cc.contact = ct.id
                  LEFT JOIN instrument i ON ct.instrument = i.id
                  LEFT JOIN user u ON u.contact = ct.id
                  LEFT JOIN concert_user cu ON cu.user = u.id AND cu.concert = ?
                  WHERE cc.concert = ?
                  GROUP BY i.id, i.name";
        $rows = $this->getSelectionSafe($query, [['i', $cid], ['i', $cid]], 'instrument-gaps-concert');
        $gaps = [];
        $attendingByInstrument = [];
        $instrumentNamesById = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $instId = (int) ($row['instrument_id'] ?? 0);
                if ($instId <= 0) continue;
                $attending = (int) ($row['attending'] ?? 0);
                $attendingByInstrument[$instId] = $attending;
                $instrumentNamesById[$instId] = (string) ($row['instrument_name'] ?? '');
                $min = isset($minimums[(string) $instId]) ? (int) $minimums[(string) $instId] : null;
                if ($min === null || $min <= 0) continue;
                if ($attending < $min) {
                    $gaps[] = [
                        'instrument_id' => (string) $instId,
                        'instrument_name' => $instrumentNamesById[$instId] ?? '',
                        'current' => $attending,
                        'minimum' => $min,
                    ];
                }
            }
        }
        if ($mode === 'section') {
            $assignedInstrumentIds = [];
            foreach ($this->getResolvedSectionsForCoverage() as $section) {
                $sectionId = (string) ($section['id'] ?? '');
                if ($sectionId === '') {
                    continue;
                }
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $assignedInstrumentIds[$iid] = true;
                    }
                }

                $targets = is_array($section['concert_instrument_targets'] ?? null) ? $section['concert_instrument_targets'] : [];
                if (count($targets) > 0) {
                    foreach ($targets as $targetInstrumentId => $requiredRaw) {
                        $targetId = (int) $targetInstrumentId;
                        $required = (int) $requiredRaw;
                        if ($targetId < 1 || $required < 1) {
                            continue;
                        }
                        $current = (int) ($attendingByInstrument[$targetId] ?? 0);
                        if ($current < $required) {
                            $gaps[] = [
                                'instrument_id' => 'section:' . $sectionId,
                                'instrument_name' => (string) ($section['name'] ?? $sectionId),
                                'current' => $current,
                                'minimum' => $required,
                            ];
                        }
                    }
                    continue;
                }

                $min = max(
                    (int) ($section['concert_min_total'] ?? 0),
                    isset($minimums['section:' . $sectionId]) ? (int) $minimums['section:' . $sectionId] : 0
                );
                if ($min < 1) {
                    continue;
                }
                $currentTotal = 0;
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $currentTotal += (int) ($attendingByInstrument[$iid] ?? 0);
                    }
                }
                if ($currentTotal < $min) {
                    $gaps[] = [
                        'instrument_id' => 'section:' . $sectionId,
                        'instrument_name' => (string) ($section['name'] ?? $sectionId),
                        'current' => $currentTotal,
                        'minimum' => $min,
                    ];
                }
            }
            foreach ($minimums as $instrumentId => $minRaw) {
                if (!is_numeric($instrumentId)) {
                    continue;
                }
                $iid = (int) $instrumentId;
                if ($iid < 1 || isset($assignedInstrumentIds[$iid])) {
                    continue;
                }
                $min = (int) $minRaw;
                $current = (int) ($attendingByInstrument[$iid] ?? 0);
                if ($min > 0 && $current < $min) {
                    $gaps[] = [
                        'instrument_id' => (string) $iid,
                        'instrument_name' => $instrumentNamesById[$iid] ?? '',
                        'current' => $current,
                        'minimum' => $min,
                    ];
                }
            }
            return $gaps;
        }
        foreach ($minimums as $instrumentId => $minRaw) {
            if (!is_numeric($instrumentId)) {
                continue;
            }
            $iid = (int) $instrumentId;
            if ($iid < 1) {
                continue;
            }
            $min = (int) $minRaw;
            if ($min < 1) {
                continue;
            }
            $current = (int) ($attendingByInstrument[$iid] ?? 0);
            if ($current < $min) {
                $gaps[] = [
                    'instrument_id' => (string) $iid,
                    'instrument_name' => $instrumentNamesById[$iid] ?? '',
                    'current' => $current,
                    'minimum' => $min,
                ];
            }
        }
        return $gaps;
    }

    /**
     * @return array{byInstrument:array<int,array{name:string,shortfalls:int,events:int}>,totalEvents:int}
     */
    private function getInstrumentCoverageRisk(): array {
        $minimumsByType = $this->getInstrumentMinimumsFromConfig();
        $coverageMode = (($minimumsByType['mode'] ?? 'instrument') === 'section') ? 'section' : 'instrument';
        $minimumsRehearsal = is_array($minimumsByType['rehearsal'] ?? null) ? $minimumsByType['rehearsal'] : [];
        $minimumsConcert = is_array($minimumsByType['concert'] ?? null) ? $minimumsByType['concert'] : [];
        if (empty($minimumsRehearsal) && empty($minimumsConcert)) {
            return ['byInstrument' => [], 'totalEvents' => 0];
        }
        $eventsWithGaps = 0;
        $byInstrument = [];
        global $system_data;
        $rehearsals = $this->getSelectionSafe(
            "SELECT id FROM rehearsal WHERE " . $this->normalizeDateExpression("begin") . " IS NOT NULL AND " . $this->normalizeDateExpression("begin") . " >= NOW() AND " . $this->normalizeDateExpression("begin") . " <= DATE_ADD(NOW(), INTERVAL 90 DAY)",
            [],
            'instrument-risk-rehearsals'
        );
        if (is_array($rehearsals)) {
            for ($i = 1; $i < count($rehearsals); $i++) {
                $rid = (int) ($rehearsals[$i]['id'] ?? 0);
                $gaps = $this->getInstrumentGapsForRehearsal($rid, $minimumsRehearsal, $coverageMode);
                if (!empty($gaps)) {
                    $eventsWithGaps++;
                    foreach ($gaps as $gap) {
                        $id = (string) ($gap['instrument_id'] ?? '');
                        if ($id === '') continue;
                        if (!isset($byInstrument[$id])) {
                            $byInstrument[$id] = ['name' => $gap['instrument_name'] ?? '', 'shortfalls' => 0, 'events' => 0];
                        }
                        $byInstrument[$id]['shortfalls']++;
                        $byInstrument[$id]['events']++;
                    }
                }
            }
        }
        $concerts = $this->getSelectionSafe(
            "SELECT id FROM concert WHERE " . $this->normalizeDateExpression("begin") . " IS NOT NULL AND " . $this->normalizeDateExpression("begin") . " >= NOW() AND " . $this->normalizeDateExpression("begin") . " <= DATE_ADD(NOW(), INTERVAL 90 DAY)",
            [],
            'instrument-risk-concerts'
        );
        if (is_array($concerts)) {
            for ($i = 1; $i < count($concerts); $i++) {
                $cid = (int) ($concerts[$i]['id'] ?? 0);
                $gaps = $this->getInstrumentGapsForConcert($cid, $minimumsConcert, $coverageMode);
                if (!empty($gaps)) {
                    $eventsWithGaps++;
                    foreach ($gaps as $gap) {
                        $id = (string) ($gap['instrument_id'] ?? '');
                        if ($id === '') continue;
                        if (!isset($byInstrument[$id])) {
                            $byInstrument[$id] = ['name' => $gap['instrument_name'] ?? '', 'shortfalls' => 0, 'events' => 0];
                        }
                        $byInstrument[$id]['shortfalls']++;
                        $byInstrument[$id]['events']++;
                    }
                }
            }
        }
        return [
            'byInstrument' => array_values($byInstrument),
            'totalEvents' => $eventsWithGaps,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUserResponseAggregates(array $context): array {
        global $system_data;
        $params = [];
        $dateFilterRehearsal = $context['scope'] === 'all'
            ? ''
            : " AND " . $this->normalizeDateExpression("r.begin") . " IS NOT NULL AND " . $this->normalizeDateExpression("r.begin") . " >= ? AND " . $this->normalizeDateExpression("r.begin") . " <= ? ";
        $dateFilterConcert = $context['scope'] === 'all'
            ? ''
            : " AND " . $this->normalizeDateExpression("c.begin") . " IS NOT NULL AND " . $this->normalizeDateExpression("c.begin") . " >= ? AND " . $this->normalizeDateExpression("c.begin") . " <= ? ";
        if ($context['scope'] !== 'all') {
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
        }

        $query = "SELECT
                    u.id as user_id,
                    c.name,
                    c.surname,
                    COALESCE(i.name, '') as instrument,
                    COUNT(*) as invited_count,
                    SUM(CASE WHEN x.response_user IS NOT NULL THEN 1 ELSE 0 END) as replied_count,
                    SUM(CASE WHEN x.participate = 1 THEN 1 ELSE 0 END) as yes_count,
                    SUM(CASE WHEN x.participate = 2 THEN 1 ELSE 0 END) as maybe_count,
                    SUM(CASE WHEN x.participate = 0 THEN 1 ELSE 0 END) as no_count,
                    SUM(CASE WHEN x.reply_dt IS NOT NULL AND x.approve_dt IS NOT NULL AND x.event_begin_dt IS NOT NULL
                              AND x.reply_dt > x.approve_dt AND x.reply_dt <= x.event_begin_dt THEN 1 ELSE 0 END) as late_count,
                    AVG(CASE WHEN x.reply_dt IS NOT NULL AND x.approve_dt IS NOT NULL AND x.event_begin_dt IS NOT NULL
                              AND x.reply_dt <= x.approve_dt
                              THEN TIMESTAMPDIFF(HOUR, x.reply_dt, x.approve_dt) END) as avg_lead_hours
                  FROM (
                    SELECT u.id as user_id, ru.user as response_user, ru.participate, ru.replyon,
                           r.approve_until, r.begin as event_begin,
                           " . $this->normalizeDateExpression("ru.replyon") . " as reply_dt,
                           " . $this->normalizeDateExpression("r.approve_until") . " as approve_dt,
                           " . $this->normalizeDateExpression("r.begin") . " as event_begin_dt
                    FROM rehearsal_contact rc
                    JOIN user u ON u.contact = rc.contact
                    JOIN rehearsal r ON r.id = rc.rehearsal
                    LEFT JOIN rehearsal_user ru ON ru.rehearsal = r.id AND ru.user = u.id
                    WHERE 1=1 $dateFilterRehearsal
                    UNION ALL
                    SELECT u.id as user_id, cu.user as response_user, cu.participate, cu.replyon,
                           c.approve_until, c.begin as event_begin,
                           " . $this->normalizeDateExpression("cu.replyon") . " as reply_dt,
                           " . $this->normalizeDateExpression("c.approve_until") . " as approve_dt,
                           " . $this->normalizeDateExpression("c.begin") . " as event_begin_dt
                    FROM concert_contact cc
                    JOIN user u ON u.contact = cc.contact
                    JOIN concert c ON c.id = cc.concert
                    LEFT JOIN concert_user cu ON cu.concert = c.id AND cu.user = u.id
                    WHERE 1=1 $dateFilterConcert
                  ) x
                  JOIN user u ON u.id = x.user_id
                  JOIN contact c ON u.contact = c.id
                  LEFT JOIN instrument i ON c.instrument = i.id
                  WHERE u.isActive = 1
                  GROUP BY u.id, c.name, c.surname, i.name";

        return $this->rows($this->getSelectionSafe($query, $params, 'user-response-aggregates'));
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserRankings(array $context): array {
        $rows = $this->getUserResponseAggregates($context);
        $minInvited = 5;
        $minReplied = 3;

        $build = function (callable $valueFn, callable $filterFn, string $direction) use ($rows) {
            $items = [];
            foreach ($rows as $row) {
                if (!$filterFn($row)) {
                    continue;
                }
                $value = $valueFn($row);
                if ($value === null) {
                    continue;
                }
                $items[] = [
                    'userId' => intval($row['user_id'] ?? 0),
                    'name' => trim((string)($row['name'] ?? '')),
                    'surname' => trim((string)($row['surname'] ?? '')),
                    'instrument' => trim((string)($row['instrument'] ?? '')),
                    'value' => $value,
                ];
            }
            usort($items, function ($a, $b) use ($direction) {
                $cmp = ($a['value'] ?? 0) <=> ($b['value'] ?? 0);
                return $direction === 'asc' ? $cmp : -$cmp;
            });
            return array_slice($items, 0, 5);
        };

        $positive = [
            'fastestResponses' => $build(
                static function ($row) {
                    $avg = $row['avg_lead_hours'] ?? null;
                    return $avg === null ? null : round(floatval($avg), 1);
                },
                static function ($row) use ($minReplied) {
                    return intval($row['replied_count'] ?? 0) >= $minReplied && $row['avg_lead_hours'] !== null;
                },
                'asc'
            ),
            'highestResponseRate' => $build(
                static function ($row) {
                    $invited = intval($row['invited_count'] ?? 0);
                    $replied = intval($row['replied_count'] ?? 0);
                    return $invited > 0 ? round(($replied / $invited) * 100, 1) : null;
                },
                static function ($row) use ($minInvited) {
                    return intval($row['invited_count'] ?? 0) >= $minInvited;
                },
                'desc'
            ),
            'highestYesRate' => $build(
                static function ($row) {
                    $replied = intval($row['replied_count'] ?? 0);
                    $yes = intval($row['yes_count'] ?? 0);
                    return $replied > 0 ? round(($yes / $replied) * 100, 1) : null;
                },
                static function ($row) use ($minReplied) {
                    return intval($row['replied_count'] ?? 0) >= $minReplied;
                },
                'desc'
            ),
            'mostResponses' => $build(
                static function ($row) {
                    return intval($row['replied_count'] ?? 0);
                },
                static function ($row) use ($minReplied) {
                    return intval($row['replied_count'] ?? 0) >= $minReplied;
                },
                'desc'
            ),
        ];

        $negative = [
            'slowestResponses' => $build(
                static function ($row) {
                    $avg = $row['avg_lead_hours'] ?? null;
                    return $avg === null ? null : round(floatval($avg), 1);
                },
                static function ($row) use ($minReplied) {
                    return intval($row['replied_count'] ?? 0) >= $minReplied && $row['avg_lead_hours'] !== null;
                },
                'desc'
            ),
            'highestNoResponseRate' => $build(
                static function ($row) {
                    $invited = intval($row['invited_count'] ?? 0);
                    $replied = intval($row['replied_count'] ?? 0);
                    $pending = max(0, $invited - $replied);
                    return $invited > 0 ? round(($pending / $invited) * 100, 1) : null;
                },
                static function ($row) use ($minInvited) {
                    return intval($row['invited_count'] ?? 0) >= $minInvited;
                },
                'desc'
            ),
            'highestLateRate' => $build(
                static function ($row) {
                    $replied = intval($row['replied_count'] ?? 0);
                    $late = intval($row['late_count'] ?? 0);
                    return $replied > 0 ? round(($late / $replied) * 100, 1) : null;
                },
                static function ($row) use ($minReplied) {
                    return intval($row['replied_count'] ?? 0) >= $minReplied;
                },
                'desc'
            ),
            'mostNoResponses' => $build(
                static function ($row) {
                    $invited = intval($row['invited_count'] ?? 0);
                    $replied = intval($row['replied_count'] ?? 0);
                    return max(0, $invited - $replied);
                },
                static function ($row) use ($minInvited) {
                    return intval($row['invited_count'] ?? 0) >= $minInvited;
                },
                'desc'
            ),
        ];

        return [
            'positive' => $positive,
            'negative' => $negative,
            'thresholds' => [
                'minInvited' => $minInvited,
                'minReplied' => $minReplied,
            ],
        ];
    }

    /**
     * @return array{late:int,total:int,rate:float}
     */
    private function getLateResponseStats(array $context): array {
        global $system_data;
        $params = [];
        $query = "SELECT
                    COUNT(*) as total_responses,
                    SUM(CASE WHEN x.reply_dt > x.approve_dt AND x.reply_dt <= x.event_begin_dt THEN 1 ELSE 0 END) as late_count
                  FROM (
                    SELECT " . $this->normalizeDateExpression("ru.replyon") . " as reply_dt,
                           " . $this->normalizeDateExpression("r.approve_until") . " as approve_dt,
                           " . $this->normalizeDateExpression("r.begin") . " as event_begin_dt
                    FROM rehearsal_user ru
                    JOIN rehearsal r ON r.id = ru.rehearsal
                    UNION ALL
                    SELECT " . $this->normalizeDateExpression("cu.replyon") . " as reply_dt,
                           " . $this->normalizeDateExpression("c.approve_until") . " as approve_dt,
                           " . $this->normalizeDateExpression("c.begin") . " as event_begin_dt
                    FROM concert_user cu
                    JOIN concert c ON c.id = cu.concert
                  ) x
                  WHERE x.reply_dt IS NOT NULL
                    AND x.approve_dt IS NOT NULL
                    AND x.event_begin_dt IS NOT NULL";
        if ($context['scope'] !== 'all') {
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $query .= " AND x.event_begin_dt >= ? AND x.event_begin_dt <= ? ";
        }

        $rows = $this->rows($this->getSelectionSafe($query, $params, 'late-responses-overall'));
        $row = count($rows) > 0 ? $rows[0] : [];
        $total = intval($row['total_responses'] ?? 0);
        $late = intval($row['late_count'] ?? 0);
        return [
            'late' => $late,
            'total' => $total,
            'rate' => $total > 0 ? round(($late / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{late:int,total:int,rate:float}
     */
    private function getLateResponseStatsForType(string $eventTable, string $responseTable, string $fk, array $context): array {
        global $system_data;
        $params = [];
        $query = "SELECT
                    COUNT(*) as total_responses,
                    SUM(CASE WHEN " . $this->normalizeDateExpression("r.replyon") . " > " . $this->normalizeDateExpression("e.approve_until") . "
                              AND " . $this->normalizeDateExpression("r.replyon") . " <= " . $this->normalizeDateExpression("e.begin") . " THEN 1 ELSE 0 END) as late_count
                  FROM `$responseTable` r
                  JOIN `$eventTable` e ON r.`$fk` = e.id
                  WHERE " . $this->normalizeDateExpression("r.replyon") . " IS NOT NULL
                    AND " . $this->normalizeDateExpression("e.approve_until") . " IS NOT NULL
                    AND " . $this->normalizeDateExpression("e.begin") . " IS NOT NULL";
        if ($context['scope'] !== 'all') {
            $params[] = ['s', (string)$context['start']];
            $params[] = ['s', (string)$context['end']];
            $query .= " AND " . $this->normalizeDateExpression("e.begin") . " >= ? AND " . $this->normalizeDateExpression("e.begin") . " <= ? ";
        }

        $rows = $this->rows($this->getSelectionSafe($query, $params, 'late-responses-by-type'));
        $row = count($rows) > 0 ? $rows[0] : [];
        $total = intval($row['total_responses'] ?? 0);
        $late = intval($row['late_count'] ?? 0);
        return [
            'late' => $late,
            'total' => $total,
            'rate' => $total > 0 ? round(($late / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @param array<int, string> $labels
     * @return array<string, mixed>
     */
    private function getResponseBehavior(array $labels, array $context): array {
        $rehearsalMix = $this->monthlyResponseMixRows('rehearsal', 'rehearsal_contact', 'rehearsal_user', 'rehearsal', $context);
        $concertMix = $this->monthlyResponseMixRows('concert', 'concert_contact', 'concert_user', 'concert', $context);
        $mixSeries = [];
        foreach ($labels as $month) {
            $re = $rehearsalMix[$month] ?? ['invited' => 0, 'yes' => 0, 'maybe' => 0, 'no' => 0, 'pending' => 0];
            $co = $concertMix[$month] ?? ['invited' => 0, 'yes' => 0, 'maybe' => 0, 'no' => 0, 'pending' => 0];
            $invited = intval($re['invited']) + intval($co['invited']);
            $yes = intval($re['yes']) + intval($co['yes']);
            $maybe = intval($re['maybe']) + intval($co['maybe']);
            $no = intval($re['no']) + intval($co['no']);
            $pending = intval($re['pending']) + intval($co['pending']);
            $mixSeries[] = [
                'month' => $month,
                'invited' => $invited,
                'yes' => $yes,
                'maybe' => $maybe,
                'no' => $no,
                'pending' => $pending,
                'pendingRate' => $invited > 0 ? round(($pending / $invited) * 100, 1) : 0.0,
            ];
        }
        $rehearsalTotals = $this->summarizeResponseMix($rehearsalMix);
        $concertTotals = $this->summarizeResponseMix($concertMix);

        $overallTotals = [
            'invited' => $rehearsalTotals['invited'] + $concertTotals['invited'],
            'yes' => $rehearsalTotals['yes'] + $concertTotals['yes'],
            'maybe' => $rehearsalTotals['maybe'] + $concertTotals['maybe'],
            'no' => $rehearsalTotals['no'] + $concertTotals['no'],
            'pending' => $rehearsalTotals['pending'] + $concertTotals['pending'],
        ];
        $respondedTotal = $overallTotals['yes'] + $overallTotals['maybe'] + $overallTotals['no'];
        $noResponseRate = $overallTotals['invited'] > 0
            ? round(($overallTotals['pending'] / $overallTotals['invited']) * 100, 1)
            : 0.0;

        $leadTime = $this->getResponseLeadTimeStats($context);
        $lateOverall = $this->getLateResponseStats($context);
        $lateRehearsal = $this->getLateResponseStatsForType('rehearsal', 'rehearsal_user', 'rehearsal', $context);
        $lateConcert = $this->getLateResponseStatsForType('concert', 'concert_user', 'concert', $context);

        return [
            'leadTimeHours' => $leadTime,
            'lateResponses' => $lateOverall,
            'noResponses' => [
                'pending' => $overallTotals['pending'],
                'invited' => $overallTotals['invited'],
                'rate' => $noResponseRate,
            ],
            'funnel' => [
                'invited' => $overallTotals['invited'],
                'responded' => $respondedTotal,
                'confirmed' => $overallTotals['yes'],
            ],
            'mixTrend' => $mixSeries,
            'byType' => [
                'rehearsals' => [
                    'invited' => $rehearsalTotals['invited'],
                    'responded' => $rehearsalTotals['yes'] + $rehearsalTotals['maybe'] + $rehearsalTotals['no'],
                    'pending' => $rehearsalTotals['pending'],
                    'pendingRate' => $rehearsalTotals['invited'] > 0
                        ? round(($rehearsalTotals['pending'] / $rehearsalTotals['invited']) * 100, 1)
                        : 0.0,
                    'lateRate' => $lateRehearsal['rate'],
                ],
                'concerts' => [
                    'invited' => $concertTotals['invited'],
                    'responded' => $concertTotals['yes'] + $concertTotals['maybe'] + $concertTotals['no'],
                    'pending' => $concertTotals['pending'],
                    'pendingRate' => $concertTotals['invited'] > 0
                        ? round(($concertTotals['pending'] / $concertTotals['invited']) * 100, 1)
                        : 0.0,
                    'lateRate' => $lateConcert['rate'],
                ],
            ],
        ];
    }

    private function getDashboard(): array {
        $context = $this->resolvePeriodContext();
        $labels = $context['labels'];
        $eventsByMonth = $this->getEventsByMonth($labels, $context);
        $membersPerGroup = $this->getMembersPerGroup();
        $topRehearsalParticipants = $this->getTopRehearsalParticipants($context);
        $topVoteParticipants = $this->getTopVoteParticipants($context);
        $criticalEvents = $this->getCriticalEvents();
        $participation = $this->getParticipationTrend($labels, $context);
        $responseCompletion = $this->getResponseCompletionTrend($labels, $context);
        $responseBehavior = $this->getResponseBehavior($labels, $context);
        $participationStability = $this->getParticipationStabilityIndex($participation['series']);
        $activeMembers = $this->getActiveMemberTrend($labels, $context);
        $responseStreaks = $this->getResponseConsistencyStreaks($context);
        $taskLatency = $this->getTaskCompletionLatency($labels, $context);
        $voteParticipation = $this->getVoteParticipationTrend($labels, $context);
        $reminderEffectiveness = $this->getReminderEffectiveness($context);
        $instrumentCoverage = $this->getInstrumentCoverageRisk();
        $userRankings = $this->getUserRankings($context);

        $pendingResponses = 0;
        foreach ($criticalEvents as $event) {
            $pendingResponses += intval($event['pendingUsers'] ?? 0);
        }

        $totalRehearsals = 0;
        $totalConcerts = 0;
        foreach ($eventsByMonth as $row) {
            $totalRehearsals += intval($row['rehearsals'] ?? 0);
            $totalConcerts += intval($row['concerts'] ?? 0);
        }

        return [
            'overview' => [
                'criticalEvents' => count($criticalEvents),
                'pendingResponses' => $pendingResponses,
                'participationRate' => $participation['overallRate'],
                'rehearsalParticipationRate' => $participation['byType']['rehearsals']['rate'],
                'concertParticipationRate' => $participation['byType']['concerts']['rate'],
                'responseCompletionRate' => $responseCompletion['overallRate'],
                'responsesTotal' => $responseCompletion['replied'],
                'invitationsTotal' => $responseCompletion['invited'],
                'rehearsalsTotal' => $totalRehearsals,
                'concertsTotal' => $totalConcerts,
            ],
            'eventsByMonth' => $eventsByMonth,
            'membersPerGroup' => $membersPerGroup,
            'participationTrend' => $participation['series'],
            'responseCompletionTrend' => $responseCompletion['series'],
            'responseBehavior' => $responseBehavior,
            'participationStability' => $participationStability,
            'activeMembersTrend' => $activeMembers,
            'responseConsistency' => $responseStreaks,
            'taskCompletionLatency' => $taskLatency,
            'voteParticipationTrend' => $voteParticipation,
            'reminderEffectiveness' => $reminderEffectiveness,
            'instrumentCoverageRisk' => $instrumentCoverage,
            'userRankings' => $userRankings,
            'criticalEventsList' => $criticalEvents,
            'topParticipants' => [
                'rehearsals' => $topRehearsalParticipants,
                'votes' => $topVoteParticipants,
            ],
            'meta' => [
                'months' => count($labels),
                'generatedAt' => date('c'),
                'scope' => $context['scope'],
                'year' => $context['year'],
                'availableYears' => $context['availableYears'],
            ],
        ];
    }
}
