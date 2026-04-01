<?php
/**
 * BNote Next Generation - Statistics API Module
 *
 * Provides admin-oriented statistics dashboard data with legacy-compatible
 * module permission semantics (Stats/Auswertungen module privilege).
 */

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class StatsModule {
    /** @var string[] */
    private array $statsModuleAliases = ['Stats', 'Auswertungen', 'Statistik', 'Statistics'];

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
        $rows = $this->rows($system_data->dbcon->getSelection($query));
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
        return $context['scope'] === 'all'
            ? "DATE_FORMAT($column, '%Y')"
            : "DATE_FORMAT($column, '%Y-%m')";
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
        return " WHERE $column >= ? AND $column <= ? ";
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
        $rows = $this->rows($system_data->dbcon->getSelection($query, $params));
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
        $rows = $this->rows($system_data->dbcon->getSelection($query));
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
        return $this->rankedParticipants($this->rows($system_data->dbcon->getSelection($query, $params)));
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
        return $this->rankedParticipants($this->rows($system_data->dbcon->getSelection($query, $params)));
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

        $query = "SELECT
                    r.id,
                    r.begin,
                    r.approve_until,
                    COUNT(DISTINCT u.id) as invited_users,
                    COUNT(DISTINCT ru.user) as replied_users,
                    SUM(CASE WHEN ru.user IS NULL THEN 1 ELSE 0 END) as pending_users
                  FROM `rehearsal` r
                  JOIN `rehearsal_contact` rc ON rc.rehearsal = r.id
                  JOIN `user` u ON u.contact = rc.contact
                  LEFT JOIN `rehearsal_user` ru ON ru.rehearsal = r.id AND ru.user = u.id
                  WHERE r.begin >= NOW() AND r.begin <= DATE_ADD(NOW(), INTERVAL 90 DAY)
                  GROUP BY r.id, r.begin, r.approve_until
                  HAVING pending_users > 0
                  ORDER BY pending_users DESC, r.begin ASC
                  LIMIT 0, ?";

        $rows = $this->rows($system_data->dbcon->getSelection($query, [['i', $limit]]));
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

        $query = "SELECT
                    c.id,
                    c.title,
                    c.begin,
                    c.approve_until,
                    COUNT(DISTINCT u.id) as invited_users,
                    COUNT(DISTINCT cu.user) as replied_users,
                    SUM(CASE WHEN cu.user IS NULL THEN 1 ELSE 0 END) as pending_users
                  FROM `concert` c
                  JOIN `concert_contact` cc ON cc.concert = c.id
                  JOIN `user` u ON u.contact = cc.contact
                  LEFT JOIN `concert_user` cu ON cu.concert = c.id AND cu.user = u.id
                  WHERE c.begin >= NOW() AND c.begin <= DATE_ADD(NOW(), INTERVAL 90 DAY)
                  GROUP BY c.id, c.title, c.begin, c.approve_until
                  HAVING pending_users > 0
                  ORDER BY pending_users DESC, c.begin ASC
                  LIMIT 0, ?";

        $rows = $this->rows($system_data->dbcon->getSelection($query, [['i', $limit]]));
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
        $rows = $this->rows($system_data->dbcon->getSelection($query, $params));
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
        $rows = $this->rows($system_data->dbcon->getSelection($query, $params));
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

